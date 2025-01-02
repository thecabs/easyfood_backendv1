<?php

// app/Models/Assurance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
