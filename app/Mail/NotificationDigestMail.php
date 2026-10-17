<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class NotificationDigestMail extends Mailable
{
    public function __construct(public readonly User $recipient, public readonly Collection $items,
        public readonly int $total, public readonly string $date) {}

    public function build(): self
    {
        return $this->subject('Ringkasan notifikasi Anda · '.$this->date)
            ->view('emails.notification-digest')
            ->text('emails.notification-digest-text');
    }
}
