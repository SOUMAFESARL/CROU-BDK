<?php

namespace App\Http\Services;

use App\Models\Etudiant;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentContractService
{
    public function html(Etudiant $etudiant): ?string
    {
        $infoChambre = $etudiant->assignationsChambre()
            ->where('statut', 'confirmee')
            ->with(['chambre.batiment', 'chambre.typeChambre', 'chambre.cite'])
            ->orderByDesc('date_assignation')->orderByDesc('created_at')->first();
        if (!$infoChambre || !$infoChambre->chambre) {
            return null;
        }
        $config = allConfig();
        $data = [
            'contrat' => (object) [
                'reference' => 'CONTRAT-' . $infoChambre->getKey()
            ],
            'config' => $config,
            'locataire_nom' => $etudiant->name ?? '[NOM DU LOCATAIRE]',
            'locataire_naissance' => $etudiant->date_naissance?->format('d-m-Y') ?? '[DATE DE NAISSANCE]',
            'locataire_matricule' => $etudiant->matricule ?? '[MATRICULE]',
            'locataire_adresse_actuelle' => $etudiant->adresse ?? '[ADRESSE ACTUELLE]',
            'chambre_designation' => $infoChambre?->chambre?->numero_chambre ?? '[NUMÉRO CHAMBRE]',
            'batiment_designation' => $infoChambre?->chambre?->batiment?->name ?? '[NUMÉRO BAIMENT]',
            'chambre_type' => $infoChambre?->chambre?->typeChambre?->name ?? 'meublée',
            'surface' => $infoChambre?->chambre?->superficie ?? '[SURFACE]',
            'adresse_logement' => $infoChambre?->chambre?->cite?->adresse ?? '[ADRESSE]',
            'date_entree' => $infoChambre->date_assignation?->format('d/m/Y') ?? '[DATE D’ENTRÉE]',

            'loyer_mensuel' => number_format($infoChambre->chambre->loyer ?? 0, 0, ',', ' ') . ' FCFA',
















            'preavis_locataire' => '1 mois',





            'date_signature' => $infoChambre->date_assignation?->format('d/m/Y'),
            'bailleur_nom' => $config->app_name ?? 'CROU',

        ];

        $data = array_merge($data, app(ContractTerms::class)->values());
        $data['preavis_locataire'] = \App\Models\DepartureSetting::current()->notice_days.' jours';
        return view('pdf.contrat', $data)->render();
    }

    public function stream(Etudiant $etudiant)
    {
        $contract = app(StudentContractApprovalService::class)->current($etudiant);
        if (!$contract) {
            return response()->view('pdf.contrat-indisponible', [], 404)
                ->header('Cache-Control', 'private, no-store');
        }
        return $this->streamApproval($contract, 'contrat-'.\Illuminate\Support\Str::slug($etudiant->matricule ?: $etudiant->id_etudiant).'.pdf');
    }

    public function streamApproval(\App\Models\StudentContractApproval $contract, string $filename)
    {
        $html = $contract->document_html;
        if ($contract->approved_at) {
            $approval = '<div style="page-break-inside:avoid;margin-top:24px;padding:12px;border:1px solid #146b55;font-size:11pt"><strong>Approbation du résident</strong><p>'
                .e($contract->student_name).' — '.e($contract->approved_at->format('d/m/Y à H:i'))
                .'</p><p>'.e($contract->consent_text).'</p></div>';
            $html = str_replace('</body>', $approval.'</body>', $html);
        }
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont'=>'DejaVu Sans', 'isHtml5ParserEnabled'=>true, 'isRemoteEnabled'=>true, 'isPhpEnabled'=>false])
            ->stream($filename)
            ->header('Cache-Control', 'private, no-store');
    }
}
