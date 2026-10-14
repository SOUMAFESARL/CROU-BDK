<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefusLogement extends Mailable
{
    use Queueable, SerializesModels;

    public $etudiant;
    public $motif;

    public function __construct($data)
    {
        $this->etudiant = $data['etudiant'];
        $this->motif = $data['motif'];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Votre demande d\'hébergement a été refusée',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.refus-logement',
            with: [
                'etudiant' => $this->etudiant,
                'motif' => $this->motif,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}