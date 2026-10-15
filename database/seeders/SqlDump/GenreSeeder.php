<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class GenreSeeder extends DumpTableSeeder
{
    protected const TABLE = 'genre';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_GENRE_0'
INSERT INTO `genre` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Homme', '2025-12-29 21:41:27', '2025-12-29 21:41:27'),
(2, 'Femme', '2025-12-29 21:41:27', '2025-12-29 21:41:27');
SQL_DUMP_GENRE_0,
    ];
}
