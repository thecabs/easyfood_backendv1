<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\Otp;


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class EmployeController extends Controller
{
    const STATUT_INACTIF = 'inactif';
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_ACTIF = 'actif';

    /**
     * Création d'un compte Employé.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'id_entreprise' => 'required|exists:entreprises,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'nom' => $request->nom,
                'id_entreprise' => $request->id_entreprise,
                'role' => 'employe',
                'statut' => self::STATUT_INACTIF,
            ]);

            // Générer l'OTP
            $otp = Otp::generateOtp($user->email);

            // Envoyer l'OTP par mail
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Compte employé créé avec succès. Un OTP a été envoyé.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Validation de l'OTP pour l'employé.
     */
    public function validateOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
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
     * Activer le compte Employé.
     */
    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->statut !== self::STATUT_EN_ATTENTE) {
            return response()->json([
                'status' => 'error',
                'message' => 'L’utilisateur doit valider son OTP avant activation.',
            ], 400);
        }

        $user->statut = self::STATUT_ACTIF;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Le compte employé a été activé avec succès.',
            'user' => $user,
        ], 200);
    }
}
