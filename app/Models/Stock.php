<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_stock';

    protected $fillable = ['id_produit', 'quantite', 'id_shop'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'id_produit');
    }

    public function shop()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_shop');
    }
}
