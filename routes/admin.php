<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\auth\AuthController;
use App\Http\Controllers\admin\Dashboard\dashboardController;
use App\Http\Controllers\admin\immobilier\parc_immobilier\ParcImmobilierController;
use App\Http\Controllers\admin\immobilier\parametrage\regimesfinancier\RegimesFinanciersController;
use App\Http\Controllers\admin\immobilier\parametrage\type_cite\TypeCiteController;
use App\Http\Controllers\admin\immobilier\parametrage\type_equipement\TypeEquipementController;
use App\Http\Controllers\admin\immobilier\equipements\EquipementController;
use App\Http\Controllers\admin\immobilier\campagnes_admission\CampagnesAdmissionController;
use App\Http\Controllers\admin\immobilier\parametrage\type_chambre\TypeChambreController;
use App\Http\Controllers\admin\immobilier\parametrage\quotas_campagne\QuotasCampagneController;
use App\Http\Controllers\admin\immobilier\parametrage\batiments_campagne\BatimentsCampagneController;
use App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController;
use App\Http\Controllers\admin\immobilier\parametrage\regles_campagne\ReglesCampagnesController;
use App\Http\Controllers\admin\immobilier\depart_etat_des_lieux\DepartEtatDesLieuxController;
use App\Http\Controllers\admin\immobilier\depart_etat_des_lieux\DepartureSettingController;
use App\Http\Controllers\admin\immobilier\parametrage\document\documentController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\VieResidentielleController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\AnnonceController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\PaiementController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\MaintenanceController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\SanctionController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\DisciplinaryReportController;
use App\Http\Controllers\admin\users\usersController;
use App\Http\Controllers\admin\parametrage\ParametrageController;
use App\Http\Controllers\admin\parametrage\CountrieController;
use App\Http\Controllers\admin\parametrage\RegionController;
use App\Http\Controllers\admin\parametrage\Villecontroller;
use App\Http\Controllers\admin\parametrage\NiveauEtudeController;
use App\Http\Controllers\admin\parametrage\DepartementController;
use App\Http\Controllers\admin\parametrage\FiliereController;
use App\Http\Controllers\admin\parametrage\TypeUtilisateurController;
use App\Http\Controllers\admin\parametrage\configurationController;
use App\Http\Controllers\admin\parametrage\RolePermissionController;
use App\Http\Controllers\admin\parametrage\ModuleController;
use App\Http\Controllers\admin\parametrage\LegalSignatoryController;
use App\Http\Controllers\admin\parametrage\LegalDocumentController;
use App\Http\Controllers\preferenceController;
use App\Http\Controllers\admin\Etudiant\etudiantController;
use App\Http\Controllers\admin\immobilier\residents_contrat\ResidentContratControler;
use App\Http\Controllers\admin\immobilier\residents_contrat\AllocationChambreController;
use App\Http\Controllers\admin\immobilier\ReportingHebergementController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\admin\ActivityLogController;

// « /admin » seul redirige vers la page de choix de connexion (routes/web.php).
Route::get('/connexion',[AuthController::class,'index'])->name('login');
Route::post('/login',[AuthController::class,'login'])->name('post.login');
Route::get('/verification-2fa',[AuthController::class,'twoFactorForm'])->name('two-factor.form');
Route::post('/verification-2fa',[AuthController::class,'twoFactorVerify'])->name('two-factor.verify');
Route::get('/mot-passe-oublie',[AuthController::class,'forgot'])->name('forgot');



Route::prefix('list')->group(function () {
    Route::controller(preferenceController::class)->group(function () {
        Route::get('/city', 'getVille')->name('getCity');
        Route::get('/cite-campus', 'getCiteOfCampus')->name('cite_campus');
        Route::get('/batiment-cite', 'getBatimentOfCite')->name('batiment_cite');
        Route::get('/chambre-batiment', 'getChambreOfBatiment')->name('chambre_batiment');


    });
});

