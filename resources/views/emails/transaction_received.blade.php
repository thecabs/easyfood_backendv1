
<!DOCTYPE html>
<html>

<head>
    <title>Transaction</title>
</head>

<body>
    <p>Bonjour {{ $transaction->compteDestinataire->user->nom }},</p>
    <p>Vous venez de faire recu des credit de  mr/mme <strong>{{ $transaction->compteEmetteur->user->nom }}</strong></p>
    <ul>
        <li>montant : {{ $transaction->montant }}</li>
    </ul>
    <p>Veuillez vous connecter à votre compte pour plus de détails.</p>
    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
</body>

</html>
