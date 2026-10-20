<?php

namespace App\Payments;

use App\Models\Loyer;
use App\Models\OnlinePayment;
use App\Models\Paiement;
use App\Models\PaiementRepartition;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlinePaymentService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function currentAssignment(string $studentId, bool $lock = false): ?\App\Models\AssignationChambre
    {
        $query = \App\Models\AssignationChambre::where('etudiant_id', $studentId)
            ->actives()->with('chambre')->orderByDesc('date_assignation')->orderByDesc('created_at');
        if ($lock) $query->lockForUpdate();
        return $query->first();
    }

    public function rentForAssignment(string $studentId, \App\Models\AssignationChambre $assignment): ?Loyer
    {
        return Loyer::where('etudiant_id', $studentId)->where('chambre_id', $assignment->chambre_id)
            ->where(fn ($q) => $q->where('assignation_id', $assignment->id_assignation)->orWhereNull('assignation_id'))
            ->where('solde_restant', '>', 0)->whereNotIn('statut', ['annule', 'suspendu'])
            ->orderBy('annee_facturation')->orderBy('mois_facturation')->orderBy('id_loyer')->first();
    }

    /** Raison pour laquelle le paiement en ligne est fermé par la Configuration › Paiements, ou null. */
    public static function indisponibilite(): ?string
    {
        return match (true) {
            ! systemConfig('online_payment_enabled', true) => 'Le paiement en ligne est désactivé par l’administration. Réglez votre loyer au guichet.',
            ! in_array('mobile', systemPaymentMethods(), true) => 'Le paiement par Mobile Money n’est pas accepté actuellement. Réglez votre loyer au guichet.',
            default => null,
        };
    }

    public function start(string $studentId, string $loyerId, string $providerCode, string $phone = '', ?int $requestedAmount = null): OnlinePayment
    {
        $base = rtrim((string) config('app.url'), '/');
        if (!filter_var($base, FILTER_VALIDATE_URL) || parse_url($base, PHP_URL_SCHEME) !== 'https') {
            throw ValidationException::withMessages(['provider' => 'Le paiement en ligne nécessite une adresse publique HTTPS configurée par l’administration.']);
        }
        if ($raison = self::indisponibilite()) {
            throw ValidationException::withMessages(['provider' => $raison]);
        }
        $token = Str::random(64);
        $created = false;
        $payment = DB::transaction(function () use ($studentId, $loyerId, $providerCode, $phone, $requestedAmount, $token, &$created) {
            $loyer = Loyer::where('etudiant_id', $studentId)->whereKey($loyerId)->lockForUpdate()->firstOrFail();
            $assignment = $this->currentAssignment($studentId, true);
            if (!$assignment || !$assignment->chambre
                || $loyer->chambre_id !== $assignment->chambre_id
                || ($loyer->assignation_id && $loyer->assignation_id !== $assignment->id_assignation)) {
                throw ValidationException::withMessages(['loyer_id' => 'Vous pouvez uniquement régler le loyer de la chambre que vous occupez actuellement.']);
            }
            $provider = PaymentProvider::where('code', $providerCode)->firstOrFail();
            $credentials = $this->gateway->credentials($provider);
            $existing = OnlinePayment::where('loyer_id', $loyerId)
                ->whereIn('status', ['initializing', 'pending', 'review'])->first();
            if ($existing) {
                if ($requestedAmount !== null && ($existing->amount !== $requestedAmount || $existing->provider_code !== $providerCode)) {
                    throw ValidationException::withMessages(['amount' => 'Un paiement est déjà en cours pour ce loyer. Consultez son suivi avant de modifier le montant ou le réseau.']);
                }
                return $existing;
            }
            if (in_array($loyer->statut, ['annule', 'suspendu'], true)
                || !preg_match('/^[0-9]+(?:\\.0{1,2})?$/D', (string) $loyer->solde_restant)
                || (int) $loyer->solde_restant <= 0) {
                throw ValidationException::withMessages(['loyer_id' => 'Ce loyer ne présente pas de solde payable en francs CFA entiers.']);
            }
            $amount = $requestedAmount ?? (int) $loyer->solde_restant;
            if ($amount <= 0 || $amount > (int) $loyer->solde_restant) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit être supérieur à zéro et ne pas dépasser le solde restant du loyer.']);
            }
            // Configuration › Paiements : montant minimum (un solde plus faible reste payable en une fois) et plafond journalier.
            $minimum = min((int) systemConfig('min_amount', 0), (int) $loyer->solde_restant);
            if ($amount < $minimum) {
                throw ValidationException::withMessages(['amount' => 'Le montant minimum d’un paiement en ligne est de '.formatSystemAmount($minimum).'.']);
            }
            $plafond = (int) systemConfig('daily_limit', 0);
            if ($plafond > 0) {
                $dejaEngage = (int) OnlinePayment::where('student_id', $studentId)->where('created_at', '>=', now()->startOfDay())
                    ->whereIn('status', ['initializing', 'pending', 'review', 'succeeded'])->sum('amount');
                if ($dejaEngage + $amount > $plafond) {
                    throw ValidationException::withMessages(['amount' => 'Ce paiement dépasse le plafond journalier de '.formatSystemAmount($plafond).' (reste possible aujourd’hui : '.formatSystemAmount(max(0, $plafond - $dejaEngage)).').']);
                }
            }
            if ($providerCode === 'cinetpay' && $amount % 5 !== 0) {
                throw ValidationException::withMessages(['provider' => 'CinetPay exige un montant multiple de 5 FCFA. Choisissez un autre réseau.']);
            }
            if ($providerCode === 'mtn' && !preg_match('/^225[0-9]{10}$/D', $phone)) {
                throw ValidationException::withMessages(['phone' => 'Indiquez le numéro MTN au format 225 suivi des 10 chiffres.']);
            }
            $id = (string) Str::uuid();
            $created = true;
            return OnlinePayment::create([
                'id' => $id, 'reference' => str_replace('-', '', $id),
                'provider_id' => $provider->id, 'provider_code' => $providerCode,
                'student_id' => $studentId, 'loyer_id' => $loyerId,
                'amount' => $amount, 'currency' => 'XOF', 'status' => 'initializing',
                'credentials' => $credentials, 'callback_token_hash' => hash('sha256', $token),
                'next_check_at' => now()->addMinute(),
            ]);
        });
        if (!$created) {
            return $payment;
        }
        $callback = $base.'/api/payments/notifications/'.$payment->id.'/'.$token;
        $return = $base.'/etudiant/paiements-en-ligne/'.$payment->id;
        try {
            $session = $this->gateway->create($payment, $phone, $callback, $return);
            // A notification may have arrived before the creation response.
            OnlinePayment::whereKey($payment->id)->update([
                'provider_reference' => $session['id'], 'checkout_url' => $session['url'] ?? null,
            ]);
            OnlinePayment::whereKey($payment->id)->where('status', 'initializing')->update(['status' => 'pending']);
        } catch (PaymentRequestRejected|\InvalidArgumentException $e) {
            OnlinePayment::whereKey($payment->id)->where('status', 'initializing')
                ->update(['status' => 'failed', 'last_error' => 'creation_rejected', 'next_check_at' => null]);
        } catch (\Throwable $e) {
            OnlinePayment::whereKey($payment->id)->where('status', 'initializing')
                ->update(['status' => 'pending', 'last_error' => 'creation_uncertain']);
            Log::warning('Création de paiement à vérifier', ['payment_id' => $payment->id, 'exception' => get_class($e)]);
        }
        return $payment->fresh();
    }

    public function refresh(OnlinePayment $payment): OnlinePayment
    {
        // Claim a short verification lease; database locks are held only during settlement.
        $claimed = OnlinePayment::whereKey($payment->id)->whereNotIn('status', ['succeeded', 'review'])
            ->where(fn ($q) => $q->whereNull('next_check_at')->orWhere('next_check_at', '<=', now()))
            ->update(['next_check_at' => now()->addMinute()]);
        if (!$claimed) {
            return $payment->fresh();
        }
        $payment->refresh();
        try {
            $result = $this->gateway->verify($payment);
            if ($result->status === 'succeeded') {
                if (!$result->matches($payment->reference, $payment->amount, $payment->currency)) {
                    OnlinePayment::whereKey($payment->id)->where('status', '!=', 'succeeded')->update(['status' => 'review', 'last_error' => 'payment_mismatch', 'next_check_at' => null]);
                } else {
                    $this->settle($payment);
                }
            } else {
                OnlinePayment::whereKey($payment->id)->whereNotIn('status', ['succeeded', 'review'])->update([
                    'status' => $result->status === 'failed' ? 'failed' : 'pending',
                    'verified_at' => now(), 'last_error' => null,
                    'next_check_at' => $result->status === 'failed' ? null : now()->addMinute(),
                ]);
            }
        } catch (\Throwable $e) {
            OnlinePayment::whereKey($payment->id)->whereNotIn('status', ['succeeded', 'review'])
                ->update(['last_error' => 'verification_unavailable', 'next_check_at' => now()->addMinutes(2)]);
            Log::warning('Vérification de paiement à reprendre', ['payment_id' => $payment->id, 'exception' => get_class($e)]);
        }
        return $payment->fresh();
    }

    private function settle(OnlinePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = OnlinePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->paiement_id || in_array($payment->status, ['succeeded', 'review'], true)) {
                return;
            }
            $loyer = Loyer::whereKey($payment->loyer_id)->lockForUpdate()->first();
            if (!$loyer || $loyer->etudiant_id !== $payment->student_id || $loyer->statut === 'annule') {
                $payment->update(['status' => 'review', 'last_error' => 'loyer_unavailable', 'next_check_at' => null]);
                return;
            }
            $paid = $this->cents((string) $loyer->total_paye) + $payment->amount * 100;
            $balance = $this->cents((string) $loyer->montant_total)
                + $this->cents((string) ($loyer->total_penalites ?? 0)) - $paid;
            if ($paid > 9999999999) {
                $payment->update(['status' => 'review', 'last_error' => 'amount_out_of_range', 'next_check_at' => null]);
                return;
            }
            $receipt = new Paiement([
                'id_paiement' => $payment->id, 'numero_paiement' => 'WEB-'.$payment->reference,
                'resident_id' => $payment->student_id, 'loyer_id' => $loyer->id_loyer,
                'contrat_id' => $loyer->assignation_id, 'chambre_id' => $loyer->chambre_id, 'cite_id' => $loyer->cite_id,
                'type_paiement' => 'loyer',
                'mois' => [sprintf('%02d-%04d', $loyer->mois_facturation ?: now()->month, $loyer->annee_facturation ?: now()->year)],
                'montant' => $payment->amount, 'montant_du' => $loyer->solde_restant, 'devise' => 'XOF',
                'methode_paiement' => $payment->provider_code === 'mtn' ? 'mtn_money' : 'autre',
                'reference_transaction' => $payment->reference, 'transaction_id' => $payment->provider_reference,
                'statut_paiement' => 'valide', 'date_paiement' => now(),
                'notes' => 'Paiement en ligne vérifié : '.PaymentProvider::NETWORKS[$payment->provider_code],
            ]);
            // Preserve the verified reference; legacy creating hooks replace transaction identifiers.
            $receipt->saveQuietly();
            PaiementRepartition::createRepartition($receipt->id_paiement, 'mois_cours', $payment->amount,
                (string) $loyer->mois_facturation, (string) $loyer->annee_facturation, 'Paiement en ligne du loyer '.$loyer->id_loyer);
            $loyer->total_paye = number_format($paid / 100, 2, '.', '');
            $loyer->solde_restant = number_format($balance / 100, 2, '.', '');
            $loyer->date_dernier_paiement = now();
            if ($balance <= 0) {
                $loyer->statut = 'termine';
                $loyer->date_paiement = now();
            }
            $loyer->saveQuietly();
            $payment->update(['paiement_id' => $receipt->id_paiement, 'status' => 'succeeded',
                'verified_at' => now(), 'last_error' => null, 'next_check_at' => null]);
        }, 3);
    }

    private function cents(string $value): int
    {
        if (!preg_match('/^(-?)([0-9]+)(?:\\.([0-9]{1,2}))?$/D', $value, $parts)) {
            throw new \RuntimeException('Montant comptable invalide.');
        }
        return ($parts[1] === '-' ? -1 : 1) * ((int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0'));
    }
}
