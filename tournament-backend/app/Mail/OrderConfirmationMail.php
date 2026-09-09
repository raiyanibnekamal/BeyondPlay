<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{product_id: int, product_name: string, download_url: string}>  $downloadLinks
     */
    public function __construct(
        public User $user,
        public Order $order,
        public array $downloadLinks = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BeyondPlay — Order #'.$this->order->id.' confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-confirmation',
        );
    }
}
