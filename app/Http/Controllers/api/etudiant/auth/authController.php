<?php
namespace App\Http\Controllers\api\etudiant\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Repositories\Interfaces\DemandeRepositoryInterface;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\CampagneRepository;
use App\Http\Services\CampagneService;
use App\Http\Services\DemandeService;
use App\Http\Services\DocumentEtudiantService;
use App\Http\Services\CampagneValidationService;
use App\Http\Requests\DemandeRequest;
use App\Http\Requests\DocumentEtudiantRequest;
use App\Http\Requests\EtudiantRequest;
use App\Http\Services\EtudiantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Etudiant;
use App\Models\StudentApiToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class authController extends Controller
{

    public function postRegister(EtudiantRequest $request)
    {
        try {

          //  return response()->json($request, 400);
            $etudiantService = new EtudiantService(
                app()->make(EtudiantRepositoryInterface::class),
                app()->make(CampagneValidationService::class)
            );
            // Le service va traiter la création du dossier
            $result = $etudiantService->creerDossier($request);


            $demandeService = new DemandeService(
                app()->make(DemandeRepositoryInterface::class)

            );

            $documentService = new DocumentEtudiantService(
                app()->make(DocumentEtudiantRepositoryInterface::class)
            );

            $requestData = $request->all();
            $requestData['id_etudiant'] = $result['data']['id_etudiant'];

            $demandeRequest = new DemandeRequest();
            $documentRequest = new DocumentEtudiantRequest();
            $demandeRequest->merge($requestData);
            $documentRequest->merge($requestData);
            // dd($demandeRequest);
            $demande = $demandeService->creerDemande($demandeRequest);

            $document = $documentService->uploadDocument($documentRequest);

            $finalisation = $etudiantService->finaliserInscriptionAutomatique(
                $result['data']['id_etudiant'],
                $demande['data']['id_demande'] ?? null
            );

            if ($result['success']) {
                return response()->json([
                    'message' => $finalisation['message'] ?? $result['message'],
                    'eligible' => $finalisation['eligible'] ?? false,
                    'etat_dossier' => $finalisation['data']['etat_dossier'] ?? null,
                    'demande_id' => $demande['data']['id_demande'] ?? null,
                ], 200);
            } else {

                return response()->json(['message' => $result['message'] ], 400);

            }
        } catch (\Exception $e) {
            return response()->json(['message' => "Erreur survenue lors de l'enregistrement."], 400);

        }
    }

    public function authenticate(Request $request)
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'], // Added email validation
            'password' => ['required'],
        ]);

        $user = Etudiant::where('email1', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if ((int) $user->statut === 1) {

                $etudiantService = new EtudiantService(
                    app()->make(EtudiantRepositoryInterface::class),
                    app()->make(CampagneValidationService::class)
                );

                $campgneService = new CampagneService(
                    app()->make(CampagneRepository::class)

                );

                // Le service va traiter la création du dossier
                $result = $etudiantService->getEtudiantDetails($user->id_etudiant);
                $result['derniere_campagne'] = $campgneService->getListeCampagne('active');

                $plainToken = Str::random(80);
                StudentApiToken::create([
                    'etudiant_id' => $user->id_etudiant,
                    'name' => trim((string) $request->input('device_name', 'mobile')) ?: 'mobile',
                    'token_hash' => hash('sha256', $plainToken),
                    'expires_at' => now()->addDays(90),
                ]);

                $result['auth'] = [
                    'token' => $plainToken,
                    'token_type' => 'Bearer',
                    'expires_at' => now()->addDays(90)->toIso8601String(),
                ];

                return response()->json( $result , 200);


            } else {
                return response()->json(['message' => "Votre compte a été désactivé. Veuillez contacter votre administrateur."], 400);


            }
        }

        return response()->json(['message' => "Veuillez vérifier votre email ou votre mot de passe."], 400);

    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        StudentApiToken::where('token_hash', hash('sha256', (string) $token))->delete();

        return response()->json(['success' => true, 'message' => 'Déconnexion réussie.']);
    }


}
