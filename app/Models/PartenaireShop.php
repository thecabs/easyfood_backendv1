<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartenaireShop extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_partenaire';

    protected $fillable = [
        'id_user',   // L'utilisateur associé
        'nom',       // Nom du partenaire
        'adresse',   // Adresse complète
        'ville',     // Ville
        'quartier',  // Quartier
    ];

    // Relation : Un partenaire appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relation : Un partenaire a plusieurs produits
    public function produits()
    {
        return $this->hasMany(Produit::class, 'id_partenaire');
    }

    // Relation : Un partenaire a plusieurs stocks
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'id_shop');
    }

    // Relation : Un partenaire a plusieurs caissières
    public function caissieres()
    {
        return $this->hasMany(Caissiere::class, 'id_partenaire');
    }
}
