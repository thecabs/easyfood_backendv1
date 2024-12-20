<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AssuranceGestController extends Controller
{
    /**
     * Création d'un compte Assurance Gestionnaire.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'id_assurance' => 'required|exists:assurances,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'nom' => $request->nom,
            'id_assurance' => $request->id_assurance,
            'role' => 'assurance_gest',
            'statut' => 'inactif',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Compte Assurance Gestionnaire créé avec succès.',
            'user' => $user,
        ], 201);
    }
}
