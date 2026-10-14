<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcceptationLogement extends Mailable
{
    use Queueable, SerializesModels;




    public $etudiant;

    public function __construct($data)
    {

        $this->etudiant = $data['etudiant'];
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Acceptation de votre demande d\'hébergement',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'admin.mail.acceptation_logement',
            with: [
               $this->etudiant,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }


}




