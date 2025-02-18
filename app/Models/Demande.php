<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $table = 'demandes';

    protected $fillable = [
        'id_user',
        'id_entreprise',
        'montant',
        'statut',
        'type',    // Ajouté pour permettre l'assignation de masse
        'motif'    // Au cas où tu voudrais l'utiliser
    ];

    protected $casts = [
        'statut' => 'string'
    ];

    public function employe()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    
    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }
}
