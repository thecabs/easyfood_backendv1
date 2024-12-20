<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_employe';

    protected $fillable = ['id_entreprise', 'id_user'];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
