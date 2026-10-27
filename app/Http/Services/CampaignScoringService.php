<?php
namespace App\Http\Services;
use App\Models\{CampaignScoring, Etudiant, DemandeEtudiant};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
class CampaignScoringService
{
    public const GROUPS=['social'=>'Situation de bourse','academique'=>'Résultats académiques','geographique'=>'Distance domicile–campus','specifique'=>'PMR confirmé'];
    public const FIELDS=[
        'social'=>['boursier'=>'Boursier','non_boursier'=>'Non-boursier'],
        'academique'=>['tb'=>'Moyenne ≥ 16/20','bien'=>'14 ≤ moyenne < 16','ab'=>'12 ≤ moyenne < 14','passable'=>'10 ≤ moyenne < 12','insuffisant'=>'Moyenne < 10'],
        'geographique'=>['local'=>'Distance < 50 km','proche'=>'50 ≤ distance < 100 km','loin'=>'100 ≤ distance < 200 km','tres_loin'=>'Distance ≥ 200 km'],
        'specifique'=>['pmr'=>'PMR confirmé','autre'=>'Autre situation'],
    ];
    /** Début (km) de chaque tranche de distance au-delà de « local » ; seuils par défaut des barèmes enregistrés sans seuils. */
    public const DISTANCE_THRESHOLDS=['proche'=>50,'loin'=>100,'tres_loin'=>200];
    /**
     * Données du dossier (formulaire de demande) sur lesquelles l'admin peut ajouter un critère au barème :
     * « nombre » → points par tranche (borne basse incluse, borne haute exclue) ; « liste » → points par valeur.
     */
    public const SOURCES=[
        'age'=>['label'=>'Âge','type'=>'nombre','unite'=>'ans'],
        'moyenne'=>['label'=>'Moyenne','type'=>'nombre','unite'=>'/20'],
        'credits'=>['label'=>'Crédits validés','type'=>'nombre','unite'=>'crédits'],
        'distance'=>['label'=>'Distance domicile–campus','type'=>'nombre','unite'=>'km'],
        'genre'=>['label'=>'Genre','type'=>'liste','unite'=>null],
        'niveau_etude'=>['label'=>'Niveau d’étude actuel','type'=>'liste','unite'=>null],
        'filiere'=>['label'=>'Filière','type'=>'liste','unite'=>null],
        'region'=>['label'=>'Région','type'=>'liste','unite'=>null],
        'nationalite'=>['label'=>'Nationalité','type'=>'liste','unite'=>null],
        'boursier'=>['label'=>'Situation de bourse','type'=>'liste','unite'=>null],
        'pmr'=>['label'=>'PMR confirmé','type'=>'liste','unite'=>null],
    ];
    /** Nombre maximal de critères ajoutés par barème. */
    public const MAX_CRITERIA=10;
    private const OUI_NON=['1'=>'Oui','0'=>'Non'];
    /** Catégorie d'un volet ou critère dont la donnée n'est pas renseignée dans le dossier (0 point). */
    public const NON_RENSEIGNE='non_renseigne';
    /** Seuils de distance du barème (par défaut si la version n'en a pas). */
    public static function distanceThresholds(?CampaignScoring $config): array
    {
        $seuils=$config?->distance_thresholds;
        if (!is_array($seuils)) return self::DISTANCE_THRESHOLDS;
        $valeurs=[];
        foreach (self::DISTANCE_THRESHOLDS as $key=>$defaut) $valeurs[$key]=is_numeric($seuils[$key] ?? null) ? (int)$seuils[$key] : $defaut;
        return $valeurs;
    }
    private function distanceKey(float $distance, array $seuils): string
    {
        foreach (array_reverse($seuils, true) as $key=>$seuil) if ($distance >= $seuil) return $key;
        return 'local';
    }
    public function calculate(Etudiant $student, DemandeEtudiant $request, CampaignScoring $config): array
    {
        $values=[];
        $categories=[];
        foreach ($config->weights as $group=>$weight) {
            if ((float)$weight === 0.0) { $values[$group]=0.0; $categories[$group]=null; continue; }
            $key=null;
            if ($group==='social') {
                $boursier=$request->boursier ?? $student->boursier;
                $key=$boursier===null ? self::NON_RENSEIGNE : ($boursier ? 'boursier':'non_boursier');
            } elseif ($group==='academique') {
                $value=$request->moyenne;
                $key=!is_numeric($value) || $value<0 || $value>20 ? self::NON_RENSEIGNE
                    : ($value>=16?'tb':($value>=14?'bien':($value>=12?'ab':($value>=10?'passable':'insuffisant'))));
            } elseif ($group==='geographique') {
                $value=$student->distance;
                $key=!is_numeric($value) || $value<0 ? self::NON_RENSEIGNE : $this->distanceKey((float)$value, self::distanceThresholds($config));
            } elseif ($group==='specifique') {
                $key=$student->pmr_confirme ? 'pmr':'autre';
            }
            // Donnée du barème non renseignée (ou invalide) dans le dossier : 0 point pour ce volet.
            if ($key===self::NON_RENSEIGNE) { $values[$group]=0.0; $categories[$group]=$key; continue; }
            if ($key===null || !isset($config->points[$group][$key])) throw new RuntimeException('Barème de scoring incomplet.');
            $values[$group]=(float)$config->points[$group][$key];
            $categories[$group]=$key;
        }
        foreach (self::criteria($config) as $critere) {
            $cle=$critere['key'];
            if ((float)$critere['weight'] === 0.0) { $values[$cle]=0.0; $categories[$cle]=null; continue; }
            [$categories[$cle], $values[$cle]]=$this->criterionPoints($critere, $student, $request);
        }
        $poids=self::allWeights($config);
        $score=0;
        foreach ($values as $group=>$value) $score+=$value*(float)$poids[$group]/100;
        return ['score'=>round($score,2),'priority'=>$config->pmr_first && $student->pmr_confirme?0:1,
            'minimum_score'=>$config->minimum_score,'scoring_version'=>$config->id,'subscores'=>$values,'categories'=>$categories];
    }
    /**
     * Détail du score par volet (poids, situation de l'étudiant, points, apport au score) pour l'affichage.
     * @return array{score:float, minimum:float, volets:list<array{volet:string, poids:float, situation:string, points:float, apport:float}>}
     */
    public function detail(Etudiant $student, DemandeEtudiant $request, CampaignScoring $config): array
    {
        $resultat=$this->calculate($student, $request, $config);
        $seuils=self::distanceThresholds($config);
        $mentions=['tb'=>'Très bien','bien'=>'Bien','ab'=>'Assez bien','passable'=>'Passable','insuffisant'=>'Insuffisant'];
        $tranches=[
            'local'=>'moins de '.$seuils['proche'].' km',
            'proche'=>$seuils['proche'].' à '.$seuils['loin'].' km',
            'loin'=>$seuils['loin'].' à '.$seuils['tres_loin'].' km',
            'tres_loin'=>$seuils['tres_loin'].' km et plus',
        ];
        $nombre=fn ($valeur)=>rtrim(rtrim(number_format((float)$valeur, 2, ',', ''), '0'), ',');
        $volets=[];
        foreach ($config->weights as $group=>$weight) {
            $key=$resultat['categories'][$group] ?? null;
            $situation=match (true) {
                $key===null=>'Volet non pris en compte (poids nul)',
                $key===self::NON_RENSEIGNE=>match ($group) {
                    'social'=>'Statut de bourse',
                    'academique'=>'Moyenne',
                    'geographique'=>'Distance',
                    default=>self::GROUPS[$group] ?? $group,
                }.' non renseigné(e) → 0 point',
                $group==='social'=>$key==='boursier' ? 'Boursier' : 'Non boursier',
                $group==='academique'=>'Moyenne '.$nombre($request->moyenne).'/20 → '.($mentions[$key] ?? $key),
                $group==='geographique'=>$nombre($student->distance).' km → tranche « '.$tranches[$key].' »',
                $group==='specifique'=>$key==='pmr' ? 'PMR confirmé' : 'Non PMR',
                default=>$key,
            };
            $points=(float)($resultat['subscores'][$group] ?? 0);
            $volets[]=['volet'=>$group==='specifique' ? 'Situation spécifique (PMR)' : (self::GROUPS[$group] ?? $group),'poids'=>(float)$weight,'situation'=>$situation,
                'points'=>$points,'apport'=>round($points*(float)$weight/100, 2)];
        }
        foreach (self::criteria($config) as $critere) {
            $key=$resultat['categories'][$critere['key']] ?? null;
            $points=(float)($resultat['subscores'][$critere['key']] ?? 0);
            $volets[]=['volet'=>$critere['label'],'poids'=>(float)$critere['weight'],
                'situation'=>$key===null ? 'Volet non pris en compte (poids nul)' : $this->criterionSituation($critere, $key, $student, $request),
                'points'=>$points,'apport'=>round($points*(float)$critere['weight']/100, 2)];
        }
        return ['score'=>(float)$resultat['score'],'minimum'=>(float)$config->minimum_score,'volets'=>$volets];
    }
    /** Critères ajoutés du barème (liste vide pour les versions qui n'en ont pas). */
    public static function criteria(?CampaignScoring $config): array
    {
        $criteres=$config?->criteria;
        if (!is_array($criteres)) return [];
        return array_values(array_filter($criteres, fn ($c)=>is_array($c) && isset($c['key'], $c['source'], self::SOURCES[$c['source']])));
    }
    /** Pondérations des volets fixes et des critères ajoutés, indexées par clé. */
    public static function allWeights(CampaignScoring $config): array
    {
        $poids=(array)$config->weights;
        foreach (self::criteria($config) as $critere) $poids[$critere['key']]=(float)$critere['weight'];
        return $poids;
    }
    /**
     * Données proposées à l'admin pour les critères ajoutés, avec les valeurs possibles des données « liste »
     * (référentiels genre, niveaux d'étude, filières, régions ; nationalités déjà saisies).
     * @return list<array{key:string, label:string, type:string, unite:?string, options:list<array{value:string, label:string}>}>
     */
    public static function sources(): array
    {
        $liste=[];
        foreach (self::SOURCES as $key=>$source) {
            $options=$source['type']==='liste' ? self::sourceOptions($key) : [];
            $liste[]=['key'=>$key,'label'=>$source['label'],'type'=>$source['type'],'unite'=>$source['unite'],
                'options'=>array_map(fn ($value, $label)=>['value'=>(string)$value,'label'=>(string)$label], array_keys($options), $options)];
        }
        return $liste;
    }
    /** Valeurs possibles (valeur => libellé) d'une donnée « liste » ; vide si le référentiel est indisponible. */
    private static function sourceOptions(string $source): array
    {
        try {
            return match ($source) {
                'boursier', 'pmr'=>self::OUI_NON,
                'genre'=>Schema::hasTable('genre') ? DB::table('genre')->orderBy('name')->pluck('name', 'id')->all() : [],
                'niveau_etude'=>Schema::hasTable('niveau_etude')
                    ? DB::table('niveau_etude')->when(Schema::hasColumn('niveau_etude', 'ordre'), fn ($q)=>$q->orderBy('ordre'))->orderBy('name')->pluck('name', 'id_niveau_etude')->all() : [],
                'filiere'=>Schema::hasTable('filiere') ? DB::table('filiere')->orderBy('name')->pluck('name', 'id_filiere')->all() : [],
                'region'=>Schema::hasTable('regions') ? DB::table('regions')->orderBy('name')->pluck('name', 'id')->all() : [],
                'nationalite'=>Schema::hasTable('etudiants')
                    ? DB::table('etudiants')->whereNotNull('nationalite')->where('nationalite', '<>', '')->distinct()->pluck('nationalite')
                        ->map(fn ($n)=>self::normalizeText($n))->unique()->sort()->mapWithKeys(fn ($n)=>[$n=>$n])->all() : [],
                default=>[],
            };
        } catch (Throwable) {
            return [];
        }
    }
    private static function normalizeText(mixed $value): string
    {
        return mb_strtoupper(trim((string)$value));
    }
    /** Valeur de la donnée pour le dossier, null si elle n'est pas renseignée. */
    private function sourceValue(string $source, Etudiant $student, DemandeEtudiant $request): mixed
    {
        $vide=fn ($v)=>$v===null || (is_string($v) && trim($v)==='');
        switch ($source) {
            case 'age':
                if ($vide($student->date_naissance)) return null;
                $naissance=$student->date_naissance instanceof Carbon ? $student->date_naissance : Carbon::parse($student->date_naissance);
                $reference=$request->date_demande ?? $request->created_at ?? now();
                return (float)floor($naissance->diffInYears($reference instanceof Carbon ? $reference : Carbon::parse($reference)));
            case 'moyenne': $v=$request->moyenne; break;
            case 'credits': $v=$request->credit_valider; break;
            case 'distance': $v=$student->distance; break;
            case 'genre': $v=$student->genre_id; break;
            case 'niveau_etude': $v=$request->niveau_etude_actuel_code; break;
            case 'filiere': $v=$request->filiere_code; break;
            case 'region': $v=$student->regions_id; break;
            case 'nationalite': return $vide($student->nationalite) ? null : self::normalizeText($student->nationalite);
            case 'boursier': $v=$request->boursier ?? $student->boursier; return $v===null ? null : ($v ? '1' : '0');
            case 'pmr': return $student->pmr_confirme ? '1' : '0';
            default: return null;
        }
        if ($vide($v)) return null;
        if (self::SOURCES[$source]['type']==='nombre') return is_numeric($v) && $v>=0 ? (float)$v : null;
        return (string)$v;
    }
    /** [catégorie, points] d'un critère ajouté : « bande_<n> » ou la valeur, « autres » si rien ne correspond. */
    private function criterionPoints(array $critere, Etudiant $student, DemandeEtudiant $request): array
    {
        $valeur=$this->sourceValue($critere['source'], $student, $request);
        if ($valeur===null) return [self::NON_RENSEIGNE, 0.0];
        if (self::SOURCES[$critere['source']]['type']==='nombre') {
            foreach ($critere['bands'] ?? [] as $index=>$bande) {
                if (($bande['min']===null || $valeur>=(float)$bande['min']) && ($bande['max']===null || $valeur<(float)$bande['max'])) return ['bande_'.$index, (float)$bande['points']];
            }
        } else {
            foreach ($critere['values'] ?? [] as $ligne) if ((string)$ligne['value']===$valeur) return [$valeur, (float)$ligne['points']];
        }
        return ['autres', (float)($critere['other_points'] ?? 0)];
    }
    private function criterionSituation(array $critere, string $key, Etudiant $student, DemandeEtudiant $request): string
    {
        $source=self::SOURCES[$critere['source']];
        if ($key===self::NON_RENSEIGNE) return $source['label'].' non renseigné(e) → 0 point';
        $valeur=$this->sourceValue($critere['source'], $student, $request);
        $nombre=fn ($v)=>rtrim(rtrim(number_format((float)$v, 2, ',', ''), '0'), ',');
        if ($source['type']==='nombre') {
            $texte=$source['label'].' : '.$nombre($valeur).($source['unite']==='/20' ? '/20' : ' '.$source['unite']);
            if ($key==='autres') return $texte.' → hors tranches';
            $bande=$critere['bands'][(int)substr($key, 6)] ?? null;
            return $bande ? $texte.' → tranche « '.self::bandLabel($bande, $source['unite'], $nombre).' »' : $texte;
        }
        $libelle=self::sourceOptions($critere['source'])[$valeur] ?? $valeur;
        return $source['label'].' : '.$libelle.($key==='autres' ? ' (autre valeur)' : '');
    }
    private static function bandLabel(array $bande, ?string $unite, callable $nombre): string
    {
        $u=$unite==='/20' ? '' : ' '.$unite;
        if ($bande['min']===null) return 'moins de '.$nombre($bande['max']).$u;
        if ($bande['max']===null) return $nombre($bande['min']).$u.' et plus';
        return $nombre($bande['min']).' à '.$nombre($bande['max']).$u;
    }
    /**
     * Contrôle et met en forme les critères ajoutés saisis par l'admin (déjà validés champ par champ) :
     * tranches bornées et sans chevauchement pour une donnée « nombre », valeurs distinctes pour une donnée « liste ».
     */
    public static function normalizeCriteria(array $criteres): array
    {
        $resultat=[];
        $cles=[];
        foreach (array_values($criteres) as $i=>$critere) {
            $source=self::SOURCES[$critere['source']];
            $cle=is_string($critere['key'] ?? null) && preg_match('/^c_[a-z0-9]{4,32}$/', $critere['key']) && !in_array($critere['key'], $cles, true)
                ? $critere['key'] : 'c_'.Str::lower(Str::random(10));
            $cles[]=$cle;
            $ligne=['key'=>$cle,'label'=>trim($critere['label']),'source'=>$critere['source'],'weight'=>(float)$critere['weight'],
                'other_points'=>(float)$critere['other_points'],'bands'=>[],'values'=>[]];
            if ($source['type']==='nombre') {
                $bandes=array_values($critere['bands'] ?? []);
                if (!$bandes) throw ValidationException::withMessages(["criteria.$i.bands"=>'Ajoutez au moins une tranche au critère « '.$ligne['label'].' ».']);
                foreach ($bandes as $j=>$bande) {
                    $min=isset($bande['min']) && $bande['min']!=='' ? (float)$bande['min'] : null;
                    $max=isset($bande['max']) && $bande['max']!=='' ? (float)$bande['max'] : null;
                    if ($min===null && $max===null) throw ValidationException::withMessages(["criteria.$i.bands.$j.min"=>'Indiquez au moins une borne pour cette tranche.']);
                    if ($min!==null && $max!==null && $max<=$min) throw ValidationException::withMessages(["criteria.$i.bands.$j.max"=>'La borne haute doit être supérieure à la borne basse.']);
                    $ligne['bands'][]=['min'=>$min,'max'=>$max,'points'=>(float)$bande['points']];
                }
                foreach ($ligne['bands'] as $j=>$a) foreach ($ligne['bands'] as $k=>$b) {
                    if ($k<=$j) continue;
                    if (($a['max']===null || $b['min']===null || $b['min']<$a['max']) && ($b['max']===null || $a['min']===null || $a['min']<$b['max']))
                        throw ValidationException::withMessages(["criteria.$i.bands.$k.min"=>'Cette tranche chevauche la tranche n° '.($j+1).'.']);
                }
            } else {
                foreach (array_values($critere['values'] ?? []) as $j=>$valeur) {
                    $v=$critere['source']==='nationalite' ? self::normalizeText($valeur['value']) : trim((string)$valeur['value']);
                    if (in_array($v, array_column($ligne['values'], 'value'), true)) throw ValidationException::withMessages(["criteria.$i.values.$j.value"=>'Cette valeur est déjà notée pour ce critère.']);
                    $ligne['values'][]=['value'=>$v,'points'=>(float)$valeur['points']];
                }
            }
            $resultat[]=$ligne;
        }
        return $resultat;
    }
}
