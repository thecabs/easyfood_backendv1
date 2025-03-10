<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture #{{ $facture->id_facture }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { width: 100%; margin-bottom: 20px; }
        .details td { padding: 5px; }
        .products { width: 100%; border-collapse: collapse; }
        .products th, .products td { border: 1px solid #000; padding: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Facture #{{ $facture->id_facture }}</h2>
        <p>Date : {{ $facture->date_facturation }}</p>
    </div>
    <table class="details">
        <tr>
            <td><strong>Client :</strong> {{ $client->numero_compte }}</td>
            <td><strong>Vendeur :</strong> {{ $vendeur->numero_compte }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Montant :</strong> {{ $facture->montant }} FCFA</td>
        </tr>
    </table>
    <h4>Détails de la facture</h4>
    <!-- Vous pouvez ici lister les lignes de facture -->
</body>
</html>
