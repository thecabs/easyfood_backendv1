<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;

class EntrepriseController extends Controller
{
    /**
     * Liste toutes les entreprises.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $entreprises = Entreprise::with('assurance')->get();
        return response()->json($entreprises, 200);
    }

    /**
     * Affiche une entreprise spécifique.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $entreprise = Entreprise::with('assurance')->find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        return response()->json($entreprise, 200);
    }

    /**
     * Crée une nouvelle entreprise.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'nom' => 'required|string|max:255',
        'secteur_activite' => 'required|string|max:255',
        'ville' => 'required|string|max:255',
        'quartier' => 'required|string|max:255',
        'adresse' => 'required|string',
        'id_assurance' => 'required|exists:assurances,id_assurance',
        'id_user' => 'required|exists:users,id_user', // Vérifie que l'utilisateur existe
    ]);

    $entreprise = Entreprise::create($validated);

    return response()->json($entreprise, 201);
}


    /**
     * Met à jour une entreprise existante.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $entreprise = Entreprise::find($id);
    
        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }
    
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'secteur_activite' => 'sometimes|required|string|max:255',
            'ville' => 'sometimes|required|string|max:255',
            'quartier' => 'sometimes|required|string|max:255',
            'adresse' => 'sometimes|required|string',
            'id_assurance' => 'sometimes|required|exists:assurances,id_assurance',
            'id_user' => 'sometimes|required|exists:users,id_user', // Vérifie que l'utilisateur existe
        ]);
    
        $entreprise->update($validated);
    
        return response()->json($entreprise, 200);
    }
    

    /**
     * Supprime une entreprise.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $entreprise = Entreprise::find($id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée'], 404);
        }

        $entreprise->delete();

        return response()->json(['message' => 'Entreprise supprimée'], 200);
    }
}
