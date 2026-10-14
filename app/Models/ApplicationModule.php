<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationModule extends Model
{
    protected $table = 'modules';
    protected $guarded = [];
    protected $casts = ['actions'=>'array','is_active'=>'boolean','show_in_menu'=>'boolean','is_system'=>'boolean'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name'); }
    public function activeDescendants() { return $this->children()->where('is_active', true)->with(['permissions'=>fn($q)=>$q->orderBy('action'),'activeDescendants']); }
    public function permissions() { return $this->hasMany(Permission::class, 'module_id'); }
}
