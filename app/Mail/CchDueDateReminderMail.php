<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CchDueDateReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $cchNumber;
    public string $cchSubject;
    public string $dueDate;
    public string $statusLabel;
    public int $daysDiff;
    public string $cchUrl;

    public function __construct(string $cchNumber, string $cchSubject, string $dueDate, string $statusLabel, int $daysDiff, string $cchUrl)
    {
        $this->cchNumber = $cchNumber;
        $this->cchSubject = $cchSubject;
        $this->dueDate = $dueDate;
        $this->statusLabel = $statusLabel;
        $this->daysDiff = $daysDiff;
        $this->cchUrl = $cchUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->statusLabel}] Reminder CCH: {$this->cchSubject}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cch.due_date',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
