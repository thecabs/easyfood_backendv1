<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Compte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Events\NewInvoiceNotification;

class FactureController extends Controller
{
    public function createInvoice(Request $request)
    {
        $vendeur = Auth::user(); // utilisateur authentifié

        $data = $request->all();

        // Vérification de l'appartenance au même shop
        if ($vendeur->id_shop != $data['shop_id']) {
            return response()->json(['error' => "Le vendeur n'appartient pas à ce shop."], 403);
        }

        // Création de la facture avec statut "pending"
        $facture = Facture::create([
            'date_facturation' => Carbon::now()->toDateString(),
            'montant' => $data['total'],
            'statut' => 'pending',
            'id_vendeur' => $vendeur->id_user,
            'id_client' => $data['user_id'],
            'shop_id' => $data['shop_id'], // Ajout du shop_id ici
        ]);

        if (!$facture) {
            return response()->json(['error' => 'Erreur lors de la création de la facture.'], 500);
        }

        // Création des lignes de facture
        foreach ($data['products'] as $prod) {
            LigneFacture::create([
                'id_facture' => $facture->id_facture,
                'id_produit' => $prod['id_produit'],
                'quantite' => $prod['quantity'],
            ]);
        }

        // Récupération des comptes
        $compteVendeur = Compte::where('id_user', $vendeur->id_user)->first();
        $compteClient = Compte::where('id_user', $data['user_id'])->first();

        if (!$compteVendeur || !$compteClient) {
            return response()->json(['error' => 'Comptes introuvables.'], 404);
        }

        // 🔹 Émettre une notification en temps réel pour le client
        event(new NewInvoiceNotification($facture->id_facture));

        return response()->json([
            'message' => 'Facture créée, veuillez saisir le PIN pour confirmer la transaction.',
            'facture_id' => $facture->id_facture
        ]);
    }
}
