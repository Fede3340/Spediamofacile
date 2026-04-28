<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reclamo ricevuto - SpediamoFacile</title>
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
                        <td style="padding: 32px 32px 16px;">
                            <h2 style="margin: 0 0 10px; color: #095866; font-size: 21px; line-height: 1.2;">
                                Abbiamo ricevuto il tuo reclamo
                            </h2>
                            <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Grazie per averci segnalato il problema relativo all'ordine
                                <strong style="color: #1f2937;">#{{ $claim->order_id }}</strong>.
                                Il tuo reclamo &egrave; stato registrato con il numero
                                <strong style="color: #E44203;">#{{ $claim->id }}</strong>
                                ed &egrave; ora in carico al nostro team di assistenza.
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
                                                <td style="color: #667085; font-size: 13px; padding-bottom: 6px;">Numero reclamo</td>
                                                <td align="right" style="color: #111827; font-size: 15px; font-weight: 700; padding-bottom: 6px;">#{{ $claim->id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #667085; font-size: 13px; padding-bottom: 6px;">Ordine</td>
                                                <td align="right" style="color: #111827; font-size: 15px; padding-bottom: 6px;">#{{ $claim->order_id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #667085; font-size: 13px; padding-bottom: 6px;">Tipologia</td>
                                                <td align="right" style="color: #111827; font-size: 15px; padding-bottom: 6px;">{{ $claim->typeLabel() }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #667085; font-size: 13px;">Aperto il</td>
                                                <td align="right" style="color: #111827; font-size: 15px;">{{ $claim->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <p style="margin: 0 0 10px; color: #111827; font-size: 14px; font-weight: 700;">Cosa succede adesso</p>
                            <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6;">
                                Il nostro team prender&agrave; in carico il reclamo entro 2 giorni lavorativi. Per i casi
                                pi&ugrave; semplici la risoluzione avviene in 5-7 giorni lavorativi; per quelli che richiedono
                                un'indagine presso il corriere pu&ograve; servire fino a 30 giorni. Ti aggiorneremo via email
                                a ogni passaggio di stato.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f4f4f7; padding: 20px 32px; border-top: 1px solid #e8eef0;">
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 12px; text-align: center;">
                                SpediamoFacile &mdash; spedizioni semplici, veloci e convenienti.
                            </p>
                            <p style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center;">
                                <a href="{{ url('/account/reclami') }}" style="color: #095866; text-decoration: underline;">Vedi i tuoi reclami</a>
                                &middot;
                                <a href="{{ url('/account/notifiche') }}" style="color: #095866; text-decoration: underline;">Preferenze notifiche</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
