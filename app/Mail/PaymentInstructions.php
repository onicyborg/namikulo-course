<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Mail\Mailable;

class PaymentInstructions extends Mailable
{
    public function __construct(public readonly Payment $payment, public readonly ?string $qrImage = null) {}

    public function build(): self
    {
        return $this->subject('Cara bayar pesanan '.$this->payment->order->order_number)
            ->view('emails.payment-instructions')
            ->text('emails.payment-instructions-text');
    }
}
