<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('assignation_chambres', fn (Blueprint $t) => $t->string('statut', 30)->default('en_attente')->change());
    }
    public function down(): void {
        // Ne pas réintroduire un enum qui supprimerait les séjours terminés par expulsion.
    }
};
