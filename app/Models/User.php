<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'email', 'password', 'nom', 'tel', 'quartier', 
        'ville', 'role', 'statut', 'id_assurance', 
        'id_entreprise', 'id_shop', 'photo_profil','id_apporteur'
    ];

    protected $hidden = [
        'password',
    ];

    // Relation avec le rôle
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function apporteur(){
        return $this->belongsTo(User::class, 'id_apporteur', 'id_user');
    }

    // Relation avec l'assurance
    public function assurance()
    {
        return $this->belongsTo(Assurance::class, 'id_assurance', 'id_assurance');
    }
    
    // Relation avec l'entreprise (pour les employés)
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    // Relation avec le partenaire shop (pour les caissières)
    public function shop()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_shop');
    }

    public function employe()
    {
        return $this->hasOne(Employe::class, 'id_user'); // Relation avec un employé
    }
    
    public function compte()
    {
        return $this->hasOne(Compte::class, 'id_user', 'id_user');
    }
}
