<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hai lasciato qualcosa nel carrello - SpediamoFacile</title>
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
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; background-color: #FEF3E0; color: #E44203; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">
                                Carrello in sospeso
                            </span>
                            <h2 style="margin: 12px 0 10px; color: #095866; font-size: 21px; line-height: 1.2;">
                                Ciao {{ $user->name ?? 'cliente' }}, hai lasciato qualcosa nel carrello
                            </h2>
                            <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Hai <strong style="color: #1f2937;">{{ $itemCount }}</strong>
                                {{ $itemCount === 1 ? 'pacco' : 'pacchi' }} in attesa di essere spedito.
                                Puoi riprendere da dove avevi interrotto in un click.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 8px 32px 24px;">
                            <a href="{{ $resumeUrl }}"
                               style="display: inline-block; padding: 14px 24px; background-color: #E44203; color: #ffffff; text-decoration: none; font-weight: 700; border-radius: 8px; font-size: 15px; letter-spacing: 0.2px;">
                                Riprendi il carrello
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 28px;">
                            <p style="margin: 0; color: #6b7280; font-size: 13px; line-height: 1.6;">
                                Se non vuoi piu' ricevere questi promemoria puoi
                                <a href="{{ rtrim(config('app.frontend_url', config('app.url')), '/') }}/account/notifiche"
                                   style="color: #095866; text-decoration: underline;">disattivare le notifiche</a>
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
