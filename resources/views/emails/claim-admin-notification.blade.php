<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo reclamo - Admin SpediamoFacile</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="background-color: #E44203; padding: 20px 28px;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 18px; font-weight: 800; letter-spacing: 0.4px; text-transform: uppercase;">
                                Admin &bull; Nuovo reclamo
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 28px;">
                            <p style="margin: 0 0 10px; color: #111827; font-size: 15px;">
                                &Egrave; stato aperto un nuovo reclamo
                                <strong style="color: #E44203;">#{{ $claim->id }}</strong>
                                sull'ordine <strong>#{{ $claim->order_id }}</strong>.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 14px; background-color: #f8fafb; border: 1px solid #e5edf0; border-radius: 8px;">
                                <tr><td style="padding: 10px 14px; color: #667085; font-size: 13px;">Cliente</td><td align="right" style="padding: 10px 14px; color: #111827; font-size: 14px; font-weight: 600;">{{ $claim->user?->name ?? '—' }} ({{ $claim->user?->email ?? '—' }})</td></tr>
                                <tr><td style="padding: 10px 14px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Tipologia</td><td align="right" style="padding: 10px 14px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $claim->typeLabel() }}</td></tr>
                                <tr><td style="padding: 10px 14px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Allegati</td><td align="right" style="padding: 10px 14px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $claim->attachments->count() }} file</td></tr>
                                <tr><td style="padding: 10px 14px; color: #667085; font-size: 13px; border-top: 1px solid #edf2f4;">Aperto il</td><td align="right" style="padding: 10px 14px; color: #111827; font-size: 14px; font-weight: 600; border-top: 1px solid #edf2f4;">{{ $claim->created_at->format('d/m/Y H:i') }}</td></tr>
                            </table>

                            <p style="margin: 18px 0 6px; color: #667085; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Descrizione</p>
                            <p style="margin: 0; color: #111827; font-size: 14px; line-height: 1.6; white-space: pre-line;">{{ $claim->description }}</p>

                            <p style="margin: 24px 0 0;">
                                <a href="{{ rtrim(config('app.frontend_url'), '/') }}/account/amministrazione/reclami" style="display: inline-block; padding: 10px 18px; background-color: #095866; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 700;">Apri pannello admin</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
