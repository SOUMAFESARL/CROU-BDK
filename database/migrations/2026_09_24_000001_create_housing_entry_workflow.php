<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL DDL is not transactional: allow recovery after a partial run.
        if (!Schema::hasTable('housing_entry_settings')) {
            Schema::create('housing_entry_settings', function (Blueprint $t) {
                $t->id(); $t->json('values'); $t->timestamps();
            });
        }
        if (!Schema::hasTable('housing_entry_messages')) {
            Schema::create('housing_entry_messages', function (Blueprint $t) {
                $t->id(); $t->string('event_key', 190)->unique(); $t->string('student_id', 150)->index();
                $t->string('assignment_id', 150)->nullable()->index(); $t->string('kind', 30);
                $t->string('subject'); $t->text('body'); $t->json('details')->nullable();
                $t->string('status', 30)->default('pending')->index(); $t->timestamp('sent_at')->nullable();
                $t->timestamp('read_at')->nullable(); $t->string('error')->nullable(); $t->timestamps();
            });
        }
        if (!Schema::hasTable('housing_entry_appointments')) {
            Schema::create('housing_entry_appointments', function (Blueprint $t) {
                $t->id(); $t->string('assignment_id', 150)->unique(); $t->string('student_id', 150)->index();
                // DATETIME avoids legacy MySQL implicit TIMESTAMP defaults.
                $t->dateTime('starts_at')->index(); $t->dateTime('ends_at'); $t->string('location');
                $t->string('status', 30)->default('scheduled'); $t->timestamps();
            });
        }
        if (!Schema::hasTable('housing_campaign_announcements')) {
            Schema::create('housing_campaign_announcements', function (Blueprint $t) {
                $t->string('campaign_id', 150)->primary(); $t->string('announcement_id', 150); $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_campaign_announcements');
        Schema::dropIfExists('housing_entry_appointments');
        Schema::dropIfExists('housing_entry_messages');
        Schema::dropIfExists('housing_entry_settings');
    }
};
