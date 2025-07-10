<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Compte;
use App\Models\Facture;
use App\Models\LigneFacture;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\NewInvoiceNotification;

class FactureController extends Controller
{
    use ApiResponseTrait;
    public function createInvoice(Request $request)
    {
        $vendeur = Auth::user(); // utilisateur authentifié

        $data = $request->validate([
            'shop_id' => 'required|integer|exists:shops,shop_id',
            'user_id' => 'required|integer|exists:users,id_user',
            'total' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.id_produit' => 'required|integer|exists:produits,id_produit',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Vérification de l'appartenance au même shop
            if ($vendeur->id_shop != $data['shop_id']) {
                return response()->json(['error' => "Le vendeur n'appartient pas à ce shop."], 403);
            }

            // Récupération des informations du shop
            $shop = Shop::find($data['shop_id']);
            if (!$shop) {
                return response()->json(['error' => 'Shop introuvable.'], 404);
            }

            // Vérification des comptes
            $compteVendeur = Compte::where('id_user', $vendeur->id_user)->first();
            $compteClient = Compte::where('id_user', $data['user_id'])->first();

            if (!$compteVendeur || !$compteClient) {
                return response()->json(['error' => 'Comptes introuvables.'], 404);
            }

            // Création de la facture avec statut "pending"
            $facture = Facture::create([
                'date_facturation' => Carbon::now()->toDateString(),
                'montant' => $data['total'],
                'statut' => 'pending',
                'id_vendeur' => $vendeur->id_user,
                'id_client' => $data['user_id'],
                'shop_id' => $data['shop_id'],
            ]);

            // Création des lignes de facture
            foreach ($data['products'] as $prod) {
                LigneFacture::create([
                    'id_facture' => $facture->id_facture,
                    'id_produit' => $prod['id_produit'],
                    'quantite' => $prod['quantity'],
                ]);
            }

            Log::info('📋 Facture créée avec ID : ' . $facture->id_facture . ' - Montant : ' . $facture->montant . ' FCFA');

            // 🔹 Émettre une notification en temps réel pour le client
            event(new NewInvoiceNotification(
                $facture->id_facture,
                $facture->id_client,
                $facture->montant,
                $shop->nom
            ));

            return response()->json([
                'message' => 'Facture créée avec succès. Le client a été notifié.',
                'facture_id' => $facture->id_facture,
                'montant' => $facture->montant,
                'shop_name' => $shop->nom,
                'status' => 'pending',
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création facture : ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la création de la facture.'], 500);
        }
    }
}
