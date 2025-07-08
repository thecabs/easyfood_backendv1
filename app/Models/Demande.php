<?php

namespace App\Models;

use App\Models\User ;
use Illuminate\Database\Eloquent\Builder;
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

    public function transaction(){
        return $this->hasOne(Transaction::class,'id_demande','id_demande');
    }

    public static function getAll($id_user): Builder{
        return self::query()->where(function ($q) use($id_user){
            $q->where('id_emetteur', $id_user)->orWhere('id_destinataire', $id_user);
        })->with('destinataire.shop','destinataire.entreprise','emetteur.entreprise', 'images');

    }
}
