<!DOCTYPE html>
<html>

<head>
    <title>Demande de crédit</title>
</head>

<body>
    <p>Bonjour {{ $demande->emetteur->nom }},</p>
    <p>Votre demande de credit a </p>
    <p></p>
    @if ($demande->role == 'admin')
        la boutique <strong>{{ $demande->destinataire->shop->nom }}</strong>
    @elseif ($demande->role == 'entreprise_gest')
        <strong>EASY FOOD</strong>
    @elseif ($demande->role == 'employe')
        l'entreprise <strong>{{ $demande->destinataire->entreprise->nom }}</strong>
    @elseif ($demande->role == 'shop_gest')
        mr/mme<strong>{{ $demande->destinataire->nom }}</strong>
    @endif
    </p>
    @php
        use Carbon\Carbon;
    @endphp
    <ul>
        <li>Date d'envoie : {{ Carbon::parse($demande->created_at)->format('d/m/Y H:i') }}</li>
        <li>montant : {{ $demande->montant }}</li>
    </ul>
    <p>A été accordée.</p>
    <p>Veuillez vous connecter à votre compte pour plus de détails.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>

</html>
