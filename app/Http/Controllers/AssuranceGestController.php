<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Assurance;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssuranceGestController extends Controller
{
    public function showGest($id_user)
{
    $user = Auth::user();

    if (!in_array($user->role, ['superadmin', 'admin','assurance_gest'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
        ], 403);
    }

    try {
        $gestionnaire = User::where('id_user', $id_user)
            ->where('role', 'assurance_gest')
            ->with(['assurance' => function ($query) {
                $query->select('id_assurance', 'code_ifc', 'libelle', 'id_gestionnaire','logo');
            }])
            ->first();

        if (!$gestionnaire) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gestionnaire introuvable ou non valide.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Gestionnaire trouvé avec succès.',
            'data' => [
                'id_user' => $gestionnaire->id_user,
                'nom' => $gestionnaire->nom,
                'email' => $gestionnaire->email,
                'tel' => $gestionnaire->tel,
                'role' => $gestionnaire->role,
                'statut' => $gestionnaire->statut,
                'photo_profil' => $gestionnaire->photo_profil,
                'assurance' => [
                    'id_assurance' => $gestionnaire->assurance->id_assurance ?? null,
                    'libelle' => $gestionnaire->assurance->libelle ?? null,
                    'code_ifc' => $gestionnaire->assurance->code_ifc ?? null,
                    'logo' => $gestionnaire->assurance->logo ?? null,
                ],
            ],
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la récupération des informations.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function register(Request $request)
{
    $user = Auth::user();
    if (!in_array($user->role, ['superadmin', 'admin'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    $validated = $request->validate([
        'nom' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'tel' => 'required|string|max:20',
        'id_assurance' => 'required|exists:assurances,id_assurance',
        'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096', // Validation de l'image
    ]);

    DB::beginTransaction();

    try {
        // Récupérer l'assurance
        $assurance = Assurance::findOrFail($validated['id_assurance']);

        // Vérifier si un gestionnaire est déjà associé
        if ($assurance->id_gestionnaire) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cette assurance a déjà un gestionnaire associé.',
            ], 403);
        }

        // Générer un mot de passe aléatoire
        $generatedPassword = Str::random(10);

        // Préparer les données de l'utilisateur
        $userData = [
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'tel' => $validated['tel'],
            'password' => Hash::make($generatedPassword),
            'role' => 'assurance_gest',
            'statut' => 'actif',
            'id_assurance' => $validated['id_assurance'], // Lier l'assurance à l'utilisateur
        ];

        // Gérer l'upload de la photo de profil
        if ($request->hasFile('photo_profil')) {
            $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
            $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
            $userData['photo_profil'] = 'storage/' . $filePath;
        }

        // Créer l'utilisateur
        $user = User::create($userData);

        // Associer l'utilisateur comme gestionnaire de l'assurance
        $assurance->id_gestionnaire = $user->id_user;
        $assurance->save();

        // Envoyer un email au gestionnaire avec ses informations de connexion
        Mail::to($user->email)->send(new \App\Mail\AccountCreatedMailA($user, $generatedPassword));

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès. Les informations de connexion ont été envoyées par email.',
            'user' => [
                'id_user' => $user->id_user,
                'nom' => $user->nom,
                'email' => $user->email,
                'role' => $user->role,
                'statut' => $user->statut,
                'photo_profil' => $user->photo_profil,
            ],
            'assurance' => [
                'id_assurance' => $assurance->id_assurance,
                'code_ifc' => $assurance->code_ifc,
                'libelle' => $assurance->libelle,
            ],
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la création de l\'utilisateur ou de l\'association.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function updateProfile(Request $request, $id_user)
{
    $currentUser = Auth::user();

    // Vérifier si l'utilisateur actuel a les droits nécessaires
    if (!in_array($currentUser->role, ['superadmin', 'assurance_gest'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    // Récupérer l'utilisateur cible
    $userToUpdate = User::findOrFail($id_user);

    // Empêcher la modification du profil d'un superadmin
    if ($userToUpdate->role === 'superadmin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous ne pouvez pas modifier le profil d\'un superadmin.',
        ], 403);
    }

    // Validation avec vérification de l'unicité du nom et de l'email
    $validated = $request->validate([
        'nom' => 'nullable|string|max:255|unique:users,nom,' . $id_user . ',id_user',
        'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
        'tel' => 'nullable|string|max:20',
        'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        'old_password' => 'nullable|required_with:password|min:8',
        'password' => 'nullable|min:8|confirmed',
    ], [
        'nom.unique' => 'Ce nom est déjà utilisé.',
        'email.unique' => 'Cet email est déjà utilisé.',
        'old_password.required_with' => 'L\'ancien mot de passe est requis pour modifier le mot de passe.',
    ]);

    DB::beginTransaction();

    try {
        // Mise à jour des champs
        if (isset($validated['nom'])) {
            $userToUpdate->nom = $validated['nom'];
        }
        if (isset($validated['email'])) {
            $userToUpdate->email = $validated['email'];
        }
        if (isset($validated['tel'])) {
            $userToUpdate->tel = $validated['tel'];
        }
        if ($request->hasFile('photo_profil')) {
            $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
            $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
            $userToUpdate->photo_profil = 'storage/' . $filePath;
        }
        if (isset($validated['password'])) {
            // Vérification de l'ancien mot de passe
            if (!Hash::check($validated['old_password'], $userToUpdate->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'L\'ancien mot de passe est incorrect.',
                ], 400);
            }

            $userToUpdate->password = Hash::make($validated['password']);
        }

        $userToUpdate->save();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'user' => [
                'id_user' => $userToUpdate->id_user,
                'nom' => $userToUpdate->nom,
                'email' => $userToUpdate->email,
                'tel' => $userToUpdate->tel,
                'role' => $userToUpdate->role,
                'statut' => $userToUpdate->statut,
                'photo_profil' => $userToUpdate->photo_profil,
            ],
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la mise à jour du profil.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    

}
