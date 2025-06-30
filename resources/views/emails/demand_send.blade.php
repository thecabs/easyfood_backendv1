<!DOCTYPE html>
<html>
<head>
    <title>Votre demande a été créé</title>
</head>
<body>
    <p>Bonjour {{ $user->nom }},</p>
    <p>Votre compte de gestionnaire a été créé avec succès. Voici vos informations de connexion :</p>
    <ul>
        <li>Email : {{ $user->email }}</li>
        <li>Mot de passe : {{ $password }}</li>
    </ul>

    <p>Voici les détails de votre compte IFC:</p>
    <ul>
        <li>Numéro de compte : {{ $compte->numero_compte }}</li>
        <li>PIN : {{ $pin }}</li>
    </ul>

    <p>Veuillez vous connecter et changer votre mot de passe et PIN si nécessaire.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>
</html>
