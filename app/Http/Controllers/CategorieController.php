<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categorie;
use Illuminate\Support\Facades\Auth;

class CategorieController extends Controller
{
    /**
     * Liste des catégories accessibles par l'utilisateur.
     */
    public function index()
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Si l'utilisateur est un gestionnaire de shop, ne récupérer que ses catégories
        $categories = $currentUser->role === 'shop_gest'
            ? Categorie::where('id_shop', $currentUser->id_shop)->get()
            : Categorie::all();

         // Récupérer toutes les categorie avec leurs shops
         $categories = Categorie::with(['shop:id_shop,nom,logo,quartier,ville' ])->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ], 200);
    }

    /**
     * Création d'une nouvelle catégorie pour un shop.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest','admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $validated = $request->validate([
            'libelle' => 'required|string|unique:categories,libelle|max:255',
            'id_shop' => 'required|exists:partenaire_shops,id_shop',
        ]);

        $categorie = Categorie::create([
            'libelle' => $validated['libelle'],
            'id_shop' => $currentUser->role === 'shop_gest' ? $currentUser->id_shop : $request->id_shop,
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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
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

        // Vérifier l'accès pour les gestionnaires de shop
        if ($currentUser->role === 'shop_gest' && $categorie->id_shop !== $currentUser->id_shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé à cette catégorie.',
            ], 403);
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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest','admin'])) {
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

        // Vérifier l'accès pour les gestionnaires de shop
        if ($currentUser->role === 'shop_gest' && $categorie->id_shop !== $currentUser->id_shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé à cette catégorie.',
            ], 403);
        }

        $validated = $request->validate([
            'libelle' => 'required|string|unique:categories,libelle,' . $id . '|max:255',
            
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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest','admin'])) {
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

        // Vérifier l'accès pour les gestionnaires de shop
        if ($currentUser->role === 'shop_gest' && $categorie->id_shop !== $currentUser->id_shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé à cette catégorie.',
            ], 403);
        }

        $categorie->delete();

        return response()->json([
            'data' => $categorie,
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès.',
        ], 200);
    }
}
