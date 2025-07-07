<?php

use App\Models\Demande;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Models\PartenaireShop;
use App\Http\Controllers\TestNotif;
use Illuminate\Support\Facades\Route;
use App\Events\NewInvoiceNotification;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\dashboardCredit;
use App\Http\Controllers\StockController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\getUserConnected;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\ProduitController;


use App\Http\Controllers\AssuranceController;
use App\Http\Controllers\CaissiereController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TravailleurController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AssuranceGestController;
use App\Http\Controllers\DashboardAssuranceController;
use App\Http\Controllers\EntrepriseGestController;
use App\Http\Controllers\PartenaireShopController;
use App\Http\Controllers\ProductFeaturesController;
use App\Http\Controllers\PartenaireShopGestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// LES USERS



// SuperAdmin
Route::prefix('superadmin')->group(function () {
    Route::post('/register', [SuperAdminController::class, 'register']);
});


// admin

Route::post('/admin/register', [AdminController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('/update/{id_user}', [AdminController::class, 'updateProfile']);
        Route::post('/demande', [AdminController::class, 'sendDemand']);
        Route::get('/demande', [AdminController::class, 'getDemandes']);
        Route::put('/demande/{id_demande}', [AdminController::class, 'updateDemand']);
        Route::delete('/demande/{id_demande}', [AdminController::class, 'deleteDemand']);
        Route::put('/update/{id_user}', [AdminController::class, 'updateProfile']);
        Route::get('/show', [AdminController::class, 'show']);
        Route::get('/', [AdminController::class, 'index']);
        Route::delete('/delete/{id_user}', [AdminController::class, 'deleteUser']);
    });
});







// ASSURANCE



// Routes protégées par middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/assurances', [AssuranceController::class, 'index']); // Liste des assurances
    Route::get('/assurances/{id}', [AssuranceController::class, 'show']); // Voir une assurance
    Route::post('/assurances', [AssuranceController::class, 'store']); // Créer une assurance

    Route::post('/assurances/{id}', [AssuranceController::class, 'update']); // Modifier une assurance
    Route::post('/assurances/{id}', [AssuranceController::class, 'update']); // Créer une assurance
    Route::put('/assurances/{id}', [AssuranceController::class, 'update']); // Modifier une assurance
    Route::delete('/assurances/{id}', [AssuranceController::class, 'destroy']); // Supprimer une assurance
    Route::post('/assurances/{id}', [AssuranceController::class, 'update']); // Modifier une assurance
    Route::post('/assurances/{id}', [AssuranceController::class, 'update']); // Créer une assurance
    Route::put('/assurances/{id}', [AssuranceController::class, 'update']); // Modifier une assurance
    Route::delete('/assurances/{id}', [AssuranceController::class, 'destroy']); // Supprimer une assurance

});


Route::middleware(['auth:sanctum'])->group(function () {
    // Route pour enregistrer un gestionnaire assurance
    Route::get('/assurance-gest', [AssuranceGestController::class, 'index']);
    Route::post('/assurance-gest/register', [AssuranceGestController::class, 'register']);
    Route::put('/assurance-gestupdate/{id_user}', [AssuranceGestController::class, 'updateProfile']);
    Route::post('/assurance-gestupdate/{id_user}', [AssuranceGestController::class, 'updateProfile']);
    // afficher un gestionnaire


    Route::get('/assurance-gest/show/{id_user}', [AssuranceGestController::class, 'showGest']);

    // Route pour confirmer l'OTP pour un gestionnaire assurance
    // Route::post('/assurance-gest/confirm-otp', [AssuranceGestController::class, 'confirmOtp']);
});





// ENTREPRISES
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/entreprises', [EntrepriseController::class, 'index']); // Liste des entreprises
    Route::get('/entreprises/{id}', [EntrepriseController::class, 'show']); // Affiche une entreprise spécifique
    Route::post('/entreprises', [EntrepriseController::class, 'store']); // Crée une nouvelle entreprise
    //Route::put('/entreprises/{id}', [EntrepriseController::class, 'update']); // Met à jour une entreprise
    Route::post('/entreprises/{id}', [EntrepriseController::class, 'update']); // Met à jour une entreprise
    Route::delete('/entreprises/{id}', [EntrepriseController::class, 'destroy']); // Supprime une entreprise

});


Route::middleware(['auth:sanctum'])->group(function () {
    // Route pour enregistrer un gestionnaire pour une entreprise
    Route::post('/entreprise-gest/register', [EntrepriseGestController::class, 'register']);

    // Route pour update un gestionnaire d'entreprise
    Route::put('/entreprise-gestupdate/{id_user}', [EntrepriseGestController::class, 'updateProfile']);
    Route::post('/entreprise-gestupdate/{id_user}', [EntrepriseGestController::class, 'updateProfile']);
    Route::get('/entreprise-gest', [EntrepriseGestController::class, 'index']);


    // afficher un gestionnaire

    Route::get('/entreprise-gest/show/{id_user}', [EntrepriseGestController::class, 'showGest']);
});



