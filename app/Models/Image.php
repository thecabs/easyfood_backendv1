<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_image';

    protected $fillable = ['url_photo', 'id_produit', 'id_utilisateur'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'id_produit');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }
}