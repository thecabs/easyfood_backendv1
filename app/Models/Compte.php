<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    use HasFactory;

    protected $primaryKey = 'numero_compte';

    protected $fillable = ['solde', 'date_creation', 'id_user'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'numero_compte');
    }

    public static function generateNumeroCompte($user)
{
    // Assurez-vous que l'utilisateur est sauvegardé et a un ID auto-incrémenté
    $user->save();

    // Récupérer l'ID de l'utilisateur après l'insertion
    $idUser = $user->id;

    // Générer le numéro de compte avec l'ID utilisateur et la date actuelle
    $date = now()->format('Ym');  // Exemple: 202412 pour décembre 2024
    $numeroCompte = 'CPT-' . $date . '-' . str_pad($idUser, 6, '0', STR_PAD_LEFT);

    return $numeroCompte;
}

    
}