// Employes


Route::middleware(['auth:sanctum'])->group(function () {

    // activer un compte

    Route::post('/employe/activate', [EmployeController::class, 'activate']);
    // Liste des employés
    Route::get('/employes', [EmployeController::class, 'index']);
    Route::get('/employes/non-actif', [EmployeController::class, 'nonActif']);

    // Affiche un employé spécifique
    Route::get('/employes/{id}', [EmployeController::class, 'show']);
});
// Travailleurs
Route::prefix('travailleur')->group(function () {
    Route::post('/register', [TravailleurController::class, 'register']);
    Route::post('/validate-otp', [TravailleurController::class, 'validateOtp']);
});
Route::middleware(['auth:sanctum'])->group(function () {

    // activer un compte

    Route::post('/travailleur/activate', [TravailleurController::class, 'activate']);
    // Liste des Travailleurs
    Route::get('/travailleur', [TravailleurController::class, 'index']);

    // Affiche un Travailleur spécifique
    Route::get('/travailleur/{id}', [TravailleurController::class, 'show']);
});



//pour l'appli mobile

Route::prefix('employe')->group(function () {
    Route::post('/register', [EmployeController::class, 'register']);
    Route::post('/validate-otp', [EmployeController::class, 'validateOtp']);
    Route::post('/entreprises/recherche', [EntrepriseController::class, 'search']);
});



Route::middleware(['auth:sanctum'])->group(function () {

    // Mettre à jour un employé
    //Route::put('/employes', [EmployeController::class, 'update']);
    Route::post('/employes', [EmployeController::class, 'update']);
    Route::get('/employe/info/{id}', [EmployeController::class, 'getEmployeInfo']);



    // lister les entreprises

    Route::get('/shop-ls', [PartenaireShopController::class, 'listShopsSimple']);

    Route::get('/employe/recherche', [ProduitController::class, 'rechercherProduit']);

    //historique



    Route::get('/employe/historique/{id_user}', [EmployeController::class, 'getHistorique']);
});


// compte easyfood pour les employes

Route::middleware(['auth:sanctum'])->group(function () {
    // Route pour récupérer les détails d'un compte
    Route::get('/compte/{numeroCompte}', [CompteController::class, 'getCompteDetails']);

    // Route pour mettre à jour le PIN d'un compte
    Route::post('/compte/{numeroCompte}/update-pin', [CompteController::class, 'updatePin']);
});


//les demandes de fonds


// Demandes de fonds

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('demande')->group(function () {
        //tester l'envoi de lemail
        Route::post('/test-email', [DemandeController::class, 'sendEmail']);
        //envoyer une demande
        Route::post('/', [DemandeController::class, 'store']);
        Route::post('/admin', [DemandeController::class, 'storeAdmin']);
        Route::put('annuler/{id}', [DemandeController::class, 'annuler']);
        Route::put('valider/{id}', [DemandeController::class, 'valider']);
        Route::put('/accorder/{id}', [DemandeController::class, 'accorder']);
        Route::put('/refuser/{id}', [DemandeController::class, 'refuser']);
        Route::delete('destroy/{id}', [DemandeController::class, 'destroy']);
        Route::get('/', [DemandeController::class, 'index']);
        Route::get('/{id}', [DemandeController::class, 'show']);
    });
});
// Route::post('demandes/fonds', [DemandeController::class, 'storesonds']);
// Route::post('demandes/fonds/{id}/valider', [DemandeController::class, 'validerFonds']);
// Route::post('demandes/fonds/{id}/refuser', [DemandeController::class, 'refuserFonds']);

// Demandes transmit
// Route::post('demandes/transmit', [DemandeController::class, 'storeTransmit']);
// Route::post('demandes/transmit/{id}/accorder', [DemandeController::class, 'accorderTransmit']);
// Route::post('demandes/transmit/{id}/refuser', [DemandeController::class, 'refuserTransmit']);

// Liste des demandes (optionnel, avec filtre via query param "type")
// Route::get('demandes', [DemandeController::class, 'index']);



//les transactions pour les comptes


Route::middleware(['auth:sanctum'])->group(function () {


    Route::get('/transaction', [TransactionController::class, 'index']);
    Route::post('/transaction', [TransactionController::class, 'store']);
});



//PartenaireShop


Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('partenaire-shop')->group(function () {
        // Créer un partenaire shop
        Route::post('/register', [PartenaireShopController::class, 'store']);

        // Mettre à jour un partenaire shop
        Route::put('update/{id_shop}', [PartenaireShopController::class, 'update']);
        Route::post('update/{id_shop}', [PartenaireShopController::class, 'update']);

        // Supprimer un partenaire shop
        Route::delete('delete/{id_shop}', [PartenaireShopController::class, 'destroy']);

        // Voir les détails d'un partenaire shop
        Route::get('show/{id_shop}', [PartenaireShopController::class, 'show']);

        // Lister tous les partenaires shops
        Route::get('/', [PartenaireShopController::class, 'index']);
    });
});


