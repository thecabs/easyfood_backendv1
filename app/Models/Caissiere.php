<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caissiere extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_caissiere';

    protected $fillable = [
        'id_partenaire', // Référence au partenaire shop
        'id_user',            // Référence à l'utilisateur
    ];

    // Relation : Une caissière appartient à un partenaire shop
    public function partenaireShop()
    {
        return $this->belongsTo(PartenaireShop::class, 'id_partenaire');
    }

     
    // Relation : Une caissière est liée à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
