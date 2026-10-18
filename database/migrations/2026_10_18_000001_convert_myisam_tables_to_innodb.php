<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les tables issues du dump SQL de base sont en MyISAM : les transactions et les verrous
 * (DB::transaction, lockForUpdate) n'y ont aucun effet. Une erreur au milieu d'un enregistrement
 * (ex. import des étudiants déjà logés) laissait l'étudiant et son dossier créés sans affectation.
 * Conversion en InnoDB ; la migration peut être relancée : seules les tables encore en MyISAM sont traitées.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = DB::select("SELECT TABLE_NAME AS nom FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND ENGINE = 'MyISAM'");

        $echecs = [];
        foreach ($tables as $table) {
            try {
                DB::statement('ALTER TABLE `'.str_replace('`', '``', $table->nom).'` ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
            } catch (\Throwable $exception) {
                $echecs[] = $table->nom.' : '.$exception->getMessage();
            }
        }

        if ($echecs) {
            throw new RuntimeException("Tables non converties en InnoDB :\n".implode("\n", $echecs));
        }
    }

    public function down(): void
    {
        // Pas de retour en MyISAM : il ferait perdre les transactions.
    }
};
