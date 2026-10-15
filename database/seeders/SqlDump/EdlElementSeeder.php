<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EdlElementSeeder extends DumpTableSeeder
{
    protected const TABLE = 'edl_element';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_EDL_ELEMENT_0'
INSERT INTO `edl_element` (`edl_element_id`, `id_edl_piece`, `name_element`, `etat`, `observation`, `ordre`, `updated_by`, `created_by`, `updated_at`, `created_at`, `photo`) VALUES
('91a2faeb-c539-4a1d-b9e5-f4981df3feb5', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Équipements', 'mauvais', 'test', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('4c72b4f5-c9bc-4e2f-b0d3-234a719d72a3', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Électricité', 'bon', 'test', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('8e62eb87-f807-4c41-838f-af77f6cd5a96', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Porte', 'bon', 'test', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('c378912e-b238-479d-bee1-30d61dbe5589', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Fenêtres', 'bon', 'test', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('d9770f2c-1c71-40f5-bbca-40451ed1fa9f', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Plafond', 'bon', 'test', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('e3f265df-06c8-448d-8666-e2911d293f20', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Murs', 'bon', 'test', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('edece04a-9e09-466b-ac6d-d1b8643cbe0d', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Sol', 'bon', 'test', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('cec02a84-e57b-4894-9250-9e1638f1079b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Équipements', 'bon', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('e2a3997e-d82d-4a9b-94ee-ae07ace6a09b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Électricité', 'bon', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('bf75398b-e973-4a3d-a5af-a75b6750ee06', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Porte', 'bon', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('fb840411-9233-4307-8d02-bd0046bebac0', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Fenêtres', 'bon', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('c24a513f-b74f-4a40-8796-c1e5c7acc400', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Plafond', 'bon', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('770341c8-1fb3-4760-b642-7e4cfe004b4b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Murs', 'bon', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('d5644b0e-6df4-4b4c-9aa6-723849423025', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Sol', 'bon', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('377a72a6-01d7-4315-b9c0-852f054f46a8', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Équipements', 'moyen', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('39516bf3-dc64-4df6-b00a-8b63794cbc88', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Électricité', 'moyen', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('cd88fbd8-0ff8-4a75-b892-7a5dc8ec8fa5', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Porte', 'moyen', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('dc8a3daa-4ef1-4896-b8ac-c52744852c5f', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Fenêtres', 'moyen', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('18f3e086-e630-4510-b650-53fa0fbdae2e', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Plafond', 'moyen', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('20b13c3d-65be-432c-a08d-e7148bff2c8f', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Sol', 'moyen', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('03dc3ebc-9345-4378-9498-f75106d55fa0', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Murs', 'moyen', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('6fa186bb-8928-4f10-bb44-ff3128ece23c', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Murs', 'moyen', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('0bf7b023-1a1c-43b3-a077-ee64b7922457', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Plafond', 'moyen', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('58d2ddb8-bd3b-4ea1-b379-65a165cdfc51', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Fenêtres', 'moyen', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('df7d5d72-2014-46e0-8fb6-aabb3adf9bac', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Porte', 'moyen', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('5909bdbd-2cad-4d77-a77a-a3b01a8e8d73', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Électricité', 'moyen', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('397c03b0-73b7-4f9b-a3e9-ef3b58bc80c9', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Équipements', 'moyen', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('f73ba91e-9ec3-414f-8bb1-60278d1d4aea', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Sol', 'bon', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e06c8596-a7f4-451e-926a-a035c87b0bf0', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Murs', 'bon', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('30f36b25-3482-476e-98fb-892f2708a4d1', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Plafond', 'bon', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1951ae1c-b223-4a06-b259-0e0083a1e5bb', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Fenêtres', 'bon', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('bef0e8be-897a-4e65-ab98-1fc16275b7a3', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Porte', 'bon', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e8afeb22-c3b9-4f94-a7ea-2484d54baf14', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Électricité', 'bon', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('ff7d14d4-79c0-4590-9b15-e96710a522a7', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Équipements', 'bon', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1991c8fb-c026-4929-b857-80d8a46fe775', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Sol', 'mauvais', 'test', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('cb7a175e-bbdb-436e-8195-ac19b5a714de', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Murs', 'mauvais', 'test', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e1a47f17-6cfd-45b2-bea5-32e6a4325311', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Plafond', 'mauvais', 'test', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('619f2f20-8e02-49c8-bb2c-bd7741cf2f6b', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Fenêtres', 'mauvais', 'test', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e3566437-3f7a-4d86-97bc-b1486658e59d', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Porte', 'mauvais', 'test', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1b024066-48a7-48a7-831d-66cb2b9656af', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Électricité', 'mauvais', 'test', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('fdf6b570-54a3-48a4-b46d-4684d6b7f107', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Équipements', 'mauvais', 'test', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('9c75355e-e481-44a0-8932-b41ff17beec8', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Sol', 'moyen', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL);
SQL_DUMP_EDL_ELEMENT_0,
    ];
}
