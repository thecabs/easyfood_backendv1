<?php

namespace App\Notifications;

use App\Models\Roles;
use App\Models\Demande;
use App\Models\VerifRole;
use App\Models\Roles_demande;
use Illuminate\Bus\Queueable;
use App\Models\Statuts_demande;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DemandeNotification extends Notification
{
    use Queueable;

    protected $demande;

    public function __construct(Demande $demande)
    {
        $this->demande = $demande;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = '';
        $line = '';
        $emetteur = null;
        $destinataire = null;

        switch ($this->demande->role){
            case Roles_demande::Admin->value :
                $emetteur = config('app.name');
                break;
            case Roles_demande::Entreprise->value :
                $emetteur = 'l\'entreprise '.$this->demande->emetteur->entreprise->nom;
                break;
            case Roles_demande::Employe->value :
                $emetteur = 'l\'employe '.$this->demande->emetteur->nom;
                break;
            case Roles_demande::Shop->value :
                $emetteur = 'la boutique '.$this->demande->emetteur->shop->nom;
                break;
        }
        if($this->demande->statut !=  Statuts_demande::En_attente->value){
            switch ($this->demande->destinataire->role){
                case Roles::Admin->value :
                    $destinataire = config('app.name');
                    break;
                case Roles::Entreprise->value :
                    $destinataire = 'l\'entreprise '.$this->demande->destinataire->entreprise->nom;
                    break;
                case Roles::Employe->value :
                    $destinataire = 'l\'employe '.$this->demande->destinataire->nom;
                    break;
                case Roles::Shop->value :
                    $destinataire = 'la boutique '.$this->demande->destinataire->shop->nom;
                    break;
            }
        }

        switch ($this->demande->statut  ) {
            case Statuts_demande::En_attente->value:
                $subject = 'Nouvelle demande de crédit reçue';
                $line = 'Vous avez reçu une nouvelle demande de crédit de la part de ' . $emetteur ?? $this->demande->emetteur->nom. '.';
                break;

            case Statuts_demande::Valide->value:
                $subject = 'Votre demande de crédit a été validée';
                $line = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été validée.';
                break;

            case Statuts_demande::Refuse->value:
                $subject = 'Votre demande de crédit a été refusée';
                $line = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été refusée.';
                break;
            case Statuts_demande::Accorde->value:
                $subject = 'Votre demande de crédit a été accordée';
                $line = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été accordée.';
                break;

            default:
                $subject = 'Notification sur votre demande de crédit';
                $line = '';
                break;
        }

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting('Bonjour ' . $notifiable->nom . ',')
                    ->line($line)
                    ->line('veuillez vous connecter pour plus de détails:')
                    ->line('Merci de votre confiance.');
    }

    public function toDatabase($notifiable)
    {
        $message = '';
        $emetteur = null;
        $destinataire = null;

        switch ($this->demande->role){
            case Roles_demande::Admin->value :
                $emetteur = config('app.name');
                break;
            case Roles_demande::Entreprise->value :
                $emetteur = 'l\'entreprise '.$this->demande->emetteur->entreprise->nom;
                break;
            case Roles_demande::Employe->value :
                $emetteur = 'l\'employe '.$this->demande->emetteur->nom;
                break;
            case Roles_demande::Shop->value :
                $emetteur = 'la boutique '.$this->demande->emetteur->shop->nom;
                break;
        }
        if($this->demande->statut !=  Statuts_demande::En_attente->value){
            switch ($this->demande->destinataire->role){
                case Roles::Admin->value :
                    $destinataire = config('app.name');
                    break;
                case Roles::Entreprise->value :
                    $destinataire = 'l\'entreprise '.$this->demande->destinataire->entreprise->nom;
                    break;
                case Roles::Employe->value :
                    $destinataire = 'l\'employe '.$this->demande->destinataire->nom;
                    break;
                case Roles::Shop->value :
                    $destinataire = 'la boutique '.$this->demande->destinataire->shop->nom;
                    break;
            }
        }
        switch ($this->demande->statut  ) {
            case Statuts_demande::En_attente->value:
                $message = 'Vous avez reçu une nouvelle demande de crédit de '.$this->demande->montant.'U de la part de ' . $emetteur ?? $this->demande->emetteur->nom. '.';
                break;

            case Statuts_demande::Valide->value:
                $message = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été validée.';
                break;

            case Statuts_demande::Refuse->value:
                $message = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été refusée.';
                break;
            case Statuts_demande::Accorde->value:
                $message = 'Votre demande de crédit d’un montant de ' . $this->demande->montant . ' a '.$destinataire.' a été accordée.';
                break;

            default:
                $message = '';
                break;
        }

        return [
            'message' => $message,
            'demande_id' => $this->demande->id_demande,
            'date' => $this->demande->created_at,
            'de' => $this->demande->emetteur,
            'type' => 'demande',
        ];
    }

}