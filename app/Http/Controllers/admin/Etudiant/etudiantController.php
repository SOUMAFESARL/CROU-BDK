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
            ->where('etat', 'disponible')
            ->orderBy('numero_chambre')
            ->get()
            ->filter(fn (Chambre $chambre) => $chambre->peutAccueillir());

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
            ])->values(),
            'aujourdhui' => today()->toDateString(),
            'import' => $avertissement ? ['message' => \Illuminate\Support\Str::before((string) $avertissement, ' Certaines lignes'), 'lignes' => $lignesRefusees] : null,
            'urls' => [
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
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
        $identity = $request->validate(['telephone1'=>'nullable|string|max:30','numero_piece'=>'nullable|string|max:100']);
        $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($identity);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'integer', Rule::exists('genre', 'id')],
            'matricule' => ['required', 'string', 'max:100', Rule::unique('etudiants', 'matricule')->ignore($existing?->getKey(), 'id_etudiant')],
            'numero_etudiant' => ['nullable', 'string', 'max:100', Rule::unique('etudiants', 'numero_etudiant')->ignore($existing?->getKey(), 'id_etudiant')],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'telephone1' => ['required', 'string', 'max:30', Rule::unique('etudiants', 'telephone1')->ignore($existing?->getKey(), 'id_etudiant')],
            'telephone2' => ['nullable', 'string', 'max:30'],
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
            'telephone_tuteur' => ['nullable', 'string', 'max:30'],
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
                'moyenne' => $data['moyenne'] ?? null,
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
            ->route('admin.etudiant.admission_directe')
            ->with('success', 'Étudiant déjà logé enregistré : dossier validé et chambre confirmée.');
    }

    public function downloadAdmissionImportTemplate()
    {
        $headers = ['name', 'civilite', 'matricule', 'telephone1', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree', 'numero_lit'];

        if (!class_exists(ZipArchive::class)) {
            return response()->streamDownload(function () use ($headers) {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $headers, ';');
                fclose($output);
            }, 'modele_import_etudiants.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $lists = [
            'Genres' => Genre::query()->orderBy('name')->pluck('name')->filter()->values()->all(),
            'Campagnes' => Campagne::query()->orderByDesc('annee_campagne')->pluck('annee_campagne')->filter()->values()->all(),
            'Cites' => Cite::query()->where('statut', '!=', 'ferme')->orderBy('name')->pluck('name')->filter()->values()->all(),
            'Batiments' => Batiment::query()->where('is_valid', 1)->orderBy('name')->pluck('name')->filter()->values()->all(),
            'Chambres' => Chambre::query()->whereNotNull('numero_chambre')->orderBy('numero_chambre')->pluck('numero_chambre')->filter()->values()->all(),
        ];
        $additionalLists = [];

        $dependentLists = [];
        $citeLookup = [];
        $buildingLookup = [];
        $citeRangeNumber = 1;
        $buildingRangeNumber = 1;
        foreach (Cite::query()->where('statut', '!=', 'ferme')->orderBy('name')->get() as $cite) {
            $buildingNames = Batiment::query()->where('cite_id', $cite->id_cite)->where('is_valid', 1)->orderBy('name')->pluck('name')->filter()->values()->all();
            if ($buildingNames) {
                $rangeName = 'CITE_' . $citeRangeNumber++;
                $dependentLists[$rangeName] = $buildingNames;
                $citeLookup[] = [$cite->name, $rangeName];
            }
        }
        foreach (Batiment::query()->where('is_valid', 1)->orderBy('name')->get() as $building) {
            $roomNames = Chambre::query()->where('batiment_id', $building->id_batiment)->whereNotNull('numero_chambre')->orderBy('numero_chambre')->pluck('numero_chambre')->filter()->values()->all();
            if ($roomNames) {
                $rangeName = 'BAT_' . $buildingRangeNumber++;
                $dependentLists[$rangeName] = $roomNames;
                $buildingLookup[] = [$building->name, $rangeName];
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'etudiants_template_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/></Relationships>');

        $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Etudiants" sheetId="1" r:id="rId1"/><sheet name="Listes" sheetId="2" state="hidden" r:id="rId2"/></sheets><definedNames>';
        foreach ($lists as $name => $values) {
            $last = max(2, count($values) + 1);
            $workbook .= '<definedName name="' . $name . '">Listes!$' . $this->excelColumn(array_search($name, array_keys($lists), true) + 1) . '$2:$' . $this->excelColumn(array_search($name, array_keys($lists), true) + 1) . '$' . $last . '</definedName>';
        }
        foreach ($additionalLists as $index => $values) {
            $column = $this->excelColumn(17 + array_search($index, array_keys($additionalLists), true));
            $workbook .= '<definedName name="' . $index . '">Listes!$' . $column . '$2:$' . $column . '$' . max(2, count($values) + 1) . '</definedName>';
        }
        $dependentColumn = 23;
        $dependentColumnMap = [];
        foreach ($dependentLists as $name => $values) {
            $column = $this->excelColumn($dependentColumn++);
            $dependentColumnMap[$name] = $column;
            $last = max(2, count($values) + 1);
            $workbook .= '<definedName name="' . $name . '">Listes!$' . $column . '$2:$' . $column . '$' . $last . '</definedName>';
        }
        $workbook .= '</definedNames></workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        $sheetRows = '<row r="1">';
        foreach ($headers as $index => $header) $sheetRows .= '<c r="' . $this->excelColumn($index + 1) . '1" t="inlineStr"><is><t>' . htmlspecialchars($header, ENT_XML1) . '</t></is></c>';
        $sheetRows .= '</row>';
        $validations = ['B' => '=Genres', 'E' => '=Cites', 'F' => '=INDIRECT("CITE_"&MATCH($E2,Listes!$K$2:$K$1000,0))', 'G' => '=Campagnes', 'H' => '=INDIRECT("BAT_"&MATCH($F2,Listes!$O$2:$O$1000,0))'];
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0" freezePane="1"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>' . $sheetRows . '</sheetData><dataValidations count="' . count($validations) . '">';
        foreach ($validations as $column => $formula) $sheet .= '<dataValidation type="list" allowBlank="1" sqref="' . $column . '2:' . $column . '1000"><formula1>' . htmlspecialchars($formula, ENT_XML1) . '</formula1></dataValidation>';
        $sheet .= '</dataValidations></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $listRows = '<row r="1">';
        foreach (array_keys($lists) as $index => $name) {
            $listRows .= '<c r="' . $this->excelColumn($index + 1) . '1" t="inlineStr"><is><t>' . htmlspecialchars($name, ENT_XML1) . '</t></is></c>';
        }
        foreach (array_keys($additionalLists) as $index => $name) {
            $column = $this->excelColumn(17 + $index);
            $listRows .= '<c r="' . $column . '1" t="inlineStr"><is><t>' . htmlspecialchars($name, ENT_XML1) . '</t></is></c>';
        }
        foreach (['K' => 'CiteLookup', 'L' => 'CiteRange', 'O' => 'BuildingLookup', 'P' => 'BuildingRange'] as $column => $name) {
            $listRows .= '<c r="' . $column . '1" t="inlineStr"><is><t>' . $name . '</t></is></c>';
        }
        foreach ($dependentColumnMap as $name => $column) {
            $listRows .= '<c r="' . $column . '1" t="inlineStr"><is><t>' . $name . '</t></is></c>';
        }
        $listRows .= '</row>';
        $maxListRows = max(array_merge(array_map('count', $lists), array_map('count', $additionalLists), [count($citeLookup), count($buildingLookup)], array_map('count', $dependentLists)));
        for ($row = 0; $row < $maxListRows; $row++) {
            $excelRow = $row + 2;
            $listRows .= '<row r="' . $excelRow . '">';
            foreach ($lists as $index => $values) {
                if (!array_key_exists($row, $values)) continue;
                $column = $this->excelColumn(array_search($index, array_keys($lists), true) + 1);
                $listRows .= '<c r="' . $column . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $values[$row], ENT_XML1) . '</t></is></c>';
            }
            foreach ($additionalLists as $index => $values) {
                if (!array_key_exists($row, $values)) continue;
                $column = $this->excelColumn(17 + array_search($index, array_keys($additionalLists), true));
                $listRows .= '<c r="' . $column . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $values[$row], ENT_XML1) . '</t></is></c>';
            }
            if (isset($citeLookup[$row])) {
                $listRows .= '<c r="K' . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $citeLookup[$row][0], ENT_XML1) . '</t></is></c>';
                $listRows .= '<c r="L' . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $citeLookup[$row][1], ENT_XML1) . '</t></is></c>';
            }
            if (isset($buildingLookup[$row])) {
                $listRows .= '<c r="O' . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $buildingLookup[$row][0], ENT_XML1) . '</t></is></c>';
                $listRows .= '<c r="P' . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $buildingLookup[$row][1], ENT_XML1) . '</t></is></c>';
            }
            foreach ($dependentLists as $name => $values) {
                if (isset($values[$row])) {
                    $listRows .= '<c r="' . $dependentColumnMap[$name] . $excelRow . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $values[$row], ENT_XML1) . '</t></is></c>';
                }
            }
            $listRows .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:' . $this->excelColumn(max(10, $dependentColumn - 1)) . ($maxListRows + 1) . '"/><sheetData>' . $listRows . '</sheetData></worksheet>');
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
            'fichier' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        try {
            $rows = $this->readImportRows($request->file('fichier'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['fichier' => \App\Support\UserFacingErrors::caught($exception)])->withInput();
        }

        if (empty($rows)) {
            return back()->withErrors(['fichier' => 'Le fichier ne contient aucune ligne de données.']);
        }

        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $data = $this->normalizeImportRow($row);
            try {
                $data = $this->resolveImportReferences($data);
                $existing = app(\App\Http\Services\StudentIdentityResolver::class)->existingForAdmin($data);
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
                'telephone1' => ['required', 'string', 'max:30', Rule::unique('etudiants', 'telephone1')->ignore($existing?->getKey(), 'id_etudiant')],
                'telephone2' => ['nullable', 'string', 'max:30'],
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
                'telephone_tuteur' => ['nullable', 'string', 'max:30'],
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
            ]);

            if ($validator->fails()) {
                $errors[] = 'Ligne ' . $line . ' : ' . implode(' ', $validator->errors()->all());
                continue;
            }

            try {
                $this->registerImportedAdmission($data);
                $successCount++;
            } catch (\Throwable $exception) {
                $errors[] = 'Ligne ' . $line . ' : ' . \App\Support\UserFacingErrors::caught($exception);
            }
        }

        $message = $successCount . ' étudiant(s) déjà logé(s) importé(s). Les dossiers et les affectations sont validés automatiquement.';
        if ($errors) {
            $message .= ' Certaines lignes n’ont pas été enregistrées : ' . implode(' | ', $errors);
        }

        return back()->with($errors ? 'warning' : 'success', $message);
    }

    private function registerImportedAdmission(array $data): void
    {
        DB::transaction(function () use ($data) {
            $chambre = app(AttributionRules::class)->verrouillerChambre($data['chambre_id']);
            $placesOccupees = AssignationChambre::mobilisantPlace()->where('chambre_id', $chambre->id_chambre)->count();

            if ($chambre->etat !== 'disponible' || $placesOccupees >= max(0, (int) $chambre->capacite_max)) {
                throw new \RuntimeException("La chambre {$data['chambre_id']} n'est plus disponible.");
            }
            if ((string) $chambre->batiment_id !== (string) $data['batiment_id'] || (string) $chambre->cite_id !== (string) $data['cite_id']) {
                throw new \RuntimeException('La chambre ne correspond pas à la cité et au bâtiment indiqués.');
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
                'moyenne' => $data['moyenne'] ?? null,
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
            'civilite' => 'genre', 'civilité' => 'genre', 'genre' => 'genre', 'genre_id' => 'genre', 'telephone' => 'telephone1', 'téléphone' => 'telephone1',
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
            $normalized[$aliases[$key] ?? str_replace([' ', '-'], '_', $key)] = is_string($value) ? trim($value) : $value;
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
        $data['campagne_id'] = Campagne::query()->where('annee_campagne', trim((string) ($data['campagne'] ?? '')))->value('id_campagne');
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
            'genre_id' => 'civilité', 'campagne_id' => 'campagne', 'cite_id' => 'cité',
            'batiment_id' => 'bâtiment', 'chambre_id' => 'chambre',
        ] as $id => $label) {
            if (empty($data[$id])) $missing[] = $label;
        }
        if ($missing) {
            throw new \RuntimeException('Valeur introuvable pour : ' . implode(', ', $missing) . '.');
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
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'rb');
            rewind($handle);
            $delimiter = str_contains((string) fgets($handle), ';') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            $rows = [];
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
                $rows[] = array_combine(array_pad($headers, count($values), ''), array_pad($values, count($headers), '')) ?: [];
            }
            fclose($handle);
            return $rows;
        }

        if ($extension !== 'xlsx' || !class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Format non supporté. Utilisez un fichier .xlsx ou .csv.');
        }
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) throw new \RuntimeException('Impossible de lire le fichier Excel.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sharedXml = simplexml_load_string($xml);
            foreach ($sharedXml->si as $item) $shared[] = (string) ($item->t ?? implode('', array_map('strval', $item->r->t ?? [])));
        }
        $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/([A-Z]+)/', $reference, $match);
                $column = 0;
                foreach (str_split($match[1] ?? '') as $letter) $column = $column * 26 + ord($letter) - 64;
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 'inlineStr') $value = (string) ($cell->is->t ?? '');
                if ((string) $cell['t'] === 's') $value = $shared[(int) $value] ?? $value;
                $values[$column - 1] = $value;
            }
            $rows[] = $values;
        }
        $zip->close();
        $headers = array_shift($rows) ?: [];
        if (!$headers) return [];
        $width = max(array_keys($headers)) + 1;
        $headerValues = [];
        for ($index = 0; $index < $width; $index++) $headerValues[] = $headers[$index] ?? '';
        $result = [];
        foreach ($rows as $row) {
            if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
            $values = [];
            for ($index = 0; $index < $width; $index++) $values[] = $row[$index] ?? '';
            $result[] = array_combine($headerValues, $values);
        }
        return $result;
    }
}
