<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public ?string $name = null,
        public int $expiresIn = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your AUTOSECURE Verification Code: {$this->otp}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp' => $this->otp,
                'name' => $this->name,
                'expiresIn' => $this->expiresIn,
            ],
        );
    }
}
