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

        return (new MailMessage)
            ->subject('Reset your AUTOSECURE password')
            ->greeting('Hello '.($notifiable->name ?? ''))
            ->line('We received a request to reset the password for your AUTOSECURE account.')
            ->action('Reset password', $this->resetUrl($notifiable))
            ->line('On the phone with the AUTOSECURE app installed, that button opens the app directly.')
            ->line('If it does not open, launch AUTOSECURE, choose "Forgot password" and enter this code:')
            ->line($this->token)
            ->line("This code expires in {$expiresIn} minutes.")
            ->line('If you did not ask for this, you can ignore this email — your password has not changed.');
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
