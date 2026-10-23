<?php

namespace App\Services;

use App\Models\Annonce;
use App\Models\Etudiant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Props de la page React « Annonces » de l'espace étudiant (Etudiant/Annonces) :
 * annonces du flux d'actualité visibles par l'étudiant (Annonce::visibleParEtudiant :
 * publiées, non expirées, canal « flux », audience qui le concerne), filtrables par
 * canal, priorité et recherche (`?canal=`, `?priorite=`, `?search=`, comme l'ancienne
 * page Blade), paginées. Seules les valeurs affichées sont envoyées.
 * « J'aime » : un par étudiant et par annonce (table `annonce_likes`), total dans `annonces.reactions`.
 */
class EtudiantAnnonces
{
    /** Canaux proposés dans le filtre (ceux de l'ancienne page). */
    public const CANAUX = [
        'flux' => 'Flux d’actualité',
        'sms' => 'SMS',
        'email' => 'Email',
        'push' => 'Push',
    ];

    /** Priorités (Annonce::PRIORITES) : libellé et teinte du badge. */
    private const PRIORITES = [
        'normale' => ['Normale', 'neutral'],
        'haute' => ['Haute', 'warning'],
        'urgente' => ['Urgente', 'danger'],
    ];

    /** Une annonce est « nouvelle » pendant ce nombre de jours après sa publication. */
    private const JOURS_NOUVELLE = 7;

    public function props(Etudiant $etudiant, Request $request): array
    {
        $filtres = [
            'canal' => array_key_exists((string) $request->query('canal'), self::CANAUX) ? (string) $request->query('canal') : '',
            'priorite' => array_key_exists((string) $request->query('priorite'), self::PRIORITES) ? (string) $request->query('priorite') : '',
            'search' => Str::limit(trim((string) $request->query('search')), 100, ''),
        ];
        $annonces = $this->filtrer($this->requete($etudiant), $filtres)
            ->with('createur')
            ->orderByDesc('date_publication')
            ->orderByDesc('id_annonce')
            ->paginate(10)
            ->withPath(route('etudiants.dashboard'))->appends($request->except('page', 'annonce'));

        // Lien partagé (`?annonce=`) : l'annonce s'ouvre directement, si elle est visible par l'étudiant.
        $selection = $request->filled('annonce')
            ? $this->requete($etudiant)->with('createur')->whereKey((string) $request->query('annonce'))->first()
            : null;

        $aimees = DB::table('annonce_likes')->where('etudiant_id', (string) $etudiant->getKey())
            ->whereIn('annonce_id', collect($annonces->items())->push($selection)->filter()->map->getKey()->all())
            ->pluck('annonce_id')->flip();
        $formater = fn (Annonce $annonce) => $this->annonce($annonce, $aimees->has($annonce->getKey()));

        return [
            'compteurs' => $this->compteurs($etudiant),
            'filtres' => $filtres,
            'canaux' => collect(self::CANAUX)->map(fn ($libelle, $cle) => ['cle' => $cle, 'libelle' => $libelle])->values()->all(),
            'priorites' => collect(self::PRIORITES)->map(fn ($p, $cle) => ['cle' => $cle, 'libelle' => $p[0]])->values()->all(),
            'annonces' => collect($annonces->items())->map($formater)->values()->all(),
            'pagination' => EtudiantMaintenance::pagination($annonces),
            'selection' => $selection ? $formater($selection) : null,
            'urls' => ['page' => route('etudiants.dashboard', ['section' => 'annonce'])],
        ];
    }

    /**
     * Ajoute ou retire le « J'aime » de l'étudiant sur une annonce qu'il peut voir.
     *
     * @return bool vrai si l'annonce est désormais aimée
     */
    public function basculerJaime(Etudiant $etudiant, string $annonceId): bool
    {
        if (! $this->requete($etudiant)->whereKey($annonceId)->exists()) {
            throw ValidationException::withMessages(['annonce' => 'Cette annonce n’est pas disponible.']);
        }
        $cle = ['annonce_id' => $annonceId, 'etudiant_id' => (string) $etudiant->getKey()];

        return DB::transaction(function () use ($cle, $annonceId) {
            $annonce = Annonce::query()->whereKey($annonceId)->lockForUpdate()->firstOrFail();
            if (DB::table('annonce_likes')->where($cle)->delete() > 0) {
                $annonce->newQuery()->whereKey($annonceId)->where('reactions', '>', 0)->decrement('reactions');

                return false;
            }
            DB::table('annonce_likes')->insert($cle + ['created_at' => now(), 'updated_at' => now()]);
            $annonce->newQuery()->whereKey($annonceId)->increment('reactions');

            return true;
        });
    }