Route::middleware(['admin'])->group(function () {
    Route::get('/mon-profil', [\App\Http\Controllers\admin\users\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/mon-profil/mot-de-passe', [\App\Http\Controllers\admin\users\ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password');

    Route::get('/journal-activite', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/journal-activite/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::get('/journal-activite/impression', [ActivityLogController::class, 'print'])->name('activity-logs.print');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');


// Routes dans un groupe "admin" si vous le souhaitez


    Route::name('dashboard.')
        ->controller(dashboardController::class)
        ->group(function () {
            Route::get('/dashboard', 'index')->name('index');
            Route::get('/dashboard/hebergement', 'index')->name('hebergement');
            Route::get('/dashboard/restauration', 'restauration')->name('restauration');
            Route::get('/dashboard/bourses', 'bourses')->name('bourses');
            Route::get('/dashboard/vie-etudiante', 'vieEtudiante')->name('vie_etudiante');
            Route::get('/dashboard/finances', 'finances')->name('finances');
        });


//    Route::get('/documentation-api', function () {
//        return view('admin/documentation/doc');
//    })->name('welcome');

    Route::get('/documentation-api', [App\Http\Controllers\DocumentationController::class, 'index'])
        ->name('welcome');



//    Route::get('/gestion-hebergement', function () {
//        return view('admin.hebergement.index');
//    })->name('hebergement.index');;

//    Route::prefix('gestion-hebergement/parc-immobilier')
//        ->name('hebergement.parc-immobilier.')
//        ->controller(ParcImmobilierController::class)
//        ->group(function () {
//
//            Route::get('/', 'index')->name('index');
//            Route::get('/create', 'create')->name('create');
//            Route::post('/storeCampus', 'storeCampus')->name('storeCampus');
//            Route::post('/storeCite', 'storeCite')->name('storeCite');
//            Route::post('/storeBatiment', 'storeBatiment')->name('storeBatiment');
//            Route::post('/storeChambre', 'storeChambre')->name('storeChambre');
//
//
//            Route::put('/updateCampus', 'updateCampus')->name('updateCampus');
//            Route::put('/updateCite', 'storeCite')->name('updateCite');
//            Route::put('/updateBatiment', 'storeBatiment')->name('updateBatiment');
//
//
//            Route::delete('/destroy/{type}/{code}', 'destroy')
//                ->name('destroy')
//                ->where('type', 'campus|cite|batiment|chambre');
//
//
//            Route::get('/chambres/batiment', 'getChambresByBatiment')->name('getChambresByBatiment');
//
//            Route::put('/update-chambre', 'updateChambre')->name('updateChambre');
//
//            Route::put('/update-multiple-chambres', 'updateMultipleChambres')->name('updateMultipleChambres');
//
//            Route::get('/chambres-by-niveau', 'getChambresByNiveau')->name('getChambresByNiveau');
//
//            Route::post('/quick-create-chambre', 'quickCreateChambre')->name('quickCreateChambre');
//        });

    Route::prefix('gestion-hebergement/parc-immobilier')
        ->name('hebergement.parc-immobilier.')
        ->controller(ParcImmobilierController::class)
        ->group(function () {

            // ─────────────────────────────────────────────
            // VUE PRINCIPALE
            // ─────────────────────────────────────────────
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');

            // ─────────────────────────────────────────────
            // CAMPUS
            // ─────────────────────────────────────────────
            Route::post('/storeCampus', 'storeCampus')->name('storeCampus');
            Route::put('/updateCampus', 'updateCampus')->name('updateCampus');

            // ─────────────────────────────────────────────
            // CITÉ
            // ─────────────────────────────────────────────
            Route::post('/storeCite', 'storeCite')->name('storeCite');
            Route::put('/updateCite', 'updateCite')->name('updateCite');

            // ─────────────────────────────────────────────
            // BÂTIMENT
            // ─────────────────────────────────────────────
            Route::post('/storeBatiment', 'storeBatiment')->name('storeBatiment');
            Route::put('/updateBatiment', 'updateBatiment')->name('updateBatiment');

            // ─────────────────────────────────────────────
            // CHAMBRE
            // ─────────────────────────────────────────────
            Route::post('/storeChambre', 'storeChambre')->name('storeChambre');
            Route::put('/update-chambre', 'updateChambre')->name('updateChambre');
            Route::put('/massCreateChambres', 'massCreateChambres')->name('massCreateChambres');
            Route::put('/update-multiple-chambres', 'updateMultipleChambres')->name('updateMultipleChambres');
            Route::post('/quick-create-chambre', 'quickCreateChambre')->name('quickCreateChambre');
            Route::post('/quick-create-all-chambre', 'quickCreateAllChambre')->name('quickCreateAllChambre');

            // ─────────────────────────────────────────────
            // SUPPRESSION
            // ─────────────────────────────────────────────
            Route::delete('/destroy/{type}/{code}', 'destroy')
                ->name('destroy')
                ->where('type', 'campus|cite|batiment|chambre');
            Route::post('/toggle-status/{type}/{code}', 'toggleStatus')
                ->name('toggle-status')
                ->where('type', 'cite|batiment|chambre');

            // ─────────────────────────────────────────────
            // AJAX — Données pour les formulaires
            // ─────────────────────────────────────────────
            Route::get('/chambres/batiment', 'getChambresByBatiment')->name('getChambresByBatiment');
            Route::get('/chambres-by-niveau', 'getChambresByNiveau')->name('getChambresByNiveau');

            // ─────────────────────────────────────────────
            // NOUVELLES ROUTES — Types d'occupation autorisés
            // ─────────────────────────────────────────────

            // Retourne les types d'occupation autorisés pour les bâtiments d'une cité
            // GET /gestion-hebergement/parc-immobilier/ajax/allowed-batiment-types?cite_code=XXX
            Route::get('/ajax/allowed-batiment-types', 'getAllowedBatimentTypes')
                ->name('getAllowedBatimentTypes');

            // Retourne les types d'occupation autorisés pour les chambres d'un bâtiment
            // GET /gestion-hebergement/parc-immobilier/ajax/allowed-chambre-types?batiment_code=XXX
            Route::get('/ajax/allowed-chambre-types', 'getAllowedChambreTypes')
                ->name('getAllowedChambreTypes');
        });

    Route::prefix('gestion-hebergement/equipements/affectations')->name('hebergement.equipements.affectations.')
        ->controller(\App\Http\Controllers\admin\immobilier\equipements\EquipmentAllocationController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:hebergement.equipements.view')->name('index');
            Route::post('/', 'store')->middleware('permission:hebergement.equipements.update')->name('store');
            Route::patch('/{affectation}', 'update')->middleware('permission:hebergement.equipements.update')->name('update');
            Route::post('/{affectation}/retour', 'retour')->middleware('permission:hebergement.equipements.update')->name('retour');
        });

        Route::prefix('gestion-hebergement/equipements')
        ->name('hebergement.equipements.')
        ->controller(EquipementController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');
            Route::post('/store', 'store')->name('store');
            Route::put('/update', 'update')->name('update');
            Route::post('/store-mouvement', 'storeMouvement')->name('store_mouvement');
            Route::get('/{materiel}/mouvement', 'mouvements')->name('mouvements');
            Route::delete('/{materiel}/delete', 'destroy')->name('destroy');

        });


    Route::prefix('gestion-hebergement/campagnes_admissions')
        ->name('hebergement.campagnes_admission.')
        ->controller(CampagnesAdmissionController::class)
        ->group(function () {

            Route::get('/liste/{etat?}', 'liste')->name('admission');
            Route::get('/campagnes/{etat?}', 'campagnes')->name('campagnes');
            Route::post('/campagnes', 'storeCampagne')->name('post_campagnes');
            Route::get('/validateDocument/{documentId}/{statut}/{message?}', 'valideDocument')->name('validate_document');
            Route::get('campagne/{id}','getCampagne')->name('getCampagne');
            Route::get('/{id}',  'show')->name('show');
            Route::post('/traiter-demande/{id}',  'submitTraitement')->name('submit-traitement');
            Route::post('/derogations/etudiant/{student}', [\App\Http\Controllers\admin\immobilier\campagnes_admission\AdmissionDerogationController::class, 'store'])->name('derogations.store');
            Route::post('/derogations/{derogation}/decision', [\App\Http\Controllers\admin\immobilier\campagnes_admission\AdmissionDerogationController::class, 'decide'])->name('derogations.decide');

        });



    Route::prefix('gestion-hebergement/depart-etat-des-lieux')
        ->name('hebergement.depart_etat_des_lieux.')
        ->controller(DepartEtatDesLieuxController::class)
        ->group(function () {

            Route::get('/', 'depart')->name('depart');
            Route::post('/demande-sortie', 'storeDepartureRequest')->name('store_departure_request');
            Route::put('/demande-sortie/{id}/decision', 'decide')->name('departure_request.decision');
            Route::post('/decompte/{id}/restitution-caution', 'restituerCaution')->name('settlement.refund');
            Route::get('/demande-sortie/{id}/decompte.pdf', 'settlementPdf')->name('settlement.pdf');
            Route::get('/etat-des-lieux', 'etatDesLieux')->name('etatDesLieux');
            Route::post('/etat-des-lieux', 'storeEdl')->name('store_edl');
            Route::put('/etat-des-lieux/entree/{id}', 'updateEdl')->name('update_eld');


        });

    Route::prefix('gestion-hebergement/depart-etat-des-lieux/parametres')
        ->name('hebergement.depart_etat_des_lieux.settings.')
        ->controller(DepartureSettingController::class)
        ->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
        });

    Route::prefix('gestion-hebergement/vie-residentielle')
        ->name('hebergement.vie_residentielle.')
        ->controller(VieResidentielleController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');
            Route::get('/annonce', 'annonce')->name('annonce');



        });

    Route::prefix('gestion-hebergement/vie-residentielle/annonce')
        ->name('hebergement.annonce.')
        ->controller(AnnonceController::class)
        ->group(function () {


            Route::get('/', 'index')->name('index');
            Route::post('/store', 'store')->name('store');
            Route::get('{id}',  'show')->name('show');
            Route::get('{id}/edit',  'edit')->name('edit');
            Route::get('{id}/update', 'update')->name('update');
            Route::post('{id}/publish', 'publish')->name('publish');
            Route::delete('{id}','destroy')->name('destroy');



        });

    Route::prefix('gestion-hebergement/vie-residentielle/sanctions')
        ->name('hebergement.vie_residentielle.sanctions.')
        ->controller(SanctionController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::patch('/{sanction}/statut', 'changeStatus')->name('status');
        });

    Route::prefix('gestion-hebergement/vie-residentielle/incidents-disciplinaires')
        ->name('hebergement.vie_residentielle.incidents.')
        ->controller(DisciplinaryReportController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::patch('/{report}/transition', 'transition')->name('transition');
        });

    Route::prefix('gestion-hebergement/paiements')
        ->name('hebergement.paiements.')
        ->controller(PaiementController::class)
        ->group(function () {

         Route::get('/rapport-financier', 'rapportFinancier')->name('rapport_financier');
            Route::get('/', 'index')->name('index');

            Route::post('/',  'store')->name('store');
            Route::post('/{id}/valider',  'valider')->name('valider');
            Route::post('/{id}/annuler',  'annuler')->name('annuler');
            Route::post('relance',  'envoyerRelances')->name('relances.envoyer');
            Route::get('/export',  'export')->name('export');
            Route::get('/recherche-residents', 'rechercheResidents')->name('recherche');
            Route::get('/detail/{id}',  'show')->name('show');



        });

    Route::prefix('gestion-hebergement/maintenance')
        ->name('hebergement.maintenance.')
        ->controller(MaintenanceController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/', 'store')->name('store_ot');

            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::post('/intervention',  'storeIntervention')->name('intervention.store');
            Route::post('/{id}/statut','changerStatut')->name('statut');
            Route::post('/{id}/annuler', 'annuler')->name('annuler');
            Route::post('/satisfaction',  'storeSatisfaction')->name('satisfaction');
            Route::post('/{id}/assigner',  'assigner')->name('assigner');
            Route::put('/{id}/update',  'update')->name('update');



            Route::post('/store-maintenancier', 'storeMaintenancier')->name('store_maintenancier');

        });



    Route::prefix('gestion-hebergement/residents_contrat')
        ->name('hebergement.residents_contrat.')
        ->controller(ResidentContratControler::class)
        ->group(function () {

            Route::get('/liste/{etat?}', 'liste')->name('admission');


        });



    Route::get('/gestion-hebergement', function () {
        return view('admin.hebergement.index');
    })->name('hebergement.index');

    Route::get('/gestion-hebergement/reporting', [ReportingHebergementController::class, 'index'])
        ->name('hebergement.reporting.index');
    Route::get('/gestion-hebergement/reporting/{module}', [ReportingHebergementController::class, 'index'])
        ->name('hebergement.reporting.module');

    Route::prefix('gestion-hebergement/parametrage-herb')
        ->name('hebergement.parametrage-herb.')
        ->group(function () {

            // Route pour la page d'accueil du paramétrage (vue statique)
            Route::get('/', function () {
                return view('admin.hebergement.parametrage-herb.index');
            })->name('index');

            // ROUTE TYPE CITE
            Route::prefix('type-cite')->name('type-cite.')
                ->controller(TypeCiteController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                    Route::patch('/{id}/toggle-status', 'toggleStatus')->name('toggle-status');
                });
            // ROUTE TYPE EQUIPEMENT
            Route::prefix('type-equipement')->name('type-equipement.')
                ->controller(TypeEquipementController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                    Route::patch('/{id}/toggle-status', 'toggleStatus')->name('toggle-status');
                });
            // ROUTE TYPE CHAMBRE
            Route::prefix('type-chambre')->name('type-chambre.')
                ->controller(TypeChambreController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                    Route::patch('/{id}/toggle-status', 'toggleStatus')->name('toggle-status');
                });


            Route::prefix('quotas-campagne')->name('quotas-campagne.')
                ->controller(QuotasCampagneController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');

                });


            Route::prefix('document')->name('document.')
                ->controller(documentController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/store', 'store')->name('store');
                    Route::put('/update/{document}', 'update')->name('update');

                });

            Route::prefix('batiments-campagne')->name('batiments-campagne.')
                ->controller(BatimentsCampagneController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');

                });

            Route::prefix('regles-campagne')->name('regles-campagne.')
                ->controller(ReglesCampagnesController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');

                });

            Route::prefix('scoring-campagne')->name('scoring-campagne.')
                ->controller(ScoringCampagneController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->middleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('store');

                });

        });


    Route::prefix('utilisateur')->name('users.')->controller(usersController::class)->group(function () {
        Route::get('/liste',           'index')  ->name('index');
        Route::get('/create',          'create') ->name('create');
        Route::post('/store',          'store')  ->name('store');
        Route::get('/{id_user}',       'show')   ->name('show');
        Route::get('/edit/{id_user}',  'edit')   ->name('edit');
        Route::put('/update/{id_user}','update') ->name('update');
        Route::put('/status/{id_user}', 'status')->name('status');
        Route::put('/status/{id_user}', 'status')->name('status');
        Route::delete('/delete/{id_user}', 'destroy')->name('delete');
    });


