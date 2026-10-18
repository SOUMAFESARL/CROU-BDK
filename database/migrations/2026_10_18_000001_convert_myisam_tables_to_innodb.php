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

        // Le mode strict (NO_ZERO_DATE) refuse de reconstruire une table dont une colonne a pour
        // défaut '0000-00-00 00:00:00' (ex. updated_at de edl_element, edl_image, maintenanciers) :
        // on assouplit le mode le temps de la migration et on remplace ces défauts par NULL.
        $sqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");

        $echecs = [];
        try {
            foreach ($tables as $table) {
                try {
                    $this->corrigerDatesZero($table->nom);
                    DB::statement('ALTER TABLE '.$this->nom($table->nom).' ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
                } catch (\Throwable $exception) {
                    $echecs[] = $table->nom.' : '.$exception->getMessage();
                }
            }
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$sqlMode]);
        }

        if ($echecs) {
            throw new RuntimeException("Tables non converties en InnoDB :\n".implode("\n", $echecs));
        }
    }

    /**
     * Rend NULL-ables les colonnes date dont le défaut est une date zéro, et remplace les dates zéro stockées par NULL.
     */
    private function corrigerDatesZero(string $table): void
    {
        $colonnes = DB::select("SELECT COLUMN_NAME AS nom, COLUMN_TYPE AS type, EXTRA AS extra
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
              AND DATA_TYPE IN ('timestamp', 'datetime', 'date')
              AND COLUMN_DEFAULT LIKE '0000-00-00%'", [$table]);

        foreach ($colonnes as $colonne) {
            $onUpdate = stripos($colonne->extra, 'on update') !== false ? ' ON UPDATE CURRENT_TIMESTAMP' : '';

            DB::statement('ALTER TABLE '.$this->nom($table).' MODIFY '.$this->nom($colonne->nom)
                .' '.$colonne->type.' NULL DEFAULT NULL'.$onUpdate);
            DB::statement('UPDATE '.$this->nom($table).' SET '.$this->nom($colonne->nom).' = NULL
                WHERE '.$this->nom($colonne->nom)." LIKE '0000-00-00%'");
        }
    }

    private function nom(string $identifiant): string
    {
        return '`'.str_replace('`', '``', $identifiant).'`';
    }

    public function down(): void
    {
        // Pas de retour en MyISAM : il ferait perdre les transactions.
    }
};
