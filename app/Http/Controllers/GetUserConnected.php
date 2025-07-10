<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;


class GetUserConnected extends Controller
{
    use ApiResponseTrait;
    /**
     * Création d'un compte admin.
     */
    public function getUser(Request $request)
    {
        $currentUser = Auth::user();


        return response()->json([
            'status' => 'success',
            'message' => 'utilisateur récupéré.',
            'user' => $currentUser,
        ], 201);
    }





}
