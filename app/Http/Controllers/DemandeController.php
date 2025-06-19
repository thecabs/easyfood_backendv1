<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\VerifRole;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use App\Models\TypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DemandeController extends Controller
{
    /**
     * Recuperer les demandes de l'admin.
     */
    public function index()
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        if ($verifRole->isAdmin()) {
            $demandes = Demande::where('id_emetteur', $user->id_user)->orWhere('id_destinataire', $user->id_user)->with('destinataire.shop', 'emetteur', 'images')->get();
        }
        if ($verifRole->isShop()) {
            $demandes = Demande::where('id_destinataire', $user->id_user)->with('destinataire.shop', 'emetteur', 'images')->get();
        }
        // if ($verifRole->isAdmin() or $verifRole->isShop() or $verifRole->isEntreprise() or $verifRole->isEmploye()) {
        //     $demandes = Demande::where('id_emetteur', $user->id_user)->orWhere('id_destinataire', $user->id_user)->get();
        // }

        return response()->json([
            'status' => 'success',
            'data' => $demandes,
            'messages' => 'demandes recupérées.',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        if ($verifRole->isAdmin()) {
            //validation de la demande
            $validated = $request->validate([
                'id_destinataire' => ['required', 'exists:users,id_user'],
                'montant' => ['required'],
                'images.*' => ['image', 'required', 'mimes:jpg,jpeg,gif,webp,png', 'max:4096'],
            ]);
            $validated['role'] = Roles_demande::Admin->value;
        } else {
            //validation de la demande
            $validated = $request->validate([
                'id_destinataire' => ['required', 'exists:users,id_user'],
                'montant' => ['required'],
            ]);
            if ($verifRole->isEntreprise()) {
                $validated['role'] = Roles_demande::Entreprise->value;
            }
            if ($verifRole->isEmploye()) {
                $validated['role'] = Roles_demande::Employe->value;
            }
        }
        $validated['id_emetteur'] = $user->id_user;
        $validated['statut'] = Statuts_demande::En_attente->value;

        DB::beginTransaction();

        try {
            extract($validated);
            $demande = Demande::create([
                'id_emetteur' => $id_emetteur,
                'id_destinataire' => $id_destinataire,
                'montant' => $montant,
                'role' => $role,
                'motif' => '',
                'statut' => $statut,
            ]);

            //enregistrement de la demande
            $demande->save();


            //verification de l'existance des image
            if ($verifRole->isAdmin()) {
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $path = $image->store('images_demande', 'public');
                        $demande->images()->create([
                            'id_demande' => $demande->id_demande,
                            'url' => 'storage/' . $path
                        ]);
                    }
                }
            }

            $demande->load(['emetteur', 'destinataire', 'images']);
            if ($verifRole->isAdmin()) {
                $demande->load(['emetteur', 'destinataire.shop', 'images']);
            }
            if ($verifRole->isEntreprise()) {
                $demande->load(['emetteur', 'destinataire']);
            }
            if ($verifRole->isEmploye()) {
                $demande->load(['emetteur', 'destinataire']);
            }
            //enregistrement de la transaction 
            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $demande,
                'message' => 'demande envoyée avec succès.'
            ]);
        } catch (Exception $e) {
            //annulation de toutes les requetes
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'une erreur est survenue lors de l\'envoi de la demande'
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isShop()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas éffectuer cette action.'
            ], 403);
        }
        //recuperer la demande
        $demande = Demande::where('id_demande', $id)->orWhere('id_destinataire', $user->id_user)->first();
        if ($demande) {

            $demande->statut = Statuts_demande::Accorde->value;

            response()->json([
                'status' => 'success',
                'data' => $demande,
                'message' => 'demande recupérée.',
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Cette demande n\'hexiste pas!'
            ], 403);
        }
    }


    /**
     * Accorder une demande de credit.
     */
    public function accorder(Request $request,$id)
    {
        $validated = $request->validate(['pin'=>['required','max:4']]);
        $user = Auth::user();
        $verifRole = new VerifRole();
        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isShop()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas éffectuer cette action.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            //recuperer la demande
            $demande = Demande::where('id_demande', $id)->where('id_destinataire', $user->id_user)->with(['emetteur.compte', 'destinataire.compte'])->first();

            if ($demande) {
                // verification du statut de la demande
                if (($verifRole->isShop() || $verifRole->isAdmin()) && $demande->statut != Statuts_demande::En_attente->value) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'le statut de cette demande empeche la transaction!'
                    ], 409);
                } else {
                    if ($verifRole->isEntreprise() && $demande->statut != Statuts_demande::Valide->value) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'le statut de cette demande empeche la transaction!'
                        ], 409);
                    }
                }
                //mettre a jour le statut
                $demande->statut = Statuts_demande::Accorde->value;

                //type de transaction
                if($verifRole->isAdmin()){
                    $type = TypeTransaction::RECHARGEENTREPRISE;
                }
                if($verifRole->isShop()){
                    $type = TypeTransaction::RECHARGEADMIN;
                }
                if($verifRole->isEntreprise()){
                    $type = TypeTransaction::RECHARGEEMPLOYE;
                }
                //creer la transaction
                $transaction = Transaction::create([
                    'id_compte_emetteur' => $demande->destinataire->compte->id_compte,
                    'id_compte_destinataire' => $demande->emetteur->compte->id_compte,
                    'montant' => $demande->montant,
                    'id_demande' => $demande->id_demande,
                    'type' => TypeTransaction::RECHARGEADMIN->value,
                ]);

                //recuperer compte destinataire
                // NB: le destinataire de la transaction est l'emetteur de la demande
                $compteDest = Compte::where('id_compte', $demande->emetteur->compte->id_compte)->first();

                if ($compteDest) {
                    if(Hash::check($validated['pin'], $demande->destinataire->compte->pin)){
                        // mettre a jour le solde du compte destinataire
                        $compteDest->solde += $demande->montant;
                        $compteDest->save();
                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => 'pin incorrect!'
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'le compte destinataire n\'hexiste pas!'
                    ], 403);
                }


                //enregistrer les modifications
                $demande->save();

                $demande->load('transaction.compteDestinataire');
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "updated_at" => $demande->updated_at,
                    "transaction" => $demande->transaction,
                ],
                'message' => 'demande accordée.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'une erreur est survenue lors de la modification de la demande',
                'error' => $e->getMessage()
            ], 422);
        }
    }
    /**
     * Refuser une demande.
     */
    public function refuser(Request $request, $id)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        $validated = $request->validate([
            'motif' => 'required|string'
        ]);

        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isShop()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas éffectuer cette action.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            //recuperer la demande
            $demande = Demande::where('id_demande', $id)->where('id_destinataire', $user->id_user)->first();

            if ($demande) {

                $demande->statut = Statuts_demande::Refuse->value;
                $demande->motif = $validated['motif'];

                //enregistrer les modifications
                $demande->save();

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "updated_at" => $demande->updated_at,
                ],
                'message' => 'demande rejetée.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'une erreur est survenue lors de la modification de la demande',
                'error' => $e->getMessage()
            ], 422);
        }
    }
    /**
     * Annuler une demande.
     */
    public function annuler($id)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        DB::beginTransaction();

        try {
            //recuperer la demande
            $demande = Demande::where('id_demande', $id)->where('id_emetteur', $user->id_user)->first();

            if ($demande) {

                $demande->statut = Statuts_demande::Annule->value;

                //enregistrer les modifications
                $demande->save();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "updated_at" => $demande->updated_at,
                ],
                'message' => 'demande annulée.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'une erreur est survenue lors de la modification de la demande',
                'error' => $e->getMessage()
            ], 422);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Demande $demande, $id)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();
            //recuperer la demande
            $demande = Demande::where('id_demande', $id)->where('id_emetteur', $user->id_user)->with('images')->first();

            if ($demande) {
                foreach ($demande->images as $image) {
                    unlink($image->url);
                }
                $demande->delete();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "updated_at" => $demande->updated_at,
                ],
                'message' => 'Demande supprimée avec succès.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'une erreur est survenue lors de la suspression de la demande',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
