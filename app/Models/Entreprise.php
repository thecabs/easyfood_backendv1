<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_entreprise';

    protected $fillable = [
        'nom',
        'secteur_activite',
        'ville',
        'quartier',
        'adresse',
        'id_assurance',
        'id_user', // Clé étrangère vers l'utilisateur gestionnaire
    ];

    // Relation : Une entreprise appartient à un utilisateur gestionnaire
    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relation avec l'assurance
    public function assurance()
    {
        return $this->belongsTo(Assurance::class, 'id_assurance');
    }

    // Relation avec les employés
    public function employes()
    {
        return $this->hasMany(Employe::class, 'id_entreprise');
    }
}
