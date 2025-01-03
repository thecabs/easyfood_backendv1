<?php

// app/Models/Entreprise.php

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
        'id_user',
        'logo', // Nouveau champ pour le logo
    ];

    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    public function assurance()
    {
        return $this->belongsTo(Assurance::class, 'id_assurance');
    }

    public function employes()
    {
        return $this->hasMany(Employe::class, 'id_entreprise');
    }
}
