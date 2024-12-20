<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;

class EntrepriseGestController extends Controller
{


 /**
     * Création d'un compte Employeur Gestionnaire.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nom' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Création de l'utilisateur
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nom' => $request->nom,
                'role' => 'entreprise_gest',
                'statut' => 'inactif', // Par défaut, inactif
            ]);

            // Génération de l'OTP
            $otp = Otp::generateOtp($user->email);

            // Envoi du mail avec l'OTP
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Compte Employeur Gestionnaire créé. OTP envoyé.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du compte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer l'inscription avec l'OTP.
     */
    public function confirmOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Vérification de l'OTP
            $otpValid = Otp::validateOtp($request->email, $request->otp);
            if (!$otpValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 400);
            }

            // Activation du compte
            $user = User::where('email', $request->email)->first();
            $user->statut = 'actif';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Compte validé avec succès.',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la validation du compte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * Modifier le profil du gestionnaire (accessible par le gestionnaire lui-même).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'entreprise_gest') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $user->id_user . ',id_user',
            'password' => 'sometimes|min:8',
            'nom' => 'sometimes|string|max:255',
            'tel' => 'sometimes|string|max:20',
            'quartier' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->fill($request->except(['password', 'role', 'statut'])); // Empêche modification du rôle et du statut
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user,
        ], 200);
    }

    /**
     * Gestion des actions CRUD par les admin et superadmin.
     */
    public function crud(Request $request)
    {
        $authUser = $request->user();

        if (!in_array($authUser->role, ['admin', 'superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:read,update,delete,deactivate',
            'user_id' => 'required|exists:users,id_user',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $targetUser = User::findOrFail($request->user_id);

        if ($targetUser->role !== 'entreprise_gest') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seuls les gestionnaires peuvent être modifiés via cette action.',
            ], 403);
        }

        switch ($request->action) {
            case 'read':
                return response()->json([
                    'status' => 'success',
                    'user' => $targetUser,
                ], 200);

            case 'update':
                $validator = Validator::make($request->all(), [
                    'email' => 'sometimes|email|unique:users,email,' . $targetUser->id_user . ',id_user',
                    'nom' => 'sometimes|string|max:255',
                    'tel' => 'sometimes|string|max:20',
                    'quartier' => 'sometimes|string|max:255',
                    'ville' => 'sometimes|string|max:255',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
                }

                $targetUser->fill($request->only(['email', 'nom', 'tel', 'quartier', 'ville']));
                $targetUser->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Utilisateur mis à jour avec succès.',
                    'user' => $targetUser,
                ], 200);

            case 'delete':
                $targetUser->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Utilisateur supprimé avec succès.',
                ], 200);

            case 'deactivate':
                $targetUser->statut = 'inactif';
                $targetUser->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Utilisateur désactivé avec succès.',
                    'user' => $targetUser,
                ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Action inconnue.',
        ], 400);
    }
}
