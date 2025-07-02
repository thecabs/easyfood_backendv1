<?php

namespace App\Mail;

use App\Models\Roles;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $compte;
    public $pin;

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $password
     * @param $compte
     * @param $pin
     */
    public function __construct($user, $password, $compte, $pin)
    {
        $this->user = $user;
        $this->password = $password;
        $this->compte = $compte;
        $this->pin = $pin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->user->role == Roles::Admin->value){

            return $this->view('emails.account_created')
                ->subject('Votre compte administrateur a été créé')
                ->with([
                    'user' => $this->user,
                    'password' => $this->password,
                    'compte' => $this->compte,
                    'pin' => $this->pin,
                ]);
        }
        if($this->user->role == Roles::Shop->value){

            return $this->view('emails.account_created')
                ->subject('Votre compte gestionnaire shop a été créé')
                ->with([
                    'user' => $this->user,
                    'password' => $this->password,
                    'compte' => $this->compte,
                    'pin' => $this->pin,
                ]);
        }
        if($this->user->role == Roles::Entreprise->value){

            return $this->view('emails.account_created')
                ->subject('Votre compte gestionnaire entreprise a été créé')
                ->with([
                    'user' => $this->user,
                    'password' => $this->password,
                    'compte' => $this->compte,
                    'pin' => $this->pin,
                ]);
        }

    }
}
