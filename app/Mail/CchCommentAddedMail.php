<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchCommentAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $commentSubject;
    public string $commentBody;
    public string $commenterName;
    public string $blockName;
    public string $cchUrl;

    public function __construct(
        string $cchNumber,
        string $cchSubject,
        string $commentSubject,
        string $commentBody,
        string $commenterName,
        string $blockName,
        string $cchUrl
    ) {
        $this->cchNumber      = $cchNumber;
        $this->cchSubject     = $cchSubject;
        $this->commentSubject = $commentSubject;
        $this->commentBody    = $commentBody;
        $this->commenterName  = $commenterName;
        $this->blockName      = $blockName;
        $this->cchUrl         = $cchUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[CCH {$this->cchNumber}] Komentar baru: {$this->commentSubject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.comment_added',
        );
    }
}
