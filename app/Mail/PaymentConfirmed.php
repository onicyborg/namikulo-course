<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class PaymentConfirmed extends Mailable
{
    public function __construct(public readonly Order $order) {}

    public function build(): self
    {
        return $this->subject('Pembayaran berhasil: '.$this->order->order_number)
            ->view('emails.payment-confirmed')
            ->text('emails.payment-confirmed-text');
    }
}
