<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_role';

    protected $fillable = ['libelle'];

    /**
     * Relation avec les utilisateurs.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'id_role');
    }
}