    private function requete(Etudiant $etudiant): Builder
    {
        return Annonce::visibleParEtudiant($etudiant);
    }

    private function filtrer(Builder $requete, array $filtres): Builder
    {
        if ($filtres['canal'] !== '') {
            $requete->parCanal($filtres['canal']);
        }
        if ($filtres['priorite'] !== '') {
            $requete->where('priorite', $filtres['priorite']);
        }
        $recherche = $filtres['search'];
        if ($recherche === '') {
            return $requete;
        }
        // Jokers saisis (% et _) recherchés littéralement ; ESCAPE explicite, compris par MySQL et SQLite.
        $motif = '%'.strtr($recherche, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

        return $requete->where(fn (Builder $q) => $q->whereRaw("titre like ? escape '!'", [$motif])->orWhereRaw("contenu like ? escape '!'", [$motif]));
    }

    /** Effectif par priorité et nombre d'annonces récentes, sans les filtres. */
    private function compteurs(Etudiant $etudiant): array
    {
        $parPriorite = $this->requete($etudiant)->toBase()->reorder()
            ->selectRaw('priorite, count(*) as total')->groupBy('priorite')->pluck('total', 'priorite');

        return [
            'toutes' => (int) $parPriorite->sum(),
            'urgente' => (int) ($parPriorite['urgente'] ?? 0),
            'haute' => (int) ($parPriorite['haute'] ?? 0),
            'normale' => (int) ($parPriorite['normale'] ?? 0),
            'nouvelles' => $this->requete($etudiant)->where('date_publication', '>=', now()->subDays(self::JOURS_NOUVELLE))->count(),
        ];
    }

    private function annonce(Annonce $annonce, bool $aimee): array
    {
        $priorite = $annonce->priorite ?: 'normale';
        [$libelle, $ton] = self::PRIORITES[$priorite] ?? [ucfirst($priorite), 'neutral'];
        $publiee = $annonce->date_publication ?? $annonce->created_at;
        $texte = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', (string) $annonce->contenu)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return [
            'id' => (string) $annonce->getKey(),
            'titre' => (string) $annonce->titre,
            'extrait' => Str::limit($texte, 220),
            // HTML de l'éditeur admin : rendu côté page par RichTextView (schéma Tiptap, pas de HTML arbitraire).
            'contenu' => (string) $annonce->contenu,
            'priorite' => ['cle' => $priorite, 'libelle' => $libelle, 'ton' => $ton],
            'canaux' => collect((array) $annonce->canaux)->filter(fn ($c) => is_string($c))
                ->map(fn (string $c) => self::CANAUX[$c] ?? ucfirst($c))->values()->all(),
            'publieeLe' => $publiee ? formatDateFr($publiee->format('Y-m-d')).' à '.$publiee->format('H:i') : null,
            'nouvelle' => $publiee !== null && $publiee->greaterThanOrEqualTo(now()->subDays(self::JOURS_NOUVELLE)),
            'expireLe' => $annonce->date_expiration ? formatDateFr($annonce->date_expiration->format('Y-m-d')) : null,
            'aime' => $aimee,
            'jaimes' => max(0, (int) $annonce->reactions),
            'auteur' => rescue(fn () => $annonce->createur?->name, null, false) ?: 'Administration',
            'piecesJointes' => collect((array) $annonce->pieces_jointes)
                ->filter(fn ($url) => is_string($url) && $url !== '' && ! str_ends_with($url, '/def.png'))
                ->map(fn (string $url) => ['url' => $url, 'nom' => basename(parse_url($url, PHP_URL_PATH) ?: $url)])
                ->values()->all(),
            'urlJaime' => route('etudiants.annonce.jaime', $annonce->getKey()),
        ];
    }
}
