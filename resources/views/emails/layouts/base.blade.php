{{--
    LAYOUT EMAIL BASE — SpedizioneFacile (M8 design system)

    Layout responsive table-based, compatibile con tutti i client email.
    Tutte le email transazionali estendono questo layout via @extends('emails.layouts.base').

    Sezioni:
      - @section('preheader')   Testo nascosto mostrato in anteprima inbox
      - @section('title')       Titolo HTML <title>
      - @section('content')     Corpo principale (yield)

    Palette ufficiale (vietato il blu):
      - Teal brand:   #095866
      - Arancione:    #E44203
      - Testo dark:   #1d2738
      - Sfondo pag.:  #f5f3ec
      - Card:         #ffffff
--}}
<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>@yield('title', 'SpedizioneFacile')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f3ec; font-family: Arial, Helvetica, sans-serif; color: #1d2738; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%;">

    {{-- PREHEADER (testo nascosto visualizzato come anteprima dai client email) --}}
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #f5f3ec; opacity: 0;">
        @yield('preheader', 'SpedizioneFacile — spedizioni semplici, veloci, convenienti.')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec;">
        <tr>
            <td align="center" style="padding: 24px 12px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width: 600px; max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(9,88,102,0.08);">

                    {{-- HEADER 600x80 — sfondo teal --}}
                    <tr>
                        <td style="background-color: #095866; height: 80px; padding: 0 32px; text-align: center;" align="center" valign="middle">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" valign="middle" style="height: 80px;">
                                        {{-- Logo testuale (massima compatibilita') --}}
                                        <a href="{{ config('app.frontend_url', config('app.url')) }}" target="_blank" style="text-decoration: none; color: #ffffff;">
                                            <span style="display: inline-block; vertical-align: middle; font-family: Arial, Helvetica, sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 0.4px; color: #ffffff;">
                                                Spedizione<span style="color: #E44203;">Facile</span>
                                            </span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CONTENUTO --}}
                    <tr>
                        <td style="padding: 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff;">
                                <tr>
                                    <td align="center" style="padding: 32px 20px;">
                                        <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 560px;">
                                            <tr>
                                                <td style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
                                                    @yield('content')
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #f5f3ec; padding: 24px 32px; border-top: 1px solid #e8e4d3;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.6; color: #6b7280;">
                                        <p style="margin: 0 0 6px;">
                                            <strong style="color: #095866;">SpedizioneFacile S.r.l.</strong> &middot; P.IVA IT00000000000
                                        </p>
                                        <p style="margin: 0 0 12px;">
                                            Spedizioni semplici, veloci, convenienti.
                                        </p>
                                        <p style="margin: 0 0 12px; font-size: 12px;">
                                            <a href="{{ config('app.frontend_url', config('app.url')) }}/contatti" style="color: #095866; text-decoration: none;">Contatti</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="{{ config('app.frontend_url', config('app.url')) }}/centro-assistenza" style="color: #095866; text-decoration: none;">Assistenza</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="{{ config('app.frontend_url', config('app.url')) }}/account/notifiche?unsubscribe=1" style="color: #095866; text-decoration: none;">Disiscriviti</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="{{ config('app.frontend_url', config('app.url')) }}/privacy-policy" style="color: #095866; text-decoration: none;">Privacy</a>
                                        </p>
                                        <p style="margin: 0 0 6px; font-size: 11px; color: #9ca3af;">
                                            Seguici: Facebook &middot; Instagram &middot; LinkedIn
                                        </p>
                                        <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                                            &copy; {{ date('Y') }} SpedizioneFacile. Tutti i diritti riservati.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
