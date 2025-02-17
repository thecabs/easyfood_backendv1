<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
 
class DemandeController extends Controller
{
    /* ============================================================
       DEMANDES DE FONDS
       - Création : Réalisée par les employés et les superadmin.
       - Statut initial : "en attente"
       - Statuts possibles ensuite : "validé" ou "refusé"
       - La validation/refus est réalisée par un utilisateur avec le rôle "entreprise_gest" ou "superadmin"
    ============================================================ */

    // 1. Création d'une demande de fonds
    public function storeFonds(Request $request)
    {
        $currentUser = Auth::user();
        // Seuls les employés et superadmin peuvent créer une demande de fonds
        if (!in_array($currentUser->role, ['employe', 'superadmin'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_user'       => 'required|exists:users,id_user',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
            'montant'       => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $demande = Demande::create([
            'id_user'       => $request->id_user,
            'id_entreprise' => $request->id_entreprise,
            'montant'       => $request->montant,
            'statut'        => 'en attente',
            'type'          => 'fonds',
            'motif'         => null,
        ]);

        return response()->json([
            'message' => 'La demande de fonds a été envoyée',
            'status'  => 'success',
            'data'    => $demande,
        ], 201);
    }

    // 2. Valider une demande de fonds (par entreprise_gest ou superadmin)
    public function validerFonds(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['entreprise_gest'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à statuer sur cette demande.',
            ], 403);
        }

        $demande = Demande::where('id', $id)
            ->where('type', 'fonds')
            ->where('id_entreprise', $currentUser->id_entreprise)
            ->first();

        if (!$demande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Demande non trouvée ou non autorisée.',
            ], 404);
        }

        $demande->statut = 'validé';
        $demande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Demande de fonds validée avec succès.',
            'data'    => $demande,
        ], 200);
    }

    // 3. Refuser une demande de fonds (par entreprise_gest ou superadmin) avec motif
    public function refuserFonds(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['entreprise_gest'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à statuer sur cette demande.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $demande = Demande::where('id', $id)
            ->where('type', 'fonds')
            ->where('id_entreprise', $currentUser->id_entreprise)
            ->first();

        if (!$demande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Demande non trouvée ou non autorisée.',
            ], 404);
        }

        $demande->statut = 'refusé';
        $demande->motif  = $request->motif;
        $demande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Demande de fonds refusée.',
            'data'    => $demande,
        ], 200);
    }

    /* ============================================================
       DEMANDES TRANSMIT
       - Création : Réalisée par entreprise_gest et superadmin.
       - Statut initial : "en attente"
       - Statuts possibles ensuite : "accordé" ou "refusé"
       - La validation (accord) ou le refus est réalisée par un utilisateur avec le rôle "admin"
    ============================================================ */

    // 4. Création d'une demande transmit
    public function storeTransmit(Request $request)
    {
        $currentUser = Auth::user();
    
        // Seuls les gestionnaires d'entreprise (entreprise_gest) et superadmin peuvent créer une demande transmit
        if (!in_array($currentUser->role, ['entreprise_gest', 'superadmin'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
    
        // Si l'utilisateur est gestionnaire, vérifier que l'id_entreprise de la demande correspond à son entreprise
        if ($currentUser->role === 'entreprise_gest' && $request->id_entreprise != $currentUser->id_entreprise) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous ne pouvez créer une demande que pour votre propre entreprise.',
            ], 403);
        }
    
        $validator = Validator::make($request->all(), [
            'id_user'       => 'required|exists:users,id_user',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
            'montant'       => 'required|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $demande = Demande::create([
            'id_user'       => $request->id_user,
            'id_entreprise' => $request->id_entreprise,
            'montant'       => $request->montant,
            'statut'        => 'en attente',
            'type'          => 'transmit',
            'motif'         => null,
        ]);
    
        return response()->json([
            'message' => 'La demande transmit a été envoyée',
            'status'  => 'success',
            'data'    => $demande,
        ], 201);
    }
    


    // 5. Accord (validation) d'une demande transmit (par admin)
    public function accorderTransmit(Request $request, $id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à statuer sur cette demande.',
            ], 403);
        }

        $demande = Demande::where('id', $id)
            ->where('type', 'transmit')
            ->first();

        if (!$demande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Demande non trouvée.',
            ], 404);
        }

        $demande->statut = 'accordé';
        $demande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Demande transmit accordée avec succès.',
            'data'    => $demande,
        ], 200);
    }

    // 6. Refuser une demande transmit (par admin) avec motif
    public function refuserTransmit(Request $request, $id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à statuer sur cette demande.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $demande = Demande::where('id', $id)
            ->where('type', 'transmit')
            ->first();

        if (!$demande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Demande non trouvée.',
            ], 404);
        }

        $demande->statut = 'refusé';
        $demande->motif  = $request->motif;
        $demande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Demande transmit refusée.',
            'data'    => $demande,
        ], 200);
    }

    /* ============================================================
       LISTER LES DEMANDES (possibilité de filtrer par type via un paramètre "type")
    ============================================================ */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        // Seuls les rôles entreprise_gest, superadmin et admin peuvent lister les demandes
        if (!in_array($currentUser->role, ['entreprise_gest', 'superadmin', 'admin'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $query = Demande::query();

        // Si on passe un paramètre "type" (fonds ou transmit), on filtre
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Pour les gestionnaires d'entreprise, on limite aux demandes de leur entreprise
        if ($currentUser->role == 'entreprise_gest') {
            $query->where('id_entreprise', $currentUser->id_entreprise);
        }

        // Charger les relations utiles (par exemple, entreprise, employe, gestionnaire)
        $demandes = $query->with([
            'entreprise' => function($query) {
                $query->select('id_entreprise', 'nom', 'adresse', 'ville', 'quartier');
            },
            'employe' => function($query) {
                $query->select('id_user', 'nom', 'tel', 'email')->where('role', 'employe');
            },
            'gestionnaire' => function($query) {
                $query->select('id_user', 'nom', 'tel', 'email')->where('role', 'entreprise_gest');
            }
        ])->get();

        return response()->json([
            'status' => 'success',
            'data'   => $demandes,
        ], 200);
    }
}
