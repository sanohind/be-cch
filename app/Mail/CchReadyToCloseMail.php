<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchReadyToCloseMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $rank;
    public string $cchUrl;

    public function __construct(string $cchNumber, string $cchSubject, string $rank, string $cchUrl)
    {
        $this->cchNumber   = $cchNumber;
        $this->cchSubject  = $cchSubject;
        $this->rank        = $rank;
        $this->cchUrl      = $cchUrl;
    }

    public function envelope(): Envelope
    {
        $rankLabel = $this->rank === 'A' ? '[Rank A] ' : '';
        return new Envelope(
            subject: "{$rankLabel}CCH {$this->cchSubject} siap untuk di-close",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.ready_to_close',
        );
    }
}
