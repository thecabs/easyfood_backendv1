<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Compte;

use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivated;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            'password' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
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
            print($e);
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
    
    //  public function activate(Request $request)
    //  {
    //      $currentUser = Auth::user();
         
    //      if (!$this->hasPermission($currentUser)) {
    //          return response()->json([
    //              'status' => 'error',
    //              'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    //          ], 403);
    //      }
     
    //      $validator = Validator::make($request->all(), [
    //          'email' => 'required|email|exists:users,email',
    //      ]);
     
    //      if ($validator->fails()) {
    //          return response()->json([
    //              'status' => 'error',
    //              'errors' => $validator->errors(),
    //          ], 422);
    //      }
     
    //      DB::beginTransaction();
     
    //      try {
    //          $user = User::where('email', $request->email)->first();
     
    //          if ($user->statut !== self::STATUT_EN_ATTENTE) {
    //              return response()->json([
    //                  'status' => 'error',
    //                  'message' => 'L’utilisateur doit valider son OTP avant activation.',
    //              ], 400);
    //          }
     
    //          $existingCompte = Compte::where('id_user', $user->id_user)->first();
    //          if ($existingCompte) {
    //              return response()->json([
    //                  'status' => 'error',
    //                  'message' => 'Un compte bancaire existe déjà pour cet employé.',
    //              ], 400);
    //          }
     
    //          $user->statut = self::STATUT_ACTIF;
    //          $user->save();
     
    //          $compte = Compte::create([
    //             'numero_compte' => Compte::generateNumeroCompte($user), // Générer avant l'insertion
    //             'solde' => 0,
    //             'date_creation' => now(),
    //             'id_user' => $user->id_user,
    //         ]);
            
     
    //         // $compte->numero_compte = Compte::generateNumeroCompte($compte->user);
    //          $compte->save();
     
    //          // Envoi d'un email à l'utilisateur
    //          Mail::to($user->email)->send(new AccountActivated($user, $compte));
     
    //          DB::commit();
     
    //          return response()->json([
    //              'status' => 'success',
    //              'message' => 'Le compte employé a été activé avec succès et un compte bancaire a été créé.',
    //              'user' => $user,
    //              'compte' => $compte,
    //          ], 200);
     
    //      } catch (\Exception $e) {
    //          DB::rollBack();
    //          Log::error('Erreur lors de l\'activation de l\'utilisateur : ' . $e->getMessage());
    //         print($e);
    //          return response()->json([
    //              'status' => 'error',
    //              'message' => 'Une erreur est survenue lors de l\'activation ou de la création du compte.',
    //              'error' => $e->getMessage(),
    //          ], 500);
    //      }
    //  }
    public function activate(Request $request)
{
    $currentUser = Auth::user();

    if (!$this->hasPermission($currentUser)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    DB::beginTransaction();

    try {
        $user = User::where('email', $request->email)->first();

        if ($user->statut !== self::STATUT_EN_ATTENTE) {
            return response()->json([
                'status' => 'error',
                'message' => 'L’utilisateur doit valider son OTP avant activation.',
            ], 400);
        }

        $existingCompte = Compte::where('id_user', $user->id_user)->first();
        if ($existingCompte) {
            return response()->json([
                'status' => 'error',
                'message' => 'Un compte bancaire existe déjà pour cet employé.',
            ], 400);
        }

        $user->statut = self::STATUT_ACTIF;
        $user->save();

        // $compte = Compte::create([
        //     'numero_compte' => Compte::generateNumeroCompte($user),
        //     'solde' => 0,
        //     'date_creation' => now(),
        //     'id_user' => $user->id_user,
        // ]);
        $defaultPin = Compte::generateDefaultPin();

        $compte = Compte::create([
            'numero_compte' => Compte::generateNumeroCompte($user),
            'solde' => 0,
            'date_creation' => now(),
            'id_user' => $user->id_user,
            'pin' => Hash::make($defaultPin), // Fournir un PIN crypté
        ]);
        
        // $defaultPin = Compte::generateDefaultPin();
        // $compte->setPin($defaultPin);

        // Envoi d'un email avec le numéro de compte et le PIN
        Mail::to($user->email)->send(new AccountActivated($user, $compte, $defaultPin));

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Le compte employé a été activé avec succès et un compte bancaire a été créé.',
            'user' => $user,
            'compte' => $compte,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur lors de l\'activation de l\'utilisateur : ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue lors de l\'activation ou de la création du compte.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

     
     private function hasPermission($user)
     {
         return in_array($user->role, ['superadmin', 'entreprise_gest']);
     }
  
    /**
     * Liste des employés.
     * Accessible uniquement par les superadmins, administrateurs et entreprise_gest.
     */
    public function index()
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'administrateur', 'entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $employes = User::where('role', 'employe')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des employés récupérée avec succès.',
            'data' => $employes,
        ], 200);
    }

    /**
     * Afficher un employé spécifique.
     * Accessible uniquement par les superadmins, administrateurs et entreprise_gest.
     */
    public function show($id)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'administrateur', 'entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $employe = User::where('id_user', $id)->where('role', 'employe')->first();

        if (!$employe) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employé non trouvé.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Employé récupéré avec succès.',
            'data' => $employe,
        ], 200);
    }

    /**
     * Mettre à jour un employé.
     * Accessible uniquement par l'employé lui-même.
     */
    public function update(Request $request)
    {
        $currentUser = Auth::user();

        if ($currentUser->role !== 'employe') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à modifier ce compte.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $currentUser->id_user . ',id_user',
            'mot_de_passe' => 'sometimes|required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('password')) {
            $currentUser->mot_de_passe = Hash::make($request->mot_de_passe);
        }

        $currentUser->update($request->except('password'));

        return response()->json([
            'status' => 'success',
            'message' => 'Employé mis à jour avec succès.',
            'data' => $currentUser,
        ], 200);
    }


}
