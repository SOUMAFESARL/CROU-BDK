<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExistingDatabaseMigrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
    }

    private function migrate(string $name): void
    {
        (require database_path('migrations/'.$name.'.php'))->up();
    }

    public function test_existing_table_is_preserved_and_other_tables_in_same_migration_are_created(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_name');
        });
        DB::table('users')->insert(['legacy_name' => 'Resident existant']);

        $this->migrate('0001_01_01_000000_create_users_table');
        $this->migrate('0001_01_01_000000_create_users_table');

        $this->assertSame('Resident existant', DB::table('users')->value('legacy_name'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertFalse(Schema::hasColumn('users', 'email'));
    }

    public static function tableMigrations(): array
    {
        return array_map(fn ($name) => [$name], [
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2026_08_10_000001_create_sanctions_tables',
            '2026_08_10_000003_create_disciplinary_reports_tables',
            '2026_08_10_000004_create_departure_settings_table',
            '2026_08_10_000005_create_departure_requests_and_settlements',
            '2026_08_18_000001_create_activity_logs_table',
            '2026_08_27_100000_create_student_api_tokens_table',
            '2026_09_20_000001_create_legal_documents_tables',
            '2026_09_23_000001_create_admission_derogations',
            '2026_09_23_000002_create_student_contract_approvals',
            '2026_09_23_000003_create_campaign_scorings',
            '2026_09_28_000002_create_equipement_affectations',
        ]);
    }

    #[DataProvider('tableMigrations')]
    public function test_table_migration_accepts_existing_tables(string $name): void
    {
        if ($name === '2026_09_23_000001_create_admission_derogations') {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
            $this->migrate('2026_08_11_000001_create_role_permissions_tables');
            $this->migrate('2026_08_11_000002_create_modules_table');
        }
        $this->migrate($name);
        $tables = Schema::getTableListing();
        $this->migrate($name);
        $this->assertSame($tables, Schema::getTableListing());
    }

    public function test_payment_migrations_preserve_credentials_and_permissions(): void
    {
        Schema::create('roles', fn (Blueprint $table) => $table->id());
        DB::table('roles')->insert(['id' => 1]);
        $names = [
            '2026_08_11_000001_create_role_permissions_tables',
            '2026_08_11_000002_create_modules_table',
            '2026_09_19_000001_create_payment_providers_table',
            '2026_09_19_000002_add_payment_provider_permission',
            '2026_09_19_000003_add_specific_payment_credentials',
            '2026_09_19_000004_create_online_payments_table',
            '2026_09_20_000002_add_legal_documents_permissions',
        ];
        foreach ($names as $name) {
            $this->migrate($name);
        }
        DB::table('payment_providers')->where('code', 'wave')->update(['api_key' => 'preserved', 'is_active' => true]);
        $counts = [];
        foreach (['modules', 'permissions', 'role_permission'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        Schema::table('payment_providers', fn (Blueprint $table) => $table->dropColumn('subscription_key'));
        Schema::drop('online_payments');
        foreach ($names as $name) {
            $this->migrate($name);
        }
        $this->assertTrue(Schema::hasColumn('payment_providers', 'subscription_key'));
        $this->assertTrue(Schema::hasTable('online_payments'));
        $this->assertSame('preserved', DB::table('payment_providers')->where('code', 'wave')->value('api_key'));
        $this->assertSame(6, DB::table('payment_providers')->count());
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }
    }

    public function test_partial_column_migration_adds_only_missing_columns(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();
        });
        DB::table('legal_documents')->insert(['file_path' => 'existing.pdf']);
        foreach (range(1, 2) as $attempt) {
            $this->migrate('2026_10_12_000001_add_uploaded_file_to_legal_documents');
            $this->migrate('2026_09_20_000003_add_legal_document_body_format');
        }
        $this->assertTrue(Schema::hasColumn('legal_documents', 'file_name'));
        $this->assertSame('existing.pdf', DB::table('legal_documents')->value('file_path'));
    }

    public function test_existing_dossier_state_is_preserved_when_transition_table_is_missing(): void
    {
        Schema::create('demande', function (Blueprint $table) {
            $table->id();
            $table->string('etat_dossier');
        });
        DB::table('demande')->insert(['etat_dossier' => 'attribue_definitif']);
        $this->migrate('2026_09_23_000006_create_dossier_workflow');
        $this->assertTrue(Schema::hasColumn('demande', 'assignation_id'));
        $this->assertTrue(Schema::hasTable('dossier_transitions'));
        $this->assertSame('attribue_definitif', DB::table('demande')->value('etat_dossier'));
        $this->assertSame(0, DB::table('dossier_transitions')->count());
    }

    public function test_existing_announcement_delivery_state_is_preserved(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->timestamp('diffusion_preparee_at')->nullable();
        });
        Schema::create('config_app', fn (Blueprint $table) => $table->id());
        DB::table('annonces')->insert(['diffusion_preparee_at' => '2026-01-01 12:00:00']);
        $this->migrate('2026_09_20_000004_create_annonce_deliveries');
        $this->migrate('2026_09_20_000004_create_annonce_deliveries');
        $this->assertTrue(Schema::hasTable('annonce_deliveries'));
        $this->assertTrue(Schema::hasColumn('config_app', 'sms_api_url'));
        $this->assertSame('2026-01-01 12:00:00', DB::table('annonces')->value('diffusion_preparee_at'));
    }

    public function test_migrator_records_existing_schema_once_and_continues_with_pending_migrations(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->id());
        $migrator = app('migrator');
        $migrator->getRepository()->createRepository();
        $paths = array_map(fn ($name) => database_path('migrations/'.$name.'.php'), [
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
        ]);
        $migrator->run($paths);
        $this->assertCount(2, $migrator->getRepository()->getRan());
        $this->assertSame([], $migrator->run($paths));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertSame(1, $migrator->getRepository()->getLastBatchNumber());
    }
}
