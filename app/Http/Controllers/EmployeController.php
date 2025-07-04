<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\Assurance;
use App\Models\VerifRole;
use App\Models\Entreprise;
use App\Models\QueryFiler;
use App\Models\Transaction;
use App\Models\LigneFacture;
use Illuminate\Http\Request;
use App\Mail\AccountActivated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'id_entreprise' => 'required|exists:entreprises,id_entreprise',
            'tel' => 'required|string|max:15',
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
                'tel' => $request->tel,
                'role' => 'employe',
            ];
            // Mise à jour des informations autorisées
            if (isset($request->ville)) {
                $userData['ville'] = $request->ville;
            }
            if (isset($request->quartier)) {
                $userData['quartier'] = $request->quartier;
            }

            if ($request->hasFile('photo_profil')) {
                $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
                $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
                $userData['photo_profil'] = 'storage/' . $filePath;
            }

            $user = User::create($userData);

            // Charger la relation entreprise
            $user->load('entreprise');

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
        if (!in_array($currentUser->role, ['superadmin', 'entreprise_gest', 'employe'])) {
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
    // tout les employes
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $verifRole = new VerifRole();

        if (!in_array($currentUser->role, ['superadmin', 'admin', 'entreprise_gest', 'assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }
        if ($verifRole->isAdmin()) {
            $query = User::query()->where('role', Roles::Employe->value)->where('statut', 'actif');
        }
        if ($verifRole->isEntreprise()) {
            $query = User::query()->where('role', Roles::Employe->value)->where('id_entreprise', $currentUser->id_entreprise)->where('statut', 'actif');
        }
        if ($verifRole->isAssurance()) {
            $query = Assurance::getEmployeAssurance($currentUser->id_assurance)->where('statut', 'actif');
        }

        // définir les relations qui seront aussi filtrées et leurs champs
        $relationMap = [
            'entreprise' => 'entreprise.nom',
        ];

        // definir les champs concerne par le filtre global
        $globalSearchFields = ['nom', 'ville', 'quartier', 'tel', 'email'];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id_user', ['id_entreprise']);
        $query = $filter->apply($query, $request);
        // Récupérer les employés avec leurs entreprises
        $employes =  $query
            ->with('entreprise:id_entreprise,id_assurance,nom,secteur_activite,ville,quartier')
            //pagination
            ->paginate($request->get('rows', 10));
        // Récupérer le dernier employe
        $last_employe = collect($employes->items())->last();
        $response = [
            'data' => $employes->items(),
            'last_item' => $last_employe,
            'current_page' => $employes->currentPage(),
            'last_page' => $employes->lastPage(),
            'per_page' => $employes->perPage(),
            'total' => $employes->total(),
        ];

        return response()->json($response);
    }
    // tout les nonActif
    public function nonActif(Request $request)
    {
        $currentUser = Auth::user();
        $verifRole = new VerifRole();

        if (!in_array($currentUser->role, ['entreprise_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }
        $query = User::query()->where('role', Roles::Employe->value)->where('id_entreprise', $currentUser->id_entreprise)->where('statut', '!=', 'actif');

        // définir les relations qui seront aussi filtrées et leurs champs
        $relationMap = [
            'entreprise' => 'entreprise.nom',
        ];

        // definir les champs concerne par le filtre global
        $globalSearchFields = ['nom', 'ville', 'quartier', 'tel', 'email'];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id_user',['id_entreprise']);
        $query = $filter->apply($query, $request);
        // Récupérer les employés avec leurs entreprises
        $employes =  $query
            ->with('entreprise:id_entreprise,id_assurance,nom,secteur_activite,ville,quartier')
            //pagination
            ->paginate($request->get('rows', 10));
        // Récupérer le dernier employe
        $last_employe = collect($employes->items())->last();
        $response = [
            'data' => $employes->items(),
            'last_item' => $last_employe,
            'current_page' => $employes->currentPage(),
            'last_page' => $employes->lastPage(),
            'per_page' => $employes->perPage(),
            'total' => $employes->total(),
        ];

        return response()->json($response);
    }


    /**
     * Afficher un employé spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'admin', 'entreprise_gest', 'employe'])) {
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
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
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

            // Charger la relation entreprise
            $currentUser->load('entreprise');

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


    public function getEmployeInfo($id_user)
    {
        // Vérifier l'authentification
        $user = Auth::user();

        if (!$user || $user->role !== 'employe') {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Vérifier si l'ID utilisateur est valide
        if (!$id_user || !is_numeric($id_user)) {
            return response()->json(['error' => 'ID utilisateur invalide'], 400);
        }

        // Récupérer les informations du compte de l'utilisateur
        $compte = Compte::where('id_user', $id_user)->first();
        if (!$compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        // Calculer le total des crédits et débits
        $credits_total = Transaction::where('numero_compte_dest', $compte->numero_compte)
            ->where('type', 'credit')
            ->sum('montant');

        $debits_total = Transaction::where('numero_compte_src', $compte->numero_compte)
            ->where('type', 'debit')
            ->sum('montant');

        // Récupérer le nom de l'entreprise associée à l'utilisateur
        $entreprise = Entreprise::where('id_entreprise', $user->id_entreprise)->first();

        return response()->json([
            'nom' => $user->nom,
            'email' => $user->email,
            'ville' => $user->ville,
            'quartier' => $user->quartier,
            'numero_compte' => $compte->numero_compte,
            'solde' => $compte->solde,
            'date_creation' => $compte->date_creation,
            'credits_total' => $credits_total,
            'debits_total' => $debits_total,
            'entreprise' => $entreprise ? $entreprise->nom : 'Non assignée',
            'entreprise_id' => $entreprise ? $entreprise->id_entreprise : 'Non assignée'

        ]);
    }

    public function getHistorique($id_user)
    {
        // Vérification de l'authentification
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'employe'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }
        // Vérifier si l'utilisateur a un compte
        $compte = Compte::where('id_user', $id_user)->first();
        if (!$compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        // Total des dépenses (Débits)
        $total_depenses = Transaction::where('numero_compte_src', $compte->numero_compte)
            ->where('type', 'debit')
            ->sum('montant');

        // Récupération des achats
        $achats = LigneFacture::join('factures', 'lignes_factures.id_facture', '=', 'factures.id_facture')
            ->join('produits', 'lignes_factures.id_produit', '=', 'produits.id_produit')
            ->where('factures.id_client', $id_user)
            ->select('factures.date_facturation', 'produits.nom', 'produits.prix_shop')
            ->get();

        // Liste des transactions (Crédits & Débits)
        $transactions = Transaction::where(function ($query) use ($compte) {
            $query->where('numero_compte_src', $compte->numero_compte)
                ->orWhere('numero_compte_dest', $compte->numero_compte);
        })
            ->select('type', 'montant', 'created_at as date')
            ->get();

        // Liste des demandes de crédit
        $demandes_credit = Demande::where('id_user', $id_user)
            ->select('montant', 'statut', 'motif', 'created_at as date')
            ->get();


        return response()->json([
            'total_depenses' => $total_depenses,
            'achats' => $achats,
            'transactions' => $transactions,
            'demandes_credit' => $demandes_credit
        ]);
    }
}
