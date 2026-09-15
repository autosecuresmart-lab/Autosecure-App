<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Audit\AuditLogger;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Customer profile.
 *
 * Scope is deliberately narrow: the customer's own record only. There is no
 * endpoint to read or list other customers — that is an admin capability and
 * belongs to /manage.
 */
class UserController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected EntitlementService $entitlements,
    ) {}

    /**
     * The authenticated customer's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'entitlements' => $this->entitlements->summaryFor($user),
        ]);
    }

    /**
     * Update profile details.
     *
     * Email changes are intentionally not supported here: changing the sign-in
     * identity needs verification, which belongs with the account-security work.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:32',
                Rule::unique('users', 'phone')->ignore($user->getKey()),
            ],
            'locale' => ['sometimes', 'string', 'max:12'],
            'timezone' => ['sometimes', 'string', 'max:64', 'timezone'],
        ]);

        $before = $user->only(array_keys($data));

        $user->fill($data);

        // A changed phone number is unverified again until it is confirmed.
        if (array_key_exists('phone', $data) && ($data['phone'] ?? null) !== ($before['phone'] ?? null)) {
            $user->phone_verified_at = null;
        }

        $user->save();

        $this->audit->log('users.profile_updated', $user, $user, [
            'before' => $before,
            'after' => $user->only(array_keys($data)),
        ]);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Change the account password.
     *
     * Every other signed-in device is signed out, because a password change is
     * the standard response to a suspected compromise.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        // The `current_password` validation rule binds to the default guard, which
        // is not the guard this request authenticated on, so the check is explicit.
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['That password does not match our records.'],
            ]);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Choose a password you have not used here before.'],
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        $revoked = $this->revokeOtherTokens($request);

        $this->audit->log(
            action: 'users.password_changed',
            auditable: $user,
            actor: $user,
            context: ['revoked_tokens' => $revoked],
            severity: 'notice',
        );

        return response()->json([
            'message' => 'Password changed. Other devices have been signed out.',
            'revoked_tokens' => $revoked,
        ]);
    }

    /**
     * Delete the current token while leaving the session the request used intact.
     */
    protected function revokeOtherTokens(Request $request): int
    {
        $current = $request->user()->currentAccessToken();

        $query = $request->user()->tokens();

        if ($current !== null) {
            $query->whereKeyNot($current->getKey());
        }

        return $query->delete();
    }
}
