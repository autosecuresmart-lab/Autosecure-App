<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Password reset email for the mobile app.
 *
 * Laravel's default notification links to a web route, which this product does
 * not have — the customer only exists in the React Native app. The link is
 * therefore a deep link into the app, and the raw code is included as well so the
 * reset still works if a mail client refuses to open a custom scheme.
 *
 * The token itself is unchanged: the app posts it to
 * POST /api/v1/auth/reset-password, which verifies it through Laravel's password
 * broker against the `password_reset_tokens` table.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $expiresIn = (int) config('auth.passwords.users.expire', 60);
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reset your AUTOSECURE password')
            ->action('Reset Password', $resetUrl)
            ->line($this->token)
            ->view('emails.reset-password', [
                'name' => $notifiable->name ?? 'there',
                'token' => $this->token,
                'resetUrl' => $resetUrl,
                'expiresIn' => $expiresIn,
            ]);
    }

    /**
     * Deep link consumed by the mobile app.
     *
     * Public (rather than the protected parent) so the URL can be asserted
     * directly in tests and reused by any future SMS/WhatsApp channel.
     */
    public function resetUrl($notifiable): string
    {
        return sprintf(
            '%s://reset-password?token=%s&email=%s',
            config('autosecure.mobile.scheme', 'autosecure'),
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset()),
        );
    }
}
