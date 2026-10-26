<?php

namespace App\Http\Controllers\admin\Etudiant;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Etudiant;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Genre;
use App\Models\Cite;
use App\Models\Batiment;
use App\Models\DemandeEtudiant;
use App\Models\TypePiece;
use App\Models\groupeSanguin;
use App\Models\Countrie;
use App\Models\Region;
use App\Models\Ville;
use App\Models\NiveauEtude;
use App\Models\Filiere;
use App\Models\Departement;
use App\Support\Telephone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use App\Http\Services\AttributionRules;

class etudiantController extends Controller
{
    public function index()
    {
        return view('admin.etudiant.index');
    }

    public function nouvelleDemande()
    {
        $liste = Etudiant::with(['region','ville','demandes'])->where('statut_inscription','en_attente')->get();

        return view('admin.etudiant.demande.index', [
            'etudiant' => $liste
        ]);


    }

    /** Admission administrative d'un étudiant déjà logé, sans demande préalable. */
    public function admissionDirecte()
    {
        $campagnes = Campagne::active()->orderByDesc('annee_debut')->get();
        $campagneActuelle = $campagnes->firstWhere('annee_campagne', getAnneeUniversitaire())
            ?? $campagnes->first();

        $chambres = Chambre::withDefaultRelations()
            ->withCount(['assignations as places_occupees' => fn ($query) => $query->mobilisantPlace()])
            ->whereNotNull('numero_chambre')
            ->orderBy('numero_chambre')
            ->get();

        $admin = request()->user('admin');
        $access = app(\App\Services\AdminAccess::class);
        $url = fn (string $route) => $access->allows($admin, $route) ? route($route) : null;
        // Rapport d'import : les lignes refusées sont renvoyées dans le message « warning », séparées par « | ».
        $avertissement = session('warning');
        $lignesRefusees = is_string($avertissement) && str_contains($avertissement, 'enregistrées : ')
            ? array_values(array_filter(array_map('trim', explode('|', \Illuminate\Support\Str::after($avertissement, 'enregistrées : ')))))
            : [];

        return \Inertia\Inertia::render('Admin/Hebergement/Admissions/AdmissionDirecte', [
            'campagnes' => $campagnes->map(fn (Campagne $campagne) => [
                'id' => (string) $campagne->id_campagne,
                'label' => $campagne->annee_campagne.($campagne->statut === 'active' ? ' — Active' : ''),
            ])->values(),
            'campagneActuelle' => $campagneActuelle ? (string) $campagneActuelle->id_campagne : null,
            'genres' => Genre::query()->orderBy('name')->get()->map(fn ($genre) => ['id' => (string) $genre->id, 'name' => $genre->name])->values(),
            'cites' => Cite::query()->where('statut', '!=', 'ferme')->orderBy('name')->get()
                ->map(fn ($cite) => ['id' => (string) $cite->id_cite, 'name' => $cite->name])->values(),
            'batiments' => Batiment::query()->where('is_valid', 1)->orderBy('name')->get()
                ->map(fn ($batiment) => ['id' => (string) $batiment->id_batiment, 'name' => $batiment->name, 'cite_id' => (string) $batiment->cite_id])->values(),
            'chambres' => $chambres->map(fn (Chambre $chambre) => [
                'id' => (string) $chambre->id_chambre,
                'numero' => $chambre->numero_chambre,
                'cite_id' => (string) $chambre->cite_id,
                'batiment_id' => (string) $chambre->batiment_id,
                'libres' => max(0, (int) $chambre->capacite_max - (int) $chambre->places_occupees),
                'disponible' => $chambre->peutAccueillir(),
            ])->values(),
            'aujourdhui' => today()->toDateString(),
            'import' => $avertissement ? ['message' => \Illuminate\Support\Str::before((string) $avertissement, ' Certaines lignes'), 'lignes' => $lignesRefusees] : null,
            'urls' => [
                'admissions' => $url('admin.hebergement.residents_contrat.admission'),
                'store' => $url('admin.etudiant.admission_directe.store'),
                'template' => $url('admin.etudiant.admission_directe.template'),
                'import' => $url('admin.etudiant.admission_directe.import'),
            ],
        ]);
    }

