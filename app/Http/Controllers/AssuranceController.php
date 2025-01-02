<?php

namespace App\Http\Controllers;

use App\Models\Assurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssuranceController extends Controller
{
    /**
     * Afficher toutes les assurances avec leurs entreprises associées.
     */
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $assurances = Assurance::with('entreprises')->get();

        return response()->json([
            'status' => 'success',
            'data' => $assurances,
        ], 200);
    }

    /**
     * Afficher une assurance spécifique avec ses entreprises associées.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
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
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $validated = $request->validate([
            'code_ifc' => 'required|string|unique:assurances,code_ifc|max:255',
            'libelle' => 'nullable|string|unique:assurances,libelle|max:255',
        ]);

        $assurance = Assurance::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Assurance créée avec succès.',
            'data' => $assurance,
        ], 201);
    }

    /**
     * Mettre à jour une assurance spécifique.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
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
        ]);

        $assurance->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Assurance mise à jour avec succès.',
            'data' => $assurance,
        ], 200);
    }

    /**
     * Supprimer une assurance spécifique.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
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

        $assurance->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Assurance supprimée avec succès.',
        ], 200);
    }
}
