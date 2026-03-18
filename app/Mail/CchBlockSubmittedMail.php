<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchBlockSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $rank;
    public string $blockName;
    public string $submitterName;
    public string $cchUrl;

    public function __construct(string $cchNumber, string $cchSubject, string $rank, string $blockName, string $submitterName, string $cchUrl)
    {
        $this->cchNumber     = $cchNumber;
        $this->cchSubject    = $cchSubject;
        $this->rank          = $rank;
        $this->blockName     = $blockName;
        $this->submitterName = $submitterName;
        $this->cchUrl        = $cchUrl;
    }

    public function envelope(): Envelope
    {
        $rankLabel = $this->rank === 'A' ? '[Rank A] ' : '';
        return new Envelope(
            subject: "{$rankLabel}CCH {$this->cchSubject} — {$this->blockName} telah disubmit",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.block_submitted',
        );
    }
}
