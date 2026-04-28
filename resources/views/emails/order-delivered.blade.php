@extends('emails.layouts.base')

@php
    $orderCode = $order->code ?? ('SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    $deliveredAt = $order->delivered_at ?? $order->brt_last_tracking_check ?? now();
    $deliveredAtFormatted = \Illuminate\Support\Carbon::parse($deliveredAt)->format('d/m/Y \a\l\l\e H:i');
    $reviewUrl = config('app.frontend_url', config('app.url')) . '/account/spedizioni/' . $order->id . '?recensione=1';
    $newOrderUrl = config('app.frontend_url', config('app.url')) . '/preventivo';
    $claimUrl = config('app.frontend_url', config('app.url')) . '/account/reclami/nuovo?ordine=' . $order->id;
@endphp

@section('title', 'Consegnato!')
@section('preheader', 'Il tuo ordine #' . $orderCode . ' è stato consegnato.')

@section('content')
    <h1 style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 26px; line-height: 1.2; color: #095866; font-weight: 700;">
        Consegnato!
    </h1>
    <p style="margin: 0 0 20px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Il tuo ordine <strong style="color: #095866;">#{{ $orderCode }}</strong> è stato consegnato a destinazione.
    </p>

    {{-- DETTAGLI CONSEGNA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 10px; margin: 0 0 28px;">
        <tr>
            <td style="padding: 22px;">
                <p style="margin: 0 0 6px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #095866; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Data e ora consegna
                </p>
                <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #1d2738; font-weight: 600;">
                    {{ $deliveredAtFormatted }}
                </p>
            </td>
        </tr>
    </table>

    {{-- CTA recensione (arancione, primaria) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 14px;">
        <tr>
            <td align="center">
                <a href="{{ $reviewUrl }}" target="_blank" style="display: inline-block; background-color: #E44203; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px;">
                    Lascia una recensione
                </a>
            </td>
        </tr>
    </table>

    {{-- CTA secondaria nuova spedizione (teal outline) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 28px;">
        <tr>
            <td align="center">
                <a href="{{ $newOrderUrl }}" target="_blank" style="display: inline-block; background-color: #ffffff; color: #095866; font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 999px; border: 2px solid #095866;">
                    Nuova spedizione
                </a>
            </td>
        </tr>
    </table>

    {{-- NOTA RECLAMI --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border: 1px solid #e8e4d3; border-radius: 8px;">
        <tr>
            <td style="padding: 16px 20px;">
                <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #6b7280; line-height: 1.6;">
                    Hai riscontrato un problema con la consegna?
                    <a href="{{ $claimUrl }}" style="color: #E44203; text-decoration: none; font-weight: 700;">Apri un reclamo</a>
                    entro 8 giorni dalla consegna.
                </p>
            </td>
        </tr>
    </table>
@endsection
