<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiornamento spedizione - SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    @php
        $totalPackages = $order->packages->sum(fn ($package) => max(1, (int) ($package->pivot->quantity ?? $package->quantity ?? 1)));
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

                    {{-- TITOLO AGGIORNAMENTO --}}
                    <tr>
                        <td style="padding: 32px 32px 16px;">
                            <h2 style="margin: 0 0 8px; color: #095866; font-size: 20px;">
                                Aggiornamento spedizione
                            </h2>
                            <p style="margin: 0; color: #555; font-size: 15px; line-height: 1.5;">
                                Lo stato del tuo ordine <strong>#{{ $order->id }}</strong> è stato aggiornato.
                            </p>
                        </td>
                    </tr>

                    {{-- TRANSIZIONE STATO --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="42%" align="center" valign="middle" style="padding: 12px 8px;">
                                                    <p style="margin: 0 0 4px; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Stato precedente</p>
                                                    <p style="margin: 0; color: #666; font-size: 15px; font-weight: 600;">{{ $oldStatusLabel }}</p>
                                                </td>
                                                <td width="16%" align="center" valign="middle" style="padding: 12px 0;">
                                                    <span style="display: inline-block; color: #095866; font-size: 22px; font-weight: bold;">&rarr;</span>
                                                </td>
                                                <td width="42%" align="center" valign="middle" style="padding: 12px 8px;">
                                                    <p style="margin: 0 0 4px; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Nuovo stato</p>
                                                    @php
                                                        $statusColor = match($newStatus) {
                                                            'delivered', 'completed' => '#16a34a',
                                                            'label_generated' => '#2563eb',
                                                            'in_transit', 'out_for_delivery' => '#095866',
                                                            'in_giacenza' => '#d97706',
                                                            'cancelled', 'payment_failed', 'refused' => '#dc2626',
                                                            'refunded', 'returned' => '#7c3aed',
                                                            default => '#095866',
                                                        };
                                                    @endphp
                                                    <p style="margin: 0; color: {{ $statusColor }}; font-size: 17px; font-weight: 700;">{{ $newStatusLabel }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- MESSAGGIO CONTESTUALE --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            @php
                                $statusMessage = match($newStatus) {
                                    'label_generated' => 'Il tuo pacco è stato preparato e l\'etichetta di spedizione è stata generata. Il corriere BRT provvederà al ritiro.',
                                    'in_transit' => 'Il tuo pacco è stato affidato al corriere BRT ed è in viaggio verso la destinazione.',
                                    'out_for_delivery' => 'Il tuo pacco è in consegna! Il corriere BRT lo sta portando all\'indirizzo indicato.',
                                    'delivered' => 'Il tuo pacco è stato consegnato con successo. Grazie per aver scelto SpediamoFacile!',
                                    'completed' => 'Il tuo ordine è stato completato. Grazie per aver scelto SpediamoFacile!',
                                    'in_giacenza' => 'Il tuo pacco è attualmente in giacenza presso il deposito BRT. Il corriere effettuerà un nuovo tentativo di consegna.',
                                    'returned' => 'Il tuo pacco è stato restituito al mittente. Contatta il nostro supporto per ulteriori informazioni.',
                                    'refused' => 'La consegna del pacco è stata rifiutata dal destinatario. Contatta il nostro supporto per ulteriori informazioni.',
                                    'cancelled' => 'Il tuo ordine è stato annullato. Se non hai richiesto tu l\'annullamento, contatta il nostro supporto.',
                                    'refunded' => 'Il rimborso per il tuo ordine è stato elaborato. L\'importo verrà accreditato entro qualche giorno lavorativo.',
                                    'processing' => 'Il tuo ordine è in fase di lavorazione. Riceverai aggiornamenti sullo stato della spedizione.',
                                    default => 'Lo stato del tuo ordine è stato aggiornato. Consulta i dettagli qui sotto.',
                                };
                            @endphp
                            <p style="margin: 0; color: #555; font-size: 14px; line-height: 1.6;">
                                {{ $statusMessage }}
                            </p>
                        </td>
                    </tr>

                    {{-- RIEPILOGO ORDINE --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fafafa; border-radius: 6px; border: 1px solid #eee;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Numero ordine</td>
                                                <td align="right" style="color: #222; font-size: 15px; font-weight: 600; padding-bottom: 6px;">#{{ $order->id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Data ordine</td>
                                                <td align="right" style="color: #222; font-size: 15px; padding-bottom: 6px;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Pacchi</td>
                                                <td align="right" style="color: #222; font-size: 15px; padding-bottom: 6px;">{{ $totalPackages }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px;">Importo</td>
                                                <td align="right" style="color: #095866; font-size: 17px; font-weight: 700;">{{ number_format($order->payableTotalCents() / 100, 2, ',', '.') }} &euro;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- INDIRIZZI DESTINATARIO --}}
                    @php
                        $firstPackage = $order->packages->first();
                        $destination = $firstPackage?->destinationAddress;
                    @endphp

                    @if($destination)
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <p style="margin: 0 0 6px; color: #095866; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Destinatario</p>
                                        <p style="margin: 0; color: #333; font-size: 14px; font-weight: 600;">{{ $destination->name }}</p>
                                        <p style="margin: 4px 0 0; color: #666; font-size: 13px; line-height: 1.5;">
                                            {{ $destination->address }} {{ $destination->address_number }}<br>
                                            {{ $destination->postal_code }} {{ $destination->city }} ({{ $destination->province }})
                                        </p>
                                    </td>
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

                    {{-- TRACKING NUMBER --}}
                    @if($order->brt_tracking_number)
                    <tr>
                        <td style="padding: 0 32px 16px;">
                            <p style="margin: 0; color: #888; font-size: 13px; text-align: center;">
                                Numero tracking: <strong style="color: #333;">{{ $order->brt_tracking_number }}</strong>
                            </p>
                        </td>
                    </tr>
                    @endif

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
