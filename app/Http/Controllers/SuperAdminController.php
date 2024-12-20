<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    /**
     * Création d'un compte SuperAdmin.
     */
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'nom' => 'required|string|max:255',
        'role' => 'required|in:superadmin', // Validation stricte du rôle
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Vérifiez si le rôle fourni correspond à "superadmin"
    if ($request->role !== 'superadmin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Le rôle fourni est invalide pour cette action.',
        ], 403); // Code 403 pour accès interdit
    }

    $user = User::create([
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'nom' => $request->nom,
        'role' => $request->role,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'SuperAdmin créé avec succès.',
        'user' => [
            'id_user' => $user->id_user,
            'email' => $user->email,
            'nom' => $user->nom,
            'role' => $user->role,
        ],
    ], 201);
}

}
