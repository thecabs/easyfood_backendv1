<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categorie;
use Illuminate\Support\Facades\Auth;


class GetUserConnected extends Controller
{
    /**
     * Création d'un compte admin.
     */
    public function getUser(Request $request)
    {
        $currentUser = Auth::user();
    
      
        return response()->json([
            'status' => 'success',
            'message' => 'admin créé avec succès.',
            'user' => $currentUser,
        ], 201);
    }

    
    


}
