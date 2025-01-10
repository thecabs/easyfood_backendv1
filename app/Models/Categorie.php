<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    // Champs assignables en masse
    protected $fillable = ['libelle', 'id_shop'];

    /**
     * Relation avec le modèle Produit
     * Une catégorie peut avoir plusieurs produits
     */
    public function produits()
    {
        return $this->hasMany(Produit::class, 'id_categorie');
    }

    /**
     * Relation avec le modèle PartenaireShop
      */
    public function shop()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_shop');
    }
}
