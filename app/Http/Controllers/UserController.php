<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponseTrait;
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
        if (!in_array($currentUser->role, ['superadmin', 'admin', 'assurance_gest', 'entreprise_gest'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
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
    public function getGestShop()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::where('role', Roles::Shop->value)->get(),
            'message' => 'gestionnaires shops récupérés avec succès.'
        ]);
    }
    public function getGestEntreprise()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::where('role', Roles::Entreprise->value)->get(),
            'message' => 'gestionnaires entreprises récupérés avec succès.'
        ]);
    }
    public function index()
    {
        $user = Auth::user();
        if ($user->role != Roles::Admin->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }
        return response()->json([
            'status' => 'success',
            'data' => User::select(['id_user', 'nom', 'tel', 'email', 'role'])->with(['compte:id_compte,id_user,numero_compte'])->get(),
            'message' => 'gestionnaires entreprises récupérés avec succès.'
        ]);
    }

    public function show($id)
    {
        $user = User::select(['id_user', 'nom', 'tel', 'email', 'role'])->where('id_user', $id)->first();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'data' => $user,
                'message' => 'gestionnaires entreprises récupérés avec succès.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Cet utilisateur n\'existe pas.'
            ], 404);
        }
    }
    public function search(Request $request)
    {
        $user = Auth::user();
        if ($user->role != Roles::Admin->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }
        $query = $request->input('q');
        $users = User::where('nom','like', "%$query%")->where('id_user','!=',$user->id_user)->select(['id_user', 'nom', 'tel', 'email', 'role'])->with('compte:id_user,id_compte,numero_compte')->limit(10)->get();
        return response()->json([
            'data' => $users,
        ], 200);
    }
}
