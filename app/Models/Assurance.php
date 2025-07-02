<?php

// app/Models/Assurance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assurance extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_assurance';

    protected $fillable = [
        'id_user',
        'code_ifc',
        'libelle',
        'logo', // Nouveau champ
    ];

    public function entreprises()
    {
        return $this->hasMany(Entreprise::class, 'id_assurance');
    }

    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    public static function getEmployeAssurance($id_assurance):Builder{
        return User::whereHas('entreprise',function ($q) use($id_assurance) {
            return $q->where('id_assurance',$id_assurance)->where('role', Roles::Employe->value);
        });
    }


}
