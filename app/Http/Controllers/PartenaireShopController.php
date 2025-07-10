<?php
namespace App\Http\Controllers;

use App\Models\QueryFiler;
use Illuminate\Http\Request;
use App\Models\PartenaireShop;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PartenaireShopController extends Controller
{
    use ApiResponseTrait;

    public function listShopsSimple(Request $request)
{
    $user = Auth::user();

    if (!in_array($user->role, ['superadmin', 'employe'])) {
        return response()->json([
            'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
        ], 403);
    }

    // Récupérer tous les shops sans relations supplémentaires.
    $shops = PartenaireShop::all();

    // Pagination manuelle
    $perPage = $request->input('per_page', 10);
    $currentPage = $request->input('page', 1);
    $paginated = $shops->slice(($currentPage - 1) * $perPage, $perPage)->values();

    // Construire la réponse paginée
    return response()->json([
        'status' => 'success',
        'data' => $paginated,
        'pagination' => [
            'total' => $shops->count(),
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($shops->count() / $perPage),
        ],
    ], 200);
}

    /**
     * Liste des partenaires shops avec filtres et tri.
     */


    // public function index(Request $request)
    // {
    //     $user = Auth::user();

    //     if (!in_array($user->role, ['superadmin', 'admin','shop_gest', 'employe'])) {
    //         return response()->json(['message' => 'Accès non autorisé.'], 403);
    //     }

    //     $query = PartenaireShop::query();

    //     // Filtres
    //     if ($request->has('nom')) {
    //         $query->where('nom', 'like', '%' . $request->input('nom') . '%');
    //     }

    //     if ($request->has('ville')) {
    //         $query->where('ville', $request->input('ville'));
    //     }

    //     if ($request->has('quartier')) {
    //         $query->where('quartier', 'like', '%' . $request->input('quartier') . '%');
    //     }

    //     // Tri
    //     if ($request->has('sort_by') && $request->has('order')) {
    //         $sortBy = $request->input('sort_by');
    //         $order = strtolower($request->input('order')) === 'desc' ? 'desc' : 'asc';
    //         $query->orderBy($sortBy, $order);
    //     } else {
    //         $query->orderBy('created_at', 'desc');
    //     }

    //     // Pagination
    //     $shops = $query->with('gestionnaire')->paginate($request->input('per_page', 10));

    //     // Formatage des données pour inclure le gestionnaire
    //     $formattedShops = $shops->map(function ($shop) {
    //         return [
    //             'id_shop' => $shop->id_shop,
    //             'nom' => $shop->nom,
    //             'adresse' => $shop->adresse,
    //             'ville' => $shop->ville,
    //             'quartier' => $shop->quartier,
    //             'logo' => $shop->logo,
    //             'created_at' => $shop->created_at,
    //             'updated_at' => $shop->updated_at,
    //             'gestionnaire' => $shop->gestionnaire ? [
    //                 'id_user' => $shop->gestionnaire->id_user,
    //                 'nom' => $shop->gestionnaire->nom,
    //                 'email' => $shop->gestionnaire->email,
    //                 'tel' => $shop->gestionnaire->tel,
    //                 'photo_profil' => $shop->gestionnaire->photo_profil,
    //             ] : null,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $formattedShops,
    //         'pagination' => [
    //             'total' => $shops->total(),
    //             'per_page' => $shops->perPage(),
    //             'current_page' => $shops->currentPage(),
    //             'last_page' => $shops->lastPage(),
    //         ],
    //     ], 200);
    // }
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'admin','shop_gest', 'employe'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $query = PartenaireShop::query()->select('id_shop','nom','adresse','ville','quartier','logo','created_at','updated_at','id_gestionnaire');

        // définir les relations qui seront aussi filtrées et leurs champs
        $relationMap = [];

        // definir les champs concerne par le filtre global
        $globalSearchFields = ['nom', 'ville','adresse','quartier'];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id_shop');
        $query = $filter->apply($query, $request)->with('gestionnaire:id_user,id_shop,nom,email,tel,role,ville,quartier');

        $shops = $query->paginate($request->get('rows', 10));
        $last_shop = collect($shops->items())->last();

        //pagination
        $response = [
            'data' => $shops->items(),
            'last_item' => $last_shop,
            'current_page' => $shops->currentPage(),
            'last_page' => $shops->lastPage(),
            'per_page' => $shops->perPage(),
            'total' => $shops->total(),
        ];

        return response()->json($response);
    }


    /**
     * Afficher un partenaire shop spécifique avec ses relations.
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'admin', 'shop_gest'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $shop = PartenaireShop::with('gestionnaire', 'caissieres')->find($id);

        if (!$shop) {
            return response()->json(['message' => 'Partenaire introuvable.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $shop,
        ], 200);
    }

    /**
     * Créer un partenaire shop avec un logo.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // Ajouter une validation pour rendre les shops uniques
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:partenaire_shops,nom,NULL,id,ville,' . $request->ville . ',quartier,' . $request->quartier,
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:100',
            'quartier' => 'required|string|max:100',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096', // Validation pour le logo
        ]);

        try {
            $logoPath = null;

            if ($request->hasFile('logo')) {
                $logoName = time() . '.' . $request->logo->extension();
                $logoPath = $request->logo->storeAs('logos/partenaire_shops', $logoName, 'public');
            }

            $validated['logo'] = $logoPath ? 'storage/' . $logoPath : null;

            $shop = PartenaireShop::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Partenaire créé avec succès.',
                'data' => $shop,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du partenaire.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Mettre à jour un partenaire shop et son logo.
     */
    public function update(Request $request, $id)
{
    $user = Auth::user();

    if (!in_array($user->role, ['superadmin', 'admin','shop_gest'])) {
        return response()->json(['message' => 'Accès non autorisé.'], 403);
    }

    $shop = PartenaireShop::find($id);

    if (!$shop) {
        return response()->json(['message' => 'Partenaire introuvable.'], 404);
    }

    $validated = $request->validate([
        'nom' => 'sometimes|required|string|max:255|unique:partenaire_shops,nom,' . $shop->id_shop . ',id_shop,ville,' . $request->ville . ',quartier,' . $request->quartier,
        'adresse' => 'sometimes|required|string|max:255',
        'ville' => 'sometimes|required|string|max:100',
        'quartier' => 'sometimes|required|string|max:100',
        'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096',
    ]);

    try {
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo
            if ($shop->logo) {
                Storage::disk('public')->delete(str_replace('storage/', '', $shop->logo));
            }

            $logoName = time() . '.' . $request->logo->extension();
            $logoPath = $request->logo->storeAs('logos/partenaire_shops', $logoName, 'public');
            $validated['logo'] = 'storage/' . $logoPath;
        }

        $shop->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Partenaire mis à jour avec succès.',
            'data' => $shop,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la mise à jour du partenaire.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Supprimer un partenaire shop et son logo.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $shop = PartenaireShop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Partenaire introuvable.'], 404);
        }

        try {
            if ($shop->logo) {
                // Supprimer le logo associé
                Storage::disk('public')->delete(str_replace('storage/', '', $shop->logo));
            }

            $shop->delete();

            return response()->json([
                'status' => 'success',
                'data' => $shop,
                'message' => 'Partenaire supprimé avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du partenaire.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
