<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma ordine - SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    @php
        $packageRows = $order->packages;
        $totalPackages = $packageRows->sum(fn ($package) => max(1, (int) ($package->pivot->quantity ?? $package->quantity ?? 1)));
        $totalCents = $order->payableTotalCents();
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background-color: #095866; padding: 28px 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">
                                SpediamoFacile
                            </h1>
                        </td>
                    </tr>

                    {{-- CONFERMA --}}
                    <tr>
                        <td style="padding: 32px 32px 16px;">
                            <h2 style="margin: 0 0 8px; color: #095866; font-size: 20px;">
                                Ordine confermato!
                            </h2>
                            <p style="margin: 0; color: #555; font-size: 15px; line-height: 1.5;">
                                Grazie per il tuo ordine. Il pagamento è stato ricevuto e la tua spedizione è in lavorazione.
                            </p>
                        </td>
                    </tr>

                    {{-- RIEPILOGO ORDINE --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Numero ordine</td>
                                                <td align="right" style="color: #222; font-size: 15px; font-weight: 600; padding-bottom: 6px;">#{{ $order->id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Data</td>
                                                <td align="right" style="color: #222; font-size: 15px; padding-bottom: 6px;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px;">Importo totale</td>
                                                <td align="right" style="color: #095866; font-size: 17px; font-weight: 700;">{{ number_format($totalCents / 100, 2, ',', '.') }} &euro;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- LISTA PACCHI --}}
                    @if($packageRows->count() > 0)
                    <tr>
                        <td style="padding: 16px 32px 8px;">
                            <h3 style="margin: 0 0 12px; color: #333; font-size: 16px; font-weight: 600;">
                                Pacchi ({{ $totalPackages }})
                            </h3>
                        </td>
                    </tr>
                    @foreach($packageRows as $index => $package)
                    <tr>
                        <td style="padding: 0 32px 12px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fafafa; border-radius: 6px; border: 1px solid #eee;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <p style="margin: 0 0 4px; color: #333; font-size: 14px; font-weight: 600;">
                                            {{ $package->package_type ?? 'Pacco' }} #{{ $index + 1 }}
                                            @php
                                                $packageQuantity = max(1, (int) ($package->pivot->quantity ?? $package->quantity ?? 1));
                                            @endphp
                                            @if($packageQuantity > 1)
                                                <span style="color: #666; font-weight: 500;">&times; {{ $packageQuantity }}</span>
                                            @endif
                                        </p>
                                        <p style="margin: 0; color: #666; font-size: 13px; line-height: 1.5;">
                                            Peso: {{ $package->weight }} kg
                                            &nbsp;&bull;&nbsp;
                                            Dimensioni: {{ $package->first_size }} x {{ $package->second_size }} x {{ $package->third_size }} cm
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                    @endif

                    {{-- INDIRIZZI --}}
                    @php
                        $firstPackage = $order->packages->first();
                        $origin = $firstPackage?->originAddress;
                        $destination = $firstPackage?->destinationAddress;
                    @endphp

                    @if($origin || $destination)
                    <tr>
                        <td style="padding: 16px 32px 8px;">
                            <h3 style="margin: 0 0 12px; color: #333; font-size: 16px; font-weight: 600;">Indirizzi</h3>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    {{-- MITTENTE --}}
                                    @if($origin)
                                    <td width="48%" valign="top" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0; padding: 14px 16px;">
                                        <p style="margin: 0 0 6px; color: #095866; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Mittente</p>
                                        <p style="margin: 0; color: #333; font-size: 14px; font-weight: 600;">{{ $origin->name }}</p>
                                        <p style="margin: 4px 0 0; color: #666; font-size: 13px; line-height: 1.5;">
                                            {{ $origin->address }} {{ $origin->address_number }}<br>
                                            {{ $origin->postal_code }} {{ $origin->city }} ({{ $origin->province }})
                                        </p>
                                    </td>
                                    @endif

                                    @if($origin && $destination)
                                    <td width="4%">&nbsp;</td>
                                    @endif

                                    {{-- DESTINATARIO --}}
                                    @if($destination)
                                    <td width="48%" valign="top" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0; padding: 14px 16px;">
                                        <p style="margin: 0 0 6px; color: #095866; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Destinatario</p>
                                        <p style="margin: 0; color: #333; font-size: 14px; font-weight: 600;">{{ $destination->name }}</p>
                                        <p style="margin: 4px 0 0; color: #666; font-size: 13px; line-height: 1.5;">
                                            {{ $destination->address }} {{ $destination->address_number }}<br>
                                            {{ $destination->postal_code }} {{ $destination->city }} ({{ $destination->province }})
                                        </p>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- TRACKING LINK --}}
                    @if($order->brt_tracking_url)
                    <tr>
                        <td style="padding: 8px 32px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 16px 0;">
                                        <a href="{{ $order->brt_tracking_url }}" target="_blank" style="display: inline-block; background-color: #095866; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 28px; border-radius: 6px;">
                                            Traccia la tua spedizione
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- INFO --}}
                    <tr>
                        <td style="padding: 8px 32px 24px;">
                            <p style="margin: 0; color: #888; font-size: 13px; line-height: 1.5; text-align: center;">
                                Riceverai una seconda email con l'etichetta di spedizione BRT non appena sarà generata.
                            </p>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #f4f4f7; padding: 20px 32px; border-top: 1px solid #e8eef0;">
                            <p style="margin: 0 0 4px; color: #999; font-size: 12px; text-align: center;">
                                SpediamoFacile &mdash; Spedizioni semplici, veloci e convenienti.
                            </p>
                            <p style="margin: 0 0 4px; color: #bbb; font-size: 11px; text-align: center;">
                                Per assistenza: <a href="mailto:assistenza@spediamofacile.it" style="color: #095866; text-decoration: none;">assistenza@spediamofacile.it</a>
                            </p>
                            <p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center;">
                                <a href="{{ url('/account/notifiche') }}" style="color: #095866; text-decoration: underline;">Gestisci preferenze notifiche</a>
                                &middot; <a href="{{ url('/privacy-policy') }}" style="color: #095866; text-decoration: underline;">Privacy Policy</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
