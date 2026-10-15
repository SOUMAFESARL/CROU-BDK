<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (in_array('--initialize-empty', $argv, true)) {
    $marker = storage_path('app/database-initialization-incomplete');
    if (file_exists($marker)) {
        fwrite(STDERR, "A previous schema initialization failed. Inspect the database before removing storage/app/database-initialization-incomplete and retrying.\n");
        exit(1);
    }
    $connection = Illuminate\Support\Facades\DB::connection();
    if ($connection->getDriverName() !== 'mysql') {
        fwrite(STDERR, "Initial schema requires MySQL or MariaDB.\n");
        exit(1);
    }
    $tables = $connection->select('SHOW FULL TABLES');
    if ($tables === []) {
        $statements = json_decode(file_get_contents(dirname(__DIR__).'/database/schema/initial-mysql.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($statements as $statement) {
            if (! preg_match('/^(CREATE TABLE `|ALTER TABLE `|INSERT INTO `migrations`)/', $statement)) {
                throw new RuntimeException('Unexpected statement in initial schema.');
            }
        }
        if (file_put_contents($marker, date(DATE_ATOM)) === false) {
            throw new RuntimeException('Cannot create database initialization marker.');
        }
        foreach ($statements as $statement) {
            $connection->unprepared($statement);
        }
        unlink($marker);
        echo "Empty database initialized without historical business data.\n";
    }
}

// The historical schema is not fully described by this project's migrations.
$missingTables = [];
foreach (['config_app', 'etudiants', 'migrations'] as $table) {
    if (! Illuminate\Support\Facades\Schema::hasTable($table)) {
        $missingTables[] = $table;
    }
}
if ($missingTables !== []) {
    fwrite(STDERR, "Database is partially initialized or incompatible. Inspect the schema and migration history before retrying; existing tables are never reset.\n");
    fwrite(STDERR, 'Missing required tables: '.implode(', ', $missingTables)."\n");
    fwrite(STDERR, "Check DB_DATABASE in the server's existing .env (ENV_PRODUCTION does not replace it), and DB_URL if configured.\n");
    fwrite(STDERR, "If this is the intended database, inspect the historical schema import and migration history before making changes.\n");
    exit(1);
}
