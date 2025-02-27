<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCardEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    public $subject;
    public $email;
    public $total;
    public $cardTypes;

    /**
     * Create a new message instance.
     */
    public function __construct($messageContent, $subject, $email, $total, $cardTypes)
    {
        $this->messageContent = $messageContent;
        $this->subject = $subject;
        $this->email = $email;
        $this->total = $total;
        $this->cardTypes = $cardTypes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'mail.cardOrder',
            with: [
                'messageContent' => $this->messageContent,
                'email' => $this->email,
                'total' => $this->total,
                'cardTypes' => $this->cardTypes,
            ],
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
