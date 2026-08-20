<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public ?string $loginUrl = null,
    ) {
        $this->loginUrl = $loginUrl ?? rtrim((string) config('app.frontend_url'), '/').'/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta de NG Training ya está activa',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.registration-approved',
            with: [
                'memberName' => $this->member->name,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
