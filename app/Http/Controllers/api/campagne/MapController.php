<?php

namespace App\Http\Controllers\api\campagne;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Cite;
use App\Models\Batiment;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * Afficher la carte interactive avec tous les campus, cités et bâtiments
     */
    public function index()
    {

        // Récupérer tous les campus avec coordonnées
        $campuses = Campus::where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // Récupérer toutes les cités avec coordonnées
        $cites = Cite::with('typeCite')->where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // Récupérer tous les bâtiments avec coordonnées
        $batiments = Batiment::where('is_valid', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return response()->json([
            'campuses' => $campuses,
            'cites' => $cites,
            'batiments' => $batiments,

        ]);

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
