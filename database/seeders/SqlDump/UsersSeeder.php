<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class UsersSeeder extends DumpTableSeeder
{
    protected const TABLE = 'users';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_USERS_0'
INSERT INTO `users` (`id_user`, `matricule`, `campus_id`, `code`, `name`, `genre_id`, `date_naissance`, `lieu_naissance`, `nationalite`, `cin`, `date_delivrance_cin`, `telephone_principal`, `telephone_secondaire`, `email`, `email_secondaire`, `adresse`, `ville_id`, `region_id`, `photo`, `type_utilisateur_id`, `role_id`, `campus_code`, `date_inscription`, `password`, `remember_token`, `statut`, `raison_suspension`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('d896afa9-bd56-4a22-8d1c-f25e1de5bdf8', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'Koffi Jephte', 1, NULL, NULL, 'IVOIRIENNE', '123456', NULL, '+2250545254655', NULL, 'jephte.koffi@soumafe.ci', NULL, 'Abidjan', 9, 1, NULL, 2, 1, NULL, '2026-06-25', '$2y$12$1gAky.FRbnDnEvmwyDaOruiuh78K4272DgJ5ZRtvKma8lZIcqR.CS', 'Ueq0wXtB4njD9tMQugtACNP1V1pc6aTnBNcSt4eIeePTMMeNaPWAS6zlUUvv', 'active', NULL, NULL, NULL, NULL, '2026-06-25 10:51:28', '2026-06-25 10:51:28', NULL),
('cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'Yapo Rodrigue', 1, NULL, NULL, 'Ivoirienne', NULL, NULL, '', NULL, 'rodrigue.yapo@soumafe.ci', NULL, NULL, 1, 1, NULL, 1, 1, NULL, '2026-06-17', '$2y$12$AVLFXIQ7HXGshGXhPBpl2.nPQVq34bq.Tg1ciFz4cRST01H.kuSk2', NULL, 'en_attente', NULL, NULL, NULL, NULL, '2026-06-17 14:06:53', '2026-06-17 14:08:09', NULL),
('a96eda67-0609-41e2-a074-3cf21042cec7', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'test', 1, NULL, NULL, 'Ivoirienne', NULL, NULL, '0707902564', NULL, 'yaporodrigue1109@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-12', '$2y$12$BCaIbCrSXjzfKB4MFqBbjeQtIro5qFruozO8L6Ho41qLg01VktVNe', 'pQuzgcagO5TjiFLARnUJUCjfxCJYlBX3t2GhtRpNrg3hcZTyPRPq2RI6Aogv', 'active', NULL, NULL, NULL, NULL, '2026-10-12 21:21:10', '2026-10-12 21:21:10', NULL);
SQL_DUMP_USERS_0,
    ];
}
