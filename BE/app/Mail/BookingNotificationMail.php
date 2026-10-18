<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectTitle;
    public string $title;
    public string $bodyContent;
    public array $details;
    public ?string $actionUrl;

    public function __construct(
        string $subjectTitle,
        string $title,
        string $bodyContent,
        array $details = [],
        ?string $actionUrl = null
    ) {
        $this->subjectTitle = $subjectTitle;
        $this->title        = $title;
        $this->bodyContent  = $bodyContent;
        $this->details      = $details;
        $this->actionUrl    = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_notification',
            with: [
                'subjectTitle' => $this->subjectTitle,
                'title'        => $this->title,
                'bodyContent'  => $this->bodyContent,
                'details'      => $this->details,
                'actionUrl'    => $this->actionUrl,
            ],
        );
    }
}
