<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public static function incomes($id_user):Builder{
        return self::query()->whereHas('compteDestinataire', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        });
    }

    public static function expenses($id_user):Builder{
        return self::query()->whereHas('compteEmetteur', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        });
    }

    public static function getAll($id_user):Builder{
        return self::query()->whereHas('compteEmetteur', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        })->orWhereHas('compteDestinataire', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        })->with([
            'compteEmetteur'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','role','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            },
            'compteDestinataire'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','role','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            }]);
    }
}
