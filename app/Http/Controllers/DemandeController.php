<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DemandeController extends Controller
{
    // ✅ Méthode pour créer une demande de fonds
    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['entreprise_gest', 'employe', 'superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_user' => 'required|exists:users,id_user',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
            'montant' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $demande = Demande::create([
            'id_user' => $request->id_user,
            'id_entreprise' => $request->id_entreprise,
            'montant' => $request->montant,
            'statut' => 'en attente'
        ]);

        return response()->json($demande, 201);
    }

    // ✅ Méthode pour lister les demandes pour le rôle 'shop_gest'
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['entreprise_gest','superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $demandes = Demande::where('id_entreprise', $currentUser->id_entreprise)->get();

        return response()->json([
            'status' => 'success',
            'data' => $demandes
        ], 200);
    }

    // ✅ Méthode pour valider une demande
    public function valider(Request $request, $id)
    {
        $currentUser = Auth::user();
        
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['entreprise_gest', 'superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $demande = Demande::where('id', $id)
                           ->where('id_entreprise', $currentUser->id_entreprise)
                           ->first();

        if (!$demande) {
            return response()->json([
                'status' => 'error',
                'message' => 'Demande non trouvée ou non autorisée.',
            ], 404);
        }

        $demande->statut = 'validé';
        $demande->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Demande validée avec succès.',
            'data' => $demande
        ], 200);
    }
}
