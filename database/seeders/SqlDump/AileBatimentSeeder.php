<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class AileBatimentSeeder extends DumpTableSeeder
{
    protected const TABLE = 'aile_batiment';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_AILE_BATIMENT_0'
INSERT INTO `aile_batiment` (`id_aile_batiment`, `code_aile`, `batiment_id`, `numero`, `name`, `nombre_niveaux`, `total_chambres`, `ordre_display`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(22, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 1, 'Aile Principale', 5, 50, 1, NULL, NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:13');
SQL_DUMP_AILE_BATIMENT_0,
    ];
}
