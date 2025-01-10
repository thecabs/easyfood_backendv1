<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_produit';

    protected $fillable = [
        'nom',
        'id_categorie',
        'prix_ifc',
        'prix_shop',
        'id_shop',
        'statut',
        'code_barre', // Nouveau champ
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'id_categorie');
    }

    public function partenaire()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_shop');
    }

    public function lignesFacture()
    {
        return $this->hasMany(LigneFacture::class, 'id_produit');
    }
    public function stock()
    {
        return $this->hasOne(Stock::class, 'id_produit');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'id_produit', 'id_produit');
    }
    // public function shop()
    // {
    //     return $this->belongsTo(PartenaireShop::class);
    // }

    public function shop()
    {
        return $this->hasOneThrough(
            PartenaireShop::class,
            Categorie::class,
            'id',         // Clé primaire de la table `categories`
            'id_shop',    // Clé étrangère dans `partenaire_shops`
            'id_categorie', // Clé étrangère dans `produits`
            'id_shop'     // Clé locale dans `categories`
        );
    }
    

    
}
