<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponseTrait;
    /**
     * Login API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    //     public function login(Request $request)
    // {
    //     // Valider les données d'entrée
    //     $validator = Validator::make($request->all(), [
    //         'email'    => 'required|email|exists:users,email',
    //         'password' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     try {
    //         // Récupérer l'utilisateur par email
    //         $user = User::where('email', $request->email)->first();

    //         // Vérifier si le mot de passe correspond
    //         if (!Hash::check($request->password, $user->password)) {
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'Email ou mot de passe incorrect.',
    //             ], 401);
    //         }

    //         // Vérifier le statut de l'utilisateur
    //         if ($user->statut !== UserController::STATUT_ACTIF) {
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'Votre compte n\'est pas actif.',
    //             ], 403);
    //         }

    //         // Charger la relation "role"
    //         $user->load('role');

    //         // Générer le token d'API (ici avec Laravel Sanctum)
    //         $token = $user->createToken('authToken')->plainTextToken;

    //         // Créer un cookie sécurisé pour stocker le token
    //         // Syntaxe : cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite)
    //         $cookie = cookie(
    //             'access_token', // Nom du cookie
    //             $token,         // Valeur (le token)
    //             60,             // Durée de vie en minutes
    //             '/',            // Chemin
    //             null,           // Domaine (null pour utiliser le domaine courant)
    //             true,           // Secure : true si vous utilisez HTTPS
    //             true,           // HttpOnly : inaccessible via JavaScript
    //             false,          // raw
    //             'Strict'        // SameSite : 'Strict' (ou 'Lax' selon vos besoins)
    //         );

    //         // Retourner la réponse JSON sans exposer le token
    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Connexion réussie.',
    //             'user'    => [
    //                 'id'    => $user->id_user,
    //                 'email' => $user->email,
    //                 'nom'   => $user->nom,
    //                 'role'  => $user->role,
    //             ],
    //         ], 200)->cookie($cookie);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la connexion : ' . $e->getMessage());
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Une erreur est survenue, veuillez réessayer plus tard.',
    //         ], 500);
    //     }
    // }

    // ancienne fonction

    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find user by email
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email ou mot de passe incorrect.',
                ], 422);
            }
            // Check if password matches
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email ou mot de passe incorrect.',
                ], 422);
            }

            // Check user status
            if ($user->statut !== UserController::STATUT_ACTIF) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Votre compte n\'est pas actif.',
                ], 403);
            }

            // Load the user's role
            $user->load('role'); // Assurez-vous que la relation "role" est correctement définie

            // Generate API token
            $token = $user->createToken('authToken')->plainTextToken;

            if ($user->role == 'shop_gest') {
                $user =  User::where('id_user', $user->id_user)
                    ->where('role', 'shop_gest')
                    ->with([
                        'shop' => function ($query) {
                            $query->select('id_shop', 'nom', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'logo');
                        },
                        'compte'
                    ])
                    ->first();
            }
            if ($user->role == 'entreprise_gest') {
                $user =  User::where('id_user', $user->id_user)
                    ->where('role', 'entreprise_gest')
                    ->with(['entreprise' => function ($query) {
                        $query->select('id_entreprise', 'nom', 'secteur_activite', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'id_assurance', 'logo');
                    }, 'compte'])
                    ->first();
            }
            if ($user->role == 'assurance_gest') {
                $user =  User::where('id_user', $user->id_user)
                    ->where('role', 'assurance_gest')
                    ->with(['assurance' => function ($query) {
                        $query->select('id_assurance', 'code_ifc', 'libelle', 'id_gestionnaire', 'logo');
                    }])
                    ->first();
            }
            if ($user->role == 'caissiere') {
                $user = User::where('role', 'caissiere')
                    ->with('shop')
                    ->find($user->id_user);
            }
            if ($user->role == 'employe') {
                $user = User::where('role', 'employe')
                    ->with('entreprise')
                    ->find($user->id_user);
            }
            if ($user->role == 'admin') {
                $user = User::where('role', 'admin')
                    ->with('shop')
                    ->find($user->id_user);
            }


            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie.',
                'token' => $token,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion : ' . $e->getMessage());
            print($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue, veuillez réessayer plus tard.',
            ], 500);
        }
    }
    // modifier le pin

    public function update_pin(Request $request, $id_user)
    {
        // Validate input
        $validator = $request->validate([
            'ancien_pin' => 'required|digits:4',
            'nouveau_pin' => 'required|digits:4',
            'confirmer_pin' => 'required|digits:4',
        ]);
        $nouveauPin =  $validator['nouveau_pin'];
        $ancienPin =  $validator['ancien_pin'];
        DB::beginTransaction();


        try {
            $user = User::find($id_user);
            // verification de l'existance de l'utilisateur
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'cet utilisateur n\'existe pas!',
                ], 403);
            }
            // recuperation du compte
            $compte = $user->compte;

            // verification de l'existance du compte
            if (!$compte) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ce compte n\'existe pas!',
                ], 403);
            }

            //verification de l'ancien pin
            if (!Hash::check($ancienPin, $compte->pin)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ancien pin incorrect.',
                ], 422);
            }

            $compte->pin = Hash::make($nouveauPin);

            $compte->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Modification du pin réussie.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Récupérer l'utilisateur actuellement authentifié
            $user = $request->user();

            if ($user) {
                // Révoquer tous les jetons de l'utilisateur
                $user->tokens()->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Déconnexion réussie.',
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion : ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue, veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Recuperer l'utilisateur connecté
     */
    public function getUser(Request $request)
    {
        $currentUser = Auth::user();
        if ($currentUser->role == 'shop_gest') {
            $currentUser =  User::where('id_user', $currentUser->id_user)
                ->where('role', 'shop_gest')
                ->with([
                    'shop' => function ($query) {
                        $query->select('id_shop', 'nom', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'logo');
                    },
                    'compte:id_compte,id_user,numero_compte,solde'
                ])
                ->first();
        }
        if ($currentUser->role == 'employe') {
            $currentUser =  User::where('id_user', $currentUser->id_user)
                ->where('role', 'employe')
                ->with([
                    'entreprise' => function ($query) {
                        $query->select('id_entreprise', 'nom', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'logo');
                    },
                    'compte'
                ])
                ->first();
        }
        if ($currentUser->role == 'entreprise_gest') {
            $currentUser =  User::where('id_user', $currentUser->id_user)
                ->where('role', 'entreprise_gest')
                ->with(['entreprise' => function ($query) {
                    $query->select('id_entreprise', 'nom', 'secteur_activite', 'adresse', 'ville', 'quartier', 'id_gestionnaire', 'id_assurance', 'logo');
                }, 'compte'])
                ->first();
        }
        if ($currentUser->role == 'assurance_gest') {
            $currentUser =  User::where('id_user', $currentUser->id_user)
                ->where('role', 'assurance_gest')
                ->with(['assurance' => function ($query) {
                    $query->select('id_assurance', 'code_ifc', 'libelle', 'id_gestionnaire', 'logo');
                }])
                ->first();
        }
        if ($currentUser->role == 'caissiere') {
            $currentUser = User::where('role', 'caissiere')
                ->with('shop')
                ->find($currentUser->id_user);
        }
        if ($currentUser->role == 'admin') {
            // $currentUser = User::where('role', 'admin')
            //     ->select('id_user', 'nom', 'email', 'tel', 'ville', 'quartier', 'role', 'photo_profil')
            //     ->find($currentUser->id_user);
        }
        if ($currentUser->role == 'employe') {
            $currentUser = User::where('role', 'employe')->with('entreprise')->find($currentUser->id_user);
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Utilisateur recupéré avec succès.',
                'user' => [
                    'id_user' => $currentUser->id_user,
                    'nom' => $currentUser->nom,
                    'email' => $currentUser->email,
                    'tel' => $currentUser->tel,
                    'quartier' => $currentUser->quartier,
                    'ville' => $currentUser->ville,
                    'role' => $currentUser->role,
                    'statut' => $currentUser->statut,
                    'photo_profil' => $currentUser->photo_profil,
                    'shop' => $currentUser->shop,
                    'assurance' => $currentUser->assurance,
                    'compte' => $currentUser->compte,
                    'entreprise' => $currentUser->entreprise,
                ]
            ],
            200
        );
    }
}
