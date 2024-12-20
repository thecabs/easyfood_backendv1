<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CaissiereController extends Controller
{
    /**
     * Création d'un compte Caissière Gestionnaire.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'id_partenaire_shop' => 'required|exists:partenaire_shops,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'nom' => $request->nom,
            'id_partenaire_shop' => $request->id_partenaire_shop,
            'role' => 'caissiere_gest',
            'statut' => 'inactif',
        ]);

        // Génération et envoi de l'OTP
        $otp = Otp::generateOtp($user->email);
        Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

        return response()->json([
            'status' => 'success',
            'message' => 'Compte Caissière Gestionnaire créé. OTP envoyé.',
            'user' => $user,
        ], 201);
    }
}
