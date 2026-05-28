<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $netProfit,
        public string $revenue,
        public int $orderCount,
        public string $margin,
        public string $returnRate,
        public array $topSkus = [],
        public array $worstSkus = [],
        public int $pendingQuestions = 0,
        public int $pendingClaims = 0,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.daily_digest_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-digest',
        );
    }
}
