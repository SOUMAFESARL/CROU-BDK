<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class TypePieceSeeder extends DumpTableSeeder
{
    protected const TABLE = 'type_piece';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_TYPE_PIECE_0'
INSERT INTO `type_piece` (`id`, `name`, `description`) VALUES
(1, 'CNI', 'Carte Nationale d’Identité'),
(2, 'PASSPORT', 'Passeport ordinaire'),
(3, 'PERMIS', 'Permis de conduire'),
(4, 'ATTESTATION', 'Attestation d’identité'),
(5, 'CARTE SCOLAIRE', 'Carte scolaire'),
(6, 'CARTE CONSULAIRE', 'Carte consulaire');
SQL_DUMP_TYPE_PIECE_0,
    ];
}
