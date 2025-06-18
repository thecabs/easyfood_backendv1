<?php
namespace App\Models;
enum Roles:string{
    case Admin = 'admin';
    case Shop = 'shop_gest';
    case Assurance = 'assurance_gest';
    case Entreprise = 'entreprise_gest';
    case Employe = 'employe';
    case Caissiere = 'caissiere';
}