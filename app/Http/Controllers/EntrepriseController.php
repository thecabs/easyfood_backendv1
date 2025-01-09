<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EntrepriseController extends Controller
{
    /**
     * Liste toutes les entreprises avec leurs assurances associées.
     */
    public function index(Request $request)
{
    $user = Auth::user();

    if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest','entreprise_gest'])) {
        return response()->json([
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
        ], 403);
    }

    // Récupérer toutes les entreprises avec leurs assurances et gestionnaires
    $entreprises = Entreprise::with([
        'assurance:id_assurance,libelle,logo',
        'gestionnaire:id_user,nom,photo_profil,tel'
    ])->get();

    // Pagination manuelle
    $perPage = $request->input('per_page', 10);
    $currentPage = $request->input('page', 1);
    $paginated = $entreprises->slice(($currentPage - 1) * $perPage, $perPage)->values();

    // Construire la réponse paginée
    return response()->json([
        'status' => 'success',
        'data' => $paginated,
        'pagination' => [
            'total' => $entreprises->count(),
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($entreprises->count() / $perPage),
        ],
    ], 200);
}

    /**
     * Affiche une entreprise spécifique avec son assurance associée.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'assurance_gest','entreprise_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::with('assurance')->find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'=> $entreprise
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
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096', // Validation du logo
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
        if (!in_array($user->role, ['superadmin', 'admin','assurance_gest','entreprise_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'secteur_activite' => 'sometimes|required|string|max:255',
            'ville' => 'sometimes|required|string|max:255',
            'quartier' => 'sometimes|required|string|max:255',
            'adresse' => 'sometimes|required|string',
            'id_assurance' => 'sometimes|required|exists:assurances,id_assurance',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096', // Validation pour le logo
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
        if (!in_array($user->role, ['superadmin', 'admin','assurance_gest'])) {
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
