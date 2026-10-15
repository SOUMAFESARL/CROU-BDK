<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\auth\AuthController;
//use App\Http\Controllers\web\contact\ContactController;
use App\Http\Controllers\web\frontedController;
use App\Http\Controllers\web\EtudiantPasswordResetController;
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

// Mot de passe oublié (étudiants)
Route::controller(EtudiantPasswordResetController::class)->group(function () {
    Route::get('/mot-de-passe-oublie', 'request')->name('etudiant.password.request');
    Route::post('/mot-de-passe-oublie', 'email')->middleware('throttle:6,1')->name('etudiant.password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', 'edit')->name('etudiant.password.reset');
    Route::post('/reinitialiser-mot-de-passe', 'update')->middleware('throttle:6,1')->name('etudiant.password.update');
});