//    Route::prefix('utilisateur')->name('users.')->controller(usersController::class)->group(function () {
//        Route::get('/liste', 'index')->name('index');
//        Route::get('/create', 'create')->name('create');
//        Route::get('/edit/{id_user}', 'edit')->name('edit');
//        Route::post('/store', 'store')->name('store');
//        Route::put('/update/{id_user}', 'update')->name('update');
//        Route::delete('/delete/{id_user}', 'destroy')->name('delete');
//    });


    Route::prefix('parametrage')->name('parametrage.')
        ->group(function () {

            Route::controller(ParametrageController::class)->group(function () {
                Route::get('/', 'index')->name('index');
            });


            Route::prefix('legal-documents')->name('legal-documents.')->controller(LegalDocumentController::class)->middleware('permission:parametrage.legal-documents.manage')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{document}/edit', 'edit')->name('edit');
                Route::put('/{document}', 'update')->name('update');
                Route::post('/{document}/publish', 'publish')->name('publish');
                Route::get('/{document}/preview', 'preview')->name('preview');
                Route::get('/{document}/pdf', 'pdf')->name('pdf');
                Route::get('/{document}/versions/{version}/pdf', 'versionPdf')->name('versions.pdf');
            });

            Route::prefix('api-paiement')->name('payment-providers.')->controller(\App\Http\Controllers\admin\parametrage\PaymentProviderController::class)->middleware('permission:parametrage.payment-providers.configure')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/{provider}', 'update')->name('update');
            });

            Route::prefix('roles-permissions')->name('roles-permissions.')->controller(RolePermissionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/roles', 'storeRole')->name('roles.store');
                Route::put('/roles/{role}', 'updateRole')->name('roles.update');
                Route::delete('/roles/{role}', 'destroyRole')->name('roles.destroy');
                Route::put('/utilisateurs/{user}/role', 'assignRole')->name('users.assign-role');
            });

            Route::prefix('modules')->name('modules.')->controller(ModuleController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{module}', 'update')->name('update');
                Route::patch('/{module}/toggle', 'toggle')->name('toggle');
                Route::delete('/{module}', 'destroy')->name('destroy');
            });

            Route::prefix('countrie')->name('countrie.')->controller(CountrieController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::prefix('region')->name('region.')->controller(RegionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::prefix('ville')->name('ville.')->controller(Villecontroller::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::prefix('niveau-etude')->name('niveau-etude.')->controller(NiveauEtudeController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });
            Route::prefix('departement')->name('departement.')->controller(DepartementController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::prefix('filiere')->name('filiere.')->controller(FiliereController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::prefix('type-utilisateur')->name('type-utilisateur.')->controller(TypeUtilisateurController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::put('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'delete')->name('delete');
            });

            Route::controller(configurationController::class)->name('configuration.')->prefix('configuration')->group(function () {
                Route::get('/{menus?}', 'index')->name('index');
                Route::put('/update/{menus?}', 'update')->name('update');

            });

        });


    Route::get('/etudiant/{etudiant}/contrat', [\App\Http\Controllers\admin\Etudiant\StudentContractController::class, '__invoke'])
        ->name('etudiant.contrat');

    Route::prefix('etudiant')
        ->name('etudiant.')
        ->controller(etudiantController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');

            Route::get('/nouvelle-demande', 'nouvelleDemande')->name('nouvelle_demande');
            Route::get('/admission-directe', 'admissionDirecte')->name('admission_directe');
            Route::post('/admission-directe', 'storeAdmissionDirecte')->name('admission_directe.store');
            Route::get('/admission-directe/modele', 'downloadAdmissionImportTemplate')->name('admission_directe.template');
            Route::post('/admission-directe/import', 'importAdmissionDirecte')->name('admission_directe.import');


        });


    Route::prefix('allocation-chambres')->name('allocation.')->group(function () {
        Route::post('/campagne/{campagneId}/pmr', [AllocationChambreController::class, 'confirmerPmr'])
            ->middleware(['admin', \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])->name('pmr');


        // Index
        Route::get('/', [AllocationChambreController::class, 'index'])->name('index');

        // Détails d'une campagne
        Route::get('/campagne/{campagneId}', [AllocationChambreController::class, 'show'])->name('show');

        // Formulaire d'allocation
        Route::get('/campagne/{campagneId}/allocate', [AllocationChambreController::class, 'allocateForm'])
            ->name('allocate-form');

        // Allouer une chambre (POST)
        Route::post('/campagne/{campagneId}/allocate', [AllocationChambreController::class, 'allocate'])
            ->name('allocate');

        // Allocation automatique (POST)
        Route::post('/campagne/{campagneId}/allocate-all', [AllocationChambreController::class, 'allocateAll'])
            ->name('allocate-all');

        // Confirmer une assignation (POST)
        Route::post('/campagne/{campagneId}/confirm', [AllocationChambreController::class, 'confirm'])
            ->name('confirm');

        // Annuler une assignation (POST)
        Route::post('/campagne/{campagneId}/cancel', [AllocationChambreController::class, 'cancel'])
            ->name('cancel');

        // Réaffecter (POST)
        Route::post('/campagne/{campagneId}/reassign', [AllocationChambreController::class, 'reassign'])
            ->name('reassign');

        // Retirer une chambre (POST)
        Route::post('/campagne/{campagneId}/remove', [AllocationChambreController::class, 'remove'])
            ->name('remove');

        // Statistiques
        Route::get('/campagne/{campagneId}/statistics', [AllocationChambreController::class, 'statistics'])
            ->name('statistics');


        // Export CSV
        Route::get('/campagne/{campagneId}/export-csv', [AllocationChambreController::class, 'exportCsv'])
            ->name('export-csv');
    });


    // Routes pour la carte interactive
    Route::controller(MapController::class)->group(function () {
        Route::get('map',  'index')->name('map');
        Route::get('map/api/campuses', 'getCampuses')->name('map.api.campuses');
        Route::get('map/api/cites',  'getCites')->name('map.api.cites');
        Route::get('map/api/batiments', 'getBatiments')->name('map.api.batiments');
        Route::get('map/api/stats', 'getStats')->name('map.api.stats');
        Route::get('map/api/nearby', 'searchNearby')->name('map.api.nearby');
        Route::get('map/api/export', 'exportData')->name('map.api.export');
    });

});

Route::post('/dossiers/{demande}/transition', [\App\Http\Controllers\DossierWorkflowController::class,'admin'])
    ->middleware(['admin', \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('dossiers.transition');

Route::middleware(['admin', \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])->group(function () {
    Route::get('/parametrage-entrees', [\App\Http\Controllers\HousingEntryController::class,'settings'])->name('housing-entry.settings');
    Route::post('/parametrage-entrees', [\App\Http\Controllers\HousingEntryController::class,'update'])->name('housing-entry.update');
    Route::post('/parametrage-entrees/reprendre-envois', [\App\Http\Controllers\HousingEntryController::class,'retry'])->name('housing-entry.retry');
});
