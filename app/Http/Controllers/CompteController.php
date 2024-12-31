<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Compte;
use App\Models\User;
use App\Mail\AccountActivated;

class CompteController extends Controller
{
    /**
     * Activer un compte pour un utilisateur.
     */
   

    /**
     * Vérifier si l'utilisateur actuel a les permissions nécessaires.
     */
   
    /**
     * Récupérer les détails d'un compte.
     */
    public function getCompteDetails(Request $request, $numeroCompte)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['superadmin', 'entreprise_gest','employe','administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $compte = Compte::where('numero_compte', $numeroCompte)->first();

        if (!$compte) {
            return response()->json([
                'status' => 'error',
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'compte' => $compte,
        ], 200);
    }

    /**
     * Mettre à jour le PIN d'un compte.
     */
    public function updatePin(Request $request, $numeroCompte)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['superadmin', 'entreprise_gest','employe','administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        $request->validate([
            'old_pin' => 'required',
            'new_pin' => 'required|min:4|max:4',
        ]);

        $compte = Compte::where('numero_compte', $numeroCompte)->first();

        if (!$compte) {
            return response()->json([
                'status' => 'error',
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        if (!Hash::check($request->old_pin, $compte->pin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN actuel invalide.',
            ], 403);
        }

        $compte->setPin($request->new_pin);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN mis à jour avec succès.',
        ], 200);
    }
}
