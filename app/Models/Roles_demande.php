<?php
namespace App\Models;

enum Roles_demande:string{
    case Admin = 'admin';
    case Employe = 'employe';
    case Entreprise = 'entreprise_gest';
}