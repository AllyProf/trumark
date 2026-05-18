<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $feedback;

    /**
     * Create a new message instance.
     */
    public function __construct(Customer $customer, $feedback)
    {
        $this->customer = $customer;
        $this->feedback = $feedback;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Asante kwa Maoni Yako / Thank You for Your Feedback! - TRUMARK',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback_thank_you_html',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
