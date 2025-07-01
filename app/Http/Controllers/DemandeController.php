<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\VerifRole;
use App\Models\Entreprise;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use App\Models\TypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Enum;

class DemandeController extends Controller
{
    /**
     * Recuperer les demandes de l'admin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        $query = Demande::getAll($user->id_user);

        if ($request->filled('id_emetteur')) {
            $query->where('id_emetteur',$request->input('id_emetteur', -1));
        }
        if ($request->filled('id_destinataire')) {
            $query->where('id_destinataire',$request->input('id_destinataire', -1));
        }

        // filtrage global
        if ($request->filled('filters.global.value') ) {
            $value = $request->input('filters.global.value');
            $query->where(function ($q) use ($value) {
                $q->where('nom', 'like', "%$value%")
                  ->orWhere('ville', 'like', "%$value%")
                  ->orWhere('quartier', 'like', "%$value%")
                  ->orWhere('tel', 'like', "%$value%")
                  ->orWhere('email', 'like', "%$value%");
            });
        }
    
        // filtrage
        foreach ($request->input('filters', []) as $field => $filter) {
            if ($field === 'global') continue;
    
            $operator = $filter['operator'] ?? 'and';
            $constraints = $filter['constraints'] ?? [];
    
            $query->where(function ($q) use ($constraints, $field, $operator) {
                foreach ($constraints as $rule) {
                    $value = $rule['value'] ?? null;
                    $mode = $rule['matchMode'] ?? 'contains';
    
                    if (is_null($value)) continue;
    
                    $clause = match ($mode) {
                        'startsWith' => [$field, 'like', $value . '%'],
                        'endsWith'   => [$field, 'like', '%' . $value],
                        'contains'   => [$field, 'like', '%' . $value . '%'],
                        'equals'     => [$field, '=', $value],
                        'notEquals'  => [$field, '!=', $value],
                        'in'         => [$field, $value],
                        default      => null
                    };
    
                    if (!$clause) continue;
    
                    $operator === 'or'
                        ? $q->orWhere(...$clause)
                        : $q->where(...$clause);
                }
            });
        }

        //tri
        if ($request->filled('sortField') && $request->filled('sortOrder')) {
            $direction = $request->sortOrder == -1 ? 'desc' : 'asc';
            $query->orderBy($request->sortField, $direction);
        }else{
            $query->orderBy('id_demande', 'desc');
        }
        
        return $query->paginate($request->get('rows', 10));
    }

    /**
     * Envooyer une demande de l'admin
     */
    public function storeAdmin(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        if (!$verifRole->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas modifier le profil d\'un superadmin.',
            ], 403);
        }

        //validation de la demande
        $validated = $request->validate([
            'id_destinataire' => ['required', 'exists:users,id_user'],
            'montant' => ['required'],
            'images.*' => ['image', 'required', 'mimes:jpg,jpeg,gif,webp,png', 'max:4096'],
        ]);
        $validated['role'] = Roles_demande::Admin->value;
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
            //verifier que le destinataire est le gestionnaire shop 
            if($demande->destinataire->role != Roles::Shop->value){
                return response()->json([
                    'status' => 'error',
                    'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                ], 422);
            }
            //enregistrement de la demande
            $demande->save();

            //verification de l'existance des image

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images_demande', 'public');
                    $demande->images()->create([
                        'id_demande' => $demande->id_demande,
                        'url' => 'storage/' . $path
                    ]);
                }
            }
            
            //envoi de l'email
            Mail::to($demande->destinataire->email)->send(new \App\Mail\DemandSent($demande));

            $demande->load(['destinataire.entreprise', 'destinataire.shop', 'images','emetteur.entreprise','emetteur.shop']);
            // envoyer une notification
            $demande->destinataire->notify(new \App\Notifications\DemandeRecu($demande));
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
                'message' => 'une erreur est survenue lors de l\'envoi de la demande verifiez votre connexion internet'
            ], 422);
        }
    }
    /**
     * Envoyer demande
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        if (!$verifRole->isShop() and !$verifRole->isEntreprise() and !$verifRole->isEmploye()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas modifier le profil d\'un superadmin.',
            ], 403);
        }


        //validation de la demande
        $validated = $request->validate([
            'id_destinataire' => ['required', 'exists:users,id_user'],
            'montant' => ['required'],
        ]);
        if ($verifRole->isEntreprise()) {
            $validated['role'] = Roles_demande::Entreprise->value;
        }
        if ($verifRole->isEmploye()) {
            //verifier si le destinataire est bien le responsable de l'employe
            if ($user->entreprise->gestionnaire->id_user != $validated['id_destinataire']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas envoyer de demande a ce gestionnaire.',
                ], 403);
            }

            $validated['role'] = Roles_demande::Employe->value;
        }
        if ($verifRole->isShop()) {
            $validated['role'] = Roles_demande::Shop->value;
        }

        $validated['id_emetteur'] = $user->id_user;
        $validated['statut'] = Statuts_demande::En_attente->value;

        DB::beginTransaction();
        try {
            if ($verifRole->isEmploye()) {
            }
            extract($validated);
            $demande = Demande::create([
                'id_emetteur' => $id_emetteur,
                'id_destinataire' => $id_destinataire,
                'montant' => $montant,
                'role' => $role,
                'motif' => '',
                'statut' => $statut,
            ]);
            //verifier le destinataire
            if ($verifRole->isEntreprise()) {
                //verifier que le destinataire est l'admin
                if($demande->destinataire->role != Roles::Admin->value){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            if ($verifRole->isShop()) {
                //verifier que le destinataire est l'employe 
                if($demande->destinataire->role != Roles::Employe->value){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            if ($verifRole->isEmploye()) {
                //verifier que le destinataire est le gestionnaire entreprise 
                if($demande->destinataire->role != Roles::Entreprise->value){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            //enregistrement de la demande
            $demande->save();

            $demande->load(['emetteur', 'destinataire.entreprise','destinataire.shop','emetteur.entreprise','emetteur.shop']);

            
            //enregistrement de la demande 
            DB::commit();
            //envoi de l'email
            Mail::to($demande->destinataire->email)->send(new \App\Mail\DemandSent($demande));

            // envoyer une notification
            $demande->destinataire->notify(new \App\Notifications\DemandeRecu($demande));

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

    //tester l'envoi de l'email
    public function sendEmail(){
        $user = Auth::user();
        $demande = Demande::where('id_emetteur', $user->id_user)->first();
        try{
            echo json_encode($demande);
            Mail::to('fredricka703@phugruphy.com')->send(new \App\Mail\DemandRefused($demande));
            return response()->json([
                'status' => 'success',
                'message' => 'Email envoyé avec succès.',
            ], 200);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de l\'envoi de l\'email: '.$e->getMessage(),
            ], 500);
        }
        
    }

    /**
     * Accorder une demande de credit.
     */
    public function accorder(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => ['required', 'max:4'],
            'type' => ['required',new Enum(TypeTransaction::class)],
        ]);
        $user = Auth::user();
        $verifRole = new VerifRole();
        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isShop() and !$verifRole->isEmploye()) {
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
                
                //type de transaction
                if ($verifRole->isAdmin()) {
                    $type = TypeTransaction::RECHARGEENTREPRISE;
                }
                if ($verifRole->isShop()) {
                    $type = TypeTransaction::RECHARGEADMIN;
                }
                if ($verifRole->isEntreprise()) {
                    $type = TypeTransaction::RECHARGEEMPLOYE;
                }
                //creer la transaction
                $transaction = Transaction::create([
                    'id_compte_emetteur' => $demande->destinataire->compte->id_compte,
                    'id_compte_destinataire' => $demande->emetteur->compte->id_compte,
                    'montant' => $demande->montant,
                    'id_demande' => $demande->id_demande,
                    'type' => $validated['type'],
                ]);

                //recuperer compte destinataire
                // NB: le destinataire de la transaction est l'emetteur de la demande
                $compteDest = Compte::where('id_compte', $demande->emetteur->compte->id_compte)->first();
                if ($compteDest) {
                    if ($verifRole->isShop()) {
                        if (Hash::check($validated['pin'], $demande->destinataire->compte->pin)) {
                            // mettre a jour le solde du compte destinataire
                            $compteDest->solde += $demande->montant;
                            $compteDest->save();
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'pin incorrect!'
                            ], 422);
                        }
                    } else {
                        //recuperer compte emetteur
                        // NB: l'emetteur de la transaction est le destinatiare de la demande
                        $compteEmet = Compte::where('id_compte', $demande->destinataire->compte->id_compte)->first();

                        if($compteEmet){
                            if (Hash::check($validated['pin'], $demande->destinataire->compte->pin)) {
                                // verifier si le solde est suffisant
                                if($compteEmet->solde >= $demande->montant){
                                    // mettre a jour le solde du compte de l'emetteur
                                    $compteEmet->solde -= $demande->montant;
                                    // mettre a jour le solde du compte destinataire
                                    $compteDest->solde += $demande->montant;
                                    
                                    $compteDest->save();
                                    $compteEmet->save();

                                }else{

                                    return response()->json([
                                        'status' => 'error',
                                        'message' => 'solde insuffisant!'
                                    ], 403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'pin incorrect!'
                                ], 422);
                            }
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'le compte emetteur n\'hexiste pas!'
                            ], 403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'le compte destinataire n\'hexiste pas!'
                    ], 403);
                }

                //mettre a jour le statut
                $demande->statut = Statuts_demande::Accorde->value;

                //enregistrer les modifications
                $demande->save();

                
                $demande->load( 'destinataire.shop','destinataire.entreprise','transaction.compteDestinataire');
                
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            //envoi des email
            Mail::to($demande->emetteur->email)->send(new \App\Mail\DemandAgree($demande));
            Mail::to($demande->destinataire->email)->send(new \App\Mail\DemandAgreeSender($demande));
            // envoyer une notification
            $demande->emetteur->notify(new \App\Notifications\NotificationDemandeAccorde($demande));

            DB::commit();
            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "motif" => $demande->motif,
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

        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isShop() and !$verifRole->isEmploye()) {
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
            //envoi de l'email
            Mail::to($demande->emetteur->email)->send(new \App\Mail\DemandRefused($demande));
            // envoyer une notification
            $demande->emetteur->notify(new \App\Notifications\NotificationDemandeRefuse($demande));
            return response()->json([
                'status' => 'success',
                'data' => [
                    "id_demande" => $demande->id_demande,
                    "statut" => $demande->statut,
                    "motif" => $demande->motif,
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
     * Valider une demande.
     */
    public function valider($id)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();

        if (!$verifRole->isEntreprise()) {
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

                $demande->statut = Statuts_demande::Valide->value;

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
                'message' => 'demande validée.',
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

            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            if (count($demande->images)) {
                foreach ($demande->images as $image) {
                    unlink($image->url);
                }
            }
            $demande->delete();
            DB::commit();
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
