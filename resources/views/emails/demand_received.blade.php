<!DOCTYPE html>
<html>
    <head>
    <title>Demande de crédit</title>
</head>
<body>
    <p>Bonjour {{ $demande->destinataire->nom }},</p>
    <p>Vous avez recu une demande de credit de la part de:
        @if ($demande->role == 'admin')
        EASY FOOD
        @elseif ($demande->role == 'entreprise_gest')
         l'entreprise <strong>{{ $demande->emetteur->entreprise->nom }}</strong>
        @elseif ($demande->role == 'employe')
         l'employé <strong>{{ $demande->emetteur->nom }}</strong>
        @elseif ($demande->role == 'shop_gest')
         la boutique <strong>{{ $demande->emetteur->shop->nom }}</strong>
        @endif
    </p>
    <ul>
        <li>Nom : {{ $demande->emetteur->nom }}</li>
        <li>Email : {{ $demande->emetteur->email }}</li>
        <li>Tel : {{ $demande->emetteur->tel }}</li>
        <li>montant : {{ $demande->montant }}</li>
    </ul>
    <p>Veuillez vous connecter à votre compte pour traiter cette demande.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>
</html>
