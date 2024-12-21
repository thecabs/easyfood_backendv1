<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntrepriseController extends Controller
{
    /**
     * Liste toutes les entreprises avec leurs assurances associées.
     */
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprises = Entreprise::with('assurance')->paginate(10); // Utilisation de la pagination
        return response()->json($entreprises, 200);
    }

    /**
     * Affiche une entreprise spécifique avec son assurance associée.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur','assurance_gest'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::with('assurance')->find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        return response()->json($entreprise, 200);
    }

    /**
     * Crée une nouvelle entreprise.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur','assurance_gest'])) {
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
        ], [
            'nom.required' => 'Le nom de l\'entreprise est obligatoire.',
            'id_assurance.exists' => 'L\'assurance associée n\'existe pas.',
        ]);

        $entreprise = Entreprise::create($validated);

        return response()->json([
         
            'message' => 'Entreprise créée avec succès.',
            'data' => $entreprise,
        ], 201);
    }

    /**
     * Met à jour une entreprise existante.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
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
        ]);

        $entreprise->update($validated);

        return response()->json([
             'message' => 'Entreprise mise à jour avec succès.',
            'data' => $entreprise,
        ], 200);
    }

    /**
     * Supprime une entreprise.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $entreprise = Entreprise::find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        $entreprise->delete();

        return response()->json([
             'message' => 'Entreprise supprimée avec succès.'
        ], 200);
    }
}
