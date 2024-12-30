<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AssuranceController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\PartenaireShopController;
use App\Http\Controllers\CaissiereController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;


use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PartenaireShopGestController;
use App\Http\Controllers\AssuranceGestController;
use App\Http\Controllers\EntrepriseGestController;

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


// Administrateur
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminController::class, 'register']);
});


 





















// ASSURANCE 


 
// Routes protégées par middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/assurances', [AssuranceController::class, 'index']); // Liste des assurances
    Route::get('/assurances/{id}', [AssuranceController::class, 'show']); // Voir une assurance
    Route::post('/assurances', [AssuranceController::class, 'store']); // Créer une assurance
    Route::put('/assurances/{id}', [AssuranceController::class, 'update']); // Modifier une assurance
    Route::delete('/assurances/{id}', [AssuranceController::class, 'destroy']); // Supprimer une assurance 
});


 
 Route::middleware(['auth:sanctum'])->group(function () {
    // Route pour enregistrer un gestionnaire assurance
    Route::post('/assurance-gest/register', [AssuranceGestController::class, 'register']);
    Route::put('/assurance-gestupdate/{id_user}', [AssuranceGestController::class, 'updateProfile']);

    // Route pour confirmer l'OTP pour un gestionnaire assurance
    // Route::post('/assurance-gest/confirm-otp', [AssuranceGestController::class, 'confirmOtp']);
});





// ENTREPRISES 
Route::middleware(['auth:sanctum'])->group(function () {
     Route::get('/entreprises', [EntrepriseController::class, 'index']); // Liste des entreprises
    Route::get('/entreprises/{id}', [EntrepriseController::class, 'show']); // Affiche une entreprise spécifique
    Route::post('/entreprises', [EntrepriseController::class, 'store']); // Crée une nouvelle entreprise
    Route::put('/entreprises/{id}', [EntrepriseController::class, 'update']); // Met à jour une entreprise
    Route::delete('/entreprises/{id}', [EntrepriseController::class, 'destroy']); // Supprime une entreprise
 
});

 
Route::middleware(['auth:sanctum'])->group(function () {
    // Route pour enregistrer un gestionnaire pour une entreprise
    Route::post('/entreprise-gest/register', [EntrepriseGestController::class, 'register']);
    
    // Route pour update un gestionnaire d'entreprise
    Route::put('/entreprise-gestupdate/{id_user}', [EntrepriseGestController::class, 'updateProfile']);
});



// Employes


Route::middleware(['auth:sanctum'])->group(function () {

    // activer un compte
   
    Route::post('/employe/activate', [EmployeController::class, 'activate']);
    // Liste des employés
    Route::get('/employes', [EmployeController::class, 'index']);

    // Affiche un employé spécifique
    Route::get('/employes/{id}', [EmployeController::class, 'show']);
   
});

 
 
 //pour l'appli mobile

 Route::prefix('employe')->group(function () {
    Route::post('/register', [EmployeController::class, 'register']);
    Route::post('/validate-otp', [EmployeController::class, 'validateOtp']);
   
});
 
Route::middleware(['auth:sanctum'])->group(function () {

    // Mettre à jour un employé
    Route::put('/employes', [EmployeController::class, 'update']);
   });


// compte easyfood pour les employes 

// Route pour récupérer les détails d'un compte
Route::get('/compte/{numeroCompte}', [CompteController::class, 'getCompteDetails']);

// Route pour mettre à jour le PIN d'un compte
Route::post('/compte/{numeroCompte}/update-pin', [CompteController::class, 'updatePin']);


//les transactions pour les comptes


Route::middleware(['auth:sanctum'])->group(function () {


Route::post('/transactions', [TransactionController::class, 'store']);


});

Route::post('/depot', [TransactionController::class, 'effectuerTransaction']);




 




 










// USER
Route::post('/register', [UserController::class, 'createAccount']);

Route::post('/validate-otp', [UserController::class, 'validateOtp']);

Route::post('/activateAccount', [UserController::class, 'activateAccount']);



 



//AUTH

Route::post('/login', [AuthController::class, 'login']);


 

 

 



//entreprises










//PartenaireShop 


Route::get('/partenaires', [PartenaireShopController::class, 'index']);
Route::get('/partenaires/{id}', [PartenaireShopController::class, 'show']);
Route::post('/partenaires', [PartenaireShopController::class, 'store']);
Route::put('/partenaires/{id}', [PartenaireShopController::class, 'update']);
Route::delete('/partenaires/{id}', [PartenaireShopController::class, 'destroy']);


//Caissiere


Route::get('/caissieres', [CaissiereController::class, 'index']);
Route::get('/caissieres/{id}', [CaissiereController::class, 'show']);
Route::post('/caissieres', [CaissiereController::class, 'store']);
Route::put('/caissieres/{id}', [CaissiereController::class, 'update']);
Route::delete('/caissieres/{id}', [CaissiereController::class, 'destroy']);
