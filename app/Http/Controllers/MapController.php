<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Cite;
use App\Models\AssignationChambre;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class MapController extends Controller
{
    /**
     * Carte du parc (Inertia) : cités, bâtiments et campus géolocalisés, avec leur occupation.
     * Les éléments sans coordonnées sont aussi envoyés pour être signalés dans la liste.
     * L'ancienne vue Blade `admin.hebergement.parc-immobilier.interactive` est conservée pour retour arrière.
     */
    public function index(Request $request, AdminAccess $access)
    {
        $admin = $request->user('admin');
        $canParc = $access->allows($admin, 'admin.hebergement.parc-immobilier.index');
        $canEdit = $access->allows($admin, 'admin.hebergement.parc-immobilier.create');

        $batiments = Batiment::query()->with('typeOccupation')->where('is_valid', 1)->orderBy('name')->get();
        $chambres = Chambre::query()->whereNotNull('numero_chambre')->whereNotNull('type_chambre_id')
            ->whereIn('batiment_id', $batiments->pluck('id_batiment')->all())
            ->get(['id_chambre', 'batiment_id', 'etat', 'capacite_max']);
        $occupants = $chambres->isEmpty() ? collect() : AssignationChambre::actives()
            ->whereIn('chambre_id', $chambres->pluck('id_chambre')->all())
            ->selectRaw('chambre_id, count(*) as total')
            ->groupBy('chambre_id')
            ->pluck('total', 'chambre_id');
        $resume = fn ($groupe) => [
            'chambres' => $groupe->count(),
            'disponibles' => $groupe->where('etat', 'disponible')->count(),
            'places' => (int) $groupe->sum('capacite_max'),
            'occupants' => (int) $groupe->sum(fn ($chambre) => (int) ($occupants[$chambre->id_chambre] ?? 0)),
        ];
        $parBatiment = $chambres->groupBy('batiment_id');
        $vide = collect();

        $cites = Cite::query()->with(['typeCite', 'responsable'])->where('statut', '!=', 'ferme')->orderBy('name')->get();

        return Inertia::render('Admin/Hebergement/Carte/Index', [
            'cites' => $cites->map(fn (Cite $cite) => [
                'id' => $cite->id_cite,
                'name' => $cite->name,
                'code' => $cite->code_cite,
                'statut' => $cite->statut,
                'occupation' => $cite->typeCite?->name,
                'adresse' => $cite->adresse ?: null,
                'responsable' => $cite->responsable?->name ?: null,
                'telephone' => $cite->telephone1 ?: null,
                'position' => $this->position($cite->latitude, $cite->longitude),
                'services' => array_keys(array_filter([
                    'Wi-Fi gratuit' => $cite->wifi_gratuit,
                    "Salle d'étude" => $cite->salle_etude,
                    'Infirmerie' => $cite->infirmerie,
                    'Restaurant' => $cite->restaurant,
                ])),
                'batiments' => $batiments->where('cite_id', $cite->id_cite)->count(),
                'resume' => $resume($chambres->whereIn('batiment_id', $batiments->where('cite_id', $cite->id_cite)->pluck('id_batiment')->all())),
                'urls' => [
                    'parc' => $canParc ? route('admin.hebergement.parc-immobilier.index', ['cite' => $cite->id_cite]) : null,
                    'edit' => $canEdit ? route('admin.hebergement.parc-immobilier.create', ['type' => 'cite', 'edit' => 1, 'id' => $cite->id_cite]) : null,
                ],
            ])->values(),
            'batiments' => $batiments->map(fn (Batiment $batiment) => [
                'id' => $batiment->id_batiment,
                'name' => $batiment->name,
                'cite_id' => $batiment->cite_id,
                'statut' => $batiment->statut,
                'occupation' => $batiment->typeOccupation?->name,
                'position' => $this->position($batiment->latitude, $batiment->longitude),
                'resume' => $resume($parBatiment->get($batiment->id_batiment, $vide)),
                'urls' => [
                    'parc' => $canParc ? route('admin.hebergement.parc-immobilier.index', ['batiment' => $batiment->id_batiment]) : null,
                    'edit' => $canEdit ? route('admin.hebergement.parc-immobilier.create', ['type' => 'batiment', 'edit' => 1, 'id' => $batiment->id_batiment]) : null,
                ],
            ])->values(),
            'campus' => Campus::query()->where('statut', '!=', 'ferme')->orderBy('name')->get()
                ->map(fn (Campus $campus) => [
                    'id' => $campus->id_campus,
                    'name' => $campus->name,
                    'adresse' => $campus->adresse ?: null,
                    'position' => $this->position($campus->latitude, $campus->longitude),
                ])
                ->filter(fn ($campus) => $campus['position'] !== null)
                ->values(),
            'urls' => [
                'parc' => $canParc ? route('admin.hebergement.parc-immobilier.index') : null,
            ],
        ]);
    }

    /** Coordonnées exploitables ou null (non renseignées, ou laissées à 0 par l'ancien formulaire). */
    private function position($latitude, $longitude): ?array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude) || ((float) $latitude === 0.0 && (float) $longitude === 0.0)) {
            return null;
        }

        return ['lat' => (float) $latitude, 'lng' => (float) $longitude];
    }

    /**
     * API: Récupérer les campus avec ou sans filtre
     */
    public function getCampuses(Request $request)
    {
        $query = Campus::where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Filtre par région si fourni
        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $campuses = $query->get();

        return response()->json([
            'success' => true,
            'count' => $campuses->count(),
            'data' => $campuses->map(function($campus) {
                return [
                    'id_campus' => $campus->id_campus,
                    'code_campus' => $campus->code_campus,
                    'name' => $campus->name,
                    'latitude' => (float)$campus->latitude,
                    'longitude' => (float)$campus->longitude,
                    'adresse' => $campus->adresse,
                    'telephone1' => $campus->telephone1,
                    'email1' => $campus->email1,
                    'description' => $campus->description,
                ];
            })->toArray()
        ]);
    }

    /**
     * API: Récupérer les cités avec filtres
     */
    public function getCites(Request $request)
    {
        $query = Cite::where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Filtre par campus
        if ($request->has('campus_code')) {
            $campusCode = $request->campus_code;
            $query->where('campus_code', $campusCode);
        }

        // Filtre par région
        if ($request->has('region_id')) {
            $campus = Campus::where('region_id', $request->region_id)->first();
            if ($campus) {
                $query->where('campus_code', $campus->code_campus);
            }
        }

        $cites = $query->get();

        return response()->json([
            'success' => true,
            'count' => $cites->count(),
            'data' => $cites->map(function($cite) {
                return [
                    'id_cite' => $cite->id_cite,
                    'code_cite' => $cite->code_cite,
                    'code' => $cite->code,
                    'name' => $cite->name,
                    'campus_code' => $cite->campus_code,
                    'latitude' => (float)$cite->latitude,
                    'longitude' => (float)$cite->longitude,
                    'adresse' => $cite->adresse,
                    'capacite_max' => $cite->capacite_max,
                    'type_cite' => $cite->type_cite_code,
                    'telephone1' => $cite->telephone1,
                    'email1' => $cite->email1,
                ];
            })->toArray()
        ]);
    }

    /**
     * API: Récupérer les bâtiments avec filtres
     */
    public function getBatiments(Request $request)
    {
        $query = Batiment::where('is_valid', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Filtre par cité
        if ($request->has('code_cite')) {
            $query->where('code_cite', $request->code_cite);
        }

        // Filtre par campus
        if ($request->has('code_campus')) {
            $query->where('code_campus', $request->code_campus);
        }

        $batiments = $query->get();

        return response()->json([
            'success' => true,
            'count' => $batiments->count(),
            'data' => $batiments->map(function($bat) {
                return [
                    'id_batiment' => $bat->id_batiment,
                    'code_batiment' => $bat->code_batiment,
                    'name' => $bat->name,
                    'code_cite' => $bat->code_cite,
                    'code_campus' => $bat->code_campus,
                    'latitude' => (float)$bat->latitude,
                    'longitude' => (float)$bat->longitude,
                    'adresse' => $bat->adresse,
                    'type_occupation' => $bat->type_occupation,
                    'total_etages' => $bat->total_etages,
                    'total_chambres' => $bat->total_chambres,
                    'description' => $bat->description,
                ];
            })->toArray()
        ]);
    }

    /**
     * API: Obtenir les statistiques complètes
     */
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'campuses_total' => Campus::where('statut', '!=', 'supprimé')->count(),
                'campuses_with_coords' => Campus::where('statut', '!=', 'supprimé')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'cites_total' => Cite::where('statut', '!=', 'supprimé')->count(),
                'cites_with_coords' => Cite::where('statut', '!=', 'supprimé')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'batiments_total' => Batiment::where('is_valid', 1)->count(),
                'batiments_with_coords' => Batiment::where('is_valid', 1)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
            ]
        ]);
    }

    /**
     * Recherche géographique : Trouver les campus proches
     */
    public function searchNearby(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:50'
        ]);

        $radius = $validated['radius_km'] ?? 10;
        $lat = $validated['latitude'];
        $lon = $validated['longitude'];
        $earthRadiusKm = 6371;

        // Formule Haversine pour calculer la distance
        $campuses = Campus::where('statut', '!=', 'supprimé')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw(
                "*, 
                ($earthRadiusKm * acos(
                    cos(radians($lat)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians($lon)) + 
                    sin(radians($lat)) * 
                    sin(radians(latitude))
                )) AS distance"
            )
            ->whereRaw(
                "($earthRadiusKm * acos(
                    cos(radians($lat)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians($lon)) + 
                    sin(radians($lat)) * 
                    sin(radians(latitude))
                )) <= ?",
                [$radius]
            )
            ->orderBy('distance')
            ->get();

        return response()->json([
            'success' => true,
            'radius_km' => $radius,
            'center' => [
                'latitude' => $lat,
                'longitude' => $lon
            ],
            'count' => $campuses->count(),
            'data' => $campuses->map(function($campus) {
                return [
                    'id_campus' => $campus->id_campus,
                    'code_campus' => $campus->code_campus,
                    'name' => $campus->name,
                    'latitude' => (float)$campus->latitude,
                    'longitude' => (float)$campus->longitude,
                    'adresse' => $campus->adresse,
                    'distance_km' => round($campus->distance, 2),
                ];
            })->toArray()
        ]);
    }

    /**
     * Exporter les données pour affichage
     */
    public function exportData(Request $request)
    {
        $format = $request->get('format', 'json'); // json ou csv

        $campuses = Campus::where('statut', '!=', 'supprimé')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $cites = Cite::where('statut', '!=', 'supprimé')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $batiments = Batiment::where('is_valid', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($format === 'csv') {
            return $this->exportCSV($campuses, $cites, $batiments);
        }

        return response()->json([
            'success' => true,
            'campuses' => $campuses->toArray(),
            'cites' => $cites->toArray(),
            'batiments' => $batiments->toArray(),
        ]);
    }

    /**
     * Exporter en CSV
     */
    private function exportCSV($campuses, $cites, $batiments)
    {
        $csv = "Type,Code,Nom,Latitude,Longitude,Adresse\n";

        foreach ($campuses as $campus) {
            $csv .= sprintf(
                'Campus,%s,"%s",%s,%s,"%s"' . "\n",
                $campus->code_campus,
                str_replace('"', '""', $campus->name),
                $campus->latitude,
                $campus->longitude,
                str_replace('"', '""', $campus->adresse)
            );
        }

        foreach ($cites as $cite) {
            $csv .= sprintf(
                'Cité,%s,"%s",%s,%s,"%s"' . "\n",
                $cite->code_cite,
                str_replace('"', '""', $cite->name),
                $cite->latitude,
                $cite->longitude,
                str_replace('"', '""', $cite->adresse)
            );
        }

        foreach ($batiments as $bat) {
            $csv .= sprintf(
                'Bâtiment,%s,"%s",%s,%s,"%s"' . "\n",
                $bat->code_batiment,
                str_replace('"', '""', $bat->name),
                $bat->latitude,
                $bat->longitude,
                str_replace('"', '""', $bat->adresse ?? 'N/A')
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="map-data.csv"');
    }
}
