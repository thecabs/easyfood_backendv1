<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_facture';

    protected $fillable = ['date_facturation', 'montant', 'statut', 'id_vendeur', 'id_client'];

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'id_vendeur');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'id_client');
    }

    public function lignesFacture()
    {
        return $this->hasMany(LigneFacture::class, 'id_facture');
    }
}
