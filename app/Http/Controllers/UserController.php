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
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    const STATUT_INACTIF = 'inactif';
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_ACTIF = 'actif';

    
     /**
     * Recherche les utilisateurs en fonction de leurs rôles (exclu superadmin et admin).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByRole(Request $request)
    {
        $user = Auth::user();
    
        // Valider les paramètres d'entrée
        $request->validate([
            'role' => 'required|in:superadmin,admin,employe,entreprise_gest,shop_gest,caissiere,assurance_gest',
        ]);
    
        $role = $request->role;
    
        try {
            $usersQuery = User::query();
    
            // Conditions basées sur le rôle de l'utilisateur connecté
            if (in_array($user->role, ['superadmin', 'admin'])) {
                // Superadmin et admin peuvent voir tous les utilisateurs
            } elseif ($user->role === 'shop_gest') {
                // Partenaire shop : peut voir uniquement les caissières de son shop
                if ($role !== 'caissiere') {
                    return response()->json([
                        'message' => 'Vous n\'êtes pas autorisé à rechercher des utilisateurs avec ce rôle.'
                    ], 403);
                }
                $usersQuery->where('role', 'caissiere')
                           ->where('id_shop', $user->id_shop);
            } elseif ($user->role === 'entreprise_gest') {
                // Entreprise gest : peut voir uniquement les employés de son entreprise
                if ($role !== 'employe') {
                    return response()->json([
                        'message' => 'Vous n\'êtes pas autorisé à rechercher des utilisateurs avec ce rôle.'
                    ], 403);
                }
                $usersQuery->where('role', 'employe')
                           ->where('id_entreprise', $user->id_entreprise);
            } elseif ($user->role === 'assurance_gest') {
                // Assurance gest : peut voir uniquement les entreprises liées à son assurance
                if ($role !== 'entreprise_gest') {
                    return response()->json([
                        'message' => 'Vous n\'êtes pas autorisé à rechercher des utilisateurs avec ce rôle.'
                    ], 403);
                }
                $usersQuery->where('role', 'entreprise_gest')
                           ->where('id_assurance', $user->id_user);
            } else {
                // Rôles non autorisés
                return response()->json([
                    'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
                ], 403);
            }
    
            // Appliquer le filtre par rôle
            $users = $usersQuery->where('role', $role)->get();
    
            return response()->json([
                'status' => 'success',
                'users' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue, veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function destroy($id)
{
    $currentUser = Auth::user();

    // Vérifier si l'utilisateur connecté est autorisé à supprimer d'autres utilisateurs
    if (!in_array($currentUser->role, ['superadmin', 'admin','assurance_gest','entreprise_gest'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    try {
        // Récupérer l'utilisateur à supprimer
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Vérifier que l'utilisateur à supprimer n'est pas un superadmin ou un admin
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer un superadmin ou un admin.',
            ], 403);
        }

        // Supprimer l'utilisateur
        $user->delete();

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'Utilisateur supprimé avec succès.',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur.',
        ], 500);
    }
}

    
}
