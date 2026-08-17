<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRegistrationAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public string $pendingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo registro pendiente de aprobación',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-registration-admin',
            with: [
                'memberName' => $this->member->name,
                'memberEmail' => $this->member->email,
                'groupLine' => $this->member->training_group
                    ? " para el grupo de las **{$this->member->training_group}**"
                    : '',
                'pendingUrl' => $this->pendingUrl,
            ],
        );
    }
}
