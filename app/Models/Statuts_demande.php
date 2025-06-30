<?php
namespace App\Models;

enum Statuts_demande:string{
    case Valide = "validé";
    case En_attente = "en attente";
    case Accorde = "accordé";
    case Refuse = "refusé";
    case Annule = "annulé";
}