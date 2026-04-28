@extends('emails.layouts.base')

@php
    // $resetUrl: URL completo gia' costruito dal Mailable (token + email firmati).
    // Variabili attese: $name (string), $resetUrl (string), $expiresInMinutes (int|null)
    $expiresInMinutes = $expiresInMinutes ?? 60;
@endphp

@section('title', 'Reimposta la tua password')
@section('preheader', 'Hai richiesto di reimpostare la password del tuo account SpedizioneFacile.')

@section('content')
    <h1 style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.3; color: #095866; font-weight: 700;">
        Reimposta la tua password
    </h1>

    <p style="margin: 0 0 16px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Ciao{{ !empty($name) ? ' ' . $name : '' }},
    </p>
    <p style="margin: 0 0 24px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        abbiamo ricevuto una richiesta di reimpostazione password per il tuo account. Clicca il pulsante qui sotto per impostare una nuova password.
    </p>

    {{-- CTA principale --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; background-color: #095866; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px;">
                    Reimposta password
                </a>
            </td>
        </tr>
    </table>

    {{-- Info scadenza --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 8px; margin: 0 0 20px;">
        <tr>
            <td style="padding: 14px 18px;">
                <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1d2738; line-height: 1.6;">
                    Questo link è valido per <strong>{{ $expiresInMinutes }} minuti</strong>. Se è scaduto, puoi richiederne uno nuovo dalla pagina di login.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #6b7280; line-height: 1.6;">
        Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:
    </p>
    <p style="margin: 0 0 24px; font-family: 'Courier New', Courier, monospace; font-size: 12px; word-break: break-all; color: #095866; background-color: #f5f3ec; padding: 10px 12px; border-radius: 6px;">
        {{ $resetUrl }}
    </p>

    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #6b7280; line-height: 1.6;">
        Se non hai richiesto tu questa email puoi ignorarla: la tua password non verrà modificata.
    </p>
@endsection
