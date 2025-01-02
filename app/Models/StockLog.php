<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = ['id_stock', 'id_user', 'action', 'details'];

    public function user()
{
    return $this->belongsTo(User::class, 'id_user', 'id_user');
}
}

