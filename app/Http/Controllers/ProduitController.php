<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\VerifRole;
use Illuminate\Support\Facades\Auth;

class ProduitController extends Controller
{
    // RECHERCHER PRODUIT POUR L'employé
    public function rechercherProduit(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des autorisations
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere','employe','employe'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Validation des paramètres de la requête
        $request->validate([
            'id_shop'    => 'required|integer',
            'code_barre' => 'required|string',
        ]);

        // Recherche du produit avec condition sur la quantité > 0
        $produit = Produit::with(['categorie', 'partenaire', 'stock', 'images'])
            ->where('code_barre', $request->code_barre)
            ->whereHas('categorie.shop', function ($query) use ($request) {
                $query->where('id', $request->id_shop);
            })
            ->whereHas('stock', function ($query) {
                $query->where('quantite', '>', 0);
            })
            ->first();

        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable pour ce magasin ou indisponible.',
            ], 404);
        }

        // Construction de la réponse JSON
        return response()->json([
            'status' => 'success',
            'data' => [
                'id_produit'          => $produit->id_produit,
                'nom'                 => $produit->nom,
                'categorie'           => [
                    'id' => $produit->categorie->id ?? null,
                    'libelle'      => $produit->categorie->libelle ?? null,
                ],
                'prix_ifc'            => $produit->prix_ifc,
                'prix_shop'           => $produit->prix_shop,
                'statut'              => $produit->statut,
                'code_barre'          => $produit->code_barre,
                'quantite_disponible' => $produit->stock->quantite ?? 0,
               'photos' => $produit->images->map(function ($image) {
    return [
        'url' => $image->url_photo, // Utilise le bon nom de colonne
        'alt' => '', // Ou, si vous ajoutez un champ alt dans le futur, utilisez-le ici
    ];
}),

                'shop' => [
                    'shopId'  => $produit->partenaire->id_shop ?? null,

                    'nom'  => $produit->partenaire->nom ?? null,
                    'logo' => $produit->partenaire->logo ?? null,
                ],
            ],
        ], 200);
    }

    /**
     * Liste des produits.
     */
    // public function index(Request $request)
    // {
    //     $currentUser = Auth::user();

    //     // Vérification des permissions
    //     if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    //         ], 403);
    //     }

    //     // Construction de la requête des produits
    //     $produitsQuery = Produit::with(['categorie', 'partenaire', 'stock', 'images']);

    //     if (in_array($currentUser->role, ['shop_gest', 'caissiere'])) {
    //         // Limiter les produits au shop de l'utilisateur
    //         $produitsQuery->where('id_shop', $currentUser->id_shop); // Assurez-vous que 'id_shop' est correct
    //     }

    //     // Pagination des résultats
    //     $perPage = $request->input('per_page', 10);
    //     $produits = $produitsQuery->paginate($perPage);

    //     // Formatage des données produits
    //     $formattedProduits = $produits->getCollection()->transform(function ($produit) {
    //         return [
    //             'id_produit' => $produit->id_produit,
    //             'nom' => $produit->nom,
    //             'categorie' => $produit->categorie,
    //             'prix_ifc' => $produit->prix_ifc,
    //             'prix_shop' => $produit->prix_shop,
    //             'statut' => $produit->statut,
    //             'code_barre' => $produit->code_barre,
    //             'created_at' => $produit->created_at,
    //             'updated_at' => $produit->updated_at,
    //             'quantite_disponible' => $produit->stock->quantite ?? 0,
    //             'shop' => [
    //                 'id_shop' => $produit->partenaire->id_shop ?? null,
    //                 'nom' => $produit->partenaire->nom ?? null,
    //                 'logo' => $produit->partenaire->logo ?? null,
    //                 'ville' => $produit->partenaire->ville ?? null,
    //                 'quartier' => $produit->partenaire->quartier ?? null,
    //             ],
    //             'photos' => $produit->images,
    //         ];
    //     });

    //     // Retour de la réponse paginée
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $formattedProduits,
    //         'pagination' => [
    //             'total' => $produits->total(),
    //             'per_page' => $produits->perPage(),
    //             'current_page' => $produits->currentPage(),
    //             'last_page' => $produits->lastPage(),
    //         ],
    //     ], 200);
    // }
    public function index(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole;

        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Construction de la requête des produits
        $query = Produit::with(['categorie', 'partenaire', 'stock', 'images']);

        if (in_array($user->role, ['shop_gest', 'caissiere'])) {
            // Limiter les produits au shop de l'utilisateur
            $query->where('id_shop', $user->id_shop); // Assurez-vous que 'id_shop' est correct
        }


            //filtrage global
            if ($request->filled('filters.global.value')) {
                $value = $request->input('filters.global.value');
                $query->where(function ($q) use ($value) {
                    $q->where('nom', 'like', "%$value%")
                        ->orWhere('code_barre', 'like', "%$value%");
                });
            }

            // filtrage par champs
            foreach ($request->input('filters', []) as $field => $filter) {
                if ($field === 'global') continue;

                $operator = $filter['operator'] ?? 'and';
                $constraints = $filter['constraints'] ?? [];
                if ($field === 'categorie') {
                    $query->whereHas('categorie',function ($q) use ($constraints, $field, $operator) {
                        foreach ($constraints as $rule) {
                            $value = $rule['value'] ?? null;
                            $mode = $rule['matchMode'] ?? 'contains';

                            if (is_null($value)) continue;

                            $clause = match ($mode) {
                                'startsWith' => ['libelle', 'like', $value . '%'],
                                'endsWith'   => ['libelle', 'like', '%' . $value],
                                'contains'   => ['libelle', 'like', '%' . $value . '%'],
                                'equals'     => ['libelle', '=', $value],
                                'notEquals'  => ['libelle', '!=', $value],
                                'in'         => ['libelle', $value],
                                default      => null
                            };

                            if (!$clause) continue;

                            $operator === 'or'
                                ? $q->orWhere(...$clause)
                                : $q->where(...$clause);
                        }
                    });
                }else if ($field === 'shop') {
                    $query->whereHas('shop',function ($q) use ($constraints, $field, $operator) {
                        foreach ($constraints as $rule) {
                            $value = $rule['value'] ?? null;
                            $mode = $rule['matchMode'] ?? 'contains';

                            if (is_null($value)) continue;

                            $clause = match ($mode) {
                                'startsWith' => ['nom', 'like', $value . '%'],
                                'endsWith'   => ['nom', 'like', '%' . $value],
                                'contains'   => ['nom', 'like', '%' . $value . '%'],
                                'equals'     => ['nom', '=', $value],
                                'notEquals'  => ['nom', '!=', $value],
                                'in'         => ['nom', $value],
                                default      => null
                            };

                            if (!$clause) continue;

                            $operator === 'or'
                                ? $q->orWhere(...$clause)
                                : $q->where(...$clause);
                        }
                    });
                }else{
                    $query->where(function ($q) use ($constraints, $field, $operator) {
                        foreach ($constraints as $rule) {
                            $value = $rule['value'] ?? null;
                            $mode = $rule['matchMode'] ?? 'contains';

                            if (is_null($value)) continue;

                            $clause = match ($mode) {
                                'startsWith' => [$field, 'like', $value . '%'],
                                'endsWith'   => [$field, 'like', '%' . $value],
                                'contains'   => [$field, 'like', '%' . $value . '%'],
                                'equals'     => [$field, '=', $value],
                                'notEquals'  => [$field, '!=', $value],
                                'in'         => [$field, $value],
                                default      => null
                            };

                            if (!$clause) continue;

                            $operator === 'or'
                                ? $q->orWhere(...$clause)
                                : $q->where(...$clause);
                        }
                    });
                }


            }
            // recuperer les produits d'un shop
            if ($request->filled('id_shop')) {
                $query->where('id_shop', 'like', '%' . $request->id_shop . '%');
            }

            //tri
            if ($request->filled('sortField') && $request->filled('sortOrder')) {
                $direction = $request->sortOrder == -1 ? 'desc' : 'asc';
                $query->orderBy($request->sortField, $direction);
            } else {
                $query->orderBy('id_produit', 'desc');
            }
            $produits = $query->with([
                'shop','categorie'
            ])->paginate($request->get('rows', 10));
            $last_produit = collect($produits->items())->last();
            //pagination
            $response = [
                'data' => $produits->items(),
                'last_item' => $last_produit,
                'current_page' => $produits->currentPage(),
                'last_page' => $produits->lastPage(),
                'per_page' => $produits->perPage(),
                'total' => $produits->total(),
            ];

            return response()->json($response);

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
                    'id' => $produit->categorie->id ?? null,
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

    // Vérification des rôles autorisés
    if (!in_array($currentUser->role, ['superadmin', 'admin', 'shop_gest'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    // Validation des données entrantes
    $validated = $request->validate([
        'nom' => 'required|string|max:255',
        'id' => 'required|exists:categories,id',
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

    // Création du produit
    $produit = Produit::create([
        'id_categorie' => $validated['id'],
        'nom' => $validated['nom'],
        'prix_ifc' => $validated['prix_ifc'],
        'prix_shop' => $validated['prix_shop'],
        'statut' => $validated['statut'],
        'id_shop' => $validated['id_shop'],
        'code_barre' => $validated['code_barre'],
    ]);

    // Création du stock associé avec une quantité initiale de zéro
    $stock = Stock::create([
        'id_produit' => $produit->id_produit,
        'quantite' => 0, // Quantité initiale nulle
        'id_shop' => $validated['id_shop'],
    ]);

    // Charger les relations categorie et shop pour le produit créé
    $produit->load('categorie', 'shop');

    return response()->json([
        'status' => 'success',
        'message' => 'Produit et stock créés avec succès.',
        'data' => [
            'produit' => $produit,
            'stock' => $stock,
        ],
    ], 201);
}

public function update(Request $request, $id)
{
    $currentUser = Auth::user();

    if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        ], 403);
    }

    $produit = Produit::find($id);
    if (!$produit || ($currentUser->role === 'shop_gest' && $produit->id_shop !== $currentUser->id_shop)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Produit introuvable ou non autorisé.',
        ], 403);
    }

    $validated = $request->validate([
        'nom' => [
            'nullable',
            'string',
            'max:255',
            function ($attribute, $value, $fail) use ($request, $produit) {
                $exists = Produit::where('nom', $value)
                    ->where('id_shop', $request->id_shop ?? $produit->id_shop)
                    ->where('id_produit', '!=', $produit->id_produit)
                    ->exists();
                if ($exists) {
                    $fail('Ce produit existe déjà pour ce magasin.');
                }
            },
        ],
        'id' => 'nullable|exists:categories,id',
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

    // Charger les relations categorie et shop après la mise à jour
    $produit->load('categorie', 'shop');

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

        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $produit = Produit::find($id);

        // if (!$produit || ($currentUser->role === 'shop_gest' && $produit->id_shop !== $currentUser->id_user)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Produit introuvable ou non autorisé.',
        //     ], 403);
        // }

        if (!$produit || ($currentUser->role === 'shop_gest' && $produit->id_shop !== $currentUser->id_shop)) {
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
