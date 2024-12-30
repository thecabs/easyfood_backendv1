<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Création d'un compte Administrateur.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'role' => 'required|in:administrateur', 
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Créez l'utilisateur avec statut forcé à 'actif'
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nom' => $request->nom,
            'role' => 'administrateur',
            'statut' => 'actif',  
        ]);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Administrateur créé avec succès.',
            'user' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'nom' => $user->nom,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 201);
    }
    



   
}
