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
    public function index(Request $request)
    {
        $currentUser = Auth::user();
    
        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
    
        // Construction de la requête des produits
        $produitsQuery = Produit::with(['categorie', 'partenaire', 'stock', 'images']);
    
        if (in_array($currentUser->role, ['shop_gest', 'caissiere'])) {
            // Limiter les produits au shop de l'utilisateur
            $produitsQuery->where('id_shop', $currentUser->id_shop); // Assurez-vous que 'id_shop' est correct
        }
    
        // Pagination des résultats
        $perPage = $request->input('per_page', 10);
        $produits = $produitsQuery->paginate($perPage);
    
        // Formatage des données produits
        $formattedProduits = $produits->getCollection()->transform(function ($produit) {
            return [
                'id_produit' => $produit->id_produit,
                'nom' => $produit->nom,
                'categorie' => $produit->categorie->libelle ?? null,
                'prix_ifc' => $produit->prix_ifc,
                'prix_shop' => $produit->prix_shop,
                'statut' => $produit->statut,
                'code_barre' => $produit->code_barre,
                'quantite_disponible' => $produit->stock->quantite ?? 0,
                'shop' => [
                    'id_shop' => $produit->partenaire->id_shop ?? null,
                    'nom' => $produit->partenaire->nom ?? null,
                    'logo' => $produit->partenaire->logo ?? null,
                    'ville' => $produit->partenaire->ville ?? null,
                    'quartier' => $produit->partenaire->quartier ?? null,
                ],
                'photos' => $produit->images,
            ];
        });
    
        // Retour de la réponse paginée
        return response()->json([
            'status' => 'success',
            'data' => $formattedProduits,
            'pagination' => [
                'total' => $produits->total(),
                'per_page' => $produits->perPage(),
                'current_page' => $produits->currentPage(),
                'last_page' => $produits->lastPage(),
            ],
        ], 200);
    }
    

    /**
     * Affiche un produit spécifique.
     */
    public function show($id)
{
    $currentUser = Auth::user();

    // Vérification des autorisations
    if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    // Charger les relations nécessaires
    $produit = Produit::with(['categorie.shop', 'partenaire', 'stock', 'images'])->find($id);

    // Vérification si le produit existe
    if (!$produit) {
        return response()->json([
            'status' => 'error',
            'message' => 'Produit introuvable.',
        ], 404);
    }

    // Construire la réponse JSON
    return response()->json([
        'status' => 'success',
        'data' => [
            'id_produit' => $produit->id_produit,
            'nom' => $produit->nom,
            'categorie' => [
                'id_categorie' => $produit->categorie->id ?? null,
                'libelle' => $produit->categorie->libelle ?? null,
            ],
            'prix_ifc' => $produit->prix_ifc,
            'prix_shop' => $produit->prix_shop,
            'statut' => $produit->statut,
            'code_barre' => $produit->code_barre,
            'quantite_disponible' => $produit->stock->quantite ?? 0,
            'photos' => $produit->images->map(function ($image) {
                return $image;
            }),
            'shop' => [
                'nom' => $produit->partenaire->nom ?? null,
                'logo' => $produit->partenaire->logo ?? null,
            ],
            'categorie' 
        ],
    ], 200);
}


    
    /**
     * Création d'un produit.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'admin', 'shop_gest'])) {
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
            'id_shop' => 'required|exists:partenaire_shops,id_shop',
            'statut' => 'required|string',
            'code_barre' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = Produit::where('code_barre', $value)
                        ->where('id_shop', $request->id_shop)
                        ->exists();
                    if ($exists) {
                        $fail('Le code-barre existe déjà pour ce magasin.');
                    }
                },
            ],
        ]);
    
        $produit = Produit::create($validated);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Produit créé avec succès.',
            'data' => $produit,
        ], 201);
    }
    
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
    
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest','admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }
    
        $produit = Produit::find($id);
    
        if (!$produit || ($currentUser->role === 'shop_gest' && $produit->id_shop !== $currentUser->id_user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable ou non autorisé.',
            ], 403);
        }
    
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'id_categorie' => 'nullable|exists:categories,id',
            'prix_ifc' => 'nullable|numeric|min:0',
            'prix_shop' => 'nullable|numeric|min:0',
            'statut' => 'nullable|string',
            'code_barre' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $produit) {
                    $exists = Produit::where('code_barre', $value)
                        ->where('id_shop', $request->id_shop ?? $produit->id_shop)
                        ->where('id_produit', '!=', $produit->id_produit)
                        ->exists();
                    if ($exists) {
                        $fail('Ce code-barre existe déjà pour ce magasin.');
                    }
                },
            ],
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

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest','admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $produit = Produit::find($id);

        if (!$produit || ($currentUser->role === 'shop_gest' && $produit->id_shop !== $currentUser->id_user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable ou non autorisé.',
            ], 403);
        }

        if ($currentUser->role === 'caissiere') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce produit.',
            ], 403);
        }

        $produit->delete();

        return response()->json([
            'status' => 'success',
            'data' => $produit,
            'message' => 'Produit supprimé avec succès.',
        ], 200);
    }
}
