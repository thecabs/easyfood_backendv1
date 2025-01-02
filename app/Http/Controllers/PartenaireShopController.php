<?php
namespace App\Http\Controllers;

use App\Models\PartenaireShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PartenaireShopController extends Controller
{
    /**
     * Liste des partenaires shops avec filtres et tri.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $query = PartenaireShop::query();

        // Filtres
        if ($request->has('nom')) {
            $query->where('nom', 'like', '%' . $request->input('nom') . '%');
        }

        if ($request->has('ville')) {
            $query->where('ville', $request->input('ville'));
        }

        if ($request->has('quartier')) {
            $query->where('quartier', 'like', '%' . $request->input('quartier') . '%');
        }

        // Tri
        if ($request->has('sort_by') && $request->has('order')) {
            $sortBy = $request->input('sort_by');
            $order = strtolower($request->input('order')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortBy, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $shops = $query->with('user')->paginate($request->input('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $shops,
        ], 200);
    }

    /**
     * Afficher un partenaire shop spécifique avec ses relations.
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
     * Créer un partenaire shop avec un logo.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
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
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:4096', // Validation pour le logo
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

        if (!in_array($user->role, ['superadmin', 'administrateur'])) {
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
