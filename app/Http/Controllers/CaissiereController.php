<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caissiere;
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
    public function index()
    {
        $caissieres = Caissiere::with(['user', 'partenaire_shop_gest'])->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $caissieres,
        ], 200);
    }

    /**
     * Ajouter une nouvelle caissière
     */
    public function register(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'nom' => 'required|string|max:255',
            'id_partenaire_shop' => 'required|exists:partenaire_shops,id_partenaire',
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
                'id_partenaire_shop' => $request->id_partenaire_shop,
                'role' => 'caissiere',
                'statut' => 'actif',
            ]);

            // Création de la caissière liée au partenaire
            $caissiere = Caissiere::create([
                'id_user' => $user->id_user,
                'id_partenaire' => $request->id_partenaire_shop,
            ]);

            // Création d'un compte bancaire pour la caissière
            $defaultPin = Compte::generateDefaultPin();
            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte($user),
                'solde' => 0,
                'date_creation' => now(),
                'id_user' => $user->id_user,
                'pin' => Hash::make($defaultPin), // PIN crypté
            ]);

            // Envoi d'un email avec les informations de connexion et le compte bancaire
            Mail::to($user->email)->send(new \App\Mail\AccountCreatedCaissMail($user, $generatedPassword, $compte, $defaultPin));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'La caissière a été créée avec succès, un compte bancaire a été généré et un email contenant les informations a été envoyé.',
                'user' => [
                    'id_user' => $user->id_user,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'statut' => $user->statut,
                ],
                'caissiere' => [
                    'id_caissiere' => $caissiere->id,
                    'id_partenaire' => $caissiere->id_partenaire,
                ],
                'compte' => [
                    'numero_compte' => $compte->numero_compte,
                    'solde' => $compte->solde,
                ],
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
    public function show($id_caissiere)

    
    {
        $currentUser = Auth::user();
    
        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }   
        $caissiere = Caissiere::with(['user', 'partenaireShop'])->find($id_caissiere);

        if (!$caissiere) {
            return response()->json([
                'status' => 'error',
                'message' => 'Caissière introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $caissiere,
        ], 200);
    }

    /**
     * Mettre à jour une caissière
     */
    public function update(Request $request, $id_caissiere)
    {   
        $currentUser = Auth::user();
    
        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'caissiere'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $validated = $request->validate([
            'id_partenaire' => 'nullable|exists:partenaire_shops,id_partenaire',
            'id_user' => 'nullable|exists:users,id_user',
        ]);

        DB::beginTransaction();

        try {
            $caissiere = Caissiere::findOrFail($id_caissiere);

            // Mettre à jour uniquement les champs fournis
            if (isset($validated['id_partenaire'])) {
                $caissiere->id_partenaire = $validated['id_partenaire'];
            }

            if (isset($validated['id_user'])) {
                // Vérifier que l'utilisateur a le rôle adéquat
                $user = User::findOrFail($validated['id_user']);
                if ($user->role !== 'caissiere') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'L\'utilisateur spécifié n\'a pas le rôle de caissière.',
                    ], 403);
                }

                $caissiere->id_user = $validated['id_user'];
            }

            $caissiere->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Caissière mise à jour avec succès.',
                'data' => $caissiere,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Caissière introuvable.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

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
    public function destroy($id_caissiere)
    {
        $currentUser = Auth::user();
    
        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        try {
            $caissiere = Caissiere::findOrFail($id_caissiere);
            $caissiere->delete();

            return response()->json([
                'status' => 'success',
                'data' => $caissiere,
                'message' => 'Caissière supprimée avec succès.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Caissière introuvable.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
