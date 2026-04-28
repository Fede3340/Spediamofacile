@extends('emails.layouts.base')

@php
    $orderCode = $order->code ?? ('SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    $trackingNumber = $order->brt_tracking_number ?? null;
    $trackingUrl = $order->brt_tracking_url
        ?? (config('app.frontend_url', config('app.url')) . '/account/spedizioni/' . $order->id);
    $eta = $order->estimated_delivery_at ?? null;
@endphp

@section('title', 'Il tuo ordine è in viaggio')
@section('preheader', 'La tua spedizione è stata presa in carico dal corriere.')

@section('content')
    <h1 style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.3; color: #095866; font-weight: 700;">
        Il tuo ordine è in viaggio
    </h1>
    <p style="margin: 0 0 24px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Buone notizie! L'ordine <strong style="color: #095866;">#{{ $orderCode }}</strong> è stato preso in carico dal corriere ed è ora in viaggio verso la destinazione.
    </p>

    {{-- TRACKING CARD --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 10px; margin: 0 0 24px;">
        <tr>
            <td style="padding: 22px;">
                @if($trackingNumber)
                    <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #095866; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        Codice di tracking
                    </p>
                    <p style="margin: 0 0 16px; font-family: 'Courier New', Courier, monospace; font-size: 18px; color: #1d2738; font-weight: 700; letter-spacing: 1px;">
                        {{ $trackingNumber }}
                    </p>
                @endif

                @if($eta)
                    <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #095866; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        Consegna prevista
                    </p>
                    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #1d2738; font-weight: 600;">
                        {{ \Illuminate\Support\Carbon::parse($eta)->locale('it')->isoFormat('dddd D MMMM YYYY') }}
                    </p>
                @else
                    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #6b7280; line-height: 1.6;">
                        I tempi di consegna BRT sono indicativamente di 24-48h lavorative dopo il ritiro.
                    </p>
                @endif
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $trackingUrl }}" target="_blank" style="display: inline-block; background-color: #E44203; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px;">
                    Traccia ora
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #6b7280; line-height: 1.6; text-align: center;">
        Riceverai una nuova email appena la spedizione sarà consegnata.
    </p>
@endsection
