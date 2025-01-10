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
    const STATUT_TEMPORAIRE = 'temporaire';

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
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
    
        DB::beginTransaction();
    
        try {
            $userData = [
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nom' => $request->nom,
                'id_entreprise' => $request->id_entreprise,
                'role' => 'employe',
            ];
            
    
            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userData['photo_profil'] = 'storage/' . $filePath;
            }
    
            $user = User::create($userData);
    
            // Générer l'OTP avec une expiration
            $otp = Otp::create([
                'email' => $user->email,
                'otp' => random_int(100000, 999999),
                'expires_at' => now()->addMinutes(10),
            ]);
    
            // Envoyer l'OTP par email
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp->otp));
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Compte employé créé avec succès. Un OTP a été envoyé.',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Méthode pour nettoyer les utilisateurs avec OTP expiré
    public function cleanExpiredUsers()
    {
        DB::transaction(function () {
            $expiredOtps = Otp::where('expires_at', '<', now())->pluck('email');
            
            User::whereIn('email', $expiredOtps)->delete();
    
            Otp::whereIn('email', $expiredOtps)->delete();
        });
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
     * Activer un compte employé.
     */
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

            $defaultPin = Compte::generateDefaultPin();

            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte($user),
                'solde' => 0,
                'date_creation' => now(),
                'id_user' => $user->id_user,
                'pin' => Hash::make($defaultPin),
            ]);

            Mail::to($user->email)->send(new AccountActivated($user, $compte, $defaultPin));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Le compte employé a été activé avec succès et un compte bancaire a été créé.',
                'data' => ['user' => $user, 'compte' => $compte]
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

    /**
     * Liste des employés.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'admin', 'entreprise_gest', 'assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        // Récupérer les employés avec leurs entreprises
        $employes = User::where('role', 'employe')
            ->with('entreprise:id_entreprise,id_assurance,nom,secteur_activite,ville,quartier')
            ->get();

        // Pagination manuelle
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $paginated = $employes->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Construire la réponse paginée
        return response()->json([
            'status' => 'success',
            'message' => 'Liste des employés récupérée avec succès.',
            'data' => $paginated,
            'pagination' => [
                'total' => $employes->count(),
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($employes->count() / $perPage),
            ],
        ], 200);
    }


    /**
     * Afficher un employé spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'admin', 'entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $employe = User::where('id_user', $id)
            ->where('role', 'employe')
            ->with('entreprise:id_entreprise,id_assurance,nom,secteur_activite,ville,quartier')
            ->first();

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
     */
    public function update(Request $request)
    {
        $currentUser = Auth::user();
    
        // Vérification des autorisations
        if ($currentUser->role !== 'employe') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à modifier ce compte.',
            ], 403);
        }
    
        // Validation des entrées
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $currentUser->id_user . ',id_user',
            'old_password' => 'sometimes|required_with:password|min:8', // Ancien mot de passe requis si nouveau mot de passe
            'password' => 'sometimes|required|min:8|confirmed', // Nouveau mot de passe avec confirmation
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096', // Validation de l'image
        ], [
            'old_password.required_with' => 'L\'ancien mot de passe est requis pour modifier le mot de passe.',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
    
        DB::beginTransaction();
    
        try {
            // Vérification et mise à jour du mot de passe
            if ($request->has('password')) {
                // Vérification de l'ancien mot de passe
                if (!Hash::check($request->old_password, $currentUser->password)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'L\'ancien mot de passe est incorrect.',
                    ], 400);
                }
    
                // Mise à jour du mot de passe
                $currentUser->password = Hash::make($request->password);
            }
    
            // Gestion de l'image de profil
            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $currentUser->photo_profil = 'storage/' . $filePath;
            }
    
            // Mise à jour des autres champs
            $currentUser->update($request->except(['password', 'photo_profil', 'old_password']));
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Employé mis à jour avec succès.',
                'data' => $currentUser,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Vérifie si un utilisateur a les permissions pour activer un compte.
     */
    private function hasPermission($user)
    {
        return in_array($user->role, ['superadmin', 'entreprise_gest']);
    }
}
