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
    private function canAccessStock($stock, $currentUser)
    {
        // Superadmin peut tout gérer
        if ($currentUser->role === 'superadmin') {
            return true;
        }

        // Vérifier si l'utilisateur est gestionnaire ou caissier du shop
        $shop = PartenaireShop::find($stock->id_shop);
        if (!$shop) {
            return false;
        }

        return ($currentUser->role === 'partenaire_shop_gest' && $shop->id_gestionnaire === $currentUser->id_user)
            || ($currentUser->role === 'caissiere' && $shop->id_partenaire === $stock->id_shop);
    }

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

        // Récupérer uniquement les stocks que l'utilisateur peut gérer
        $stocks = Stock::with('produit')->get()->filter(function ($stock) use ($currentUser) {
            return $this->canAccessStock($stock, $currentUser);
        });

        return response()->json([
            'status' => 'success',
            'data' => $stocks->values(),
        ], 200);
    }

    /**
     * Afficher un stock spécifique.
     */
    public function show($id)
    {
        $currentUser = Auth::user();
        $stock = Stock::with('produit')->find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        if (!$this->canAccessStock($stock, $currentUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à ce stock.',
            ], 403);
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

        if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest'])) {
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

        // Vérifier que l'utilisateur peut gérer le shop
        $shop = PartenaireShop::find($validated['id_shop']);
        if (!$shop || ($shop->id_gestionnaire !== $currentUser->id_user && $currentUser->role !== 'superadmin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à gérer ce shop.',
            ], 403);
        }

        $stock = Stock::create($validated);

        // Journalisation
        $this->logStockAction($stock->id_stock, 'create', $validated);
        
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
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        if (!$this->canAccessStock($stock, $currentUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à mettre à jour ce stock.',
            ], 403);
        }

        $validated = $request->validate([
            'quantite' => 'required|integer|min:0',
        ]);

        $stock->update($validated);
// Journalisation
$this->logStockAction($stock->id_stock, 'update', $validated);

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
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock introuvable.',
            ], 404);
        }

        if (!$this->canAccessStock($stock, $currentUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce stock.',
            ], 403);
        }

        $stock->delete();
// Journalisation
$this->logStockAction($stock->id_stock, 'delete');

        return response()->json([
            'status' => 'success',
            'data' => $stock,

            'message' => 'Stock supprimé avec succès.',
        ], 200);
    }

    public function logs($id_stock)
{
    $currentUser = Auth::user();

    if (!in_array($currentUser->role, ['superadmin', 'partenaire_shop_gest', 'administrateur'])) {
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