    public function storeAdmissionDirecte(Request $request)
    {
        $request->merge([
                'pays' => $this->lookupNamedId(Countrie::class, $request->input('pays')),
                'pays_id' => $this->lookupNamedId(Countrie::class, $request->input('pays')),
                'countrie_id' => $this->lookupNamedId(Countrie::class, $request->input('pays')),
            'regions_id' => $this->lookupNamedId(Region::class, $request->input('region')),
            'ville_id' => $this->lookupNamedId(Ville::class, $request->input('ville')),
        ]);
        Telephone::normaliserRequete($request, ['telephone1', 'telephone2', 'telephone_tuteur']);
        $identity = $request->validate(['telephone1'=>['nullable', Telephone::regle()],'numero_piece'=>'nullable|string|max:100']);
        $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($identity);
        $this->ensureSameStudent($existing, $request->only('matricule', 'telephone1'));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'integer', Rule::exists('genre', 'id')],
            'matricule' => ['required', 'string', 'max:100', Rule::unique('etudiants', 'matricule')->ignore($existing?->getKey(), 'id_etudiant')],
            'numero_etudiant' => ['nullable', 'string', 'max:100', Rule::unique('etudiants', 'numero_etudiant')->ignore($existing?->getKey(), 'id_etudiant')],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'telephone1' => ['required', Telephone::regle(), Rule::unique('etudiants', 'telephone1')->ignore($existing?->getKey(), 'id_etudiant')],
            'telephone2' => ['nullable', Telephone::regle()],
            'email1' => ['nullable', 'email', 'max:255', Rule::unique('etudiants', 'email1')->ignore($existing?->getKey(), 'id_etudiant')],
            'email2' => ['nullable', 'email', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'countrie_id' => ['nullable', 'integer'],
            'regions_id' => ['nullable', 'integer'],
            'ville_id' => ['nullable', 'integer'],
            'departement_code' => ['nullable', 'string', 'max:100'],
            'faculte' => ['nullable', 'string', 'max:255'],
            'moyenne' => ['nullable', 'numeric'],
            'annee_academique_actuel' => ['nullable', 'string', 'max:50'],
            'type_piece_id' => ['nullable', 'integer'],
            'numero_piece' => ['nullable', 'string', 'max:100'],
            'date_delivrance' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date'],
            'groupe_sanguin_id' => ['nullable', 'integer'],
            'allergies' => ['nullable', 'boolean'],
            'maladies_chroniques' => ['nullable', 'boolean'],
            'autiste' => ['nullable', 'boolean'],
            'pmr_confirme' => ['nullable', 'boolean'],
            'numero_lit' => ['nullable', 'integer', 'min:1'],
            'name_tuteur' => ['nullable', 'string', 'max:255'],
            'telephone_tuteur' => ['nullable', Telephone::regle()],
            'adresse_tuteur' => ['nullable', 'string', 'max:255'],
            'boursier' => ['nullable', 'boolean'],
            'type_bourse' => ['nullable', 'string', 'max:100'],
            'distance' => ['nullable', 'numeric'],
            'cite_id' => ['required', Rule::exists('cite', 'id_cite')],
            'batiment_id' => ['required', Rule::exists('batiment', 'id_batiment')],
            'campagne_id' => ['required', Rule::exists('campagne', 'id_campagne')->whereNull('deleted_at')],
            'chambre_id' => ['required', Rule::exists('chambre', 'id_chambre')->whereNull('deleted_at')],
            'date_entree' => ['required', 'date', 'before_or_equal:today'],
            'commentaires' => ['nullable', 'string', 'max:1000'],
            'type_hebergement_souhaite' => ['nullable', 'string', 'max:100'],
            'annee_academique_precedent' => ['nullable', 'string', 'max:50'],
            'niveau_etude_precedent_code' => ['nullable', 'string', 'max:100'],
            'niveau_etude_actuel_code' => ['nullable', 'string', 'max:100'],
            'filiere_code' => ['nullable', 'string', 'max:100'],
            'credit_valider' => ['nullable', 'numeric'],
            'periode' => ['nullable', 'string', 'max:100'],
        ], [
            'matricule.unique' => 'Un étudiant possède déjà ce matricule.',
            'telephone1.unique' => 'Un étudiant possède déjà ce numéro de téléphone.',
            'email1.unique' => 'Un étudiant possède déjà cette adresse e-mail.',
            'date_entree.before_or_equal' => "La date d'entrée ne peut pas être future.",
        ]);

        $etatDossier = DB::transaction(function () use ($data) {
            $chambre = app(AttributionRules::class)->verrouillerChambre($data['chambre_id']);
            $placesOccupees = AssignationChambre::mobilisantPlace()
                ->where('chambre_id', $chambre->id_chambre)
                ->count();

            if ($chambre->etat !== 'disponible' || $placesOccupees >= max(0, (int) $chambre->capacite_max)) {
                throw ValidationException::withMessages([
                    'chambre_id' => "Cette chambre n'est plus disponible. Veuillez en sélectionner une autre.",
                ]);
            }
            if ((string) $chambre->batiment_id !== (string) $data['batiment_id'] || (string) $chambre->cite_id !== (string) $data['cite_id']) {
                throw ValidationException::withMessages(['chambre_id' => 'La chambre ne correspond pas à la cité et au bâtiment sélectionnés.']);
            }

            $campagne = Campagne::query()->findOrFail($data['campagne_id']);
            $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($data);
            if ($existing) {
                $existing = Etudiant::whereKey($existing->getKey())->lockForUpdate()->firstOrFail();
                app(\App\Http\Services\ReadmissionService::class)->ensureCanApply($existing, $campagne);
            }
            $etudiant = $existing ?? Etudiant::create([
                'name' => $data['name'],
                'genre_id' => $data['genre_id'],
                'matricule' => $data['matricule'],
                'numero_etudiant' => ($data['numero_etudiant'] ?? null) ?: $data['matricule'],
                'date_naissance' => $data['date_naissance'] ?? null,
                'lieu_naissance' => $data['lieu_naissance'] ?? null,
                'telephone1' => $data['telephone1'],
                'telephone2' => $data['telephone2'] ?? null,
                'email1' => $data['email1'] ?? null,
                'email2' => $data['email2'] ?? null,
                'nationalite' => ($data['nationalite'] ?? null) ?: 'IVOIRIENNE',
                'adresse' => $data['adresse'] ?? null,
                'countrie_id' => $data['countrie_id'] ?? null,
                'regions_id' => $data['regions_id'] ?? null,
                'ville_id' => $data['ville_id'] ?? null,
                'departement_code' => $data['departement_code'] ?? null,
                'faculte' => $data['faculte'] ?? null,
                'moyenne' => $data['moyenne'] ?? null,
                'annee_academique_actuel' => $data['annee_academique_actuel'] ?? $campagne->annee_campagne,
                'type_piece_id' => $data['type_piece_id'] ?? null,
                'numero_piece' => $data['numero_piece'] ?? null,
                'date_delivrance' => $data['date_delivrance'] ?? null,
                'date_expiration' => $data['date_expiration'] ?? null,
                'groupe_sanguin_id' => $data['groupe_sanguin_id'] ?? null,
                'allergies' => $data['allergies'] ?? false,
                'maladies_chroniques' => $data['maladies_chroniques'] ?? false,
                'autiste' => $data['autiste'] ?? false,
                'pmr_confirme' => $data['pmr_confirme'] ?? false,
                'pmr_confirme_by' => !empty($data['pmr_confirme']) ? auth('admin')->id() : null,
                'pmr_confirme_at' => !empty($data['pmr_confirme']) ? now() : null,
                'name_tuteur' => $data['name_tuteur'] ?? null,
                'telephone_tuteur' => $data['telephone_tuteur'] ?? null,
                'adresse_tuteur' => $data['adresse_tuteur'] ?? null,
                'boursier' => $data['boursier'] ?? false,
                'type_bourse' => $data['type_bourse'] ?? null,
                'distance' => $data['distance'] ?? null,
                'statut_inscription' => 'demande',
                'statut' => 1,
                'is_validation' => false,
                'date_inscription' => $data['date_entree'],
                'date_validation' => now(),
                'users_validation' => getInfoAdmin()->users->id_user ?? null,
            ]);

            $dossier = DemandeEtudiant::create([
                'etudiant_id' => $etudiant->id_etudiant,
                'campagne_id' => $campagne->id_campagne,
                'date_demande' => $data['date_entree'],
                'type_hebergement_souhaite' => $data['type_hebergement_souhaite'] ?? null,
                'annee_academique_precedent' => $data['annee_academique_precedent'] ?? null,
                'annee_academique_actuel' => $data['annee_academique_actuel'] ?? $campagne->annee_campagne,
                'niveau_etude_precedent_code' => $data['niveau_etude_precedent_code'] ?? null,
                'niveau_etude_actuel_code' => $data['niveau_etude_actuel_code'] ?? null,
                'filiere_code' => $data['filiere_code'] ?? null,
                'moyenne' => $data['moyenne'] ?? 0, // colonne demande.moyenne NOT NULL (défaut 0)
                'credit_valider' => $data['credit_valider'] ?? null,
                'boursier' => $data['boursier'] ?? false,
                'periode' => $data['periode'] ?? null,
                'statut_demande' => 'En_attente',
                'commentaires' => $data['commentaires'] ?? null,
            ]);

            return app(\App\Http\Services\DossierWorkflow::class)->admissionDirecte(
                $dossier, $chambre, $campagne, $data['numero_lit'] ?? null);

        });

        return redirect()
            ->route('admin.hebergement.residents_contrat.admission')
            ->with('success', 'Étudiant déjà logé enregistré : dossier validé et chambre confirmée.');
    }

    /**
     * L'étudiant existant est retrouvé par téléphone (ou pièce) : si le matricule saisi est différent,
     * ce n'est pas la même personne (numéro réutilisé ou erroné) et on refuse plutôt que de rattacher le dossier à un autre étudiant.
     */
    private function ensureSameStudent(?Etudiant $existing, array $data): void
    {
        $normaliser = fn ($valeur) => mb_strtoupper(preg_replace('/[\s-]+/u', '', (string) $valeur));
        $matricule = $normaliser($data['matricule'] ?? '');
        if (!$existing || $matricule === '' || $normaliser($existing->matricule) === $matricule) {
            return;
        }
        throw ValidationException::withMessages(['telephone1' => 'Le téléphone ' . ($data['telephone1'] ?? '')
            . " est déjà utilisé par l'étudiant « {$existing->name} » (matricule {$existing->matricule}). Vérifiez le numéro de téléphone ou le matricule."]);
    }

    /** En-têtes du modèle d'import, en français (les anciens en-têtes `name`/`telephone1` restent acceptés à l'import). */
    private const IMPORT_HEADERS = ['nom_prenoms', 'civilite', 'matricule', 'telephone', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree', 'numero_lit'];

    /** Une ligne dont le nom commence par ce préfixe est un exemple : l'import l'ignore. */
    private const IMPORT_EXAMPLE_PREFIX = 'EXEMPLE';

    /** Ligne d'exemple du modèle, construite avec des valeurs réelles pour rester cohérente avec les listes déroulantes. */
    private function importExampleRow(): array
    {
        $room = null;
        foreach (Cite::query()->where('statut', '!=', 'ferme')->orderBy('name')->get() as $cite) {
            foreach (Batiment::query()->where('cite_id', $cite->id_cite)->where('is_valid', 1)->orderBy('name')->get() as $building) {
                $numero = Chambre::query()->where('batiment_id', $building->id_batiment)->whereNotNull('numero_chambre')->orderBy('numero_chambre')->value('numero_chambre');
                if ($numero !== null) {
                    $room = [$cite->name, $building->name, $numero];
                    break 2;
                }
            }
        }
        $campagne = Campagne::query()->where('statut', 'active')->orderByDesc('annee_campagne')->value('annee_campagne');

        return [
            'nom_prenoms' => self::IMPORT_EXAMPLE_PREFIX . ' - Kouassi Jean (ligne à remplacer)',
            'civilite' => Genre::query()->orderBy('name')->value('name') ?? 'Masculin',
            'matricule' => 'MAT-0001',
            'telephone' => '0700000000',
            'cite' => $room[0] ?? 'Nom de la cité',
            'batiment' => $room[1] ?? 'Nom du bâtiment',
            'campagne' => $campagne ?? getAnneeUniversitaire(),
            'chambre' => $room[2] ?? '101',
            'date_entree' => today()->format('d/m/Y'),
            'numero_lit' => '1',
        ];
    }

    public function downloadAdmissionImportTemplate()
    {
        $headers = self::IMPORT_HEADERS;
        $example = $this->importExampleRow();

        if (!class_exists(ZipArchive::class)) {
            return response()->streamDownload(function () use ($headers, $example) {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $headers, ';');
                fputcsv($output, array_map(fn ($header) => $example[$header], $headers), ';');
                fclose($output);
            }, 'modele_import_etudiants.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $cites = Cite::query()->where('statut', '!=', 'ferme')->orderBy('name')->get();
        // Seules les campagnes actives permettent l'admission directe (DossierWorkflow::admissionDirecte).
        $campagnes = Campagne::query()->where('statut', 'active')->orderByDesc('annee_campagne')->pluck('annee_campagne');
        $lists = [
            'Genres' => Genre::query()->orderBy('name')->pluck('name')->filter()->unique()->values()->all(),
            'Campagnes' => $campagnes->filter()->unique()->values()->all(),
            'Cites' => $cites->pluck('name')->filter()->unique()->values()->all(),
        ];

        // Listes dépendantes : cité → bâtiments (CITE_n), puis « cité|bâtiment » → chambres (BAT_n),
        // pour que deux bâtiments homonymes de cités différentes ne mélangent pas leurs chambres.
        $dependentLists = [];
        $citeLookup = [];
        $buildingLookup = [];
        foreach ($cites as $cite) {
            $buildings = Batiment::query()->where('cite_id', $cite->id_cite)->where('is_valid', 1)->orderBy('name')->get();
            $buildingNames = $buildings->pluck('name')->filter()->unique()->values()->all();
            if (!$buildingNames) continue;
            $rangeName = 'CITE_' . (count($citeLookup) + 1);
            $dependentLists[$rangeName] = $buildingNames;
            $citeLookup[] = [$cite->name, $rangeName];
            foreach ($buildings as $building) {
                $roomNames = Chambre::query()->where('batiment_id', $building->id_batiment)->whereNotNull('numero_chambre')
                    ->orderBy('numero_chambre')->pluck('numero_chambre')->filter()->unique()->values()->all();
                if (!$roomNames) continue;
                $rangeName = 'BAT_' . (count($buildingLookup) + 1);
                $dependentLists[$rangeName] = $roomNames;
                $buildingLookup[] = [$cite->name . '|' . $building->name, $rangeName];
            }
        }

        $cell = fn (string $ref, mixed $value, string $style = '') => '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t>' . htmlspecialchars((string) $value, ENT_XML1) . '</t></is></c>';

        $path = tempnam(sys_get_temp_dir(), 'etudiants_template_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        // Styles : 0 = défaut, 1 = texte (garde le 0 initial des téléphones/matricules), 2 = date jj/mm/aaaa, 3 = en-tête en gras,
        // 4/5/6 = ligne d'exemple en gris italique (défaut / texte / date).
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><i/><sz val="11"/><color rgb="FF6B7280"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="49" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="164" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');

        $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Etudiants" sheetId="1" r:id="rId1"/><sheet name="Listes" sheetId="2" state="hidden" r:id="rId2"/></sheets><definedNames>';
        $listColumns = [];
        foreach (array_keys($lists) as $index => $name) {
            $listColumns[$name] = $this->excelColumn($index + 1);
            $workbook .= '<definedName name="' . $name . '">Listes!$' . $listColumns[$name] . '$2:$' . $listColumns[$name] . '$' . max(2, count($lists[$name]) + 1) . '</definedName>';
        }
        // Feuille « Listes » : K/L = cité → plage des bâtiments, O/P = cité|bâtiment → plage des chambres, W… = plages dépendantes.
        $dependentColumn = 23;
        $dependentColumnMap = [];
        foreach ($dependentLists as $name => $values) {
            $column = $this->excelColumn($dependentColumn++);
            $dependentColumnMap[$name] = $column;
            $workbook .= '<definedName name="' . $name . '">Listes!$' . $column . '$2:$' . $column . '$' . max(2, count($values) + 1) . '</definedName>';
        }
        $workbook .= '</definedNames></workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        $sheetRows = '<row r="1">';
        foreach ($headers as $index => $header) $sheetRows .= $cell($this->excelColumn($index + 1) . '1', $header, ' s="3"');
        $sheetRows .= '</row><row r="2">';
        foreach ($headers as $index => $header) {
            $ref = $this->excelColumn($index + 1) . '2';
            $sheetRows .= match ($header) {
                // Date en numéro de série Excel pour qu'elle s'affiche au format jj/mm/aaaa.
                'date_entree' => '<c r="' . $ref . '" s="6"><v>' . (int) today()->diffInDays(\Illuminate\Support\Carbon::create(1899, 12, 30), true) . '</v></c>',
                'numero_lit' => '<c r="' . $ref . '" s="4"><v>' . (int) $example[$header] . '</v></c>',
                'matricule', 'telephone' => $cell($ref, $example[$header], ' s="5"'),
                default => $cell($ref, $example[$header], ' s="4"'),
            };
        }
        $sheetRows .= '</row>';
        $cols = '<cols><col min="1" max="1" width="32" customWidth="1"/><col min="2" max="2" width="14" customWidth="1"/>'
            . '<col min="3" max="4" width="16" style="1" customWidth="1"/><col min="5" max="8" width="20" customWidth="1"/>'
            . '<col min="9" max="9" width="14" style="2" customWidth="1"/><col min="10" max="10" width="12" customWidth="1"/></cols>';
        $validations = [
            'B' => '=Genres',
            'E' => '=Cites',
            'F' => '=INDIRECT("CITE_"&MATCH($E2,Listes!$K$2:$K$1000,0))',
            'G' => '=Campagnes',
            'H' => '=INDIRECT("BAT_"&MATCH($E2&"|"&$F2,Listes!$O$2:$O$1000,0))',
        ];
        // Listes déroulantes à titre de suggestion uniquement : showErrorMessage="0" laisse saisir ou coller
        // n'importe quelle valeur (le contrôle se fait côté serveur à l'import).
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . $cols . '<sheetData>' . $sheetRows . '</sheetData><dataValidations count="' . count($validations) . '">';
        foreach ($validations as $column => $formula) {
            $sheet .= '<dataValidation type="list" allowBlank="1" showErrorMessage="0" sqref="' . $column . '2:' . $column . '1000"><formula1>' . htmlspecialchars($formula, ENT_XML1) . '</formula1></dataValidation>';
        }
        $sheet .= '</dataValidations></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $listRows = '<row r="1">';
        foreach ($listColumns as $name => $column) $listRows .= $cell($column . '1', $name);
        foreach (['K' => 'CiteLookup', 'L' => 'CiteRange', 'O' => 'BuildingLookup', 'P' => 'BuildingRange'] as $column => $name) $listRows .= $cell($column . '1', $name);
        foreach ($dependentColumnMap as $name => $column) $listRows .= $cell($column . '1', $name);
        $listRows .= '</row>';
        $maxListRows = max(array_merge([0, count($citeLookup), count($buildingLookup)], array_map('count', $lists), array_map('count', $dependentLists)));
        for ($row = 0; $row < $maxListRows; $row++) {
            $excelRow = $row + 2;
            $listRows .= '<row r="' . $excelRow . '">';
            foreach ($lists as $name => $values) {
                if (array_key_exists($row, $values)) $listRows .= $cell($listColumns[$name] . $excelRow, $values[$row]);
            }
            if (isset($citeLookup[$row])) $listRows .= $cell('K' . $excelRow, $citeLookup[$row][0]) . $cell('L' . $excelRow, $citeLookup[$row][1]);
            if (isset($buildingLookup[$row])) $listRows .= $cell('O' . $excelRow, $buildingLookup[$row][0]) . $cell('P' . $excelRow, $buildingLookup[$row][1]);
            foreach ($dependentLists as $name => $values) {
                if (isset($values[$row])) $listRows .= $cell($dependentColumnMap[$name] . $excelRow, $values[$row]);
            }
            $listRows .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:' . $this->excelColumn(max(16, $dependentColumn - 1)) . ($maxListRows + 1) . '"/><sheetData>' . $listRows . '</sheetData></worksheet>');
        $zip->close();

        return response()->download($path, 'modele_import_etudiants.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function excelColumn(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $column = chr(65 + $remainder) . $column;
            $number = intdiv($number - 1, 26);
        }
        return $column;
    }

    public function importAdmissionDirecte(Request $request)
    {
        $request->validate([
            // `extensions` contrôle le nom ; `mimes` inclut zip car un .xlsx produit hors Excel est souvent détecté comme une archive ZIP.
            'fichier' => ['required', 'file', 'extensions:csv,txt,xlsx', 'mimes:csv,txt,xlsx,zip', 'max:10240'],
        ], [
            'fichier.extensions' => 'Formats acceptés : .xlsx ou .csv.',
            'fichier.mimes' => 'Formats acceptés : .xlsx ou .csv.',
        ]);

        try {
            $rows = $this->readImportRows($request->file('fichier'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['fichier' => \App\Support\UserFacingErrors::caught($exception)])->withInput();
        }

        if (empty($rows)) {
            return back()->withErrors(['fichier' => 'Le fichier ne contient aucune ligne de données.']);
        }

        $colonnes = array_keys($this->normalizeImportRow(array_fill_keys(array_keys($rows[0]), '')));
        $manquantes = array_values(array_diff(['name', 'genre', 'matricule', 'telephone1', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree'], $colonnes));
        if ($manquantes) {
            $libelles = array_combine(['name', 'genre', 'telephone1'], ['nom_prenoms', 'civilite', 'telephone']);
            return back()->withErrors(['fichier' => 'Colonnes manquantes : ' . implode(', ', array_map(fn ($colonne) => $libelles[$colonne] ?? $colonne, $manquantes))
                . '. Téléchargez le modèle et conservez ses en-têtes.']);
        }

        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $data = $this->normalizeImportRow($row);
            // Ligne d'exemple du modèle laissée en place : ignorée.
            if (str_starts_with(mb_strtoupper(trim((string) ($data['name'] ?? ''))), self::IMPORT_EXAMPLE_PREFIX)) continue;
            foreach (['telephone1', 'telephone2', 'telephone_tuteur'] as $champ) {
                if (!array_key_exists($champ, $data)) continue;
                // Cellule numérique d'Excel : le 0 initial est perdu (« 700000000 »).
                $telephone = Telephone::normaliser($data[$champ]);
                $data[$champ] = is_numeric($data[$champ]) && is_string($telephone) && strlen($telephone) === 9 ? '0' . $telephone : $telephone;
            }
            try {
                $data = $this->resolveImportReferences($data);
                $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($data);
                $this->ensureSameStudent($existing, $data);
            } catch (\Throwable $exception) {
                $errors[] = 'Ligne ' . $line . ' : ' . \App\Support\UserFacingErrors::caught($exception);
                continue;
            }
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'genre_id' => ['required', 'integer', Rule::exists('genre', 'id')],
                'matricule' => ['required', 'string', 'max:100', Rule::unique('etudiants', 'matricule')->ignore($existing?->getKey(), 'id_etudiant')],
                'numero_etudiant' => ['nullable', 'string', 'max:100', Rule::unique('etudiants', 'numero_etudiant')->ignore($existing?->getKey(), 'id_etudiant')],
                'date_naissance' => ['nullable', 'date', 'before:today'],
                'lieu_naissance' => ['nullable', 'string', 'max:255'],
                'telephone1' => ['required', Telephone::regle(), Rule::unique('etudiants', 'telephone1')->ignore($existing?->getKey(), 'id_etudiant')],
                'telephone2' => ['nullable', Telephone::regle()],
                'email1' => ['nullable', 'email', 'max:255', Rule::unique('etudiants', 'email1')->ignore($existing?->getKey(), 'id_etudiant')],
                'email2' => ['nullable', 'email', 'max:255'],
                'nationalite' => ['nullable', 'string', 'max:100'],
                'adresse' => ['nullable', 'string', 'max:255'],
                'countrie_id' => ['nullable', 'integer'],
                'regions_id' => ['nullable', 'integer'],
                'ville_id' => ['nullable', 'integer'],
                'departement_code' => ['nullable', 'string', 'max:100'],
                'faculte' => ['nullable', 'string', 'max:255'],
                'moyenne' => ['nullable', 'numeric'],
                'annee_academique_actuel' => ['nullable', 'string', 'max:50'],
                'type_piece_id' => ['nullable', 'integer'],
                'numero_piece' => ['nullable', 'string', 'max:100'],
                'date_delivrance' => ['nullable', 'date'],
                'date_expiration' => ['nullable', 'date'],
                'groupe_sanguin_id' => ['nullable', 'integer'],
                'allergies' => ['nullable', 'boolean'],
                'maladies_chroniques' => ['nullable', 'boolean'],
                'autiste' => ['nullable', 'boolean'],
            'pmr_confirme' => ['nullable', 'boolean'],
            'numero_lit' => ['nullable', 'integer', 'min:1'],
                'name_tuteur' => ['nullable', 'string', 'max:255'],
                'telephone_tuteur' => ['nullable', Telephone::regle()],
                'adresse_tuteur' => ['nullable', 'string', 'max:255'],
                'boursier' => ['nullable', 'boolean'],
                'type_bourse' => ['nullable', 'string', 'max:100'],
                'distance' => ['nullable', 'numeric'],
                'cite_id' => ['required', Rule::exists('cite', 'id_cite')],
                'batiment_id' => ['required', Rule::exists('batiment', 'id_batiment')],
                'campagne_id' => ['required', Rule::exists('campagne', 'id_campagne')->whereNull('deleted_at')],
                'chambre_id' => ['required', Rule::exists('chambre', 'id_chambre')->whereNull('deleted_at')],
                'date_entree' => ['required', 'date', 'before_or_equal:today'],
                'commentaires' => ['nullable', 'string', 'max:1000'],
                'type_hebergement_souhaite' => ['nullable', 'string', 'max:100'],
                'annee_academique_precedent' => ['nullable', 'string', 'max:50'],
                'niveau_etude_precedent_code' => ['nullable', 'string', 'max:100'],
                'niveau_etude_actuel_code' => ['nullable', 'string', 'max:100'],
                'filiere_code' => ['nullable', 'string', 'max:100'],
                'credit_valider' => ['nullable', 'numeric'],
                'periode' => ['nullable', 'string', 'max:100'],
            ], [
                'matricule.unique' => 'Un étudiant possède déjà ce matricule.',
                'telephone1.unique' => 'Un étudiant possède déjà ce numéro de téléphone.',
                'email1.unique' => 'Un étudiant possède déjà cette adresse e-mail.',
                'date_entree.before_or_equal' => "La date d'entrée ne peut pas être future.",
                'date_entree.date' => "La date d'entrée doit être au format jj/mm/aaaa.",
            ], [
                'name' => 'nom_prenoms', 'genre_id' => 'civilite', 'telephone1' => 'telephone',
                'cite_id' => 'cite', 'batiment_id' => 'batiment', 'campagne_id' => 'campagne',
                'chambre_id' => 'chambre', 'date_entree' => 'date_entree', 'numero_lit' => 'numero_lit',
            ]);

            if ($validator->fails()) {
                $errors[] = 'Ligne ' . $line . ' : ' . implode(' ', $validator->errors()->all());
                continue;
            }

            try {
                $data['numero_lit'] = isset($data['numero_lit']) ? (int) $data['numero_lit'] : null;
                $this->registerImportedAdmission($data);
                $successCount++;
            } catch (\Throwable $exception) {
                $errors[] = 'Ligne ' . $line . ' : ' . \App\Support\UserFacingErrors::caught($exception);
            }
        }

        $message = $successCount . ' étudiant(s) déjà logé(s) importé(s). Les dossiers et les affectations sont validés automatiquement.';
        if (!$errors) {
            return redirect()->route('admin.hebergement.residents_contrat.admission')->with('success', $message);
        }
        $message .= ' Certaines lignes n’ont pas été enregistrées : ' . implode(' | ', $errors);

        // Lignes refusées : retour sur la page d'import, où le rapport s'affiche dans une fenêtre modale.
        return back()->with('warning', $message);
    }

    private function registerImportedAdmission(array $data): void
    {
        DB::transaction(function () use ($data) {
            $chambre = app(AttributionRules::class)->verrouillerChambre($data['chambre_id']);
            $placesOccupees = AssignationChambre::mobilisantPlace()->where('chambre_id', $chambre->id_chambre)->count();

            // ValidationException (et non RuntimeException) : le motif reste lisible dans le rapport d'import.
            if ($chambre->etat !== 'disponible' || $placesOccupees >= max(0, (int) $chambre->capacite_max)) {
                throw ValidationException::withMessages(['chambre' => "La chambre « {$chambre->numero_chambre} » n'est plus disponible (pleine ou indisponible)."]);
            }
            if ((string) $chambre->batiment_id !== (string) $data['batiment_id'] || (string) $chambre->cite_id !== (string) $data['cite_id']) {
                throw ValidationException::withMessages(['chambre' => 'La chambre ne correspond pas à la cité et au bâtiment indiqués.']);
            }

            $campagne = Campagne::query()->findOrFail($data['campagne_id']);
            $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($data);
            if ($existing) {
                $existing = Etudiant::whereKey($existing->getKey())->lockForUpdate()->firstOrFail();
                // Message adapté à l'admin (celui de ReadmissionService s'adresse à l'étudiant).
                $dossierEnCours = DemandeEtudiant::where('etudiant_id', $existing->getKey())->where('campagne_id', $campagne->getKey())
                    ->whereNotIn('etat_dossier', ['cloture', 'refuse', 'annule', 'expire'])->value('etat_dossier');
                if ($dossierEnCours !== null) {
                    throw ValidationException::withMessages(['ligne' => "L'étudiant « {$existing->name} » (matricule {$existing->matricule}) a déjà un dossier "
                        . "« " . str_replace('_', ' ', (string) $dossierEnCours) . " » pour la campagne {$campagne->annee_campagne}. Traitez-le depuis la liste des admissions."]);
                }
                app(\App\Http\Services\ReadmissionService::class)->ensureCanApply($existing, $campagne);
            }
            $etudiant = $existing ?? Etudiant::create([
                'name' => $data['name'],
                'genre_id' => $data['genre_id'],
                'matricule' => $data['matricule'],
                'numero_etudiant' => ($data['numero_etudiant'] ?? null) ?: $data['matricule'],
                'date_naissance' => $data['date_naissance'] ?? null,
                'lieu_naissance' => $data['lieu_naissance'] ?? null,
                'telephone1' => $data['telephone1'],
                'telephone2' => $data['telephone2'] ?? null,
                'email1' => $data['email1'] ?? null,
                'email2' => $data['email2'] ?? null,
                'nationalite' => ($data['nationalite'] ?? null) ?: 'IVOIRIENNE',
                'adresse' => $data['adresse'] ?? null,
                'countrie_id' => $data['countrie_id'] ?? null,
                'regions_id' => $data['regions_id'] ?? null,
                'ville_id' => $data['ville_id'] ?? null,
                'departement_code' => $data['departement_code'] ?? null,
                'faculte' => $data['faculte'] ?? null,
                'moyenne' => $data['moyenne'] ?? null,
                'annee_academique_actuel' => $data['annee_academique_actuel'] ?? $campagne->annee_campagne,
                'type_piece_id' => $data['type_piece_id'] ?? null,
                'numero_piece' => $data['numero_piece'] ?? null,
                'date_delivrance' => $data['date_delivrance'] ?? null,
                'date_expiration' => $data['date_expiration'] ?? null,
                'groupe_sanguin_id' => $data['groupe_sanguin_id'] ?? null,
                'allergies' => $data['allergies'] ?? false,
                'maladies_chroniques' => $data['maladies_chroniques'] ?? false,
                'autiste' => $data['autiste'] ?? false,
                'pmr_confirme' => $data['pmr_confirme'] ?? false,
                'pmr_confirme_by' => !empty($data['pmr_confirme']) ? auth('admin')->id() : null,
                'pmr_confirme_at' => !empty($data['pmr_confirme']) ? now() : null,
                'name_tuteur' => $data['name_tuteur'] ?? null,
                'telephone_tuteur' => $data['telephone_tuteur'] ?? null,
                'adresse_tuteur' => $data['adresse_tuteur'] ?? null,
                'boursier' => $data['boursier'] ?? false,
                'type_bourse' => $data['type_bourse'] ?? null,
                'distance' => $data['distance'] ?? null,
                'statut_inscription' => 'demande',
                'statut' => 1,
                'is_validation' => false,
                'date_inscription' => $data['date_entree'],
                'date_validation' => now(),
                'users_validation' => getInfoAdmin()->users->id_user ?? null,
            ]);

            $dossier = DemandeEtudiant::create([
                'etudiant_id' => $etudiant->id_etudiant,
                'campagne_id' => $campagne->id_campagne,
                'date_demande' => $data['date_entree'],
                'type_hebergement_souhaite' => $data['type_hebergement_souhaite'] ?? null,
                'annee_academique_precedent' => $data['annee_academique_precedent'] ?? null,
                'annee_academique_actuel' => $data['annee_academique_actuel'] ?? $campagne->annee_campagne,
                'niveau_etude_precedent_code' => $data['niveau_etude_precedent_code'] ?? null,
                'niveau_etude_actuel_code' => $data['niveau_etude_actuel_code'] ?? null,
                'filiere_code' => $data['filiere_code'] ?? null,
                'moyenne' => $data['moyenne'] ?? 0, // colonne demande.moyenne NOT NULL (défaut 0)
                'credit_valider' => $data['credit_valider'] ?? null,
                'boursier' => $data['boursier'] ?? false,
                'periode' => $data['periode'] ?? null,
                'statut_demande' => 'En_attente',
                'commentaires' => $data['commentaires'] ?? null,
            ]);

            return app(\App\Http\Services\DossierWorkflow::class)->admissionDirecte(
                $dossier, $chambre, $campagne, $data['numero_lit'] ?? null);

        });
    }

    private function normalizeImportRow(array $row): array
    {
        $aliases = [
            'nom' => 'name', 'nom et prenoms' => 'name', 'nom et prénoms' => 'name',
            'nom_prenoms' => 'name', 'nom_prénoms' => 'name', 'nom_et_prenoms' => 'name', 'nom_et_prénoms' => 'name', 'nom prenoms' => 'name', 'nom prénoms' => 'name',
            'civilite' => 'genre', 'civilité' => 'genre', 'genre' => 'genre', 'genre_id' => 'genre', 'telephone' => 'telephone1', 'téléphone' => 'telephone1',
            'numéro lit' => 'numero_lit', 'numero lit' => 'numero_lit', 'numéro_lit' => 'numero_lit', 'lit' => 'numero_lit',
            'date_entrée' => 'date_entree',
            'email' => 'email1', 'e-mail' => 'email1', 'annee_academique' => 'campagne',
            'année académique' => 'campagne', 'campagne_id' => 'campagne', 'chambre' => 'chambre', 'chambre_id' => 'chambre',
            'cité' => 'cite', 'cite' => 'cite', 'cite_id' => 'cite',
            'bâtiment' => 'batiment', 'batiment' => 'batiment', 'batiment_id' => 'batiment',
            'pays' => 'pays', 'pays_id' => 'pays', 'countrie' => 'pays', 'countrie_id' => 'pays',
            'région' => 'region', 'region' => 'region', 'region_id' => 'region', 'regions_id' => 'region',
            'ville' => 'ville', 'ville_id' => 'ville',
            'type_piece' => 'type_piece', 'type_piece_id' => 'type_piece',
            'groupe_sanguin' => 'groupe_sanguin', 'groupe_sanguin_id' => 'groupe_sanguin',
            'numéro chambre' => 'chambre', 'numero chambre' => 'chambre',
            'date entree' => 'date_entree', 'date d entree' => 'date_entree', 'date_d_entree' => 'date_entree', 'date d’entrée' => 'date_entree',
            'date entrée' => 'date_entree',
        ];
        $normalized = [];
        foreach ($row as $key => $value) {
            $key = mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $key)));
            if ($key === '') continue;
            $value = is_string($value) ? trim($value) : $value;
            // Cellule vide = valeur absente (pas de chaîne vide en base, ni de '' passé à un paramètre ?int).
            $normalized[$aliases[$key] ?? str_replace([' ', '-'], '_', $key)] = $value === '' ? null : $value;
        }
        foreach (['date_entree', 'date_naissance', 'date_delivrance', 'date_expiration'] as $field) {
            $value = $normalized[$field] ?? null;
            if ($value === null || $value === '') continue;
            if (is_numeric($value) && (float) $value > 0 && (float) $value < 100000) {
                $normalized[$field] = (new \DateTimeImmutable('1899-12-30'))->modify('+'.(int) floor((float) $value).' days')->format('Y-m-d');
            } elseif (preg_match('~^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$~', (string) $value, $parts) && checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3])) {
                $normalized[$field] = sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1]);
            }
        }
        return $normalized;
    }

    private function resolveImportReferences(array $data): array
    {
        $data['genre_id'] = Genre::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) ($data['genre'] ?? '')) ?: '')])->value('id');
        // Plusieurs campagnes peuvent partager une année : on retient l'active (seule acceptée pour une admission directe).
        $data['campagne_id'] = Campagne::query()->where('annee_campagne', trim((string) ($data['campagne'] ?? '')))
            ->orderByRaw("CASE WHEN statut = 'active' THEN 0 ELSE 1 END")->value('id_campagne');
        $data['cite_id'] = Cite::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) ($data['cite'] ?? '')) ?: '')])->value('id_cite');
        $cite = $data['cite_id'] ? Cite::query()->find($data['cite_id']) : null;
        $data['batiment_id'] = $cite
            ? Batiment::query()->where('cite_id', $cite->id_cite)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) ($data['batiment'] ?? '')) ?: '')])->value('id_batiment')
            : null;
        $data['chambre_id'] = $data['batiment_id']
            ? Chambre::query()->where('batiment_id', $data['batiment_id'])->whereRaw('LOWER(numero_chambre) = ?', [mb_strtolower(trim((string) ($data['chambre'] ?? '')) ?: '')])->value('id_chambre')
            : null;
        $data['type_piece_id'] = $this->lookupNamedId(TypePiece::class, $data['type_piece'] ?? null);
        $data['groupe_sanguin_id'] = $this->lookupNamedId(groupeSanguin::class, $data['groupe_sanguin'] ?? null);
        $data['countrie_id'] = $this->lookupNamedId(Countrie::class, $data['pays'] ?? null);
        $data['regions_id'] = $this->lookupNamedId(Region::class, $data['region'] ?? null);
        $data['ville_id'] = $this->lookupNamedId(Ville::class, $data['ville'] ?? null);
        $data['departement_code'] = $this->lookupCode(Departement::class, $data['departement_code'] ?? null, 'code_departement');
        $data['niveau_etude_precedent_code'] = $this->lookupCode(NiveauEtude::class, $data['niveau_etude_precedent_code'] ?? null, 'code_niveau');
        $data['niveau_etude_actuel_code'] = $this->lookupCode(NiveauEtude::class, $data['niveau_etude_actuel_code'] ?? null, 'code_niveau');
        $data['filiere_code'] = $this->lookupCode(Filiere::class, $data['filiere_code'] ?? null, 'code_filiere');
        foreach (['allergies', 'maladies_chroniques', 'autiste', 'pmr_confirme', 'boursier'] as $booleanField) {
            if (array_key_exists($booleanField, $data)) {
                $data[$booleanField] = in_array(mb_strtolower(trim((string) $data[$booleanField])), ['oui', 'yes', '1', 'true'], true) ? 1 : 0;
            }
        }

        $missing = [];
        foreach ([
            'genre_id' => ['civilite', 'genre'], 'campagne_id' => ['campagne', 'campagne'], 'cite_id' => ['cite', 'cite'],
            'batiment_id' => ['batiment', 'batiment'], 'chambre_id' => ['chambre', 'chambre'],
        ] as $id => [$label, $source]) {
            if (!empty($data[$id])) continue;
            $value = trim((string) ($data[$source] ?? ''));
            $missing[] = $value === '' ? $label . ' (vide)' : $label . ' « ' . $value . ' » introuvable';
        }
        if ($missing) {
            // ValidationException : message conservé tel quel par UserFacingErrors::caught().
            throw ValidationException::withMessages(['ligne' => 'Valeur à corriger : ' . implode(', ', $missing) . '.']);
        }
        return $data;
    }

    private function lookupNamedId(string $model, mixed $value): mixed
    {
        if ($value === null || trim((string) $value) === '') return null;
        return $model::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])->value($model::query()->getModel()->getKeyName());
    }

    private function lookupCode(string $model, mixed $value, string $codeColumn): mixed
    {
        if ($value === null || trim((string) $value) === '') return null;
        return $model::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
            ->orWhereRaw('LOWER(' . $codeColumn . ') = ?', [mb_strtolower(trim((string) $value))])
            ->value($codeColumn);
    }

    private function readImportRows(UploadedFile $file): array
    {
        return \App\Support\SimpleXlsx::read($file);
    }
}
