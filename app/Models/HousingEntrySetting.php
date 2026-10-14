<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
class HousingEntrySetting extends Model
{
    protected $guarded=[];
    protected $casts=['values'=>'array'];
    public static function current(): self
    {
        return static::firstOrCreate(['id'=>1],['values'=>['enabled'=>false,'started_at'=>now()->toDateTimeString()]]);
    }
    public static function options(): array
    {
        // Compatibilité avec les contrats historiques pendant le déploiement.
        return Schema::hasTable('housing_entry_settings') ? (static::find(1)?->values ?? []) : [];
    }
}
