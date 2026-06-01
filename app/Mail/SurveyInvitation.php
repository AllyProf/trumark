<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $url;
    public $subjectLine;
    public $bodyContent;

    /**
     * Create a new message instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
        $this->url = \App\Models\SystemSetting::surveyLink($customer->survey_uuid);

        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        
        $this->subjectLine = $settings['survey_email_subject'] ?? 'Maoni Yako ni Muhimu kwa TRUMARK';
        $bodyTemplate = $settings['survey_email_body'] ?? 'Asante kwa kuendelea kuwa mteja wetu wa TRUMARK. Tunathamini sana ushirikiano wako na tungependa kusikia maoni yako kuhusu huduma tulizokupatia.';
        
        $this->bodyContent = str_replace(
            ['{name}', '{link}'], 
            [$customer->name, $this->url], 
            $bodyTemplate
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.survey_invitation_html',
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
