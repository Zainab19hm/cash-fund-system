<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تفعيل حسابك في ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
