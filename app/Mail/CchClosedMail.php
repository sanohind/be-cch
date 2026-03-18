<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $closedByName;
    public string $cchUrl;

    public function __construct(string $cchNumber, string $cchSubject, string $closedByName, string $cchUrl)
    {
        $this->cchNumber    = $cchNumber;
        $this->cchSubject   = $cchSubject;
        $this->closedByName = $closedByName;
        $this->cchUrl       = $cchUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "CCH {$this->cchSubject} telah di-close",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.closed',
        );
    }
}
