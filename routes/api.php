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


 
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmployeurGestController;
use App\Http\Controllers\PartenaireShopGestController;
use App\Http\Controllers\CaissiereGestController;
use App\Http\Controllers\AssuranceGestController;
use App\Http\Controllers\EntrepriseGestController;

// Employé
Route::prefix('employe')->group(function () {
    Route::post('/register', [EmployeController::class, 'register']);
    Route::post('/validate-otp', [EmployeController::class, 'validateOtp']);
    Route::post('/activate', [EmployeController::class, 'activate']);
});

// SuperAdmin
Route::prefix('superadmin')->group(function () {
    Route::post('/register', [SuperAdminController::class, 'register']);
});

// Administrateur
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminController::class, 'register']);
});

// Entreprise Gestionnaire
Route::prefix('entreprise-gest')->group(function () {
    Route::post('/register', [EntrepriseGestController::class, 'register']);
});

// Partenaire Shop Gestionnaire
Route::prefix('partenaire-shop-gest')->group(function () {
    Route::post('/register', [PartenaireShopGestController::class, 'register']);
         Route::get('/list', [PartenaireShopGestController::class, 'list']);
    });
    
 

// Caissière Gestionnaire
Route::prefix('caissiere-gest')->group(function () {
    Route::post('/register', [CaissiereController::class, 'register']);
});

// Assurance Gestionnaire
Route::prefix('assurance-gest')->group(function () {
    Route::post('/register', [AssuranceGestController::class, 'register']);
});





 
// Modifier le profil du gestionnaire
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/entreprise_gest/profile', [EntrepriseGestController::class, 'updateProfile']);
});

// CRUD pour admin et superadmin
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/entreprise_gest/crud', [EntrepriseGestController::class, 'crud']);
});





















// USER
Route::post('/register', [UserController::class, 'createAccount']);

Route::post('/validate-otp', [UserController::class, 'validateOtp']);

Route::post('/activateAccount', [UserController::class, 'activateAccount']);



 



//AUTH

Route::post('/login', [AuthController::class, 'login']);


// ROLE 




Route::get('/roles', [RoleController::class, 'index']); // Lister tous les rôles
Route::post('/roles', [RoleController::class, 'store']); // Créer un rôle
Route::put('/roles/{id}', [RoleController::class, 'update']); // Mettre à jour un rôle
Route::delete('/roles/{id}', [RoleController::class, 'destroy']); // Supprimer un rôle




// assurances




Route::prefix('assurances')->group(function () {
    Route::get('/', [AssuranceController::class, 'index']); // Liste des assurances
    Route::get('/{id}', [AssuranceController::class, 'show']); // Détails d'une assurance
    Route::post('/', [AssuranceController::class, 'store']); // Créer une assurance
    Route::put('/{id}', [AssuranceController::class, 'update']); // Mettre à jour une assurance
    Route::delete('/{id}', [AssuranceController::class, 'destroy']); // Supprimer une assurance
});




//entreprises




Route::prefix('entreprises')->group(function () {
    Route::get('/', [EntrepriseController::class, 'index']); // Liste des entreprises
    Route::get('/{id}', [EntrepriseController::class, 'show']); // Affiche une entreprise spécifique
    Route::post('/', [EntrepriseController::class, 'store']); // Crée une nouvelle entreprise
    Route::put('/{id}', [EntrepriseController::class, 'update']); // Met à jour une entreprise
    Route::delete('/{id}', [EntrepriseController::class, 'destroy']); // Supprime une entreprise
});




//employes 





Route::prefix('employes')->group(function () {
    Route::get('/', [EmployeController::class, 'index']); // Liste des employés
    Route::get('/{id}', [EmployeController::class, 'show']); // Affiche un employé spécifique
    Route::post('/', [EmployeController::class, 'create']); // Crée un nouvel employé
    Route::put('/{id}', [EmployeController::class, 'update']); // Met à jour un employé
    Route::delete('/{id}', [EmployeController::class, 'destroy']); // Supprime un employé
});

Route::prefix('employe')->group(function () {
    Route::post('/create', [EmployeController::class, 'create']);
    Route::post('/validate-otp', [EmployeController::class, 'validateOtp']);
});

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
