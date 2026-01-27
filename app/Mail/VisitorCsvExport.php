<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitorCsvExport extends Mailable
{
    use Queueable, SerializesModels;

    public $csvPath;
    public $recipientEmail;
    public $dateRange;

    /**
     * Create a new message instance.
     */
    public function __construct($csvPath, $recipientEmail, $dateRange = null)
    {
        $this->csvPath = $csvPath;
        $this->recipientEmail = $recipientEmail;
        $this->dateRange = $dateRange;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visitor Data Export - ' . now()->format('Y-m-d'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.visitor-csv-export',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->csvPath)
                ->as('visitors_export_' . now()->format('Y-m-d_His') . '.csv')
                ->withMime('text/csv'),
        ];
    }
}
