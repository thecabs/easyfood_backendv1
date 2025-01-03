<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartenaireShop extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_partenaire';

    protected $fillable = [
        'id_user',
        'nom',
        'adresse',
        'ville',
        'quartier',
        'logo',  
    ];

    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    public function produits()
    {
        return $this->hasMany(Produit::class, 'id_partenaire');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'id_shop');
    }

    public function caissieres()
    {
        return $this->hasMany(Caissiere::class, 'id_partenaire');
    }
}
