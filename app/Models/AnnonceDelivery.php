<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AnnonceDelivery extends Model
{
    protected $guarded = [];
    protected $hidden = ['destination', 'body'];
    protected $casts = ['sent_at' => 'datetime'];
}
