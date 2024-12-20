<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneFacture extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_ligne_facture';

    protected $fillable = ['id_facture', 'id_produit', 'quantite'];

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'id_facture');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'id_produit');
    }
}
