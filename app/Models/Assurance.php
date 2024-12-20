<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assurance extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_assurance';

    protected $fillable = [
        'id_user',  // Référence à l'utilisateur
        'code_ifc',
        'libelle',  // Nom de l'assurance
    ];

    // Relation : Une assurance a plusieurs entreprises
    public function entreprises()
    {
        return $this->hasMany(Entreprise::class, 'id_assurance');
    }

    // Relation : Une assurance appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
