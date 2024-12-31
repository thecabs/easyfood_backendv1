<?php

namespace App\Http\Controllers;

use App\Models\PartenaireShop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartenaireShopController extends Controller
{
    /**
     * Liste des partenaires shops.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
    
        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
    
        // Appliquer des filtres facultatifs
        $query = PartenaireShop::query();
    
        // Filtrage par nom, ville ou quartier
        if ($request->has('nom')) {
            $query->where('nom', 'like', '%' . $request->input('nom') . '%');
        }
    
        if ($request->has('ville')) {
            $query->where('ville', $request->input('ville'));
        }
    
        if ($request->has('quartier')) {
            $query->where('quartier', 'like', '%' . $request->input('quartier') . '%');
        }
    
        // Tri par colonne et ordre
        if ($request->has('sort_by') && $request->has('order')) {
            $sortBy = $request->input('sort_by');
            $order = strtolower($request->input('order')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortBy, $order);
        } else {
            $query->orderBy('created_at', 'desc'); // Tri par défaut
        }
    
        // Récupération des données paginées avec les relations nécessaires
        $shops = $query->with('user')->paginate($request->input('per_page', 10));
    
        return response()->json([
            'status' => 'success',
            'data' => $shops,
        ], 200);
    }
    

    /**
     * Afficher un partenaire shop spécifique.
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'administrateur', 'partenaire_shop_gest'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $shop = PartenaireShop::with('user', 'caissieres')->find($id);

        if (!$shop) {
            return response()->json(['message' => 'Partenaire introuvable.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $shop,
        ], 200);
    }

    /**
     * Créer un partenaire shop.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
    
        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
    
        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:100',
            'quartier' => 'required|string|max:100',
        ]);
    
        // Création du shop sans gestionnaire initial
        $shop = PartenaireShop::create($validated);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Partenaire créé avec succès. Gestionnaire non encore associé.',
            'data' => $shop,
        ], 201);
    }
    

    /**
     * Mettre à jour un partenaire shop.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $shop = PartenaireShop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Partenaire introuvable.'], 404);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'adresse' => 'sometimes|required|string|max:255',
            'ville' => 'sometimes|required|string|max:100',
            'quartier' => 'sometimes|required|string|max:100',
        ]);

        $shop->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Partenaire mis à jour avec succès.',
            'data' => $shop,
        ], 200);
    }

    /**
     * Supprimer un partenaire shop.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $shop = PartenaireShop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Partenaire introuvable.'], 404);
        }

        $shop->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Partenaire supprimé avec succès.',
        ], 200);
    }
}
