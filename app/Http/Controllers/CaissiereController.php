<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CaissiereController extends Controller
{
    /**
     * Lister toutes les caissières
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user(); // Obtenir l'utilisateur authentifié
    
        // Vérification du rôle de l'utilisateur
        if ($currentUser->role === 'superadmin') {
            $caissieres = User::where('role', 'caissiere')->with(['partenaireShop'])->get();
        } elseif ($currentUser->role === 'shop_gest') {
            $caissieres = User::where('role', 'caissiere')->where('id_shop', $currentUser->id_shop)->with(['partenaireShop'])->get();
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
    
        // Supprimer les champs `id_entreprise` et `id_assurance` avant de retourner la réponse
        $caissieres = $caissieres->map(function ($caissiere) {
            unset($caissiere->id_entreprise, $caissiere->id_assurance);
            return $caissiere;
        });
    
        // Pagination manuelle
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $paginated = $caissieres->slice(($currentPage - 1) * $perPage, $perPage)->values();
    
        return response()->json([
            'status' => 'success',
            'data' => $paginated,
            'pagination' => [
                'total' => $caissieres->count(),
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($caissieres->count() / $perPage),
            ],
        ], 200);
    }
    
    

    /**
     * Ajouter une nouvelle caissière
     */
    public function register(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'nom' => 'required|string|max:255',
            'id_shop' => 'required|exists:partenaire_shops,id_shop',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Générer un mot de passe aléatoire
            $generatedPassword = Str::random(10);

            // Création de l'utilisateur (caissière)
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($generatedPassword),
                'nom' => $request->nom,
                'id_shop' => $request->id_shop,
                'role' => 'caissiere',
                'statut' => 'actif',
            ]);

            // Création d'un compte bancaire pour la caissière
            $defaultPin = Compte::generateDefaultPin();
            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte($user),
                'solde' => 0,
                'date_creation' => now(),
                'id_user' => $user->id_user,
                'pin' => Hash::make($defaultPin),
            ]);

            // Envoi d'un email avec les informations de connexion et le compte bancaire
            Mail::to($user->email)->send(new \App\Mail\AccountCreatedCaissMail($user, $generatedPassword, $compte, $defaultPin));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'La caissière a été créée avec succès, un compte bancaire a été généré et un email contenant les informations a été envoyé.',
                'user' => $user,
                'compte' => $compte,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement de la caissière : ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la création de la caissière.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une caissière spécifique
     */
    public function show($id_user)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $user = User::where('role', 'caissiere')->with('shop_gest')->find($id_user);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Caissière introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ], 200);
    }

    /**
     * Mettre à jour une caissière
     */
    public function update(Request $request, $id_user)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $validator = $request->validate([
            'id_shop' => 'nullable|exists:partenaire_shops,id_shop',
            'email' => 'nullable|email|unique:users,email,' . $id_user,
            'nom' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::where('role', 'caissiere')->findOrFail($id_user);
            $user->update($validator);
            return response()->json([
                'status' => 'success',
                'message' => 'Caissière mise à jour avec succès.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une caissière
     */
    public function destroy($id_user)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        try {
            $user = User::where('role', 'caissiere')->findOrFail($id_user);
            $user->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Caissière supprimée avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
