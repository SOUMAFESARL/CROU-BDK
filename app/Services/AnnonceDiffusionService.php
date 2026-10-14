<?php
namespace App\Services;

use App\Mail\AnnonceMail;
use App\Models\Annonce;
use App\Models\AnnonceDelivery;
use App\Models\ConfigApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AnnonceDiffusionService
{
    public function prepare(string $id): void
    {
        DB::transaction(function () use ($id) {
            $annonce = Annonce::query()->lockForUpdate()->find($id);
            if (!$annonce || $annonce->diffusion_preparee_at || !$annonce->est_publiee || $annonce->est_expiree) {
                return;
            }
            $channels = array_intersect($annonce->canaux ?? [], Annonce::CANAUX);
            $body = $this->plainText($annonce->contenu);
            if ($channels) {
                app(AnnonceAudience::class)->recipients($annonce)->chunkById(200, function ($students) use ($annonce, $channels, $body) {
                    foreach ($students as $student) {
                        $email = collect([$student->email1, $student->email2])->first(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
                        $phone = collect([$student->telephone1, $student->telephone2])->first(fn ($phone) => $phone && numeroCI($phone));
                        $destinations = [];
                        // Tous les canaux incluent un seul e-mail par étudiant.
                        if ($email) $destinations['email'] = $email;
                        if (in_array('sms', $channels, true) && $phone) $destinations['sms'] = $phone;
                        foreach ($destinations as $channel => $destination) {
                            AnnonceDelivery::firstOrCreate([
                                'annonce_id' => $annonce->id_annonce,
                                'etudiant_id' => $student->id_etudiant,
                                'channel' => $channel,
                            ], [
                                'destination' => $destination, 'subject' => $annonce->titre,
                                'body' => $body, 'status' => 'pending',
                            ]);
                        }
                    }
                }, 'id_etudiant');
            }
            $annonce->diffusion_preparee_at = now();
            $annonce->saveQuietly();
        });
    }

    public function plainText(string $html): string
    {
        $html = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $html);
        $html = preg_replace('~<br\s*/?>|</(?:p|div|li|tr|h[1-6])>~i', "\n", $html);
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function sendPending(?string $annonceId = null, int $limit = 30): void
    {
        $query = AnnonceDelivery::where('status', 'pending')->orderBy('id');
        if ($annonceId) $query->where('annonce_id', $annonceId);
        foreach ($query->limit($limit)->get() as $delivery) {
            // Réservation atomique : deux processus ne transmettent pas le même envoi.
            if (!AnnonceDelivery::whereKey($delivery->id)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()])) continue;
            try {
                $annonce = Annonce::actif()->find($delivery->annonce_id);
                if (!$annonce) {
                    $delivery->update(['status' => 'cancelled']);
                    continue;
                }
                if ($delivery->channel === 'sms') {
                    if (!EnvoiSMS($delivery->destination, $delivery->subject."\n".$delivery->body)) {
                        throw new \RuntimeException('SMS non accepté : vérifier la configuration et la passerelle.');
                    }
                } else {
                    $this->configureMail();
                    Mail::to($delivery->destination)->send(new AnnonceMail($delivery->subject, $delivery->body));
                }
                $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } catch (\Throwable $exception) {
                // Ne pas stocker les secrets ou réponses fournisseurs dans le journal.
                $delivery->update(['status' => 'failed', 'error' => 'Envoi non confirmé. Vérifier la configuration '.$delivery->channel.'.']);
            }
        }
    }

    public function configureMail(): void
    {
        $settings = ConfigApp::query()->first();
        if ($settings && !$settings->email_enabled) {
            throw new \RuntimeException('Envoi e-mail désactivé.');
        }
        $driver = $settings?->mail_driver ?: config('mail.default');
        if (in_array($driver, ['log', 'array'], true) && !app()->runningUnitTests()) {
            throw new \RuntimeException('Configurer un transport e-mail réel.');
        }
        config(['mail.default' => $driver, 'mail.mailers.smtp.timeout' => 20]);
        foreach (['host', 'port', 'username', 'password'] as $field) {
            if ($settings?->{'mail_'.$field}) config(['mail.mailers.smtp.'.$field => $settings->{'mail_'.$field}]);
        }
        if ($settings?->mail_from_address) config(['mail.from.address' => $settings->mail_from_address]);
        if ($settings?->mail_from_name) config(['mail.from.name' => $settings->mail_from_name]);
        // Le manager peut déjà avoir résolu le transport avant lecture du paramétrage.
        if (!app()->runningUnitTests()) Mail::purge($driver);
    }
}
