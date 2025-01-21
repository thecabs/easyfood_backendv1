<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;

class ProductFeaturesController extends Controller
{
    /**
     * Ajoute une image à un produit.
     */
    public function store(Request $request, $id_produit)
    {
        $currentUser = Auth::user();

        // Vérification des rôles
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Vérifier si le produit existe
        $produit = Produit::find($id_produit);
        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable.',
            ], 404);
        }

        // Si l'utilisateur n'est pas superadmin, vérifier qu'il gère le shop du produit
        if ($currentUser->role !== 'superadmin') {
            $shop = $produit->shop; // Relation à ajouter dans le modèle Produit
            if (!$shop || $shop->id_gestionnaire !== $currentUser->id_user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous n\'êtes pas autorisé à ajouter une image à ce produit.',
                ], 403);
            }
        }

        // Validation des données
        $request->validate([
            'photo' => 'required|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        try {
            // Téléchargement de l'image
            $photoName = time() . '.' . $request->photo->extension();
            $filePath = $request->photo->storeAs('images', $photoName, 'public');

            // Création de l'image
            $image = new Image();
            $image->url_photo = 'storage/' . $filePath; // Chemin accessible publiquement
            $produit->images()->save($image);

            

            return response()->json([
                'status' => 'success',
                'message' => 'Image ajoutée avec succès.',
                'data' => $image,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajout de l\'image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste des images d'un produit.
     */
    public function listImages($id_produit)
    {
        $currentUser = Auth::user();

        // Vérification des rôles
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Vérifier si le produit existe
        $produit = Produit::find($id_produit);
        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit introuvable.',
            ], 404);
        }

        // Si l'utilisateur n'est pas superadmin, vérifier qu'il gère le shop du produit
        if ($currentUser->role !== 'superadmin') {
            $shop = $produit->shop; // Relation à ajouter dans le modèle Produit
            if (!$shop || $shop->id_gestionnaire !== $currentUser->id_user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous n\'êtes pas autorisé à voir les images de ce produit.',
                ], 403);
            }
        }

        // Liste des images du produit
        $images = $produit->images; // Relation à ajouter dans le modèle Produit

        return response()->json([
            'status' => 'success',
            'data' => $images,
        ], 200);
    }

    /**
     * Supprime une image.
     */
    public function deleteImage($id_image)
    {
        $currentUser = Auth::user();

        // Vérification des rôles
        if (!in_array($currentUser->role, ['superadmin', 'shop_gest'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        // Vérifier si l'image existe
        $image = Image::find($id_image);
        if (!$image) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image introuvable.',
            ], 404);
        }

        // Vérifier si l'utilisateur est autorisé à supprimer cette image
        $produit = $image->produit;
        if (!$produit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit associé introuvable.',
            ], 404);
        }

        if ($currentUser->role !== 'superadmin') {
            $shop = $produit->shop; // Relation à ajouter dans le modèle Produit
            if (!$shop || $shop->id_gestionnaire !== $currentUser->id_user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous n\'êtes pas autorisé à supprimer cette image.',
                ], 403);
            }
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image supprimée avec succès.',
            'data'=> $image

        ], 200);
    }
}
