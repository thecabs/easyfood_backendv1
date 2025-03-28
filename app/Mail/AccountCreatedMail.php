<?php

namespace App\Mail;

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
        return $this->view('emails.account_created')
            ->subject('Votre compte gestionnaire assurance a été créé')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
                'compte' => $this->compte,
                'pin' => $this->pin,
            ]);
    }
}
