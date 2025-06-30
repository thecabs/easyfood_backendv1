<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Compte extends Model
{
    use HasFactory;

    // Définir la clé primaire comme `numero_compte`
    protected $primaryKey = 'numero_compte';
    public $incrementing = false; // Désactiver l'auto-incrémentation
    protected $keyType = 'string'; // Spécifier que la clé est une chaîne

    // Champs pouvant être remplis via un formulaire ou une requête
    protected $fillable = ['numero_compte', 'solde', 'date_creation', 'id_user', 'pin'];

    // Relation avec le modèle User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Recuperer les transaction emises
    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'id_compte_emetteur');
    }
    // Recuperer les transaction recue
    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'id_compte_destinataire');
    }

    // Génération d'un numéro de compte unique
    public static function generateNumeroCompte($user)
    {
        if (!$user->id) {
            $user->save();
        }

        $idUser = $user->id;
        $date = now()->format('Ym');
        $attempt = 0;

        do {
            $randomSuffix = strtoupper(substr(uniqid(), -4));
            $numeroCompte = 'CPT-' . $date . '-' . str_pad($idUser, 6, '0', STR_PAD_LEFT) . '-' . $randomSuffix;

            $exists = self::where('numero_compte', $numeroCompte)->exists();
            $attempt++;
        } while ($exists && $attempt < 5);

        if ($attempt >= 5) {
            throw new \Exception("Impossible de générer un numéro de compte unique.");
        }

        return $numeroCompte;
    }

    // Génération d'un PIN par défaut
    public static function generateDefaultPin()
    {
        return rand(1000, 9999); // PIN à 4 chiffres
    }

    // Définir le PIN crypté
    public function setPin($plainPin)
    {
        $this->pin = Hash::make($plainPin);
        $this->save();
    }

    // Vérifier le PIN
    public function verifyPin($plainPin)
    {
        return Hash::check($plainPin, $this->pin);
    }
}
