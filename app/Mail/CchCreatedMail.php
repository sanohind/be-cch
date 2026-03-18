<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $rank;
    public string $creatorName;
    public string $cchUrl;

    public function __construct(string $cchNumber, string $cchSubject, string $rank, string $creatorName, string $cchUrl)
    {
        $this->cchNumber   = $cchNumber;
        $this->cchSubject  = $cchSubject;
        $this->rank        = $rank;
        $this->creatorName = $creatorName;
        $this->cchUrl      = $cchUrl;
    }

    public function envelope(): Envelope
    {
        $rankLabel = $this->rank === 'A' ? '[Rank A] ' : '';
        return new Envelope(
            subject: "{$rankLabel}CCH Baru: {$this->cchSubject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.created',
        );
    }
}
