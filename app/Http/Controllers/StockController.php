<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\PartenaireShop;
use Illuminate\Support\Facades\Auth;
use App\Models\StockLog;

class StockController extends Controller
{
    private function logStockAction($id_stock, $action, $details = null)
    {
        $currentUser = Auth::user();

        StockLog::create([
            'id_stock' => $id_stock,
            'id_user' => $currentUser->id_user,
            'action' => $action,
            'details' => $details ? json_encode($details) : null,
        ]);
    }

    /**
     * Vérifie si l'utilisateur est autorisé à gérer le stock.
     */
    private function canAccessStock($stock, $currentUser, $id_shop = null)
    {
        if ($currentUser->role === 'superadmin') {
            return true;
        }

        $shop = PartenaireShop::find($stock->id_shop);
        if (!$shop || ($id_shop && $stock->id_shop != $id_shop)) {
            return false;
        }

        return ($currentUser->role === 'shop_gest' && $shop->id_gestionnaire === $currentUser->id_user)
            || ($currentUser->role === 'caissiere' && $shop->id_shop === $stock->id_shop);
    }

    /**
     * Met à jour la quantité d'un stock (ajout ou retrait) basé sur id_produit et id_shop.
     */
    public function update(Request $request)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $validated = $request->validate([
            'id_produit' => 'required|exists:produits,id_produit',
            'id_shop' => 'required|exists:partenaire_shops,id_shop',
            'quantite' => 'required|integer',
        ]);

        $stock = Stock::where('id_produit', $validated['id_produit'])
                      ->where('id_shop', $validated['id_shop'])
                      ->first();

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable pour ce produit et ce magasin.',
            ], 404);
        }

        if (!$this->canAccessStock($stock, $currentUser, $validated['id_shop'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à gérer ce stock.',
            ], 403);
        }

        $newQuantity = $stock->quantite + $validated['quantite'];

        if ($newQuantity < 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quantité insuffisante dans le stock.',
            ], 400);
        }

        $action = $validated['quantite'] > 0 ? 'Ajout' : 'Retrait';
        $stock->update(['quantite' => $newQuantity]);

        $this->logStockAction($stock->id_stock, $action, [
            'id_shop' => $validated['id_shop'],
            'id_produit' => $validated['id_produit'],
            'quantité_changéé' => $validated['quantite'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock mis à jour avec succès.',
            'data' => $stock,
        ], 200);
    }

    public function index(Request $request)
{
    $currentUser = Auth::user();

    // Définir les rôles autorisés pour consulter les stocks
    $allowedRoles = ['superadmin', 'shop_gest', 'admin', 'caissiere'];

    if (!in_array($currentUser->role, $allowedRoles)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
        ], 403);
    }

    // Préparer la requête de base sur le modèle Stock
    $stocksQuery = Stock::query();

    // Si l'utilisateur n'est pas superadmin, limiter l'accès aux stocks
    if ($currentUser->role !== 'superadmin') {
        if ($currentUser->role === 'shop_gest') {
            // Pour un gestionnaire de magasin, on récupère tous les magasins qu'il gère
            $shopIds = \App\Models\PartenaireShop::where('id_gestionnaire', $currentUser->id_user)
                         ->pluck('id_shop');
            $stocksQuery->whereIn('id_shop', $shopIds);
        } elseif ($currentUser->role === 'caissiere') {
            // Pour une caissière, on suppose que le magasin auquel elle est associée est stocké dans une propriété (exemple : id_shop)
            $stocksQuery->where('id_shop', $currentUser->id_shop);
        }
        // Vous pouvez ajouter d'autres restrictions si nécessaire pour le rôle 'admin'
    }

    // Mise en place de la pagination
    $perPage = $request->input('per_page', 10);
    $currentPage = $request->input('page', 1);

    // Récupérer le nombre total de stocks correspondant aux critères
    $total = $stocksQuery->count();

    // Appliquer la pagination sur la requête
    $stocks = $stocksQuery
                ->skip(($currentPage - 1) * $perPage)
                ->take($perPage)
                ->get();

    // Construire et retourner la réponse
    return response()->json([
        'status' => 'success',
        'message' => 'Liste des stocks récupérée avec succès.',
        'data' => $stocks,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
        ],
    ], 200);
}


    /**
     * Afficher les logs d'un stock.
     */
    public function logs($id_stock)
    {
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à consulter ces logs.',
            ], 403);
        }

        $logs = StockLog::where('id_stock', $id_stock)->with('user')->get();

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ], 200);
    }
}
