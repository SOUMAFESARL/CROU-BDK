<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\auth\AuthController;
//use App\Http\Controllers\web\contact\ContactController;
use App\Http\Controllers\web\frontedController;
use App\Http\Controllers\web\EtudiantPasswordResetController;
use App\Http\Controllers\web\ReclamationPubliqueController;
use App\Http\Controllers\web\ResultatPublicController;
use App\Http\Controllers\MapController;
// Routes d'authentification admin
Route::get('/admin/login', [AuthController::class, 'index'])->name('login');
// « /admin » seul (ex. rafraîchissement après une coupure) renvoie vers la page de choix de connexion.
Route::redirect('/admin', '/');

// Routes publiques du site



Route::prefix('map')->name('map.')->group(function () {

    // Vue interactive
    Route::get('/', [MapController::class, 'index'])->name('index');

    // APIs pour récupérer les données
    Route::get('/api/campuses', [MapController::class, 'getCampuses'])->name('api.campuses');
    Route::get('/api/cites', [MapController::class, 'getCites'])->name('api.cites');
    Route::get('/api/batiments', [MapController::class, 'getBatiments'])->name('api.batiments');

    // Statistiques
    Route::get('/api/stats', [MapController::class, 'getStats'])->name('api.stats');

    // Recherche géographique
    Route::post('/api/search-nearby', [MapController::class, 'searchNearby'])->name('api.search-nearby');

    // Export des données
    Route::get('/api/export', [MapController::class, 'exportData'])->name('api.export');
});



Route::controller(frontedController::class)->group(function () {
    Route::get('/', 'index')->name('accueil');
    Route::get('/a-propos', 'about')->name('a-propos');
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contact_send')->name('contact.send');
    Route::get('/login', 'login')->name('etudiant.login');
    Route::get('/register', 'register')->name('etudiant.register');
    Route::post('/register', 'postRegister')->name('register.post');
    Route::post('/login-etudiant', 'authenticateEtudiant')->name('login.etudiant');

});

// Fiche de demande (PDF) proposée à la fin du formulaire public : lien signé temporaire (FicheDemandeService::lienPublic).
Route::get('/demande/{demande}/fiche', [\App\Http\Controllers\FicheDemandeController::class, 'publique'])
    ->middleware(['signed:relative', 'throttle:60,1'])->name('etudiant.demande.fiche');

// Réclamation sur une demande d'hébergement (pendant une période de réclamation ouverte)
Route::controller(ReclamationPubliqueController::class)->group(function () {
    Route::get('/reclamation', 'create')->name('etudiant.reclamation');
    Route::post('/reclamation', 'store')->middleware('throttle:10,1')->name('etudiant.reclamation.store');
});

// Résultats d'admission, après la publication des listes définitives (limite large : un campus partage une adresse IP).
Route::controller(ResultatPublicController::class)->group(function () {
    Route::get('/resultats', 'create')->name('etudiant.resultats');
    Route::post('/resultats', 'show')->middleware('throttle:60,1')->name('etudiant.resultats.show');
});

// Mot de passe oublié (étudiants)
Route::controller(EtudiantPasswordResetController::class)->group(function () {
    Route::get('/mot-de-passe-oublie', 'request')->name('etudiant.password.request');
    Route::post('/mot-de-passe-oublie', 'email')->middleware('throttle:6,1')->name('etudiant.password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', 'edit')->name('etudiant.password.reset');
    Route::post('/reinitialiser-mot-de-passe', 'update')->middleware('throttle:6,1')->name('etudiant.password.update');
});



