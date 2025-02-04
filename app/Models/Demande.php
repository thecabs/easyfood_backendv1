<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'id_entreprise',
        'montant',
        'statut'
    ];

    protected $casts = [
        'statut' => 'string'
    ];

    protected $table = 'demandes';
}