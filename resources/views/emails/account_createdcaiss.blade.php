<!DOCTYPE html>
<html>
<head>
    <title>Votre compte a été créé</title>
</head>
<body>
    <p>Bonjour {{ $user->nom }},</p>
    <p>Votre compte de caissière a été créé avec succès. Voici vos informations de connexion :</p>
    <ul>
        <li><strong>Email :</strong> {{ $user->email }}</li>
        <li><strong>Mot de passe :</strong> {{ $password }}</li>
    </ul>
    <p>Nous vous recommandons de changer votre mot de passe après votre première connexion.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>
</html>
