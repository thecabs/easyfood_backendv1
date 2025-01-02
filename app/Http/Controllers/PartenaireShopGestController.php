<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PartenaireShop;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class PartenaireShopGestController extends Controller
{
    /**
     * Afficher les détails d'un gestionnaire de shop partenaire.
     */
    public function showGest($id_user)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        try {
            $gestionnaire = User::where('id_user', $id_user)
                ->where('role', 'partenaire_shop_gest')
                ->with([
                    'partenaireShop' => function ($query) {
                        $query->select('id_partenaire', 'nom', 'adresse', 'id_gestionnaire');
                    },
                    'compte'
                ])
                ->first();

            if (!$gestionnaire) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gestionnaire introuvable ou non valide.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Gestionnaire trouvé avec succès.',
                'data' => [
                    'id_user' => $gestionnaire->id_user,
                    'nom' => $gestionnaire->nom,
                    'email' => $gestionnaire->email,
                    'tel' => $gestionnaire->tel,
                    'photo_profil' => $gestionnaire->photo_profil, // Ajout de photo_profil
                    'role' => $gestionnaire->role,
                    'statut' => $gestionnaire->statut,
                    'shop' => $gestionnaire->partenaireShop,
                    'compte' => $gestionnaire->compte ? [
                        'numero_compte' => $gestionnaire->compte->numero_compte,
                        'solde' => $gestionnaire->compte->solde,
                    ] : null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des informations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistre un gestionnaire pour un shop partenaire.
     */
    public function register(Request $request)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['administrateur', 'superadmin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'tel' => 'required|string|max:20',
            'id_partenaire' => 'required|exists:partenaire_shops,id_partenaire',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096', // Validation de l'image
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'id_partenaire.exists' => 'Shop partenaire introuvable.',
        ]);

        DB::beginTransaction();

        try {
            $shop = PartenaireShop::findOrFail($validated['id_partenaire']);
            if ($shop->id_gestionnaire) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce shop a déjà un gestionnaire.',
                ], 403);
            }

            $generatedPassword = Str::random(10);

            $userData = [
                'nom' => $validated['nom'],
                'email' => $validated['email'],
                'tel' => $validated['tel'],
                'password' => Hash::make($generatedPassword),
                'role' => 'partenaire_shop_gest',
                'statut' => 'actif',
            ];

            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userData['photo_profil'] = 'storage/' . $filePath;
            }

            $user = User::create($userData);

            $shop->id_gestionnaire = $user->id_user;
            $shop->save();

            $defaultPin = Compte::generateDefaultPin();
            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte($user),
                'solde' => 0,
                'date_creation' => now(),
                'id_user' => $user->id_user,
                'pin' => Hash::make($defaultPin),
            ]);

            Mail::to($user->email)->send(new \App\Mail\AccountCreatedMail($user, $generatedPassword, $compte, $defaultPin));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Gestionnaire créé avec succès. Les informations de connexion et du compte bancaire ont été envoyées.',
                'user' => [
                    'id_user' => $user->id_user,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'statut' => $user->statut,
                    'photo_profil' => $user->photo_profil, // Ajout de photo_profil
                ],
                'compte' => [
                    'numero_compte' => $compte->numero_compte,
                    'solde' => $compte->solde,
                ],
                'shop' => [
                    'id_partenaire' => $shop->id_partenaire,
                    'nom' => $shop->nom,
                    'adresse' => $shop->adresse,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la création.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'un gestionnaire.
     */
    public function updateProfile(Request $request, $id_user)
    {
        $currentUser = Auth::user();
    
        // Vérification des autorisations de l'utilisateur actuel
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
    
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
            'tel' => 'nullable|string|max:20',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096', // Validation de l'image
            'password' => 'nullable|string|min:8|confirmed', // Validation pour le mot de passe
        ]);
    
        DB::beginTransaction();
    
        try {
            $userToUpdate = User::findOrFail($id_user);
    
            // Empêcher la modification des profils superadmin par d'autres utilisateurs
            if ($userToUpdate->role === 'superadmin' && $currentUser->id_user !== $userToUpdate->id_user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas modifier le profil d\'un superadmin.',
                ], 403);
            }
    
            // Vérification spécifique pour le rôle partenaire_shop_gest
            if ($userToUpdate->role !== 'partenaire_shop_gest' && $userToUpdate->role !== 'superadmin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'L\'utilisateur spécifié n\'est pas un gestionnaire de shop partenaire.',
                ], 403);
            }
    
            // Mise à jour des informations autorisées
            if (isset($validated['nom'])) {
                $userToUpdate->nom = $validated['nom'];
            }
            if (isset($validated['email'])) {
                $userToUpdate->email = $validated['email'];
            }
            if (isset($validated['tel'])) {
                $userToUpdate->tel = $validated['tel'];
            }
            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userToUpdate->photo_profil = 'storage/' . $filePath;
            }
            if (isset($validated['password'])) {
                $userToUpdate->password = Hash::make($validated['password']);
            }
    
            $userToUpdate->save();
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'user' => [
                    'id_user' => $userToUpdate->id_user,
                    'nom' => $userToUpdate->nom,
                    'email' => $userToUpdate->email,
                    'tel' => $userToUpdate->tel,
                    'role' => $userToUpdate->role,
                    'statut' => $userToUpdate->statut,
                    'photo_profil' => $userToUpdate->photo_profil,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}
