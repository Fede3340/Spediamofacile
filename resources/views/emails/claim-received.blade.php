@extends('emails.layouts.base')

@php
    $claimsUrl = config('app.frontend_url', config('app.url')) . '/account/reclami';
    $claimDetailUrl = $claimsUrl . '/' . $claim->id;
@endphp

@section('title', 'Reclamo ricevuto')
@section('preheader', 'Abbiamo ricevuto il tuo reclamo. Ti risponderemo entro 5 giorni lavorativi.')

@section('content')
    <h1 style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.3; color: #095866; font-weight: 700;">
        Abbiamo ricevuto il tuo reclamo #{{ $claim->id }}
    </h1>

    <p style="margin: 0 0 20px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Grazie per averci segnalato il problema. Il tuo reclamo è stato registrato correttamente e preso in carico dal nostro team assistenza.
    </p>

    {{-- DETTAGLI RECLAMO --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 10px; margin: 0 0 24px;">
        <tr>
            <td style="padding: 22px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;">
                    <tr>
                        <td style="padding: 4px 0; color: #6b7280;">Riferimento</td>
                        <td align="right" style="padding: 4px 0; color: #1d2738; font-weight: 700;">#{{ $claim->id }}</td>
                    </tr>
                    @if(!empty($claim->order_id))
                        <tr>
                            <td style="padding: 4px 0; color: #6b7280;">Ordine collegato</td>
                            <td align="right" style="padding: 4px 0; color: #1d2738; font-weight: 600;">#{{ $claim->order?->code ?? $claim->order_id }}</td>
                        </tr>
                    @endif
                    @if(!empty($claim->type))
                        <tr>
                            <td style="padding: 4px 0; color: #6b7280;">Tipologia</td>
                            <td align="right" style="padding: 4px 0; color: #1d2738; font-weight: 600;">{{ ucfirst((string) $claim->type) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding: 4px 0; color: #6b7280;">Data invio</td>
                        <td align="right" style="padding: 4px 0; color: #1d2738; font-weight: 600;">
                            {{ optional($claim->created_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TEMPI DI RISPOSTA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border: 1px solid #e8e4d3; border-radius: 8px; margin: 0 0 24px;">
        <tr>
            <td style="padding: 16px 20px;">
                <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #095866; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Tempi di risposta
                </p>
                <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #1d2738; line-height: 1.6;">
                    Ti risponderemo indicativamente entro <strong>5 giorni lavorativi</strong>. In casi complessi che richiedono verifiche con il corriere, i tempi possono estendersi fino a 10 giorni lavorativi.
                </p>
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 16px;">
        <tr>
            <td align="center">
                <a href="{{ $claimDetailUrl }}" target="_blank" style="display: inline-block; background-color: #095866; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px;">
                    Vedi dettaglio reclamo
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #6b7280; line-height: 1.6; text-align: center;">
        Puoi seguire lo stato di tutti i tuoi reclami nella sezione
        <a href="{{ $claimsUrl }}" style="color: #095866; text-decoration: underline;">I miei reclami</a>.
    </p>
@endsection
