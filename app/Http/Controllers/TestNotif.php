<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestNotif extends Controller
{
    public function store(){
        $user = Auth::user();
        $user = User::where('id_user',$user->id_user)->first();
        $user->notify(new TestNotif($user));
    }
}
