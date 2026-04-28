<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo utilizzo referral - SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
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

                    {{-- TITOLO --}}
                    <tr>
                        <td style="padding: 32px 32px 16px;">
                            <h2 style="margin: 0 0 8px; color: #095866; font-size: 20px;">
                                Nuovo utilizzo del tuo codice referral
                            </h2>
                            <p style="margin: 0; color: #555; font-size: 15px; line-height: 1.5;">
                                Il tuo codice referral è stato utilizzato per un nuovo ordine.
                            </p>
                        </td>
                    </tr>

                    {{-- DETTAGLI --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border-radius: 6px; border: 1px solid #e8eef0;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Ordine</td>
                                                <td align="right" style="color: #222; font-size: 15px; font-weight: 600; padding-bottom: 6px;">#{{ $usage->order_id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px; padding-bottom: 6px;">Totale ordine</td>
                                                <td align="right" style="color: #222; font-size: 15px; padding-bottom: 6px;">{{ number_format((float) $usage->order_amount, 2, ',', '.') }} &euro;</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #777; font-size: 13px;">Commissione maturata</td>
                                                <td align="right" style="color: #095866; font-size: 17px; font-weight: 700;">{{ number_format((float) $usage->commission_amount, 2, ',', '.') }} &euro;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- INFO --}}
                    <tr>
                        <td style="padding: 8px 32px 24px;">
                            <p style="margin: 0; color: #888; font-size: 13px; line-height: 1.5; text-align: center;">
                                Puoi consultare il dettaglio delle tue commissioni nella sezione Referral del tuo account.
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
