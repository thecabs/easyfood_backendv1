<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartenaireShop extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_shop';

    protected $fillable = [
        'id_user',
        'id_gestionnaire',
        'id_compte', // Champ ajouté pour la relation avec le compte bancaire
        'nom',
        'adresse',
        'ville',
        'quartier',
        'logo',
    ];

    /**
     * Relation avec le gestionnaire (User)
     */
    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    /**
     * Relation avec les produits (Produit)
     */
    public function produits()
    {
        return $this->hasMany(Produit::class, 'id_shop');
    }

    /**
     * Relation avec les stocks (Stock)
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'id_shop');
    }

    /**
     * Relation avec les caissières (User)
     */
    public function caissieres()
    {
        return $this->hasMany(User::class, 'id_shop');
    }

    /**
     * Relation avec les catégories (Categorie)
     */
    public function categories()
    {
        return $this->hasMany(Categorie::class, 'id_shop');
    }

    /**
     * Relation avec le compte bancaire (Compte)
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }
}
