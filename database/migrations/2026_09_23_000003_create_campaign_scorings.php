<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('campaign_scorings',function(Blueprint $t){
            $t->id(); $t->string('campagne_id')->index();
            $t->json('weights'); $t->json('points'); $t->decimal('minimum_score',5,2)->default(0);
            $t->boolean('pmr_first')->default(false); $t->boolean('active')->default(false);
            $t->string('created_by'); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('campaign_scorings'); }
};
