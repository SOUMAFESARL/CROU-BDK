<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class AnnonceMail extends Mailable
{
    public function __construct(public string $title, public string $content) {}

    public function build(): self
    {
        return $this->subject($this->title)->text('emails.annonce', ['content' => $this->content]);
    }
}
