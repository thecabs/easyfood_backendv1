<?php
namespace App\Models;

enum TypeTransaction:string{
    case REMBOURSEMENT = "remboursement";
    case RECHARGEADMIN = "recharge-admin";
    case RECHARGEENTREPRISE = "recharge-entreprise";
    case RECHARGEEMPLOYE= "recharge-employe";
    case ACHAT= "achat";
}