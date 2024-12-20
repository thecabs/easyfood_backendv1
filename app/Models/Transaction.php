<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'numero_transaction';

    protected $fillable = ['numero_compte', 'montant', 'date', 'type'];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'numero_compte');
    }
}
