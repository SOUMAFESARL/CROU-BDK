<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lits', function (Blueprint $table) {
            $table->id();
            $table->string('chambre_id');
            $table->unsignedInteger('numero');
            $table->timestamps();
            $table->unique(['chambre_id', 'numero']);
        });
        Schema::table('assignation_chambres', function (Blueprint $table) {
            $table->foreignId('lit_id')->nullable()->constrained('lits')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assignation_chambres', fn (Blueprint $table) => $table->dropConstrainedForeignId('lit_id'));
        Schema::dropIfExists('lits');
    }
};
