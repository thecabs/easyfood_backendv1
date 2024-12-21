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
}
