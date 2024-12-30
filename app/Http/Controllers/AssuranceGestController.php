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
    

    public function register(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'tel' => 'required|string|max:20',
            'id_assurance' => 'required|exists:assurances,id_assurance',
        ]);

        DB::beginTransaction();

        try {
            $assurance = Assurance::findOrFail($validated['id_assurance']);

            if ($assurance->id_gestionnaire) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette assurance a déjà un gestionnaire associé.',
                ], 403);
            }

            // Générer un mot de passe aléatoire
            $generatedPassword = Str::random(10);

            $user = User::create([
                'nom' => $validated['nom'],
                'email' => $validated['email'],
                'tel' => $validated['tel'],
                'password' => Hash::make($generatedPassword),
                'role' => 'assurance_gest',
                'statut' => 'actif', // Compte activé par défaut
            ]);

            $assurance->id_gestionnaire = $user->id_user;
            $assurance->save();

            // Envoyer les informations de connexion par email
            Mail::to($user->email)->send(new \App\Mail\AccountCreatedMail($user, $generatedPassword));

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
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'assurance_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }
    
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
            'tel' => 'nullable|string|max:20',
        ]);
    
        DB::beginTransaction();
    
        try {
            $userToUpdate = User::findOrFail($id_user);
    
            // Mettre à jour les champs fournis
            if (isset($validated['nom'])) {
                $userToUpdate->nom = $validated['nom'];
            }
            if (isset($validated['email'])) {
                $userToUpdate->email = $validated['email'];
            }
            if (isset($validated['tel'])) {
                $userToUpdate->tel = $validated['tel'];
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
