<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitApprovalRequestEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $hostName;
    public $visitorName;
    public $visitorEmail;
    public $visitorPhone;
    public $purpose;
    public $visitDate;
    public $visitType;
    public $approvalLink;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->hostName = $data['host_name'];
        $this->visitorName = $data['visitor_name'];
        $this->visitorEmail = $data['visitor_email'];
        $this->visitorPhone = $data['visitor_phone'] ?? 'N/A';
        $this->purpose = $data['purpose'];
        $this->visitDate = $data['visit_date'];
        $this->visitType = $data['visit_type'];
        $this->approvalLink = $data['approval_link'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visit Approval Request - UCB Bank',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.visit-approval-request',
            with: [
                'hostName' => $this->hostName,
                'visitorName' => $this->visitorName,
                'visitorEmail' => $this->visitorEmail,
                'visitorPhone' => $this->visitorPhone,
                'purpose' => $this->purpose,
                'visitDate' => $this->visitDate,
                'visitType' => $this->visitType,
                'approvalLink' => $this->approvalLink,
            ]
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
