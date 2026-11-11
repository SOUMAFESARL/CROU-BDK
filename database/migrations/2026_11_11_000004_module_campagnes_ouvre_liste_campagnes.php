<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le module « Campagnes et admissions » ouvre désormais la liste des campagnes
 * (onglets Campagnes puis Admissions), et non plus directement la liste des admissions.
 */
return new class extends Migration {
    private const SLUG = 'hebergement.admissions';

    private const ANCIENNE_ROUTE = 'admin.hebergement.campagnes_admission.admission';

    private const NOUVELLE_ROUTE = 'admin.hebergement.campagnes_admission.campagnes';

    public function up(): void
    {
        $this->changerRoute(self::ANCIENNE_ROUTE, self::NOUVELLE_ROUTE);
    }

    public function down(): void
    {
        $this->changerRoute(self::NOUVELLE_ROUTE, self::ANCIENNE_ROUTE);
    }

    /** Ne touche qu'une route encore à sa valeur d'origine (une route personnalisée par un admin est conservée). */
    private function changerRoute(string $de, string $vers): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')
            ->where('slug', self::SLUG)
            ->where('route_name', $de)
            ->update(['route_name' => $vers, 'updated_at' => now()]);
    }
};
