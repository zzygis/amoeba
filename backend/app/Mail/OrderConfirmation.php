<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent after checkout with the keys, like eneba.com does.
 * Locally (MAIL_MAILER=log) it's written to storage/logs/laravel.log.
 */
class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your order #{$this->order->id}: your keys are ready");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-confirmation',
            with: ['orderUrl' => config('app.frontend_url')."/orders/{$this->order->id}"],
        );
    }
}
