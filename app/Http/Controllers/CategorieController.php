<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categorie;
use Illuminate\Support\Facades\Auth;


class CategorieController extends Controller
{
    /**
     * Liste des catégories.
     */

     
    public function index()
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest,administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $categories = Categorie::all();
        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ], 200);
    }

    /**
     * Création d'une nouvelle catégorie.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin','partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        
        $validated = $request->validate([
            'libelle' => 'required|string|unique:categories,libelle|max:255',
        ]);

        $categorie = Categorie::create([
            'libelle' => $validated['libelle'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie créée avec succès.',
            'data' => $categorie,
        ], 201);
    }

    /**
     * Afficher une catégorie spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest,administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $categorie = Categorie::find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $categorie,
        ], 200);
    }

    /**
     * Mise à jour d'une catégorie.
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $categorie = Categorie::find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        $validated = $request->validate([
            'libelle' => 'required|string|unique:categories,libelle|max:255',
        ]);

        $categorie->update([
            'libelle' => $validated['libelle'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie mise à jour avec succès.',
            'data' => $categorie,
        ], 200);
    }

    /**
     * Suppression d'une catégorie.
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $categorie = Categorie::find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        $categorie->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès.',
        ], 200);
    }
}