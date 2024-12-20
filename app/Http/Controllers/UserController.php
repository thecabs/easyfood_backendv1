<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    const STATUT_INACTIF = 'inactif';
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_ACTIF = 'actif';

    /**
     * Reusable validation rules.
     */
    private function userValidationRules(array $additionalRules = []): array
    {
        return array_merge([
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|min:8',
            'nom' => 'required|string|max:255',
        ], $additionalRules);
    }

    /**
     * Création d'un compte utilisateur.
     */
    public function createAccount(Request $request)
    {
        $validator = Validator::make($request->all(), $this->userValidationRules([
            'tel' => 'nullable|string|max:20',
            'quartier' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'id_role' => 'required|exists:roles,id_role',
            'id_assurance' => 'nullable|exists:assurances,id_assurance',
            'id_entreprise' => 'nullable|exists:entreprises,id_entreprise',
            'id_partenaire_shop' => 'nullable|exists:partenaire_shops,id_partenaire',
        ]));

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'nom' => $request->nom,
                'tel' => $request->tel,
                'quartier' => $request->quartier,
                'ville' => $request->ville,
                'id_role' => $request->id_role,
                'id_assurance' => $request->id_assurance,
                'id_entreprise' => $request->id_entreprise,
                'id_partenaire_shop' => $request->id_partenaire_shop,
                'statut' => self::STATUT_INACTIF,
            ]);

            $otp = Otp::generateOtp($user->email);
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Compte créé avec succès. Un OTP a été envoyé à votre email.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du compte utilisateur : ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue, veuillez réessayer plus tard.',
            ], 500);
        }
    }

    /**
     * Validate OTP.
     */
    public function validateOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP invalide ou expiré.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->statut = self::STATUT_EN_ATTENTE;
        $user->save();

        $otpRecord->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'OTP validé avec succès. Le compte est maintenant en attente d\'activation.',
        ], 200);
    }

    /**
     * Activate account by email or ID.
     */
    public function activateAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:id_user|email|exists:users,email',
            'id_user' => 'required_without:email|exists:users,id_user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->email ? User::where('email', $request->email)->first() : User::find($request->id_user);

        if ($user->statut === self::STATUT_ACTIF) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le compte est déjà actif.',
            ], 400);
        }

        $user->statut = self::STATUT_ACTIF;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Le compte a été activé avec succès.',
        ], 200);
    }
}
