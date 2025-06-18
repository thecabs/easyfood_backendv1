<?php

namespace App\Models;

use App\Models\User ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_demande',
        'id_emetteur',
        'id_destinataire',
        'montant',
        'statut',
        'motif',
        'role'
    ];
    protected $primaryKey = 'id_demande';


    public function emetteur(){
        return $this->belongsTo(User::class, 'id_emetteur');
    }

    public function destinataire(){
        return $this->belongsTo(User::class, 'id_destinataire');
    }

    public function images(){
        return $this->hasMany(Images_demande::class,'id_demande','id_demande');
    }
}
