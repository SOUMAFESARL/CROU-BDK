<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('annonce_deliveries')) {
            Schema::create('annonce_deliveries', function (Blueprint $table) {
                $table->id();
                $table->string('annonce_id', 64);
                $table->string('etudiant_id', 64);
                $table->string('channel', 10);
                $table->string('destination');
                $table->string('subject');
                $table->longText('body');
                $table->string('status', 20)->default('pending')->index();
                $table->string('error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->unique(['annonce_id', 'etudiant_id', 'channel'], 'annonce_recipient_channel_unique');
            });
        }
        if (! Schema::hasColumn('annonces', 'diffusion_preparee_at')) {
            Schema::table('annonces', fn (Blueprint $table) => $table->timestamp('diffusion_preparee_at')->nullable());
            // Une installation ne renvoie pas les annonces déjà publiées.
            DB::table('annonces')->where('statut', 'publie')->update(['diffusion_preparee_at' => now()]);
            DB::table('annonces')->where('statut', 'publie')->whereNull('date_publication')
                ->update(['date_publication' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
        }
        if (! Schema::hasColumn('config_app', 'sms_api_url')) {
            Schema::table('config_app', fn (Blueprint $table) => $table->string('sms_api_url', 2048)->nullable());
        }
    }
    public function down(): void {
        Schema::dropIfExists('annonce_deliveries');
        Schema::table('annonces', fn (Blueprint $table) => $table->dropColumn('diffusion_preparee_at'));
        Schema::table('config_app', fn (Blueprint $table) => $table->dropColumn('sms_api_url'));
    }
};
