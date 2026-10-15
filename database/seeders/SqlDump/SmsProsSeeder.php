<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class SmsProsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'sms_pros';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_SMS_PROS_0'
INSERT INTO `sms_pros` (`id`, `id_compagnie`, `sender_name`, `authorization_bearer`, `active`, `api_path`) VALUES
(1, 0, 'AGAT', 'Bearer 4|eicvRsC6T4BxKLHDeZ5Yfihk1bfeqrWyaoxlPDvg573ecd94 ', 1, 'https://vs.smsvisio.com/api/v3/sms/send');
SQL_DUMP_SMS_PROS_0,
    ];
}
