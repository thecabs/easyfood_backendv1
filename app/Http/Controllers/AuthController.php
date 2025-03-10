<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
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
            'email' => 'required|email|exists:users,email',
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

            // Check if password matches
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email ou mot de passe incorrect.',
                ], 401);
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

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie.',
                'token' => $token,
                'user' => [
                    'id' => $user->id_user,
                    'email' => $user->email,
                    'nom' => $user->nom,
                    'role' => $user->role, // Inclut les informations sur le rôle
                ],
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

}
