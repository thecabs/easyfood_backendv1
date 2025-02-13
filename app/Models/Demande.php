<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;
    public function employe()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

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