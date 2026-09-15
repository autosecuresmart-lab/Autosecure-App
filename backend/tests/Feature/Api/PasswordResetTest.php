<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_reset_for_a_known_address_sends_the_app_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_the_response_does_not_reveal_whether_the_address_exists(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertOk()->assertJsonPath('message', $unknown->json('message'));
        $unknown->assertOk();

        Notification::assertNotSentTo(
            User::factory()->make(['email' => 'nobody@example.com']),
            ResetPasswordNotification::class,
        );
    }

    public function test_a_suspended_customer_is_not_sent_a_reset_code(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'suspended']);

        // Same generic response, but nothing is delivered.
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_the_reset_email_deep_links_into_the_mobile_app(): void
    {
        $user = User::factory()->create();
        $notification = new ResetPasswordNotification('a-test-token');

        $url = $notification->resetUrl($user);

        $this->assertStringStartsWith('autosecure://reset-password', $url);
        $this->assertStringContainsString('token=a-test-token', $url);
        $this->assertStringContainsString(urlencode($user->email), $url);

        // The raw code is also in the body, so the reset still works if a mail
        // client refuses to open a custom scheme.
        $mail = $notification->toMail($user);

        $this->assertSame('autosecure://reset-password?token=a-test-token&email='.urlencode($user->email), $mail->actionUrl);
        $this->assertContains('a-test-token', $mail->outroLines);
    }

    public function test_an_invalid_email_is_rejected_before_any_lookup(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonValidationErrors('email');
    }

    public function test_a_valid_code_resets_the_password_and_signs_every_session_out(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);
        $user->createToken('phone');
        $user->createToken('tablet');

        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Fresh!Passw0rd',
            'password_confirmation' => 'Fresh!Passw0rd',
        ])->assertOk();

        $fresh = $user->fresh();

        $this->assertTrue(Hash::check('Fresh!Passw0rd', $fresh->password));

        // A password reset is the standard response to a compromise, so every
        // existing token must be gone.
        $this->assertSame(0, $fresh->tokens()->count());
    }

    public function test_the_old_password_no_longer_works_after_a_reset(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);
        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Fresh!Passw0rd',
            'password_confirmation' => 'Fresh!Passw0rd',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password!2345',
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Fresh!Passw0rd',
        ])->assertOk();
    }

    public function test_an_invalid_reset_code_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'not-a-real-token',
            'password' => 'Fresh!Passw0rd',
            'password_confirmation' => 'Fresh!Passw0rd',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('Password!2345', $user->fresh()->password));
    }

    public function test_a_reset_code_cannot_be_used_twice(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('users')->createToken($user);

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Fresh!Passw0rd',
            'password_confirmation' => 'Fresh!Passw0rd',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertStatus(422);
    }

    public function test_a_weak_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_recovery_is_rate_limited_with_a_consistent_error_code(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // 3 per minute per target address.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
                ->assertOk();
        }

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limited')
            ->assertJsonStructure(['message', 'code', 'retry_after']);
    }
}
