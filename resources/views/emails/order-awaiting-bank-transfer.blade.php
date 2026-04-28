<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Istruzioni bonifico - SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    @php
        $subtotal = number_format($order->payableTotalCents() / 100, 2, ',', '.');
        $hasBankCoordinates = filled($bankTransferDetails['iban'] ?? null);
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="background-color: #095866; padding: 28px 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 0.4px;">
                                SpediamoFacile
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 32px 18px;">
                            <h2 style="margin: 0 0 10px; color: #095866; font-size: 21px; line-height: 1.2;">
                                Ordine registrato, in attesa del bonifico
                            </h2>
                            <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Abbiamo registrato il tuo ordine <strong style="color: #1f2937;">#{{ $order->id }}</strong>.
                                Per avviare la spedizione, completa il bonifico usando i dati qui sotto e inserisci come causale
                                <strong style="color: #1f2937;">{{ $bankTransferDetails['reference'] }}</strong>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border: 1px solid #e5edf0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #667085; font-size: 13px; padding-bottom: 6px;">Numero ordine</td>
                                                <td align="right" style="color: #111827; font-size: 15px; font-weight: 700; padding-bottom: 6px;">#{{ $order->id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #667085; font-size: 13px; padding-bottom: 6px;">Data</td>
                                                <td align="right" style="color: #111827; font-size: 15px; padding-bottom: 6px;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #667085; font-size: 13px;">Importo da versare</td>
                                                <td align="right" style="color: #E44203; font-size: 18px; font-weight: 800;">{{ $subtotal }} &euro;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #dbe6ea; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td colspan="2" style="padding: 14px 18px; background-color: rgba(9, 88, 102, 0.06); color: #095866; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">
                                        Dati per il bonifico
                                    </td>
                                </tr>
                                @if($hasBankCoordinates)
                                    <tr>
                                        <td style="padding: 12px 18px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Beneficiario</td>
                                        <td style="padding: 12px 18px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $bankTransferDetails['beneficiary'] ?: 'SpediamoFacile' }}</td>
                                    </tr>
                                    @if(filled($bankTransferDetails['bank_name']))
                                        <tr>
                                            <td style="padding: 12px 18px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Banca</td>
                                            <td style="padding: 12px 18px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $bankTransferDetails['bank_name'] }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 12px 18px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">IBAN</td>
                                        <td style="padding: 12px 18px; color: #111827; font-size: 14px; font-weight: 700; letter-spacing: 0.04em; border-top: 1px solid #edf2f4;">{{ $bankTransferDetails['iban'] }}</td>
                                    </tr>
                                    @if(filled($bankTransferDetails['bic']))
                                        <tr>
                                            <td style="padding: 12px 18px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">BIC / SWIFT</td>
                                            <td style="padding: 12px 18px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $bankTransferDetails['bic'] }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 12px 18px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Causale</td>
                                        <td style="padding: 12px 18px; color: #111827; font-size: 14px; font-weight: 700; border-top: 1px solid #edf2f4;">{{ $bankTransferDetails['reference'] }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="2" style="padding: 16px 18px; color: #4b5563; font-size: 14px; line-height: 1.6; border-top: 1px solid #edf2f4;">
                                            Le coordinate bancarie non sono ancora configurate automaticamente.
                                            Rispondi a questa email oppure contatta
                                            <a href="mailto:{{ $bankTransferDetails['support_email'] }}" style="color: #095866; font-weight: 700; text-decoration: none;">{{ $bankTransferDetails['support_email'] }}</a>
                                            indicando il riferimento <strong>{{ $bankTransferDetails['reference'] }}</strong>.
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: rgba(228, 66, 3, 0.06); border: 1px solid rgba(228, 66, 3, 0.16); border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <p style="margin: 0 0 8px; color: #111827; font-size: 14px; font-weight: 700;">Prossimi passi</p>
                                        <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6;">
                                            Appena il bonifico risulta registrato, il tuo ordine entrer&agrave; in lavorazione e riceverai la conferma con gli aggiornamenti della spedizione.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f4f4f7; padding: 20px 32px; border-top: 1px solid #e8eef0;">
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 12px; text-align: center;">
                                SpediamoFacile &mdash; spedizioni semplici, veloci e convenienti.
                            </p>
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 11px; text-align: center;">
                                Assistenza:
                                <a href="mailto:{{ $bankTransferDetails['support_email'] }}" style="color: #095866; text-decoration: none;">{{ $bankTransferDetails['support_email'] }}</a>
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
