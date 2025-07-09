<?php

namespace App\Http\Controllers;

use App\Models\Assurance;
use App\Models\QueryFiler;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssuranceController extends Controller
{
    use ApiResponseTrait;
    /**
     * Afficher toutes les assurances avec leurs entreprises associées.
     */
    // public function index(Request $request)
    // {
    //     $user = Auth::user();

    //     if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
    //         ], 403);
    //     }

    //     // Récupération des assurances avec leurs entreprises et gestionnaires
    //     $assurances = Assurance::with([
    //         'entreprises',
    //         'gestionnaire:id_user,nom,photo_profil,tel,email'
    //     ])->get();

    //     // Pagination manuelle
    //     $perPage = $request->input('per_page', 10);
    //     $currentPage = $request->input('page', 1);
    //     $paginated = $assurances->slice(($currentPage - 1) * $perPage, $perPage)->values();

    //     // Construire la réponse paginée
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $paginated,
    //         'pagination' => [
    //             'total' => $assurances->count(),
    //             'per_page' => $perPage,
    //             'current_page' => $currentPage,
    //             'last_page' => ceil($assurances->count() / $perPage),
    //         ],
    //     ], 200);
    // }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }
        $query = Assurance::query();
        // définir les relations qui seront aussi filtrées et leurs champs
        $relationMap = [];

        // definir les champs concerne par le filtre global
        $globalSearchFields = ['libelle', 'code_ifc',];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id_assurance');
        $query = $filter->apply($query, $request);

        $assurances = $query->paginate($request->get('rows', 10));
        $last_assurance = collect($assurances->items())->last();

        //pagination
        $response = [
            'data' => $assurances->items(),
            'last_item' => $last_assurance,
            'current_page' => $assurances->currentPage(),
            'last_page' => $assurances->lastPage(),
            'per_page' => $assurances->perPage(),
            'total' => $assurances->total(),
        ];

        return $this->successResponse($response);
    }


    /**
     * Afficher une assurance spécifique avec ses entreprises associées.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
        }

        $assurance = Assurance::with('entreprises')->findOrFail($id);
        return $this->successResponse($assurance);
    }

    /**
     * Créer une nouvelle assurance.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
        }

        // Validation des données (on ne demande plus le code_ifc puisque celui-ci sera généré automatiquement)
        $validated = $request->validate([
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

            // Automatisation du code IFC
            // On récupère la dernière assurance créée pour en déduire le prochain numéro
            $latestAssurance = Assurance::orderBy('id_assurance', 'desc')->first();
            $nextId = $latestAssurance ? $latestAssurance->id_assurance + 1 : 1;
            // Par exemple, le code IFC sera sous la forme IFC-00001, IFC-00002, etc.
            $validated['code_ifc'] = 'IFC-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            // Création de l'assurance
            $assurance = Assurance::create($validated);
            return $this->successResponse($assurance,'Assurance créée avec succès.',201);
        } catch (\Exception $e) {
            // Log des erreurs
            Log::error('Erreur création assurance : ', ['error' => $e->getMessage()]);

            return $this->errorResponse('Erreur lors de la création de l\'assurance.',500,$e->getMessage());
        }
    }



    /**
     * Mettre à jour une assurance spécifique.
     */

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
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
