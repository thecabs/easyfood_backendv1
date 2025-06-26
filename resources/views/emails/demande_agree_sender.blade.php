<!DOCTYPE html>
<html>

<head>
    <title>Transaction</title>
</head>

<body>
    <p>Bonjour {{ $demande->destinataire->nom }},</p>
    <p>Vous venez de faire un dépot a </p>
    <p></p>
    @if ($demande->role == 'admin')
    <strong>EASY FOOD</strong>
    @elseif ($demande->role == 'entreprise_gest')
    l'entreprise <strong>{{ $demande->emetteur->entreprise->nom }}</strong>
    @elseif ($demande->role == 'employe')
    mr/mme <strong>{{ $demande->emetteur->nom }}</strong>
    @elseif ($demande->role == 'shop_gest')
    la boutique <strong>{{ $demande->emetteur->shop->nom }}</strong>
    @endif
    </p>
    @php
        use Carbon\Carbon;
    @endphp
    <ul>
        <li>montant : {{ $demande->montant }}</li>
    </ul>
    <p>Veuillez vous connecter à votre compte pour plus de détails.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>

</html>