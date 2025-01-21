<?php

namespace App\Http\Controllers;

use App\Models\Assurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

 class AssuranceController extends Controller
{
    /**
     * Afficher toutes les assurances avec leurs entreprises associées.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
    
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }
    
        // Récupération des assurances avec leurs entreprises et gestionnaires
        $assurances = Assurance::with([
            'entreprises',
            'gestionnaire:id_user,nom,photo_profil,tel,email'
        ])->get();
    
        // Pagination manuelle
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $paginated = $assurances->slice(($currentPage - 1) * $perPage, $perPage)->values();
    
        // Construire la réponse paginée
        return response()->json([
            'status' => 'success',
            'data' => $paginated,
            'pagination' => [
                'total' => $assurances->count(),
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($assurances->count() / $perPage),
            ],
        ], 200);
    }
    
    
    /**
     * Afficher une assurance spécifique avec ses entreprises associées.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin','assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $assurance = Assurance::with('entreprises')->find($id);

        if (!$assurance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Assurance non trouvée',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $assurance,
        ], 200);
    }

    /**
     * Créer une nouvelle assurance.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }
    
        // Validation des données
        $validated = $request->validate([
            'code_ifc' => 'required|string|unique:assurances,code_ifc|max:255',
            'libelle' => 'nullable|string|unique:assurances,libelle|max:255',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096',
        ]);
    
        try {
            $logoPath = null;
    
            // Gestion du téléversement du logo
            if ($request->hasFile('logo')) {
                $logoName = time() . '.' . $request->logo->extension();
                $logoPath = $request->logo->storeAs('logos/assurances', $logoName, 'public');
            }
    
            $validated['logo'] = $logoPath ? 'storage/' . $logoPath : null;
    
            // Création de l'assurance
            $assurance = Assurance::create($validated);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Assurance créée avec succès.',
                'data' => $assurance,
            ], 201);
        } catch (\Exception $e) {
            // Log des erreurs
            Log::error('Erreur création assurance : ', ['error' => $e->getMessage()]);
    
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'assurance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
   

    /**
     * Mettre à jour une assurance spécifique.
     */
 
     public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin','assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $assurance = Assurance::find($id);

        if (!$assurance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Assurance non trouvée',
            ], 404);
        }

        $validated = $request->validate([
            'code_ifc' => 'sometimes|required|string|max:255',
            'libelle' => 'nullable|string|max:255',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        try {
            // Suppression de l'ancien logo si un nouveau est téléversé
            if ($request->hasFile('logo')) {
                if ($assurance->logo) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $assurance->logo));
                }

                $logoName = time() . '.' . $request->logo->extension();
                $logoPath = $request->logo->storeAs('logos/assurances', $logoName, 'public');
                $validated['logo'] = 'storage/' . $logoPath;
            }

            $assurance->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Assurance mise à jour avec succès.',
                'data' => $assurance,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de l\'assurance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Supprimer une assurance spécifique.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $assurance = Assurance::find($id);

        if (!$assurance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Assurance non trouvée',
            ], 404);
        }

        try {
            // Suppression du logo associé
            if ($assurance->logo) {
                Storage::disk('public')->delete(str_replace('storage/', '', $assurance->logo));
            }

            $assurance->delete();

            return response()->json([
                'status' => 'success',
                'data' => $assurance,
                'message' => 'Assurance supprimée avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'assurance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
