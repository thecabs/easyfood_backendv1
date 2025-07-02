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
        'logo',
        'id_assurance',
        'id_compte',
        'id_gestionnaire', // Clé étrangère vers users
    ];

    /**
     * Relation avec le gestionnaire (User)
     */
    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    /**
     * Relation avec l'assurance (Assurance)
     */
    public function assurance()
    {
        return $this->belongsTo(Assurance::class, 'id_assurance');
    }

    /**
     * Relation avec les employés (Employe)
     */
    public function employes()
    {
        return $this->hasMany(Employe::class, 'id_entreprise');
    }

    /**
     * Relation avec le compte bancaire (Compte)
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }

}
