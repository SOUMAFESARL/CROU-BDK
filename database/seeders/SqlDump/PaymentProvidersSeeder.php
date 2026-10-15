<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class PaymentProvidersSeeder extends DumpTableSeeder
{
    protected const TABLE = 'payment_providers';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_PAYMENT_PROVIDERS_0'
INSERT INTO `payment_providers` (`id`, `code`, `name`, `api_url`, `api_key`, `merchant_key`, `is_active`, `created_at`, `updated_at`, `api_user`, `subscription_key`, `request_signing_secret`, `environment`, `webhook_secret`) VALUES
(1, 'moov', 'Moov', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(2, 'orange', 'Orange', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(3, 'mtn', 'MTN', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(4, 'wave', 'Wave', 'https://api.wave.com', 'eyJpdiI6InR3cTNZdEFmQlFOQXBVWUkrVFY1SUE9PSIsInZhbHVlIjoiZzFZY0F2UGFuaUk4Y3poZ3Q1dTAvdz09IiwibWFjIjoiNjhjNmMwYTkxYmM4NzcxYjdjZGNlZDdkZDE2YzExZDgyMjlmMzM0NjE1YWZjYWNiMmExMDVhMmY2ZWQzMWQwNiIsInRhZyI6IiJ9', NULL, 1, '2026-09-19 20:13:50', '2026-09-25 15:18:38', NULL, NULL, 'eyJpdiI6IkNKMTQ4V0hoNDNVeFlaNzl6SnB4YWc9PSIsInZhbHVlIjoiWWVPbmVGb1paSm0vcXQyRXdpRi95VHlNWkZLS3MyYVowODViUHV1WGkxWT0iLCJtYWMiOiJjMjRiYjhlZjVkNmIwMjVkZmM1NmFjNzZmMTMzZjAzYjg3ZmU5YjUwZWE1ZTM4M2Q0Y2I0Yzg3ZGIwNjA1MTIwIiwidGFnIjoiIn0=', 'sandbox', 'eyJpdiI6InYyTXo4MlNvc3JWWHcwVWJMRTdNK0E9PSIsInZhbHVlIjoiN1U1K3ZSaFJQRGRQOHkvQmQ0SnQ3Zz09IiwibWFjIjoiNDAxZmIxYTg2OGRlOTIwZjMyZWE0ZmE1NTYxZmE1YzRjNDU2MDhkNDk1YWFhMWQ2MmZhOTI0ZGVhNTQyYjkxNSIsInRhZyI6IiJ9'),
(5, 'cinetpay', 'CinetPay', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(6, 'tresorpay', 'TrésorPay', NULL, NULL, NULL, 0, '2026-09-19 20:13:51', '2026-09-19 20:13:51', NULL, NULL, NULL, 'sandbox', NULL);
SQL_DUMP_PAYMENT_PROVIDERS_0,
    ];
}
