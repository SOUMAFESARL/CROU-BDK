<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Colonnes et référentiels nécessaires au formulaire de modification d'un étudiant (`EtudiantEditionService`). */
trait EditionEtudiantTables
{
    protected function creerTablesEditionEtudiant(): void
    {
        $colonnes = [
            'string' => ['matricule', 'numero_etudiant', 'telephone1', 'telephone2', 'email1', 'email2', 'lieu_naissance', 'nationalite',
                'adresse', 'name_tuteur', 'telephone_tuteur', 'adresse_tuteur', 'numero_piece', 'annee_academique_actuel', 'type_bourse'],
            'date' => ['date_naissance', 'date_delivrance', 'date_expiration'],
            'integer' => ['groupe_sanguin_id', 'type_piece_id', 'regions_id', 'ville_id', 'distance'],
            'boolean' => ['boursier', 'allergies', 'maladies_chroniques', 'autiste'],
        ];
        Schema::table('etudiants', function (Blueprint $t) use ($colonnes) {
            foreach ($colonnes as $type => $noms) {
                foreach ($noms as $nom) {
                    if (! Schema::hasColumn('etudiants', $nom)) {
                        $colonne = $t->{$type}($nom)->nullable();
                        if ($type === 'boolean') {
                            $colonne->default(false);
                        }
                    }
                }
            }
        });
        Schema::table('demande', function (Blueprint $t) {
            foreach (['annee_academique_precedent', 'niveau_etude_precedent_code', 'filiere_code'] as $nom) {
                $t->string($nom)->nullable();
            }
            $t->decimal('moyenne', 10, 2)->default(0);
            $t->integer('credit_valider')->nullable();
            $t->boolean('boursier')->default(false);
        });

        foreach (['type_piece', 'groupe_sanguins', 'regions'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamps();
            });
        }
        Schema::create('villes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('region_id');
            $t->timestamps();
        });
        Schema::create('filiere', function (Blueprint $t) {
            $t->increments('id_filiere');
            $t->string('code_filiere')->nullable();
            $t->string('name');
            $t->timestamps();
        });

        DB::table('type_piece')->insert(['id' => 1, 'name' => 'CNI']);
        DB::table('groupe_sanguins')->insert(['id' => 1, 'name' => 'O+']);
        DB::table('regions')->insert([['id' => 1, 'name' => 'Gontougo'], ['id' => 2, 'name' => 'Abidjan']]);
        DB::table('villes')->insert([['id' => 10, 'name' => 'Bondoukou', 'region_id' => 1], ['id' => 20, 'name' => 'Cocody', 'region_id' => 2]]);
        DB::table('filiere')->insert(['id_filiere' => 1, 'name' => 'Informatique']);
        DB::table('niveau_etude')->insert([['id_niveau_etude' => 'L1', 'name' => 'Licence 1'], ['id_niveau_etude' => 'L2', 'name' => 'Licence 2']]);
    }
}
