<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $daysLeft) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.trial_ending_subject', ['days' => $this->daysLeft]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.trial-ending',
        );
    }
}
