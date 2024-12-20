<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Liste tous les rôles avec leurs utilisateurs.
     */
    public function index()
    {
        $roles = Role::with('users')->get(); // Chargement des utilisateurs associés

        return response()->json([
            'status' => 'success',
            'roles' => $roles,
        ]);
    }

    /**
     * Création d'un nouveau rôle.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255|unique:roles,libelle',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'libelle' => $request->libelle,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rôle créé avec succès.',
            'role' => $role,
        ], 201);
    }

    /**
     * Mise à jour d'un rôle existant.
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'libelle' => 'required|string|max:255|unique:roles,libelle,' . $id . ',id_role',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->update([
            'libelle' => $request->libelle,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rôle mis à jour avec succès.',
            'role' => $role,
        ]);
    }

    /**
     * Suppression d'un rôle.
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rôle supprimé avec succès.',
        ]);
    }

    /**
     * Afficher les détails d'un rôle spécifique avec ses utilisateurs.
     */
    public function show($id)
    {
        $role = Role::with('users')->find($id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'role' => $role,
        ]);
    }
}
