<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Mail\WelcomeMail;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
// Aliased: the validation rule and the password broker share the name `Password`.
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mobile authentication (Sanctum personal access tokens).
 *
 * Admin authentication is entirely separate: it lives on the `admin` guard in
 * App\Http\Controllers\Manage\AuthController.
 */
class AuthController extends Controller
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected AuditLogger $audit,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required_without:name', 'nullable', 'string', 'max:60'],
            'last_name' => ['required_without:name', 'nullable', 'string', 'max:60'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'account_type' => ['nullable', 'in:individual,business'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'in:ios,android,web'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ]);

        $fullName = !empty($data['name'])
            ? $data['name']
            : trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        $user = DB::transaction(function () use ($data, $fullName) {
            $user = User::create([
                'name' => $fullName,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'account_type' => $data['account_type'] ?? 'individual',
                'company_name' => $data['company_name'] ?? null,
                'password' => $data['password'],
            ]);

            // One wallet per customer, created up front so ledger writes never
            // need to race on creation.
            $user->coinWallet()->create([]);

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        $this->registerPushToken($user, $data);
        $this->audit->log('auth.registered', $user, $user);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user->name));
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'Account created.',
            'token' => $token,
            'user' => new UserResource($user),
            'entitlements' => $this->entitlements->summaryFor($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'in:ios,android,web'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'This account is not active. Please contact AUTOSECURE support.',
                'code' => 'account_inactive',
            ], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->registerPushToken($user, $data);
        $this->audit->log('auth.login', $user, $user);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'entitlements' => $this->entitlements->summaryFor($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'entitlements' => $this->entitlements->summaryFor($user),
        ]);
    }

    /**
     * Password recovery — step 1: request a reset code.
     *
     * The response is identical whether or not the address exists, so the
     * endpoint cannot be used to enumerate registered customers.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            PasswordBroker::broker('users')->sendResetLink(
                $request->only('email'),
                function (User $user, string $token): void {
                    // Suspended accounts are not sent a reset code.
                    if (! $user->isActive()) {
                        return;
                    }

                    $user->sendPasswordResetNotification($token);

                    $this->audit->log(
                        action: 'auth.password_reset_requested',
                        auditable: $user,
                        actor: $user,
                        severity: 'notice',
                    );
                },
            );
        } catch (Throwable $e) {
            // A mail transport failure must not reveal that the address exists.
            report($e);
        }

        return response()->json([
            'message' => 'If that email address is registered, a reset code has been sent.',
        ]);
    }

    /**
     * Password recovery — step 2: exchange the code for a new password.
     *
     * Every existing token is revoked, because a password reset is the standard
     * response to a suspected compromise.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::broker('users')->reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $revoked = $user->tokens()->delete();

                event(new PasswordReset($user));

                $this->audit->log(
                    action: 'auth.password_reset_completed',
                    auditable: $user,
                    actor: $user,
                    context: ['revoked_tokens' => $revoked],
                    severity: 'notice',
                );
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [$this->passwordStatusMessage($status)],
            ]);
        }

        return response()->json([
            'message' => 'Your password has been reset. Please sign in again.',
        ]);
    }

    /**
     * Signed-in devices, so a customer can see and revoke them.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $currentKey = $current?->getKey();

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($token) => [
                'uuid' => $token->uuid,
                'name' => $token->name,
                'is_current' => $currentKey !== null && $token->getKey() === $currentKey,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Revoke one signed-in device, addressed by the token uuid.
     *
     * The lookup is scoped to the authenticated customer, so one customer can
     * never revoke another's session.
     */
    public function revokeSession(Request $request, string $session): JsonResponse
    {
        $user = $request->user();

        $token = $user->tokens()->where('uuid', $session)->first();

        if ($token === null) {
            return response()->json([
                'message' => 'Session not found.',
                'code' => 'not_found',
            ], 404);
        }

        $wasCurrent = $user->currentAccessToken()?->getKey() === $token->getKey();

        $token->delete();

        $this->audit->log(
            action: 'auth.session_revoked',
            auditable: $user,
            actor: $user,
            context: ['session_uuid' => $session, 'was_current' => $wasCurrent],
            severity: 'notice',
        );

        return response()->json([
            'message' => $wasCurrent ? 'This device has been signed out.' : 'Session revoked.',
            'was_current' => $wasCurrent,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        $this->audit->log('auth.logout', $user, $user);

        return response()->json(['message' => 'Signed out.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();

        $this->audit->log('auth.logout_all', $user, $user, severity: 'notice');

        return response()->json(['message' => 'Signed out of all devices.']);
    }

    /**
     * Human message for a password broker status.
     *
     * Mapped explicitly rather than via __() because translation files are not
     * published in this project and the alternative is a raw key like
     * "passwords.token" reaching the customer.
     */
    protected function passwordStatusMessage(string $status): string
    {
        return match ($status) {
            PasswordBroker::INVALID_TOKEN => 'That reset code is invalid or has expired. Request a new one.',
            PasswordBroker::INVALID_USER => 'That reset code is invalid or has expired. Request a new one.',
            PasswordBroker::RESET_THROTTLED => 'Please wait a moment before requesting another code.',
            default => 'We could not reset the password. Please request a new code.',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function registerPushToken(User $user, array $data): void
    {
        if (empty($data['push_token'])) {
            return;
        }

        DeviceToken::updateOrCreate(
            ['token' => $data['push_token']],
            [
                'user_id' => $user->getKey(),
                'platform' => $data['platform'] ?? 'android',
                'device_name' => $data['device_name'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );
    }
}
