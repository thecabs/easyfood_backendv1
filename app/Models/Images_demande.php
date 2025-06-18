<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Images_demande extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_image',
        'id_demande',
        'url',
    ];

    public function demande(){
        return $this->belongsTo(Demande::class,'id_demande');
    }
}
