<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMemberMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido a NG Training',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.welcome-member',
            with: [
                'memberName' => $this->member->name,
                'groupLine' => $this->member->training_group
                    ? " para el grupo de las **{$this->member->training_group}**"
                    : '',
            ],
        );
    }
}
