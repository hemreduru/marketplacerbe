<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeTrialMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $trialDays) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.welcome_trial_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-trial',
        );
    }
}
