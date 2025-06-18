<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\UpdateDemandeRequest;
use App\Models\Demande;
use App\Models\Roles;
use App\Models\Roles_demande;
use App\Models\Statuts_demande;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Création d'un compte admin.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nom' => 'required|string|max:255',
            'role' => 'required|in:admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nom' => $request->nom,
            'role' => 'admin',
            'statut' => 'actif',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'admin créé avec succès.',
            'user' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'nom' => $user->nom,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 201);
    }

    /**
     * Recuperer l'admin
     */
    public function show($id)
    {
        $admin = User::find($id);
        if ($admin) {
            return response()->json([
                "data" => $admin,
                "message" => "utilisateur récupéré avec succès."
            ], 200);
        } else {
            return response()->json([
                "data" => $admin,
                "message" => "utilisateur non Trouvé."
            ], 200);
        }
    }

    /**
     * Mise à jour du profil d'un utilisateur.
     */
    public function updateProfile(Request $request, $id_user)
    {
        $currentUser = auth()->user();

        // Vérification des autorisations de l'utilisateur actuel
        if (!in_array($currentUser->role, ['superadmin', 'admin'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // Récupération de l'utilisateur cible
        $user = User::findOrFail($id_user);

        // Empêcher la mise à jour d'un autre superadmin
        if ($user->role === 'superadmin' && $currentUser->id_user !== $user->id_user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas modifier le profil d\'un autre superadmin.',
            ], 403);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id_user . ',id_user',
            'tel' => 'nullable|string|max:20',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Mise à jour des données utilisateur
        if ($request->has('nom')) {
            $user->nom = $validated['nom'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('tel')) {
            $user->tel = $validated['tel'];
        }
        if ($request->has('ville')) {
            $user->ville = $validated['ville'];
        }
        if ($request->has('quartier')) {
            $user->quartier = $validated['quartier'];
        }
        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo_profil && Storage::exists(str_replace('storage/', '', $user->photo_profil))) {
                Storage::delete(str_replace('storage/', '', $user->photo_profil));
            }

            $photoName = time() . '.' . $request->photo_profil->getClientOriginalExtension();
            $filePath = $request->photo_profil->storeAs('photos_profil', $photoName, 'public');
            $user->photo_profil = 'storage/' . $filePath;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'data' => [
                'id_user' => $user->id_user,
                'email' => $user->email,
                'nom' => $user->nom,
                'tel' => $user->tel,
                'ville' => $user->ville,
                'quartier' => $user->quartier,
                'photo_profil' => $user->photo_profil,
                'role' => $user->role,
                'statut' => $user->statut,
            ],
        ], 200);
    }


    /**
     * Suppression d'un utilisateur.
     */
    public function deleteUser($id_user)
    {
        $currentUser = auth()->user();

        if ($currentUser->role !== 'superadmin') {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $user = User::findOrFail($id_user);

        // Supprimer la photo de profil si elle existe
        if ($user->photo_profil && Storage::exists(str_replace('storage/', '', $user->photo_profil))) {
            Storage::delete(str_replace('storage/', '', $user->photo_profil));
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur supprimé avec succès.',
        ], 200);
    }

    /**
     * Recuperer les demandes de l'admin.
     */
    public function getDemandes()
    {
        $user = Auth::user();

        if ($user->role != Roles::Admin->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
            ], 403);
        }

        $demandes =  Demande::where('role', Roles::Admin->value)->with('destinataire.partenaireShop')->get();


        return response()->json([
            'status' => 'success',
            'data' => $demandes,
        ], 200);
    }

    /**
     * envoyer une demande de l'admin.
     */
    public function sendDemand(StoreDemandeRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try{

            // creation de la demande
            $demande = Demande::create([
                'id_emetteur'=> $validated['id_emetteur'],
                'id_destinataire'=> $validated['id_destinataire'],
                'montant'=> $validated['montant'],
                'role'=> Roles_demande::Admin->value,
                'statut'=> Statuts_demande::En_attente->value,
                'motif'=> '',
            ]);

            $demande->load(['emetteur','destinataire.partenaireShop']);
            // Sauvegarde explicite pour obtenir l'ID
            $demande->save();
            //enregistrement des images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images_demande','public');
                    $demande->images()->create([
                        'url' => $path,
                        'id_demande' => $demande->id_demande
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Demande envoyée avec succès.',
                'data' => $demande
            ],201);

        } catch(Exception $e){

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de la demande.',
                'error' => $e->getMessage(),
            ],500);
        }
    }
    /**
     * modifier une demande de l'admin.
     */
    public function updateDemand(UpdateDemandeRequest $request, $id_demande)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try{
            //verification de l'existance de la demande
            $demande = Demande::findOrFail($id_demande);

            //modification des donnee
            if(isset($validated['motif'])){
                $demande->motif = $validated['motif'];
            }
            $demande->statut = $validated['statut'];
            // creation de la demande
            $demande->save();

            $demande->load(['emetteur','destinataire.partenaireShop']);
    
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Demande modifiée avec succès.',
                'data' => $demande
            ],201);

        } catch(Exception $e){

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification de la demande.',
                'error' => $e->getMessage(),
            ],500);
        }
    }
    /**
     * supprimer une demande de l'admin.
     */
    public function deleteDemand($id_demande)
    {
        DB::beginTransaction();
        try{
            //verification de l'existance de la demande
            $demande = Demande::findOrFail($id_demande);

            // suspression la demande
            $demande->destroy();
    
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Demande suprimée avec succès.',
                'data' => $demande
            ],201);

        } catch(Exception $e){

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suspression de la demande.',
                'error' => $e->getMessage(),
            ],500);
        }
    }
}
