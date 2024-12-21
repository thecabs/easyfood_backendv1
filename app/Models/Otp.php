<?php

namespace App\Models;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'otp', 'expires_at'];

    public static function generateOtp($email)
    {
        $otp = rand(100000, 999999); // Génère un OTP à 6 chiffres
        $expires_at = now()->addMinutes(10); // L'OTP expire dans 5 minutes

        self::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => $expires_at,
        ]);

        // Envoyer l'OTP par email
        Mail::to($email)->send(new OtpMail($otp));

        return $otp;
    }

    public static function verifyOtp($email, $otp)
    {
        $otpRecord = self::where('email', $email)
                         ->where('otp', $otp)
                         ->where('expires_at', '>', now())
                         ->first();

        return $otpRecord ? true : false;
    }
}
?>