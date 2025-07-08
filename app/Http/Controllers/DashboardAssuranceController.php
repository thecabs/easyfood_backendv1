<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Assurance;
use App\Models\User;
use App\Models\VerifRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardAssuranceController extends Controller
{
    function index(Request $request){
        $user = Auth::user();
        $verifRole = new VerifRole();

        if(!$verifRole->isAssurance()){
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
            ], 403);
        }

        $employes = Assurance::getEmployeAssurance($user->id_assurance)->paginate($request->get('rows',10));
        $entreprises = $user->assurance->entreprises()->paginate($request->get('rows',10));

        return response()->json([
            'employes' => $employes,
            'entreprises' => $entreprises,
        ],200);
    }

}
