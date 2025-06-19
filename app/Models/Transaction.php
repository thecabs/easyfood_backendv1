<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_compte_emetteur',
        'id_compte_destinataire',
        'id_demande',
        'montant',
        'type'
    ];

    public function compteEmetteur(){
        return $this->belongsTo(Compte::class,'id_compte_emetteur', 'id_compte');
    }

    public function compteDestinataire(){
        return $this->belongsTo(Compte::class, 'id_compte_destinataire', 'id_compte');
    }

    public function demande(){
        return $this->belongsTo(Demande::class, 'id_demande');
    }
}
