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
        'id_partenaire',
        'statut',
        'code_barre', // Nouveau champ
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'id_categorie');
    }

    public function partenaire()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_partenaire');
    }

    public function lignesFacture()
    {
        return $this->hasMany(LigneFacture::class, 'id_produit');
    }
}
