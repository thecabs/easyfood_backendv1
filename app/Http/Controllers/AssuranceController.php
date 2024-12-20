<?php

namespace App\Http\Controllers;

use App\Models\Assurance;
use Illuminate\Http\Request;

class AssuranceController extends Controller
{
    // Afficher toutes les assurances
    public function index()
    {
        $assurances = Assurance::with('entreprises')->get();
        return response()->json($assurances, 200);
    }

    // Afficher une assurance spécifique
    public function show($id)
    {
        $assurance = Assurance::with('entreprises')->find($id);

        if (!$assurance) {
            return response()->json(['message' => 'Assurance non trouvée'], 404);
        }

        return response()->json($assurance, 200);
    }

    // Créer une nouvelle assurance
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_user' => 'required|exists:users,id', // ID utilisateur requis et doit exister dans la table users
            'code_ifc' => 'required|string|max:255',
            'libelle' => 'nullable|string|max:255',
        ]);

        $assurance = Assurance::create($validated);

        return response()->json($assurance, 201);
    }

    // Mettre à jour une assurance spécifique
    public function update(Request $request, $id)
    {
        $assurance = Assurance::find($id);

        if (!$assurance) {
            return response()->json(['message' => 'Assurance non trouvée'], 404);
        }

        $validated = $request->validate([
            'id_user' => 'sometimes|exists:users,id', // ID utilisateur facultatif mais doit exister s'il est présent
            'code_ifc' => 'sometimes|required|string|max:255',
            'libelle' => 'nullable|string|max:255',
        ]);

        $assurance->update($validated);

        return response()->json($assurance, 200);
    }

    // Supprimer une assurance spécifique
    public function destroy($id)
    {
        $assurance = Assurance::find($id);

        if (!$assurance) {
            return response()->json(['message' => 'Assurance non trouvée'], 404);
        }

        $assurance->delete();

        return response()->json(['message' => 'Assurance supprimée avec succès'], 200);
    }
}
