<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('edl_element') && !Schema::hasColumn('edl_element', 'photo')) {
            $this->alter(fn (Blueprint $table) => $table->text('photo')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('edl_element', 'photo')) {
            $this->alter(fn (Blueprint $table) => $table->dropColumn('photo'));
        }
    }

    private function alter(\Closure $change): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('edl_element', $change);
            return;
        }
        // Older imported tables have zero-date timestamp defaults. Allow their
        // existing definition during this DDL only; restore the session afterward.
        $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $ddlMode = implode(',', array_diff(explode(',', $mode), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']));
        try {
            DB::statement('SET SESSION sql_mode = ?', [$ddlMode]);
            Schema::table('edl_element', $change);
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$mode]);
        }
    }
};