// gestionnaire du shop

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('partenaire-shop/gest')->group(function () {
        // Créer un gestionnaire pour un partenaire shop
        Route::post('/register', [PartenaireShopGestController::class, 'register']);

        // Mettre à jour un gestionnaire
        Route::put('/update/{id_user}', [PartenaireShopGestController::class, 'updateProfile']);
        Route::post('/update/{id_user}', [PartenaireShopGestController::class, 'updateProfile']);

        // afficher  un gestionnaire

        Route::get('/show/{id_user}', [PartenaireShopGestController::class, 'showGest']);
        Route::get('/', [PartenaireShopGestController::class, 'index']);
    });
});


//Caissiere



Route::middleware(['auth:sanctum'])->group(function () {
    // Routes pour les caissières
    Route::get('caissieres/', [CaissiereController::class, 'index']); // Lister toutes les caissières
    Route::post('caissieres/register', [CaissiereController::class, 'register']); // Ajouter une caissière
    Route::get('caissieres/{id_caissiere}', [CaissiereController::class, 'show']); // Afficher une caissière spécifique
    Route::put('caissieres/{id_caissiere}', [CaissiereController::class, 'update']); // Mettre à jour une caissière
    Route::delete('caissieres/{id_caissiere}', [CaissiereController::class, 'destroy']); // Supprimer une caissière
});



//les routes pour le partenaire shop
// categories




Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategorieController::class, 'index']); // Liste des catégories
        Route::post('/add', [CategorieController::class, 'store']); // Créer une catégorie
        Route::get('/{id}', [CategorieController::class, 'show']); // Afficher une catégorie spécifique
        Route::put('update/{id}', [CategorieController::class, 'update']); // Mettre à jour une catégorie
        Route::delete('/{id}', [CategorieController::class, 'destroy']); // Supprimer une catégorie
    });
});


// les produits



Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('produits')->group(function () {
        Route::get('/', [ProduitController::class, 'index']); // Liste des produits
        Route::post('/add', [ProduitController::class, 'store']); // Créer un produit
        Route::get('/{id}', [ProduitController::class, 'show']); // Afficher un produit spécifique
        Route::put('update/{id}', [ProduitController::class, 'update']); // Mettre à jour un produit
        Route::delete('/{id}', [ProduitController::class, 'destroy']); // Supprimer un produit

        //nouvelle route


    });
});





// le stock


Route::middleware('auth:sanctum')->group(function () {
    Route::put('/stocks/update/', [StockController::class, 'update']); // Mettre à jour un stock

    Route::get('/stocks', [StockController::class, 'index']);
});

// logs sur le stocks


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/stocks/{id}/logs', [StockController::class, 'logs']);
});


// les images sur les produits



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/produits/{id_produit}/images', [ProductFeaturesController::class, 'store']);
    Route::get('/produits/{id_produit}/images', [ProductFeaturesController::class, 'listImages']);
    Route::delete('/images/{id_image}', [ProductFeaturesController::class, 'deleteImage']);
});


// notifications
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('notification')->group(function () {
        Route::get('/', [NotificationController::class, 'index']); //recuperer les notifications
        Route::get('/unreadnotifications', [NotificationController::class, 'getUnRead']); //recuperer les notifications non lues
        Route::get('/readnotifications', [NotificationController::class, 'getRead']); //recuperer les notifications lues
        Route::put('/{id}', [NotificationController::class, 'showNotification']); //marquer comme lue
        Route::delete('/{id}', [NotificationController::class, 'delete']); //supprimer une notification
    });
});
//AUTH

//login
Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/getuser', [AuthController::class, 'getUser']); // récupérer l'utilisateur connecté
        Route::put('/update-pin/{id_user}', [AuthController::class, 'update_pin']); // modifier le pin
        Route::get('/search', [UserController::class, 'search']); // tout les utilisateurs
        Route::get('/all', [UserController::class, 'index']); // tout les utilisateurs
        Route::get('/{id_user}', [UserController::class, 'show']); // un utilisateur
    });
});



//logout
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

//delete user
Route::middleware('auth:sanctum')->delete('delete/users/{id}', [UserController::class, 'destroy']);

// RECHERCHE

Route::middleware('auth:sanctum')->post('/users/search-by-role', [UserController::class, 'searchByRole']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/facture/create', [FactureController::class, 'createInvoice']);
    Route::post('/transaction/confirm', [TransactionController::class, 'confirmTransaction']);
});

// <<<<<<< HEAD


Route::post('/test-pusher', function () {
    event(new NewInvoiceNotification(999));
    return response()->json(['message' => 'Événement Pusher envoyé']);
});


// Dahsboards
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('credit', [dashboardCredit::class, 'index']);
        Route::get('assurance', [DashboardAssuranceController::class, 'index']);
    });
});
// >>>>>>> b9fb884e700fc9e86188df41a55166c090aa22b8
