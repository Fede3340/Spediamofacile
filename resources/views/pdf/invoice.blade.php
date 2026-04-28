{{--
  FILE: resources/views/pdf/invoice.blade.php
  SCOPO: Template HTML per fattura/ricevuta ordine.
         Predisposto per uso con barryvdh/laravel-dompdf quando installato.
         Attualmente il PDF viene generato da InvoicePdfService con raw PDF.

  VARIABILI:
    - $order (Order con relazioni user, packages, originAddress, destinationAddress)
    - $orderNumber (string, es. "SF-000042")
    - $subtotalCents (int, totale in centesimi)
    - $imponibileCents (int, imponibile in centesimi)
    - $ivaCents (int, IVA in centesimi)
--}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ricevuta {{ $orderNumber }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #333; margin: 0; padding: 32px; }
        h1 { color: #095866; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #095866; font-size: 15px; margin: 24px 0 10px; }
        .header { text-align: center; border-bottom: 2px solid #095866; padding-bottom: 16px; margin-bottom: 24px; }
        .subtitle { color: #777; font-size: 12px; }
        .info-row { margin: 4px 0; }
        .info-label { font-weight: 600; display: inline-block; width: 140px; }
        table.packages { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.packages th { background: #f0f4f5; padding: 8px 10px; text-align: left; font-size: 12px; border-bottom: 1px solid #ddd; }
        table.packages td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12px; }
        .totals { text-align: right; margin-top: 16px; }
        .totals .row { margin: 4px 0; }
        .totals .total { font-size: 16px; font-weight: 700; color: #095866; border-top: 2px solid #095866; padding-top: 8px; margin-top: 8px; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 11px; border-top: 1px solid #ddd; padding-top: 12px; }
        .addresses { display: flex; gap: 20px; }
        .address-box { flex: 1; background: #f8fafb; border: 1px solid #e8eef0; border-radius: 6px; padding: 12px; }
        .address-label { color: #095866; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SpediamoFacile</h1>
        <p class="subtitle">Ricevuta di spedizione</p>
    </div>

    <h2>Dettagli ordine</h2>
    <div class="info-row"><span class="info-label">Numero ordine:</span> {{ $orderNumber }}</div>
    <div class="info-row"><span class="info-label">Data:</span> {{ $order->created_at?->format('d/m/Y H:i') ?? 'n/d' }}</div>
    @if($order->brt_parcel_id)
    <div class="info-row"><span class="info-label">Codice BRT:</span> {{ $order->brt_parcel_id }}</div>
    @endif
    @if($order->brt_tracking_number)
    <div class="info-row"><span class="info-label">Tracking:</span> {{ $order->brt_tracking_number }}</div>
    @endif

    <h2>Dati cliente</h2>
    @php
        $billingData = $order->billing_data;
        $userName = trim(($order->user->name ?? '') . ' ' . ($order->user->surname ?? ''));
    @endphp
    @if(is_array($billingData) && !empty($billingData))
        <div class="info-row"><span class="info-label">Nome:</span> {{ $billingData['name'] ?? $userName }}</div>
        @if(!empty($billingData['fiscal_code']))
        <div class="info-row"><span class="info-label">Cod. Fiscale:</span> {{ $billingData['fiscal_code'] }}</div>
        @endif
        @if(!empty($billingData['vat_number']))
        <div class="info-row"><span class="info-label">P.IVA:</span> {{ $billingData['vat_number'] }}</div>
        @endif
        @if(!empty($billingData['address']))
        <div class="info-row"><span class="info-label">Indirizzo:</span> {{ $billingData['address'] }}</div>
        @endif
    @else
        <div class="info-row"><span class="info-label">Nome:</span> {{ $userName }}</div>
        @if($order->user?->email)
        <div class="info-row"><span class="info-label">Email:</span> {{ $order->user->email }}</div>
        @endif
    @endif

    @php
        $firstPackage = $order->packages->first();
        $origin = $firstPackage?->originAddress;
        $destination = $firstPackage?->destinationAddress;
    @endphp

    @if($origin || $destination)
    <h2>Indirizzi spedizione</h2>
    <div class="addresses">
        @if($origin)
        <div class="address-box">
            <div class="address-label">Mittente</div>
            <strong>{{ $origin->name }}</strong><br>
            {{ $origin->address }} {{ $origin->address_number }}<br>
            {{ $origin->postal_code }} {{ $origin->city }} ({{ $origin->province }})
        </div>
        @endif
        @if($destination)
        <div class="address-box">
            <div class="address-label">Destinatario</div>
            <strong>{{ $destination->name }}</strong><br>
            {{ $destination->address }} {{ $destination->address_number }}<br>
            {{ $destination->postal_code }} {{ $destination->city }} ({{ $destination->province }})
        </div>
        @endif
    </div>
    @endif

    <h2>Pacchi ({{ $order->packages->count() }})</h2>
    <table class="packages">
        <thead>
            <tr>
                <th>N.</th>
                <th>Tipo</th>
                <th>Dimensioni (cm)</th>
                <th>Peso</th>
                <th>Prezzo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->packages as $index => $package)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $package->package_type ?? 'Pacco' }}</td>
                <td>{{ $package->first_size }} x {{ $package->second_size }} x {{ $package->third_size }}</td>
                <td>{{ $package->weight }} kg</td>
                <td>{{ number_format($package->getRawOriginal('single_price') / 100, 2, ',', '.') }} &euro;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        @if(!empty($discountCents))
            <div class="row">Totale lordo: {{ number_format(($grossSubtotalCents ?? $subtotalCents + $discountCents) / 100, 2, ',', '.') }} &euro;</div>
            <div class="row">Sconto: -{{ number_format($discountCents / 100, 2, ',', '.') }} &euro;</div>
        @endif
        <div class="row">Imponibile: {{ number_format($imponibileCents / 100, 2, ',', '.') }} &euro;</div>
        <div class="row">IVA (22%): {{ number_format($ivaCents / 100, 2, ',', '.') }} &euro;</div>
        <div class="row total">TOTALE: {{ number_format($subtotalCents / 100, 2, ',', '.') }} &euro;</div>
    </div>

    <div class="footer">
        <p>SpediamoFacile &mdash; Spedizioni semplici, veloci e convenienti</p>
        <p>Documento generato automaticamente &mdash; Non costituisce fattura fiscale</p>
        <p>assistenza@spediamofacile.it</p>
    </div>
</body>
</html>
