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

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digest
     */
    public function __construct(
        public User $user,
        public array $digest = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BeyondPlay — Your weekly digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.weekly-digest',
        );
    }
}
