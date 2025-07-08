<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerifRole;
use App\Models\QueryFiler;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    // recuperer tout ses clients
    public function index(Request $request){
        $verifRole = new VerifRole();
        if(!$verifRole->isAdmin() && !$verifRole->isShop() && !$verifRole->isCaissiere()){
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }

        $query = User::query();
        if($verifRole->isShop() || $verifRole->isCaissiere()){
            $query = User::where(function($q){
                $q->where('id_shop', auth()->user()->id_shop)
                  ->orWhere('id_partenaire', auth()->user()->id_partenaire);
            });
        }
        // definir les champs concerne par le filtre global
        $globalSearchFields = ['nom','tel','email'];
        $filter = new QueryFiler([], $globalSearchFields, 'id_stock');
        $query = $filter->apply($query, $request);
        $stocks = $query->paginate($request->get('rows', 10));

        $last_stock = collect($stocks->items())->last();
        //pagination
        $response = [
            'data' => $stocks->items(),
            'last_item' => $last_stock,
            'current_page' => $stocks->currentPage(),
            'last_page' => $stocks->lastPage(),
            'per_page' => $stocks->perPage(),
            'total' => $stocks->total(),
        ];

        return response()->json($response);
    }
    public function potentialClient(Request $request){
        $verifRole = new VerifRole();
        if(!$verifRole->isAdmin() && !$verifRole->isShop() && !$verifRole->isCaissiere()){
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            ], 403);
        }

        $query = User::query();
        // definir les champs concerne par le filtre global
        $globalSearchFields = ['nom','tel','email'];
        $filter = new QueryFiler([], $globalSearchFields, 'id_stock');
        $query = $filter->apply($query, $request);
        $stocks = $query->paginate($request->get('rows', 10));

        $last_stock = collect($stocks->items())->last();
        //pagination
        $response = [
            'data' => $stocks->items(),
            'last_item' => $last_stock,
            'current_page' => $stocks->currentPage(),
            'last_page' => $stocks->lastPage(),
            'per_page' => $stocks->perPage(),
            'total' => $stocks->total(),
        ];

        return response()->json($response);
    }
}
