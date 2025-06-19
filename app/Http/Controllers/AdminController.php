<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\VerifRole;
use Illuminate\Http\Request;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\UpdateDemandeRequest;
use Illuminate\Foundation\Auth\User as AuthUser;

class AdminController extends Controller
{
    /**
     * Création d'un compte admin.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'role' => 'required|in:admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nom' => $request->nom,
            'role' => 'admin',
            'statut' => 'actif',
        ]);

        // Créer un compte bancaire pour l'utilisateur
        $defaultPin = Compte::generateDefaultPin();
        $compte = Compte::create([
            'numero_compte' => Compte::generateNumeroCompte($user),
            'solde' => 0,
            'date_creation' => now(),
            'id_user' => $user->id_user,
            'pin' => Hash::make($defaultPin),
        ]);

        // Envoyer un email au gestionnaire avec ses informations de connexion
        Mail::to($user->email)->send(new \App\Mail\AccountCreatedMail($user, $request->password, $compte, $defaultPin));


        return response()->json([
            'status' => 'success',
            'message' => 'admin créé avec succès.',
            'user' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'nom' => $user->nom,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 201);
    }

    /**
     * Recuperer l'admin
     */
    public function show($id)
    {
        $admin = User::find($id);
        if ($admin) {
            return response()->json([
                "data" => $admin,
                "message" => "utilisateur récupéré avec succès."
            ], 200);
        } else {
            return response()->json([
                "data" => $admin,
                "message" => "utilisateur non Trouvé."
            ], 200);
        }
    }

    /**
     * Mise à jour du profil d'un utilisateur.
     */
    public function updateProfile(Request $request, $id_user)
    {
        $currentUser = auth()->user();

        // Vérification des autorisations de l'utilisateur actuel
        if (!in_array($currentUser->role, ['superadmin', 'admin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // Récupération de l'utilisateur cible
        $user = User::findOrFail($id_user);

        // Empêcher la mise à jour d'un autre superadmin
        if ($user->role === 'superadmin' && $currentUser->id_user !== $user->id_user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas modifier le profil d\'un autre superadmin.',
            ], 403);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
            'tel' => 'nullable|string|max:20',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Mise à jour des données utilisateur
        if ($request->has('nom')) {
            $user->nom = $validated['nom'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('tel')) {
            $user->tel = $validated['tel'];
        }
        if ($request->has('ville')) {
            $user->ville = $validated['ville'];
        }
        if ($request->has('quartier')) {
            $user->quartier = $validated['quartier'];
        }
        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo_profil && Storage::exists(str_replace('storage/', '', $user->photo_profil))) {
                Storage::delete(str_replace('storage/', '', $user->photo_profil));
            }

            $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
            $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
            $user->photo_profil = 'storage/' . $filePath;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'data' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'nom' => $user->nom,
                'tel' => $user->tel,
                'ville' => $user->ville,
                'quartier' => $user->quartier,
                'photo_profil' => $user->photo_profil,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 200);
    }


    /**
     * Suppression d'un utilisateur.
     */
    public function deleteUser($id_user)
    {
        $currentUser = auth()->user();

        if ($currentUser->role !== 'superadmin') {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $user = User::findOrFail($id_user);

        // Supprimer la photo de profil si elle existe
        if ($user->photo_profil && Storage::exists(str_replace('storage/', '', $user->photo_profil))) {
            Storage::delete(str_replace('storage/', '', $user->photo_profil));
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur supprimé avec succès.',
        ], 200);
    }

    public function index()
    {
        $verifRole = new VerifRole();
        if($verifRole->isAdmin() OR $verifRole->isShop() ){
            return response()->json([
                'status' => 'success',
                'data' => User::select('id_user','nom','ville','quartier','tel','email','id_shop','photo_profil')->where('role', Roles::Admin->value)->get(),
                'message' => 'admins récupérés avec succès.'
            ],200);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'vous ne pouvez pas éffectuer cette action.'
            ]);

        }
    }
}
