<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;

class ProduitController extends Controller
{
    /**
     * Liste des produits.
     */
    public function index()
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur', 'caissiere'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
    
        $produits = Produit::with(['categorie', 'partenaire', 'stock'])->get();
    
        $produits = $produits->map(function ($produit) {
            return [
                'id' => $produit->id_produit,
                'nom' => $produit->nom,
                'categorie' => $produit->categorie->nom ?? null,
                'prix_ifc' => $produit->prix_ifc,
                'prix_shop' => $produit->prix_shop,
                'statut' => $produit->statut,
                'code_barre' => $produit->code_barre,
                'quantite_disponible' => $produit->stock->quantite ?? 0, // Quantité disponible
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $produits,
        ], 200);
    }
    

    /**
     * Création d'un produit.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'id_categorie' => 'required|exists:categories,id',
            'prix_ifc' => 'required|numeric|min:0',
            'prix_shop' => 'required|numeric|min:0',
            'statut' => 'required|string',
            'code_barre' => 'nullable|string|unique:produits,code_barre|max:255',
        ]);

        $produit = Produit::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produit créé avec succès.',
            'data' => $produit,
        ], 201);
    }

    /**
     * Afficher un produit spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest','administrateur','caissiere','employe'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $produit = Produit::with('categorie', 'partenaire')->find($id);

        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $produit,
        ], 200);
    }

    /**
     * Mise à jour d'un produit.
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
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'id_categorie' => 'nullable|exists:categories,id',
            'prix_ifc' => 'nullable|numeric|min:0',
            'prix_shop' => 'nullable|numeric|min:0',
            'statut' => 'nullable|string',
            'code_barre' => 'nullable|string|unique:produits,code_barre|max:255',
        ]);

        $produit->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produit mis à jour avec succès.',
            'data' => $produit,
        ], 200);
    }

    /**
     * Suppression d'un produit.
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
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $produit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produit supprimé avec succès.',
        ], 200);
    }
}
