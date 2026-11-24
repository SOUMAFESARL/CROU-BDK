<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AdmissionResultMail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $messageText,
        public ?array $access,
        public ?array $reclamation,
    ) {}

    public function build(): self
    {
        if ($this->reclamation) {
            $this->replyTo($this->reclamation['email']);
        }

        return $this->subject($this->subjectLine)
            ->view('emails.admission-result')
            ->text('emails.admission-result-text');
    }
}
