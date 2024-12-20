<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class PartenaireShopGestController extends Controller
{
    /**
     * Création d'un compte Partenaire Shop Gestionnaire.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'id_partenaire_shop' => 'required|exists:partenaire_shops,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'nom' => $request->nom,
            'id_partenaire_shop' => $request->id_partenaire_shop,
            'role' => 'partenaire_shop_gest',
            'statut' => 'inactif',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Compte Partenaire Shop Gestionnaire créé avec succès.',
            'user' => $user,
        ], 201);
    }

    /**
     * Liste des partenaires shop gestionnaires.
     */
    public function list(Request $request)
    {
        $users = User::where('role', 'partenaire_shop_gest')->get();

        return response()->json([
            'status' => 'success',
            'users' => $users,
        ], 200);
    }
}
