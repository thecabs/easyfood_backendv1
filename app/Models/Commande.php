<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_commande';

    protected $fillable = ['date_commande', 'statut', 'montant', 'id_client', 'telephone'];

    public function client()
    {
        return $this->belongsTo(User::class, 'id_client');
    }

    public function lignesCommande()
    {
        return $this->hasMany(LigneCommande::class, 'id_commande');
    }
}
