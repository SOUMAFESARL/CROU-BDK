<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cache')) {
            $columns = Schema::getColumnListing('cache');
            if ($columns === ['key']) {
                // The historical dump contains only nullable keys, not usable
                // cache entries. Rebuild this disposable cache, never business data.
                Schema::drop('cache');
            } elseif (array_diff(['key', 'value', 'expiration'], $columns) !== []) {
                throw new RuntimeException('Unexpected cache schema; inspect it before repairing.');
            }
        }

        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key', 191)->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key', 191)->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    public function down(): void
    {
        // Keep the working cache schema on rollback; the legacy shape was invalid.
    }
};
