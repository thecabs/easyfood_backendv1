<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Compte;
use Carbon\Carbon;
use PDF; 
use Illuminate\Support\Facades\Log;

use App\Models\Facture;
use App\Events\NewInvoiceNotification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Effectuer un transfert entre deux comptes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function operation(Request $request)
    {
        // Validation de la requête avec les deux numéros de compte, le montant et le type d'opération
        $validated = $request->validate([
            'numero_compte_src'  => 'required|exists:comptes,numero_compte',
            'numero_compte_dest' => 'required|exists:comptes,numero_compte|different:numero_compte_src',
            'montant'            => 'required|numeric|min:0.01',
            'type'               => 'required|string|in:debit,credit',
        ]);
    
        // Vérification des autorisations (adapter selon vos besoins)
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['admin', 'superadmin', 'entreprise_gest'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette opération.'
            ], 403);
        }
    
        DB::beginTransaction();
    
        try {
            // Récupération des comptes source et destination
            $compteSrc  = Compte::where('numero_compte', $validated['numero_compte_src'])->firstOrFail();
            $compteDest = Compte::where('numero_compte', $validated['numero_compte_dest'])->firstOrFail();
    
            // Traitement selon le type d'opération
            if ($validated['type'] === 'debit') {
                // Pour un débit : le compte source est débité et le compte destination est crédité
                if ($compteSrc->solde < $validated['montant']) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Solde insuffisant sur le compte source pour effectuer un débit.'
                    ], 400);
                }
    
                $compteSrc->solde  -= $validated['montant'];
                $compteDest->solde += $validated['montant'];
            } elseif ($validated['type'] === 'credit') {
                // Pour un crédit : le compte destination est débité et le compte source est crédité
                if ($compteDest->solde < $validated['montant']) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Solde insuffisant sur le compte destination pour effectuer un crédit.'
                    ], 400);
                }
    
                $compteDest->solde -= $validated['montant'];
                $compteSrc->solde  += $validated['montant'];
            }
    
            // Sauvegarde des modifications sur les comptes
            $compteSrc->save();
            $compteDest->save();
    
            // Enregistrement de la transaction
            $transaction = Transaction::create([
                'numero_compte_src'  => $validated['numero_compte_src'],
                'numero_compte_dest' => $validated['numero_compte_dest'],
                'montant'            => $validated['montant'],
                'type'               => $validated['type'],
                'date'               => now(),
            ]);
    
            DB::commit();
    
            return response()->json([
                'status'      => 'success',
                'message'     => 'Opération effectuée avec succès.',
                'transaction' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'status'  => 'error',
                'message' => 'Une erreur est survenue lors de l\'opération.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    // public function confirmTransaction(Request $request)
    // {
    //     $client = $request->user(); // utilisateur authentifié (client)
    //     $hashedPin = $request->input('hashed_pin');
    //     $facture_id = $request->input('facture_id');

    //     if (!$facture_id) {
    //         return response()->json(['error' => 'Facture ID manquant.'], 400);
    //     }

    //     $compteClient = Compte::where('id_user', $client->id_user)->first();
    //     if (!$compteClient) {
    //         return response()->json(['error' => 'Compte client introuvable.'], 404);
    //     }

    //     // Comparaison du PIN haché (le PIN stocké doit être préalablement haché et, idéalement, salé)
    //     if ($hashedPin !== $compteClient->pin) {
    //         return response()->json(['error' => 'PIN invalide.'], 403);
    //     }

    //     $facture = Facture::find($facture_id);
    //     if (!$facture) {
    //         return response()->json(['error' => 'Facture introuvable.'], 404);
    //     }

    //     // Mise à jour du statut de la facture
    //     $facture->statut = 'confirmed';
    //     $facture->save();

    //     $compteVendeur = Compte::where('id_user', $facture->id_vendeur)->first();
    //     if (!$compteVendeur) {
    //         return response()->json(['error' => 'Compte vendeur introuvable.'], 404);
    //     }

    //     // Création de la transaction
    //     Transaction::create([
    //         'numero_compte_src' => $compteClient->numero_compte,
    //         'numero_compte_dest' => $compteVendeur->numero_compte,
    //         'montant' => $facture->montant,
    //         'date' => Carbon::now(),
    //         'type' => 'transfert',
    //     ]);

    //     // Génération du PDF avec Dompdf
    //     $data = [
    //         'facture' => $facture,
    //         'client' => $compteClient,
    //         'vendeur' => $compteVendeur,
    //     ];
    //     $pdf = PDF::loadView('pdf.invoice', $data);
    //     // Sauvegarde du PDF sur le serveur
    //     $pdfPath = storage_path("app/invoices/{$facture->id_facture}.pdf");
    //     $pdf->save($pdfPath);

    //     return response()->json([
    //         'message' => 'Transaction confirmée et facture générée.',
    //         'pdf_url' => url("invoices/{$facture->id_facture}.pdf")
    //     ]);
    // }
    public function confirmTransaction(Request $request)
    {
        $client = $request->user(); // Utilisateur authentifié (client)
        
        $validatedData = $request->validate([
            'hashed_pin' => 'required|string',
            'facture_id' => 'required|integer|exists:factures,id_facture',
        ]);

        $hashedPin = $validatedData['hashed_pin'];
        $factureId = $validatedData['facture_id'];

        try {
            // Vérification du compte client
            $compteClient = Compte::where('id_user', $client->id_user)->first();
            if (!$compteClient) {
                return response()->json(['error' => 'Compte client introuvable.'], 404);
            }

            // Vérification du PIN
            if ($hashedPin !== $compteClient->pin) {
                Log::warning('🚫 Tentative de PIN invalide pour client ID : ' . $client->id_user);
                return response()->json(['error' => 'PIN invalide.'], 403);
            }

            // Récupération de la facture
            $facture = Facture::with('shop')->find($factureId);
            if (!$facture) {
                return response()->json(['error' => 'Facture introuvable.'], 404);
            }

            // Vérification du statut de la facture
            if ($facture->statut !== 'pending') {
                return response()->json(['error' => 'Cette facture n\'est plus en attente.'], 400);
            }

            // Vérification que c'est bien le client de cette facture
            if ($facture->id_client !== $client->id_user) {
                return response()->json(['error' => 'Accès non autorisé à cette facture.'], 403);
            }

            // Vérification du solde client
            if ($compteClient->solde < $facture->montant) {
                return response()->json(['error' => 'Solde insuffisant.'], 400);
            }

            // Récupération du compte vendeur
            $compteVendeur = Compte::where('id_user', $facture->id_vendeur)->first();
            if (!$compteVendeur) {
                return response()->json(['error' => 'Compte vendeur introuvable.'], 404);
            }

            // Début de la transaction
            \DB::beginTransaction();

            try {
                // Mise à jour des soldes
                $compteClient->solde -= $facture->montant;
                $compteVendeur->solde += $facture->montant;
                $compteClient->save();
                $compteVendeur->save();

                // Mise à jour du statut de la facture
                $facture->statut = 'confirmed';
                $facture->date_confirmation = Carbon::now();
                $facture->save();

                // Création de la transaction
                Transaction::create([
                    'numero_compte_src' => $compteClient->numero_compte,
                    'numero_compte_dest' => $compteVendeur->numero_compte,
                    'montant' => $facture->montant,
                    'date' => Carbon::now(),
                    'type' => 'transfert',
                    'facture_id' => $facture->id_facture,
                ]);

                // Génération du PDF
                $shopName = $facture->shop->nom;
                $directory = storage_path("app/public/invoices/$shopName");
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                $invoiceFileName = "facture_{$facture->id_facture}_" . date('YmdHis') . ".pdf";
                $pdfPath = "$directory/$invoiceFileName";

                $data = [
                    'facture' => $facture,
                    'client' => $compteClient,
                    'vendeur' => $compteVendeur,
                    'shop' => $facture->shop,
                    'date_confirmation' => $facture->date_confirmation,
                ];

                $pdf = PDF::loadView('pdf.invoice', $data);
                $pdf->save($pdfPath);

                \DB::commit();

                Log::info('✅ Transaction confirmée - Facture ID : ' . $facture->id_facture . ' - Montant : ' . $facture->montant . ' FCFA');

                // 🔹 Émettre notification vers la caissière
                event(new InvoiceConfirmedNotification(
                    $facture->id_vendeur, 
                    $shopName, 
                    $invoiceFileName, 
                    $facture->id_facture, 
                    $facture->montant
                ));

                return response()->json([
                    'message' => 'Transaction confirmée avec succès.',
                    'pdf_url' => url("storage/invoices/$shopName/$invoiceFileName"),
                    'facture_id' => $facture->id_facture,
                    'montant' => $facture->montant,
                    'nouveau_solde' => $compteClient->solde,
                ], 200);

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur confirmation transaction : ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la confirmation de la transaction.'], 500);
        }
    }
}

    
    
