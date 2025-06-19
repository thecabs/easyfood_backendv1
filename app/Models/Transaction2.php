<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
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
}
