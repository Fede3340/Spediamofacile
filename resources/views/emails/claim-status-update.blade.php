<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiornamento reclamo #{{ $claim->id }} - SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
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
                        <td style="padding: 32px 32px 12px;">
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; background-color: #ECFDF3; color: #047857; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">
                                {{ $claim->statusLabel() }}
                            </span>
                            <h2 style="margin: 12px 0 10px; color: #095866; font-size: 21px; line-height: 1.2;">
                                Risposta sul tuo reclamo
                            </h2>
                            <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Reclamo <strong style="color: #1f2937;">#{{ $claim->id }}</strong>
                                sull'ordine <strong style="color: #1f2937;">#{{ $claim->order_id }}</strong>
                                ({{ $claim->typeLabel() }}).
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafb; border: 1px solid #e5edf0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <p style="margin: 0 0 6px; color: #667085; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Messaggio del team</p>
                                        <p style="margin: 0; color: #111827; font-size: 14px; line-height: 1.6; white-space: pre-line;">{{ $replyMessage }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6;">
                                Puoi rispondere a questa email o aprire la
                                <a href="{{ rtrim(config('app.frontend_url', config('app.url')), '/') }}/account/reclami/{{ $claim->id }}" style="color: #095866; font-weight: 700; text-decoration: underline;">scheda del reclamo</a>
                                dal tuo account.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f4f4f7; padding: 20px 32px; border-top: 1px solid #e8eef0;">
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 12px; text-align: center;">
                                SpediamoFacile &mdash; spedizioni semplici, veloci e convenienti.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
