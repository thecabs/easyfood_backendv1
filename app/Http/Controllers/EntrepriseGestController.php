<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class EntrepriseGestController extends Controller
{
    /**
     * Enregistre un gestionnaire pour une entreprise.
     */
    public function register(Request $request)
    {
        // Vérification des rôles
        $user = Auth::user();
        if (!in_array($user->role, ['administrateur', 'superadmin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'tel' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'id_entreprise.exists' => 'Entreprise introuvable.',
        ]);

        DB::beginTransaction();

        try {
            // Vérifier si l'entreprise existe
            $entreprise = Entreprise::find($validated['id_entreprise']);
            if ($entreprise->id_gestionnaire) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette entreprise a déjà un gestionnaire.',
                ], 403);
            }

            // Créer le gestionnaire
            $user = User::create([
                'nom' => $validated['nom'],
                'email' => $validated['email'],
                'tel' => $validated['tel'],
                'password' => Hash::make($validated['password']),
                'role' => 'entreprise_gest',
                'statut' => 'inactif', // Par défaut inactif
            ]);

            // Associer le gestionnaire à l'entreprise
            $entreprise->id_gestionnaire = $user->id_user;
            $entreprise->save();

            // Générer et envoyer l'OTP
            $otp = Otp::generateOtp($user->email);
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Gestionnaire créé avec succès. OTP envoyé.',
                'user' => [
                    'id_user' => $user->id_user,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'statut' => $user->statut,
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
            ], 500);
        }
    }

    /**
     * Confirme l'OTP pour activer le gestionnaire.
     */
    public function confirmOtp(Request $request)
    {
        // Vérification des rôles
        $user = Auth::user();
        if (!in_array($user->role, ['administrateur', 'superadmin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // Validation des données
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ], [
            'email.exists' => 'Aucun utilisateur trouvé avec cet email.',
        ]);

        DB::beginTransaction();

        try {
            // Vérifier si l'OTP est valide
            $otpValid = Otp::verifyOtp($validated['email'], $validated['otp']);
            if (!$otpValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP invalide ou expiré.',
                ], 400);
            }

            // Récupérer l'utilisateur
            $user = User::where('email', $validated['email'])->first();
            if ($user->statut === 'actif') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le compte est déjà activé.',
                ], 400);
            }

            // Activer le compte
            $user->statut = 'actif';
            $user->save();

            DB::commit();

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
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la validation OTP.',
            ], 500);
        }
    }
}
