<?php

namespace App\Http\Controllers;

use App\Models\Assurance;
use App\Models\VerifRole;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EntrepriseController extends Controller
{


    /**
     * Rechercher les entreprises.
     */


    public function search(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'secteur_activite' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'quartier' => 'nullable|string|max:255',
        ]);

        try {
            $query = Entreprise::query();

            if (!empty($validated['nom'])) {
                $query->where('nom', 'like', '%' . $validated['nom'] . '%');
            }
            if (!empty($validated['secteur_activite'])) {
                $query->where('secteur_activite', 'like', '%' . $validated['secteur_activite'] . '%');
            }
            if (!empty($validated['ville'])) {
                $query->where('ville', 'like', '%' . $validated['ville'] . '%');
            }
            if (!empty($validated['quartier'])) {
                $query->where('quartier', 'like', '%' . $validated['quartier'] . '%');
            }

            $entreprises = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $entreprises,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la recherche des entreprises.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Liste toutes les entreprises avec leurs assurances associées.
     */
    //     public function index(Request $request)
    // {
    //     $user = Auth::user();

    //     if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest','entreprise_gest'])) {
    //         return response()->json([
    //             'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
    //         ], 403);
    //     }

    //     // Récupérer toutes les entreprises avec leurs assurances et gestionnaires
    //     $entreprises = Entreprise::with([
    //         'assurance:id_assurance,libelle,logo',
    //         'gestionnaire:id_user,nom,photo_profil,tel,email'
    //     ])->get();

    //     // Pagination manuelle
    //     $perPage = $request->input('per_page', 10);
    //     $currentPage = $request->input('page', 1);
    //     $paginated = $entreprises->slice(($currentPage - 1) * $perPage, $perPage)->values();

    //     // Construire la réponse paginée
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $paginated,
    //         'pagination' => [
    //             'total' => $entreprises->count(),
    //             'per_page' => $perPage,
    //             'current_page' => $currentPage,
    //             'last_page' => ceil($entreprises->count() / $perPage),
    //         ],
    //     ], 200);
    // }
    public function index(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }
        if ($verifRole->isAdmin()) {
            $query = Entreprise::query();
        }
        if ($verifRole->isAssurance()) {
            $query = Entreprise::query()->where('id_assurance', $user->id_assurance);
        }
        if ($request->filled('id_assurance')) {
            $query->where('id_assurance', $request->input('id_assurance', -1));
        }
        //filtrage global
        if ($request->filled('filters.global.value')) {
            $value = $request->input('filters.global.value');
            $query->where(function ($q) use ($value) {
                $q->where('nom', 'like', "%$value%")
                    ->orWhere('ville', 'like', "%$value%")
                    ->orWhere('quartier', 'like', "%$value%")
                    ->orWhere('adresse', 'like', "%$value%")
                    ->orWhere('secteur_activite', 'like', "%$value%");
            });
        }

        // filtrage par champs
        foreach ($request->input('filters', []) as $field => $filter) {
            if ($field === 'global') continue;

            $operator = $filter['operator'] ?? 'and';
            $constraints = $filter['constraints'] ?? [];
            if ($field === 'assurance') {
                $query->whereHas('assurance',function ($q) use ($constraints, $field, $operator) {
                    foreach ($constraints as $rule) {
                        $value = $rule['value'] ?? null;
                        $mode = $rule['matchMode'] ?? 'contains';

                        if (is_null($value)) continue;

                        $clause = match ($mode) {
                            'startsWith' => ['libelle', 'like', $value . '%'],
                            'endsWith'   => ['libelle', 'like', '%' . $value],
                            'contains'   => ['libelle', 'like', '%' . $value . '%'],
                            'equals'     => ['libelle', '=', $value],
                            'notEquals'  => ['libelle', '!=', $value],
                            'in'         => ['libelle', $value],
                            default      => null
                        };

                        if (!$clause) continue;

                        $operator === 'or'
                            ? $q->orWhere(...$clause)
                            : $q->where(...$clause);
                    }
                });
            }else{
                $query->where(function ($q) use ($constraints, $field, $operator) {
                    foreach ($constraints as $rule) {
                        $value = $rule['value'] ?? null;
                        $mode = $rule['matchMode'] ?? 'contains';

                        if (is_null($value)) continue;

                        $clause = match ($mode) {
                            'startsWith' => [$field, 'like', $value . '%'],
                            'endsWith'   => [$field, 'like', '%' . $value],
                            'contains'   => [$field, 'like', '%' . $value . '%'],
                            'equals'     => [$field, '=', $value],
                            'notEquals'  => [$field, '!=', $value],
                            'in'         => [$field, $value],
                            default      => null
                        };

                        if (!$clause) continue;

                        $operator === 'or'
                            ? $q->orWhere(...$clause)
                            : $q->where(...$clause);
                    }
                });
            }


        }
        // recuperer les entreprises d'une assurance
        if ($request->filled('id_assurance')) {
            $query->where('id_assurance', 'like', '%' . $request->id_assurance . '%');
        }

        //tri
        if ($request->filled('sortField') && $request->filled('sortOrder')) {
            $direction = $request->sortOrder == -1 ? 'desc' : 'asc';
            $query->orderBy($request->sortField, $direction);
        } else {
            $query->orderBy('id_entreprise', 'desc');
        }
        $entreprises = $query->with([
            'assurance:id_assurance,libelle,logo',
            'gestionnaire:id_user,nom,photo_profil,tel,email'
        ])->paginate($request->get('rows', 10));
        $last_entreprise = collect($entreprises->items())->last();
        //pagination
        $response = [
            'data' => $entreprises->items(),
            'last_item' => $last_entreprise,
            'current_page' => $entreprises->currentPage(),
            'last_page' => $entreprises->lastPage(),
            'per_page' => $entreprises->perPage(),
            'total' => $entreprises->total(),
        ];

        return response()->json($response);
    }

    /**
     * Affiche une entreprise spécifique avec son assurance associée.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest', 'entreprise_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::with(['assurance', 'gestionnaire'])->find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $entreprise
        ], 200);
    }

    /**
     * Crée une nouvelle entreprise avec un logo.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'secteur_activite' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'adresse' => 'required|string',
            'id_assurance' => 'required|exists:assurances,id_assurance',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096',
        ], [
            'nom.required' => 'Le nom de l\'entreprise est obligatoire.',
            'id_assurance.exists' => 'L\'assurance associée n\'existe pas.',
        ]);

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoName = time() . '.' . $request->logo->extension();
                $logoPath = $request->logo->storeAs('logos/entreprises', $logoName, 'public');
            }

            $validated['logo'] = $logoPath ? 'storage/' . $logoPath : null;

            $entreprise = Entreprise::create($validated);

            // Charger la relation assurance pour renvoyer l'entreprise avec son assurance associée
            $entreprise->load('assurance');

            return response()->json([
                'status' => 'success',
                'message' => 'Entreprise créée avec succès.',
                'data' => $entreprise,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'entreprise.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Met à jour une entreprise existante et gère le logo.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest', 'entreprise_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'secteur_activite' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            'quartier' => 'sometimes|string|max:255',
            'adresse' => 'sometimes|string',
            'id_assurance' => 'sometimes|required|exists:assurances,id_assurance',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        try {
            if ($request->hasFile('logo')) {
                // Supprimer l'ancien logo si un nouveau est téléversé
                if ($entreprise->logo) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $entreprise->logo));
                }

                $logoName = time() . '.' . $request->logo->extension();
                $logoPath = $request->logo->storeAs('logos/entreprises', $logoName, 'public');
                $validated['logo'] = 'storage/' . $logoPath;
            }

            $entreprise->update($validated);

            // Charger la relation assurance pour l'entreprise mise à jour
            $entreprise->load('assurance');

            return response()->json([
                'status' => 'success',
                'message' => 'Entreprise mise à jour avec succès.',
                'data' => $entreprise,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de l\'entreprise.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Supprime une entreprise et son logo associé.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        try {
            if ($entreprise->logo) {
                // Supprimer le logo associé
                Storage::disk('public')->delete(str_replace('storage/', '', $entreprise->logo));
            }

            $entreprise->delete();

            return response()->json([
                'status' => 'success',
                'data' => $entreprise,
                'message' => 'Entreprise supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'entreprise.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
