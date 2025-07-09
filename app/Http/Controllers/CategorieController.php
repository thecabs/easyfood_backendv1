<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\VerifRole;
use App\Models\QueryFiler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CategorieController extends Controller
{
    /**
     * Liste des catégories accessibles par l'utilisateur.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        // Vérification des permissions
        if (!in_array($user->role, ['superadmin', 'shop_gest', 'admin', 'caissiere', 'employe'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
        }

        if ($verifRole->isShop() or $verifRole->isCaissiere()) {
            $query = Categorie::query()->where('id_shop', $user->id_shop);
        } else {
            $query = Categorie::query();
        }
        //filtrage global
        if ($request->filled('filters.global.value')) {
            $value = $request->input('filters.global.value');
            $query->where(function ($q) use ($value) {
                $q->where('libelle', 'like', "%$value%");
            });
        }

        // definir les relations qui seront aussi filtree et leurs champs
        $relationMap = [
            'shop' => 'shop.nom'
        ];
        // definir les champs concerne par le filtre global
        $globalSearchFields = ['libelle'];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id',['id_shop']);
        $query = $filter->apply($query, $request);

        $categories = $query->with([
            'shop:id_shop,nom,logo',
        ])->paginate($request->get('rows', 10));
        $last_categorie = collect($categories->items())->last();
        //pagination
        $response = [
            'data' => $categories->items(),
            'last_item' => $last_categorie,
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'per_page' => $categories->perPage(),
            'total' => $categories->total(),
        ];

        return $this->successResponse($response);
    }
    /**
     * Création d'une nouvelle catégorie pour un shop.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Vérification des permissions
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
        }

        $validated = $request->validate([
            'libelle' => ['required', 'string', Rule::unique('categories')->where(function ($query) {
                $user =  Auth::user();
                $query->where('id_shop', $user->id_shop);
            })],
            'id_shop' => 'required|exists:partenaire_shops,id_shop',
        ]);

        $categorie = Categorie::create([
            'libelle' => $validated['libelle'],
            'id_shop' => $currentUser->role === 'shop_gest' ? $currentUser->id_shop : $request->id_shop,
        ]);

        // Charger la relation shop pour renvoyer la catégorie avec son shop associé
        $categorie->load('shop');

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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin', 'caissiere'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
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

        // Charger la relation shop après la mise à jour
        $categorie->load('shop');

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
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à effectuer cette action.',403);
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
