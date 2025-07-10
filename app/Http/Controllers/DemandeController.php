<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Roles;
use App\Models\Compte;
use App\Models\Demande;
use App\Models\VerifRole;
use App\Models\Entreprise;
use App\Models\QueryFiler;
use App\Models\Transaction;
use App\Events\DemandeEvent;
use Illuminate\Http\Request;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use App\Models\TypeTransaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Enum;

class DemandeController extends Controller
{
    use ApiResponseTrait;
    /**
     * Recuperer les demandes de l'admin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $verifRole = new VerifRole();
        $query = Demande::getAll($user->id_user);

        // definir les relations qui seront aussi filtree et leurs champs
        $relationMap = [
            'emetteur' => 'emetteur.nom',
            'destinataire' => 'destinataire.nom',
        ];
        // definir les champs concerne par le filtre global
        $globalSearchFields = ['emetteur.nom', 'destinataire.nom', 'montant'];
        $filter = new QueryFiler($relationMap, $globalSearchFields, 'id_demande', ['id_user']);
        $query = $filter->apply($query, $request);

        $demandes = $query->paginate($request->get('rows', 10));

        $last_demande = collect($demandes->items())->last();
        //pagination
        $response = [
            'data' => $demandes->items(),
            'last_item' => $last_demande,
            'current_page' => $demandes->currentPage(),
            'last_page' => $demandes->lastPage(),
            'per_page' => $demandes->perPage(),
            'total' => $demandes->total(),
        ];

        return response()->json($response);
    }

    /**
     * Envoyer une demande de l'admin
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
            if ($demande->destinataire->role != Roles::Shop->value) {
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
            // Mail::to($demande->destinataire->email)->send(new \App\Mail\DemandSent($demande));

            $demande->load(['destinataire.entreprise', 'destinataire.shop', 'images', 'emetteur.entreprise', 'emetteur.shop']);
            $destinataire = $demande->destinataire;

            DB::afterCommit(function () use ($demande, $destinataire) {
                // 1. Envoyer la notification à l'utilisateur
                $destinataire->notify(new \App\Notifications\DemandeNotification($demande));

                // 2. Récupérer la dernière notification stockée en base
                $lastNotification = $destinataire->notifications()->latest()->first();

                // 3. Déclencher l'event (broadcast)
                event(new DemandeEvent($lastNotification, $destinataire->id_user));
            });
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
                if ($demande->destinataire->role != Roles::Admin->value) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            if ($verifRole->isShop()) {
                //verifier que le destinataire est l'employe
                if ($demande->destinataire->role != Roles::Employe->value) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            if ($verifRole->isEmploye()) {
                //verifier que le destinataire est le gestionnaire entreprise
                if ($demande->destinataire->role != Roles::Entreprise->value) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'vous ne pouvez pas envoyer de message a ce destinataire'
                    ], 422);
                }
            }
            //enregistrement de la demande
            $demande->save();

            $demande->load(['emetteur', 'destinataire.entreprise', 'destinataire.shop', 'emetteur.entreprise', 'emetteur.shop']);
            $destinataire = $demande->destinataire;
            DB::afterCommit(function () use ($demande, $destinataire) {

                // 1. Envoyer la notification à l'utilisateur
                $destinataire->notify(new \App\Notifications\DemandeNotification($demande));

                // 2. Récupérer la dernière notification stockée en base
                $lastNotification = $destinataire->notifications()->latest()->first();

                // 3. Déclencher l'event (broadcast)
                event(new DemandeEvent($lastNotification, $destinataire->id_user));
            });
            //enregistrement de la demande
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
        if (!$verifRole->isAdmin() and !$verifRole->isEntreprise() and !$verifRole->isEmploye() and !$verifRole->isShop()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas éffectuer cette action.'
            ], 403);
        }
        //recuperer la demande
        $demande = Demande::where('id_demande', $id)->with(['emetteur.entreprise', 'emetteur.shop', 'destinataire.entreprise', 'destinataire.shop', 'images'])->first();
        if ($demande) {


            return response()->json([
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
    public function accorder(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => ['required', 'max:4'],
            'type' => ['required', new Enum(TypeTransaction::class)],
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
            // verifier si la demande existe
            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette demande n\'hexiste pas!'
                ], 403);
            }
            // verification du statut de la demande
            if (($verifRole->isShop() || $verifRole->isAdmin()) && $demande->statut != Statuts_demande::En_attente->value) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'le statut de cette demande empeche la transaction!'
                ], 409);
            }
            if ($verifRole->isEntreprise() && $demande->statut != Statuts_demande::Valide->value) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'le statut de cette demande empeche la transaction!'
                ], 409);
            }
            // $compte = $demande->destinataire->compte;
            // $compte->pin = Hash::make($validated['pin']);
            // $compte->save();
            // verification du pin
            if (!Hash::check($validated['pin'], $demande->destinataire->compte->pin)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'pin incorrect!'
                ], 422);
            }
            // NB: le destinataire de la transaction est l'emetteur de la demande

            //creer la transaction
            $transaction = Transaction::create([
                'id_compte_emetteur' => $demande->destinataire->compte->id_compte,
                'id_compte_destinataire' => $demande->emetteur->compte->id_compte,
                'montant' => $demande->montant,
                'id_demande' => $demande->id_demande,
                'type' => $validated['type'],
            ]);
            $transaction->save();


            //mettre a jour le statut
            $demande->statut = Statuts_demande::Accorde->value;
            //enregistrer la transaction dans la demande

            //enregistrer les modifications
            $demande->save();


            $demande->load('destinataire.shop', 'destinataire.entreprise', 'emetteur.entreprise', 'emetteur.shop', 'transaction.compteDestinataire');
            $emetteur = $demande->emetteur;


            DB::afterCommit(function () use ($demande, $emetteur) {

                // 1. Envoyer la notification à l'utilisateur
                $emetteur->notify(new \App\Notifications\DemandeNotification($demande));

                // 2. Récupérer la dernière notification stockée en base
                $lastNotification = $emetteur->notifications()->latest()->first();

                // 3. Déclencher l'event (broadcast)
                event(new DemandeEvent($lastNotification, $emetteur->id_user));
            });
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

            $demande->load('destinataire.shop', 'destinataire.entreprise', 'emetteur.entreprise', 'emetteur.shop', 'transaction.compteDestinataire');
            $emetteur = $demande->emetteur;


            DB::afterCommit(function () use ($demande, $emetteur) {
                // 1. Envoyer la notification à l'utilisateur
                $emetteur->notify(new \App\Notifications\DemandeNotification($demande));

                // 2. Récupérer la dernière notification stockée en base
                $lastNotification = $emetteur->notifications()->latest()->first();

                // 3. Déclencher l'event (broadcast)
                event(new DemandeEvent($lastNotification, $emetteur->id_user));
            });
            DB::commit();
            //envoi de l'email
            // Mail::to($demande->emetteur->email)->send(new \App\Mail\DemandRefused($demande));
            // // envoyer une notification
            // $demande->emetteur->notify(new \App\Notifications\NotificationDemandeRefuse($demande));
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
