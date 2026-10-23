<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\etudiant\dashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\etudiant\DisciplinaryIncidentController;

//Route::middleware(['auth:etudiant'])->group(function () {
Route::middleware(['etudiants'])->group(function () {
    Route::get('/entree-logement', [\App\Http\Controllers\HousingEntryController::class,'student'])->name('housing-entry');
    Route::get('/readmission', [\App\Http\Controllers\etudiant\ReadmissionController::class,'show'])->name('readmission');
    Route::post('/readmission', [\App\Http\Controllers\etudiant\ReadmissionController::class,'store'])
        ->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('readmission.store');

    Route::post('/dossiers/{demande}/transition', [\App\Http\Controllers\DossierWorkflowController::class,'student'])
        ->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('dossiers.transition');
    Route::get('/mon-contrat/approbation', [\App\Http\Controllers\etudiant\ContractApprovalController::class, 'show'])->name('contract.approval');
    Route::post('/mon-contrat/approbation', [\App\Http\Controllers\etudiant\ContractApprovalController::class, 'store'])
        ->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('contract.approve');

    Route::get('/documents', [\App\Http\Controllers\etudiant\DocumentController::class, 'index'])->name('documents.index');
    Route::get('/mes-documents', [\App\Http\Controllers\etudiant\DocumentController::class, 'personal'])->name('documents.personal');
    Route::get('/mes-documents/recu/{id}', [\App\Http\Controllers\etudiant\DocumentController::class, 'receipt'])->name('documents.receipt');
    Route::get('/mes-documents/etat-des-lieux/{id}', [\App\Http\Controllers\etudiant\DocumentController::class, 'inventory'])->name('documents.inventory');
    Route::get('/mes-documents/justificatif/{id}', [\App\Http\Controllers\etudiant\DocumentController::class, 'attachment'])->name('documents.attachment');
    Route::get('/mes-documents/decompte/{id}', [\App\Http\Controllers\etudiant\DocumentController::class, 'settlement'])->name('documents.settlement');
    Route::get('/documents/{document}/pdf', [\App\Http\Controllers\etudiant\DocumentController::class, 'pdf'])->name('documents.pdf');
    Route::prefix('paiements-en-ligne')->name('online-payments.')
        ->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->controller(\App\Http\Controllers\etudiant\OnlinePaymentController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
            Route::get('/{payment}', 'show')->name('show');
            Route::post('/{payment}/verifier', 'check')->middleware('throttle:10,1')->name('check');
        });


    Route::controller(dashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::post('/demandes', 'listDemandes')->name('list-demandes');
        Route::post('/demande/{idDemande}/validation', 'showValidationPage')->name('show-validation');
        Route::post('/demande/{idDemande}/statut', 'getStatutDemande')->name('get-statut-demande');
        Route::post('/demande/{idDemande}/valider', 'AcceptOrRefuseDemande')->name('validation-demande');
        Route::post('/demande/{idDemande}/accepter', 'AcceptOrRefuseDemande')->name('accepter-demande');
        Route::post('/demande/{idDemande}/refuser', 'AcceptOrRefuseDemande')->name('refuser-demande');
        Route::post('/demandes-sortie', 'storeDepartureRequest')->name('departure-requests.store');
        Route::get('/profil', 'profile')->name('profile');
        Route::patch('/profil', 'updateProfile')->name('profile.update');
        Route::put('/profil/mot-de-passe', 'updatePassword')->middleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, 'throttle:6,1'])->name('profile.password');

        Route::post('/demande/eld',  'storeEdl')->name('store_eld');
        Route::put('/demande/eld/{id}', 'updateEdl')->name('update_eld');

        Route::post('/demande/ot',  'storeOrdreTravail')->name('store_ot');
        Route::get('/contrat/{id?}',  'pdfContrat')->name('pdfContrat');
        Route::get('/annonce',  'annonces')->name('annonces');
        Route::post('/annonce/{annonce}/jaime', 'toggleJaimeAnnonce')
            ->middleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, 'throttle:30,1'])->name('annonce.jaime');
    });


    Route::controller(MapController::class)->group(function () {
        Route::get('map',  'index')->name('map');
        Route::get('map/api/campuses', 'getCampuses')->name('map.api.campuses');
        Route::get('map/api/cites',  'getCites')->name('map.api.cites');
        Route::get('map/api/batiments', 'getBatiments')->name('map.api.batiments');
        Route::get('map/api/stats', 'getStats')->name('map.api.stats');
        Route::get('map/api/nearby', 'searchNearby')->name('map.api.nearby');
        Route::get('map/api/export', 'exportData')->name('map.api.export');
    });

    Route::post('/incidents-disciplinaires', [DisciplinaryIncidentController::class, 'store'])
        ->name('incidents.store');

    Route::post('/deconnexion', [\App\Http\Controllers\web\frontedController::class, 'logoutEtudiant'])
        ->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->name('logout');

});
