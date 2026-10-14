<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalSignatory extends Model
{
    public const ROLES = [
        'sous_dal' => 'Sous-DAL',
        'chef_logement' => 'Chef de service logement',
        'directeur_cite' => 'Directeur de cité',
        'concierge_principal' => 'Concierge principal',
    ];

    protected $guarded = ['id'];
    protected $hidden = ['signature_path'];
    protected $casts = ['is_active' => 'boolean'];
}
