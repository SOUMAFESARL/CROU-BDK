<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class ConfigAppSeeder extends DumpTableSeeder
{
    protected const TABLE = 'config_app';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_CONFIG_APP_0'
INSERT INTO `config_app` (`config_id`, `app_name`, `locale`, `timezone`, `date_format`, `maintenance_mode`, `public_registration`, `activity_log`, `force_dark_mode`, `company_name`, `country`, `region_id`, `ville_id`, `phone`, `email`, `website`, `logo`, `brand_color`, `two_factor_auth`, `two_factor_method`, `session_lifetime`, `allow_multiple_sessions`, `password_min_length`, `password_require_uppercase`, `password_require_number`, `password_require_special`, `password_expiry_days`, `max_login_attempts`, `lockout_duration`, `ip_restriction`, `allowed_ips`, `force_https`, `tva_enabled`, `tva_rate`, `tva_number`, `price_display`, `tva_on_services`, `digital_tax_rate`, `service_fee`, `tax_on_delivery`, `google_maps_enabled`, `google_maps_key`, `ga_tracking_id`, `fb_pixel_id`, `livechat_enabled`, `livechat_provider`, `livechat_token`, `social_google`, `social_facebook`, `social_github`, `methods`, `online_payment_enabled`, `payment_gateway`, `api_key_public`, `api_key_secret`, `sandbox_mode`, `currency`, `auto_receipt`, `min_amount`, `three_d_secure`, `anti_fraud`, `daily_limit`, `email_enabled`, `mail_driver`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_from_name`, `mail_from_address`, `sms_enabled`, `sms_provider`, `sms_api_key`, `sms_sender`, `notif_new_user`, `notif_new_order`, `notif_payment_ok`, `notif_payment_fail`, `notif_low_stock`, `notif_report`, `base_currency`, `currency_symbol`, `symbol_position`, `thousands_separator`, `secondary_currencies`, `auto_rate`, `rate_frequency`, `created_by`, `updated_by`, `created_at`, `updated_at`, `sms_api_url`, `address`) VALUES
('ed3acb1a-d6f6-45c0-979a-b6bbbc08cd05', 'Crou de Daloa', 'fr', 'Africa/Abidjan', 'd/m/Y', 0, 0, 1, 0, 'Centre Régional des Œuvres Universitaires de Daloa', 'Côte d\'Ivoire', '16', '62', '+2250707902963', 'rodrigue.yapo@soumafe.ci', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/logos/6ace0590aac7b3.65231798.jpeg', '#000000', 0, 'totp', 120, 1, 6, 1, 1, 0, 0, 5, 15, 0, NULL, 0, 1, 18.00, NULL, 'TTC', 1, 0.00, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, 0, 0, 0, '[\"cash\",\"mobile\"]', 1, 'orange', NULL, NULL, 1, 'XOF', 1, 500, 0, 0, 500000, 1, 'smtp', NULL, 587, NULL, NULL, 'Crou_connect', NULL, 1, 'orange_sms_api', NULL, 'MonApp', 1, 1, 1, 1, 1, 1, 'XOF', 'FCFA', 'after', NULL, '[\"NGN\",\"GBP\"]', 0, 'daily', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-29 12:58:24', '2026-10-13 10:40:25', NULL, 'Bondoukou');
SQL_DUMP_CONFIG_APP_0,
    ];
}
