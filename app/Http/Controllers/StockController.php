<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    /**
     * Liste des stocks.
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
        $stocks = Stock::with('produit')->get();

        return response()->json([
            'status' => 'success',
            'data' => $stocks,
        ], 200);
    }

    /**
     * Afficher un stock spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur', 'caissiere'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $stock = Stock::with('produit')->find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $stock,
        ], 200);
    }

    /**
     * Création d'un stock.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $validated = $request->validate([
            'id_produit' => 'required|exists:produits,id_produit',
            'quantite' => 'required|integer|min:0',
            'id_shop' => 'required|exists:partenaire_shops,id_partenaire',
        ]);

        $stock = Stock::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock créé avec succès.',
            'data' => $stock,
        ], 201);
    }

    /**
     * Mise à jour d'un stock.
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $validated = $request->validate([
            'quantite' => 'required|integer|min:0',
        ]);

        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        $stock->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock mis à jour avec succès.',
            'data' => $stock,
        ], 200);
    }

    /**
     * Suppression d'un stock.
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        $stock->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock supprimé avec succès.',
        ], 200);
    }
}
