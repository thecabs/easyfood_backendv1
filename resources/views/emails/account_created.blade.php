<!DOCTYPE html>
<html>
<head>
    <title>Votre compte a été créé</title>
</head>
<body>
    <p>Bonjour {{ $user->nom }},</p>
    <p>Votre compte de gestionnaire a été créé avec succès. Voici vos informations de connexion :</p>
    <ul>
        <li>Email : {{ $user->email }}</li>
        <li>Mot de passe : {{ $password }}</li>
    </ul>
    <p>Veuillez vous connecter et changer votre mot de passe si nécessaire.</p>
    <p>Cordialement,</p>
    <p>L'équipe TacTicTECH</p>
</body>
</html>
