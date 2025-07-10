<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\VerifRole;
use App\Models\Entreprise;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EntrepriseGestController extends Controller
{
    use ApiResponseTrait;
    /**
     * Afficher les détails d'un gestionnaire d'entreprise.
     */
    public function showGest($id_user)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'admin', 'assurance_gest', 'entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        try {
            $gestionnaire = User::where('id_user', $id_user)
                ->where('role', 'entreprise_gest')
                ->with(['entreprise' => function ($query) {
                    $query->select('id_entreprise', 'nom', 'secteur_activite', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'id_assurance', 'logo');
                }, 'compte'])
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
                    'ville' => $gestionnaire->ville,
                    'quartier' => $gestionnaire->quartier,
                    'photo_profil' => $gestionnaire->photo_profil,
                    'role' => $gestionnaire->role,
                    'statut' => $gestionnaire->statut,
                    'entreprise' => $gestionnaire->entreprise,
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
     * Enregistre un gestionnaire pour une entreprise.
     */
    public function register(Request $request)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['admin', 'superadmin', 'assurance_gest'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'tel' => 'required|string|max:20',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096', // Validation de l'image
        ]);

        DB::beginTransaction();

        try {
            // Récupérer l'entreprise
            $entreprise = Entreprise::findOrFail($validated['id_entreprise']);

            // Vérifier si un gestionnaire est déjà associé
            if ($entreprise->id_gestionnaire) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette entreprise a déjà un gestionnaire.',
                ], 403);
            }

            // Générer un mot de passe aléatoire
            $generatedPassword = Str::random(10);

            // Préparer les données de l'utilisateur
            $userData = [
                'nom' => $validated['nom'],
                'email' => $validated['email'],
                'tel' => $validated['tel'],
                'password' => Hash::make($generatedPassword),
                'role' => 'entreprise_gest',
                'statut' => 'actif',
                'id_entreprise' => $validated['id_entreprise'], // Lier l'entreprise à l'utilisateur
            ];
            // Mise à jour des informations autorisées
            if (isset($validated['ville'])) {
                $userData['ville'] = $validated['ville'];
            }
            if (isset($validated['quartier'])) {
                $userData['quartier'] = $validated['quartier'];
            }

            // Gérer l'upload de la photo de profil
            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userData['photo_profil'] = 'storage/' . $filePath;
            }

            // Créer l'utilisateur
            $user = User::create($userData);
            // Charger la relation entreprise pour renvoyer l'entreprise avec son gestionnaire associée
            $user->load('entreprise');

            // Associer l'utilisateur comme gestionnaire de l'entreprise
            $entreprise->id_gestionnaire = $user->id_user;
            $entreprise->save();


            // Créer un compte bancaire pour l'utilisateur
            $defaultPin = Compte::generateDefaultPin();
            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte($user),
                'solde' => 0,
                'date_creation' => now(),
                'id_user' => $user->id_user,
                'pin' => Hash::make($defaultPin),
            ]);

            // Envoyer un email au gestionnaire avec ses informations de connexion
            Mail::to($user->email)->send(new \App\Mail\AccountCreatedMail($user, $generatedPassword, $compte, $defaultPin));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Gestionnaire créé avec succès. Les informations de connexion et du compte bancaire ont été envoyées.',
                'data' => [
                    'id_user' => $user->id_user,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tel' => $user->tel,
                    'statut' => $user->statut,
                    'photo_profil' => $user->photo_profil,
                    'entreprise' => $user->entreprise
                ],
                'compte' => [
                    'numero_compte' => $compte->numero_compte,
                    'solde' => $compte->solde,
                ],
                'entreprise' => [
                    'id_entreprise' => $entreprise->id_entreprise,
                    'nom' => $entreprise->nom,
                    'secteur_activite' => $entreprise->secteur_activite,
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
        if (!in_array($currentUser->role, ['superadmin', 'entreprise_gest', 'admin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
            'tel' => 'nullable|string|max:20',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'old_password' => 'nullable|string|required_with:password|min:8', // Exige l'ancien mot de passe si un nouveau mot de passe est fourni
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'old_password.required_with' => 'L\'ancien mot de passe est requis pour modifier le mot de passe.',
        ]);

        DB::beginTransaction();
        try {
            $userToUpdate = User::findOrFail($id_user);

            // Empêcher la modification des profils des superadmins par d'autres utilisateurs
            if ($userToUpdate->role === 'superadmin' && $currentUser->id_user !== $userToUpdate->id_user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas modifier le profil d\'un superadmin.',
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
            if (isset($validated['ville'])) {
                $userToUpdate->ville = $validated['ville'];
            }
            if (isset($validated['quartier'])) {
                $userToUpdate->quartier = $validated['quartier'];
            }
            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userToUpdate->photo_profil = 'storage/' . $filePath;
            }
            if (isset($validated['password'])) {
                // Vérification de l'ancien mot de passe
                if (!Hash::check($validated['old_password'], $userToUpdate->password)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'L\'ancien mot de passe est incorrect.',
                    ], 400);
                }

                // Mise à jour du nouveau mot de passe
                $userToUpdate->password = Hash::make($validated['password']);
            }

            $userToUpdate->save();
            // Charger la relation entreprise pour renvoyer l'entreprise avec son gestionnaire associée
            $userToUpdate->load('entreprise');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'data' => [
                    'id_user' => $userToUpdate->id_user,
                    'nom' => $userToUpdate->nom,
                    'email' => $userToUpdate->email,
                    'ville' => $userToUpdate->ville,
                    'quartier' => $userToUpdate->quartier,
                    'tel' => $userToUpdate->tel,
                    'role' => $userToUpdate->role,
                    'statut' => $userToUpdate->statut,
                    'photo_profil' => $userToUpdate->photo_profil,
                    'entreprise' => $userToUpdate->entreprise,
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
    public function index()
    {
        $verifRole = new VerifRole();
        if($verifRole->isAdmin() OR $verifRole->isEntreprise() Or $verifRole->isEmploye()){
            return response()->json([
                'status' => 'success',
                'data' => User::select('id_user','nom','ville','quartier','tel','email','id_entreprise','photo_profil')->where('role', Roles::Entreprise->value)->with('entreprise:id_entreprise,nom,ville,quartier')->get(),
                'message' => 'gestionnaires entreprise récupérés avec succès.'
            ],200);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'vous ne pouvez pas éffectuer cette action.'
            ]);

        }
    }
}
