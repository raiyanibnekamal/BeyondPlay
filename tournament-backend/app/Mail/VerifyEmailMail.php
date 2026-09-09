<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your BeyondPlay email');
    }

    public function content(): Content
    {
        $hash = sha1($this->user->email);
        $expires = now()->addDay();
        $signedApiUrl = URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            $expires,
            ['id' => $this->user->id, 'hash' => $hash]
        );
        $query = parse_url($signedApiUrl, PHP_URL_QUERY) ?: '';
        $frontend = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:5500'), '/');
        $url = $frontend.'/verify-email.html?id='.$this->user->id.'&hash='.$hash.($query ? '&'.$query : '');

        return new Content(
            htmlString: '<p>Hi '.$this->user->username.',</p>'
                .'<p>Please verify your email by clicking the link below:</p>'
                .'<p><a href="'.$url.'">Verify email</a></p>'
                .'<p>This link expires in 24 hours.</p>'
        );
    }
}
