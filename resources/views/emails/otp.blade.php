<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP - Votre code de validation</title>
</head>
<body>
    <p>Votre code OTP est : <strong>{{ $otp }}</strong></p>

    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>
</html>
