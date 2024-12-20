<?php

namespace App\Http\Controllers;

use App\Models\PartenaireShop;
use Illuminate\Http\Request;

class PartenaireShopController extends Controller
{
    // Afficher tous les partenaires
    public function index()
    {
        $partenaires = PartenaireShop::with(['user', 'produits', 'stocks', 'caissieres'])->get();
        return response()->json($partenaires, 200);
    }

    // Afficher un partenaire spécifique
    public function show($id)
    {
        $partenaire = PartenaireShop::with(['user', 'produits', 'stocks', 'caissieres'])->find($id);

        if (!$partenaire) {
            return response()->json(['message' => 'Partenaire non trouvé'], 404);
        }

        return response()->json($partenaire, 200);
    }

    // Créer un nouveau partenaire
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_user' => 'required|exists:users,id', // ID utilisateur requis
            'nom' => 'required|string|max:255',    // Nom du partenaire
            'adresse' => 'required|string|max:255', // Adresse complète
            'ville' => 'required|string|max:255',  // Ville
            'quartier' => 'required|string|max:255', // Quartier
        ]);

        $partenaire = PartenaireShop::create($validated);

        return response()->json($partenaire, 201);
    }

    // Mettre à jour un partenaire existant
    public function update(Request $request, $id)
    {
        $partenaire = PartenaireShop::find($id);

        if (!$partenaire) {
            return response()->json(['message' => 'Partenaire non trouvé'], 404);
        }

        $validated = $request->validate([
            'id_user' => 'sometimes|exists:users,id', // ID utilisateur facultatif
            'nom' => 'sometimes|required|string|max:255',
            'adresse' => 'sometimes|required|string|max:255',
            'ville' => 'sometimes|required|string|max:255',
            'quartier' => 'sometimes|required|string|max:255',
        ]);

        $partenaire->update($validated);

        return response()->json($partenaire, 200);
    }

    // Supprimer un partenaire
    public function destroy($id)
    {
        $partenaire = PartenaireShop::find($id);

        if (!$partenaire) {
            return response()->json(['message' => 'Partenaire non trouvé'], 404);
        }

        $partenaire->delete();

        return response()->json(['message' => 'Partenaire supprimé avec succès'], 200);
    }
}
