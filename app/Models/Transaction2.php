<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction2 extends Model
{
    use HasFactory;

    protected $table = 'transactions'; // S'assurer que le nom de la table est correct

    protected $primaryKey = 'id'; // Correspondance avec la migration

    protected $fillable = [
        'numero_compte_src',
        'numero_compte_dest',
        'montant',
        'date',
        'type',
    ];

    /**
     * Relation avec le compte source.
     */
    public function compteSource()
    {
        return $this->belongsTo(Compte::class, 'numero_compte_src', 'numero_compte');
    }

    /**
     * Relation avec le compte destination.
     */
    public function compteDestination()
    {
        return $this->belongsTo(Compte::class, 'numero_compte_dest', 'numero_compte');
    }

    public function incomes(Builder $query, $id_user):Builder{
        return $query()->whereHas('compteDestination', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        });
    }

    public function expenses(Builder $query, $id_user):Builder{
        return $query()->whereHas('compteSource', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        });
    }

    public static function getAll($id_user):Builder{
        return self::query()->whereHas('compteSource', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        })->whereHas('compteDestination', function($q) use ($id_user){
            return $q->where('id_user',$id_user);
        })->with([
            'compteEmetteur'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            },
            'compteDestinataire'=>function($query){
                $query->select('id_compte','id_user','numero_compte','solde','created_at','updated_at')->with(['user'=>function($query){
                    $query->select('id_user','photo_profil','nom','email','tel','ville','quartier','id_shop','id_entreprise')->with(['entreprise:nom,id_entreprise,ville,quartier','shop:nom,id_shop,ville,quartier']);
                }]);
            }]);
    }
}
