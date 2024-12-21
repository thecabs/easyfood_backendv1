<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Assurance;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

 class AssuranceGestController extends Controller
{
    public function register(Request $request)
{
     // Vérifier si le rôle est admin ou superadmin
     $user = Auth::user();
     if (!in_array($user->role, ['administrateur', 'superadmin'])) {
        return response()->json(['message' => 'Accès non autorisé.'], 403);
    }
    // Valider les données
    $validated = $request->validate([
        'nom' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'tel' => 'required|string|max:20',
        'password' => 'required|string|min:8',
        'id_assurance' => 'required|exists:assurances,id_assurance', // L'assurance doit exister
    ]);

    DB::beginTransaction(); // Démarrer une transaction

    try {
        // Vérifier que l'assurance existe
        $assurance = Assurance::find($validated['id_assurance']);
        if (!$assurance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Assurance introuvable.',
            ], 404);
        }

        // Vérifier si l'assurance n'a pas déjà un gestionnaire
        if ($assurance->id_gestionnaire) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cette assurance a déjà un gestionnaire associé.',
            ], 403);
        }

        // Créer l'utilisateur avec le rôle assurance_gest
        $user = User::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'tel' => $validated['tel'],
            'password' => Hash::make($validated['password']),
            'role' => 'assurance_gest',
            'statut' => 'inactif', // Statut inactif à la création
        ]);

        // Associer l'utilisateur en tant que gestionnaire de l'assurance
        $assurance->id_gestionnaire = $user->id_user;
        $assurance->save();

        // Générer l'OTP
        $otp = Otp::generateOtp($user->email);

        // Envoyer l'OTP par email
        Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

        DB::commit(); // Valider la transaction

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès. OTP envoyé pour vérification.',
            'user' => [
                'id_user' => $user->id_user,
                'nom' => $user->nom,
                'email' => $user->email,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
            'assurance' => [
                'id_assurance' => $assurance->id_assurance,
                'code_ifc' => $assurance->code_ifc,
                'libelle' => $assurance->libelle,
            ],
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack(); // Annuler la transaction en cas d'erreur

        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la création de l\'utilisateur ou de l\'association.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function confirmOtp(Request $request)

{

    // Vérifier si le rôle est admin ou superadmin
    $user = Auth::user();
    if (!in_array($user->role, ['administrateur', 'superadmin'])) {
       return response()->json(['message' => 'Accès non autorisé.'], 403);
   }
    // Valider l'entrée
    $validated = $request->validate([
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|numeric',
    ]);

    DB::beginTransaction(); // Démarrer une transaction

    try {
        // Vérifier si l'OTP est valide
        $otpValid = Otp::verifyOtp($validated['email'], $validated['otp']);
        if (!$otpValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Code OTP invalide ou expiré.',
            ], 400);
        }

        // Récupérer l'utilisateur
        $user = User::where('email', $validated['email'])->first();

        if ($user->statut === 'actif') {
            return response()->json([
                'status' => 'error',
                'message' => 'Le compte est déjà actif.',
            ], 400);
        }

        // Activer le compte
        $user->statut = 'actif';
        $user->save();

        DB::commit(); // Valider la transaction

        return response()->json([
            'status' => 'success',
            'message' => 'Compte activé avec succès.',
            'user' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack(); // Annuler la transaction en cas d'erreur

        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la validation de l\'OTP.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
