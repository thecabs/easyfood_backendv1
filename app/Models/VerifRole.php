<?php
namespace App\Models;

use Illuminate\Support\Facades\Auth;

class VerifRole {
    public  function isAdmin():bool{
        return Auth::user()->role == Roles::Admin->value;
    }
    public function isShop():bool{
        return Auth::user()->role == Roles::Shop->value;
    }
    public function isAssurance():bool{
        return Auth::user()->role == Roles::Assurance->value;
    }
    public function isEntreprise():bool{
        return Auth::user()->role == Roles::Entreprise->value;
    }
    public function isCaissiere():bool{
        return Auth::user()->role == Roles::Caissiere->value;
    }
    public function isEmploye():bool{
        return Auth::user()->role == Roles::Employe->value;
    }

}