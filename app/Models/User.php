<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = "users";
    protected $primaryKey = 'id_user';
    public $incrementing = false; // 🔹 Important pour UUID
    protected $keyType = 'string'; // 🔹 L’UUID est une chaîne de caractères

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Génère automatiquement un UUID lors de la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->campus_id)) {
                $model->campus_id = getInfoAdmin()->users->campus_id ?? null;
            }
        });
    }

    // 🔹 Relations
    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id','id_campus');
    }

    public function ville()
    {
        return $this->belongsTo(Ville::class, 'ville_id');
    }

    public function type_utilisateur()
    {
        return $this->belongsTo(TypeUtilisateur::class, 'type_utilisateur_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ((int) $this->role_id === 1) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        $this->role->loadMissing('permissions.applicationModule');

        return $this->role->permissions->contains(fn ($item) =>
            $item->slug === $permission && (bool) $item->applicationModule?->is_active
        );
    }

    public function campusCode()
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }
}
