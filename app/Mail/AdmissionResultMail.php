<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AdmissionResultMail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $messageText,
        /** Mise en forme déjà nettoyée à l'enregistrement du modèle ; `null` : texte simple. */
        public ?string $messageHtml,
        public ?array $access,
        public ?array $reclamation,
    ) {}

    public function build(): self
    {
        if ($this->reclamation) {
            $this->replyTo($this->reclamation['email']);
        }

        return $this->subject($this->subjectLine)
            ->view('emails.admission-result', ['logo' => $this->logo(), 'contenu' => $this->contenu()])
            ->text('emails.admission-result-text');
    }

    /** Message HTML : paragraphes vides conservés (lignes blanches de l'éditeur) et adresses web cliquables. */
    private function contenu(): string
    {
        $html = str_replace('<p></p>', '<p>&nbsp;</p>', $this->messageHtml ?? nl2br(e($this->messageText)));

        // Le HTML nettoyé ne contient aucun attribut portant une adresse : elles sont toutes dans le texte.
        return preg_replace('~https?://[^\s<]*[^\s<.,;:!?)]~u', '<a href="$0">$0</a>', $html) ?? $html;
    }

    /**
     * Logo du paramétrage, à défaut celui du CROU de Bondoukou ; joint au message (les messageries bloquent les images
     * « data: » et n'atteignent pas un serveur local).
     *
     * @return array{data: string, nom: string, mime: string}|null
     */
    private function logo(): ?array
    {
        $logo = systemConfig('logo');
        $source = $logo && $logo !== 'null' ? systemLogoDocument() : '';
        if (preg_match('~^data:(image/[\w.+-]+);base64,(.+)$~s', $source, $image)) {
            [, $mime, $data] = $image;
            $data = base64_decode($data);
        } else {
            $fichier = public_path('admin/assets/images/logos/crou_bdk.jpeg');
            if (! is_file($fichier)) {
                return null;
            }
            $mime = 'image/jpeg';
            $data = file_get_contents($fichier);
        }

        return ['data' => $data, 'nom' => 'logo.'.(explode('+', explode('/', $mime)[1])[0]), 'mime' => $mime];
    }
}
