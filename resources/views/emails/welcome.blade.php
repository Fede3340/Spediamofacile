@extends('emails.layouts.base')

@section('title', 'Benvenuto in SpedizioneFacile')
@section('preheader', 'Il tuo account è attivo. Inizia a spedire in pochi clic.')

@section('content')
    <h1 style="margin: 0 0 16px; font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.3; color: #095866; font-weight: 700;">
        Ciao {{ $name }}, benvenuto!
    </h1>

    <p style="margin: 0 0 16px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Grazie per esserti registrato a <strong style="color: #095866;">SpedizioneFacile</strong>. Il tuo account è attivo e pronto all'uso.
    </p>

    <p style="margin: 0 0 24px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Da oggi puoi spedire in tutta Italia in pochi clic, con prezzi trasparenti e tracking in tempo reale.
    </p>

    {{-- VANTAGGI --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 10px; margin: 0 0 28px;">
        <tr>
            <td style="padding: 20px 22px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.7; color: #1d2738;">
                <strong style="color: #095866; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">I tuoi vantaggi</strong>
                <ul style="margin: 10px 0 0; padding-left: 18px; color: #1d2738;">
                    <li style="margin-bottom: 6px;">Preventivi istantanei senza registrazione</li>
                    <li style="margin-bottom: 6px;">Tracking BRT integrato</li>
                    <li style="margin-bottom: 6px;">Pagamenti sicuri (Stripe, bonifico)</li>
                    <li>Assistenza italiana 6 giorni su 7</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- CTA principale --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 28px;">
        <tr>
            <td align="center">
                <a href="{{ $url }}" target="_blank" style="display: inline-block; background-color: #095866; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px; mso-padding-alt: 0;">
                    Vai al tuo account
                </a>
            </td>
        </tr>
    </table>

    {{-- SUGGERIMENTI --}}
    <p style="margin: 0 0 10px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #095866; font-weight: 700;">
        Da dove iniziare
    </p>
    <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.7; color: #1d2738;">
        &bull; <a href="{{ config('app.frontend_url', config('app.url')) }}/preventivo" style="color: #E44203; text-decoration: none; font-weight: 600;">Crea il primo preventivo</a>
    </p>
    <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.7; color: #1d2738;">
        &bull; <a href="{{ config('app.frontend_url', config('app.url')) }}/traccia" style="color: #E44203; text-decoration: none; font-weight: 600;">Traccia una spedizione</a>
    </p>
    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.7; color: #1d2738;">
        &bull; <a href="{{ config('app.frontend_url', config('app.url')) }}/centro-assistenza" style="color: #E44203; text-decoration: none; font-weight: 600;">Visita il centro assistenza</a>
    </p>
@endsection
