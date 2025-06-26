<?php

namespace App\Http\Controllers;

use App\Events\NewTransactionNotification;
use Exception;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TypeTransaction;
use App\Models\VerifRole;
use App\Notifications\TransactionReçue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Enum;

class TransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user = User::find($user->id_user);
        $transactions = Transaction::getAll($user->id_user)->get();

        return response()->json([
            'status' => "success",
            'data' => $transactions,
            'message' => "transactions récupérée avec succès!"
        ], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        if(!$verifRole->isAdmin()){
            return response()->json([
                'status' => "error",
                'message' => "Vous n'avez pas les droits pour effectuer cette action."
            ], 403);
        }
        $validated = $request->validate([
            'id_compte_destinataire' => 'required|exists:comptes,id_compte',
            'montant' => 'required|numeric|min:0',
            'pin' => 'required|digits:4',
            'type' => ['required', new Enum(TypeTransaction::class)],
        ]);

        $compteEmetteur = $user->compte;
        $compteDestinataire = Compte::where("id_compte",$validated['id_compte_destinataire'])->first();

        if (!$compteDestinataire) {
            return response()->json([
                'status' => "error",
                'message' => "Le compte destinataire n'existe pas."
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Vérifier le PIN
            if (!Hash::check($validated['pin'], $compteEmetteur->pin) ) {
                return response()->json([
                    'status' => "error",
                    'message' => "Le PIN est incorrect."
                ], 400);
            }

            // Vérifier si le solde est suffisant
            if ($compteEmetteur->solde < $validated['montant']) {
                return response()->json([
                    'status' => "error",
                    'message' => "Le solde du compte émetteur est insuffisant."
                ], 400);
            }

            // Vérifier si le compte destinataire est le même que l'émetteur
            if ($compteEmetteur->id_compte === $compteDestinataire->id_compte) {
                return response()->json([
                    'status' => "error",
                    'message' => "Le compte destinataire ne peut pas être le même que le compte émetteur."
                ], 400);
            }

            //decrementer le solde du compte emetteur
            $compteEmetteur->solde -= $validated['montant'];

            //incrementer le solde du compte destinataire
            $compteDestinataire->solde += $validated['montant'];

            // Enregistrer les modifications
            $compteDestinataire->save();
            $compteEmetteur->save();
            $transaction = Transaction::create([
                'id_compte_emetteur' => $compteEmetteur->id_compte,
                'id_compte_destinataire' => $validated['id_compte_destinataire'],
                'montant' => $validated['montant'],
                'type' => $validated['type'],
            ]);
            $transaction->load([
                'compteEmetteur' => function ($q) {
                    $q->select('id_compte', 'id_user', 'numero_compte', )
                      ->with(['user' => function ($q2) {
                          $q2->select('id_user', 'nom', 'email','tel');
                      }]);
                },
                'compteDestinataire' => function ($q) {
                    $q->select('id_compte', 'id_user', 'numero_compte', )
                      ->with(['user' => function ($q2) {
                          $q2->select('id_user', 'nom', 'email','tel');
                      }]);
                }
            ]);

            Mail::to($compteEmetteur->user->email)->send(new \App\Mail\TransactionEmitted($transaction));
            Mail::to($compteDestinataire->user->email)->send(new \App\Mail\TransactionReceived($transaction));
            $compteDestinataire->user->notify(new TransactionReçue($transaction));
            event(new NewTransactionNotification(
                $compteDestinataire->user->id_user,
                [
                    'message' => 'Vous avez reçu ' . $transaction->montant . ' U',
                    'transaction_id' => $transaction->id,
                    'date' => $transaction->created_at,
                    'de' => $transaction->compteEmetteur->user->nom,
                    'transaction_type' => $transaction->type,
                ]
            ));
            $notifications = $compteDestinataire->user->unreadNotifications;
            DB::commit();
            return response()->json([
                'status' => "success",
                'data' => [
                    'transaction'=>$transaction,
                    'notifications'=>$notifications,
                ],
                'message' => "transactions éffectuée avec succès!"
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => "error",
                'message' => "Une erreur s'est produite lors de la transaction.",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
