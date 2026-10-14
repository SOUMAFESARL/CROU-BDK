<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\parametrage\parametrageController;
use App\Http\Controllers\api\campagne\campagneController;
use App\Http\Controllers\api\etudiant\auth\authController;
use App\Http\Controllers\api\campagne\MapController;
use App\Http\Controllers\api\ordreTravail\OrdreTravailController;
use App\Http\Controllers\etudiant\dashboardController;
use App\Http\Controllers\api\paiement\paiementController;
use App\Http\Controllers\api\discipline\StudentDisciplinaryReportController;
use App\Http\Controllers\api\discipline\SecurityDisciplinaryReportController;
use App\Http\Controllers\api\etudiant\StudentMobileController;

Route::prefix('payments/notifications')->name('payments.notifications.')
    ->middleware('throttle:120,1')
    ->withoutMiddleware(\App\Http\Middleware\LogUserActivity::class)
    ->controller(\App\Http\Controllers\api\paiement\OnlinePaymentNotificationController::class)
    ->group(function () {
        Route::post('/wave', 'wave')->name('wave');
        Route::match(['get', 'post'], '/{payment}/{token}', 'notification')->name('transaction');
    });

Route::prefix('V1')->group(function () {
    Route::middleware('student.api')->group(function () {
        Route::get('entree-logement', [\App\Http\Controllers\HousingEntryController::class,'student']);
        Route::get('readmission', [\App\Http\Controllers\etudiant\ReadmissionController::class,'show']);
        Route::post('readmission', [\App\Http\Controllers\etudiant\ReadmissionController::class,'store']);

        Route::get('dossiers/{demande}', [\App\Http\Controllers\DossierWorkflowController::class,'show']);
        Route::post('dossiers/{demande}/transition', [\App\Http\Controllers\DossierWorkflowController::class,'student']);
    });

    Route::prefix('incidents-disciplinaires')
       
        ->controller(StudentDisciplinaryReportController::class)
        ->group(function () {
            Route::get('/{etudiant_id}', 'index')->name('index');
            Route::post('/{etudiant_id}', 'store')->name('store');
            Route::get('/{report}', 'show')->name('show');
        });

    Route::prefix('service-securite/incidents-disciplinaires')
        ->name('security.disciplinary-reports.')
        ->middleware(['web', 'permission:discipline.manage'])
        ->controller(SecurityDisciplinaryReportController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{report}', 'show')->name('show');
            Route::patch('/{report}/transition', 'transition')->name('transition');
        });


    Route::prefix('auth')->group(function () {
        Route::controller(authController::class)->group(function () {

            Route::post('register',"postRegister");
            Route::post('authenticate',"authenticate");
            Route::post('logout', 'logout')->middleware('student.api');


        });
    });

    Route::prefix('etudiant')->middleware('student.api')->controller(StudentMobileController::class)->group(function () {
        Route::get('demandes-sortie/{etudiant_id?}', 'departureRequests');
        Route::post('demandes-sortie', 'submitDepartureRequest');

        Route::get('etat-des-lieux-sortie', [\App\Http\Controllers\api\etudiant\InspectionController::class, 'latestExit']); 
        Route::match(['post', 'patch'], 'etat-des-lieux-sortie/{inspection}/approuver', [\App\Http\Controllers\api\etudiant\InspectionController::class, 'approve'])->name('api.student.inspections.exit.approve');

        Route::get('maintenances/{etudiant_id?}', 'maintenances');
        Route::post('maintenances', 'submitMaintenance');
    });


    Route::prefix('parametrage')->group(function () {
        Route::controller(parametrageController::class)->group(function () {

            Route::get('region',"region");
            Route::get('ville/{region_id}',"ville");
            Route::get('type-document',"typeDocument");
            Route::get('type-chanbre',"typeChanbre");
             Route::get('niveau-etude',"niveauEtude");
             Route::get('type-piece',"TypePiece");
             Route::get('groupe-sanguin',"groupeSanguin");
            Route::get('filiere',"Filiere");
                Route::get('genre',"genre");
            Route::get('config',"config");
            Route::get('annonces/{etudiant_id}',"annonces");

        });
    });


    Route::get('/contrat/{id?}', [\App\Http\Controllers\api\etudiant\ContractApprovalController::class, 'legacyPdf'])
        ->middleware('student.api')->name('api.student.contracts.legacy-pdf');

    Route::prefix('campagne')->group(function () {
        Route::controller(campagneController::class)->group(function () {

            Route::get('derniere-campagne',"index");
            Route::post('demande/acceptation_refus', 'AcceptOrRefuseDemande')->middleware('student.api');
            Route::get('/contrat/{id}', [\App\Http\Controllers\api\etudiant\ContractApprovalController::class, 'legacyPdf'])
                ->middleware('student.api')->name('api.student.contracts.legacy-campaign-pdf');


        });
    });

    Route::controller(MapController::class)->prefix('map')->group(function () {
        Route::get('/',  'index');
        Route::get('campuses', 'getCampuses');
        Route::get('cites',  'getCites');
        Route::get('batiments', 'getBatiments');
        Route::get('stats', 'getStats');
        Route::get('nearby', 'searchNearby');
        Route::get('export', 'exportData');
    });

       Route::controller(paiementController::class)->prefix('paiement')->middleware('student.api')->group(function () {
        Route::get('/{etudiant_id?}', 'getPaiementData')->name('api.student.payments');
    
    });



        Route::controller(OrdreTravailController::class)->group(function () {

            Route::prefix('ordre-travail')->group(function () {


                Route::post('store',  'store')->name('store');

        });


    });


    Route::middleware('student.api')->name('api.student.')->group(function () {
        Route::prefix('contrats')->name('contracts.')
            ->controller(\App\Http\Controllers\api\etudiant\ContractApprovalController::class)->group(function () {
                Route::get('actuel', 'current')->name('current');
                Route::match(['post', 'patch'], '{contract}/approuver', 'approve')->name('approve');
                Route::get('{contract}/pdf', 'pdf')->name('pdf');
            });
        Route::get('paiements', [paiementController::class, 'getPaiementData'])->name('payments.index');
        Route::prefix('paiements-en-ligne')->name('online-payments.')
            ->controller(\App\Http\Controllers\api\paiement\OnlinePaymentController::class)->group(function () {
                Route::get('moyens', 'providers')->name('providers');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
                Route::get('{payment}', 'show')->name('show');
                Route::post('{payment}/verifier', 'check')->middleware('throttle:10,1')->name('check');
            });
        Route::prefix('etat-des-lieux')->name('inspections.')
            ->controller(\App\Http\Controllers\api\etudiant\InspectionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('eld', 'store')->name('store');
                Route::put('eld/{inspection}', 'update')->name('update');
                Route::match(['post', 'patch'], '{inspection}/approuver', 'approve')->name('approve');
                Route::get('{inspection}', 'show')->name('show');
            });
        Route::get('documents', [\App\Http\Controllers\api\etudiant\DocumentController::class, 'index'])->name('documents.index');
        Route::get('mes-documents', [\App\Http\Controllers\api\etudiant\DocumentController::class, 'personal'])->name('documents.personal');
        Route::controller(\App\Http\Controllers\etudiant\DocumentController::class)->name('documents.')->group(function () {
            Route::get('documents/{document}/pdf', 'pdf')->name('pdf');
            Route::get('mes-documents/recu/{id}', 'receipt')->name('receipt');
            Route::get('mes-documents/etat-des-lieux/{id}', 'inventory')->name('inventory');
            Route::get('mes-documents/justificatif/{id}', 'attachment')->name('attachment');
            Route::get('mes-documents/decompte/{id}', 'settlement')->name('settlement');
        });
    });
});
