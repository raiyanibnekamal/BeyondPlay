<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BeyondPlay — Reset your password',
        );
    }

    public function content(): Content
    {
        $resetUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            .'/reset-password.html?token='.$this->token.'&email='.urlencode($this->user->email);

        return new Content(
            view: 'mail.password-reset',
            with: ['resetUrl' => $resetUrl],
        );
    }
}
