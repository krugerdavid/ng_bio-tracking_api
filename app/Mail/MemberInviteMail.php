<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public string $acceptUrl,
        public int $expiresInHours = 72,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a NG Training — activá tu acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.member-invite',
            with: [
                'memberName' => $this->member->name,
                'acceptUrl' => $this->acceptUrl,
                'expiresInHours' => $this->expiresInHours,
            ],
        );
    }
}
