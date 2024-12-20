<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_ligne_commande';

    protected $fillable = ['id_commande', 'id_produit', 'quantite'];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'id_produit');
    }
}
