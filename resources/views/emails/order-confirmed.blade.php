@extends('emails.layouts.base')

@php
    // Codice ordine: usa $order->code se presente, altrimenti $order->id formattato
    $orderCode = $order->code ?? ('SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    $totalCents = $order->payableTotalCents();
    $totalEur = number_format($totalCents / 100, 2, ',', '.');
    $packages = $order->packages ?? collect();
    $firstPackage = $packages->first();
    $origin = $firstPackage?->originAddress;
    $destination = $firstPackage?->destinationAddress;
    $trackingUrl = $order->brt_tracking_url
        ?? (config('app.frontend_url', config('app.url')) . '/account/spedizioni/' . $order->id);
@endphp

@section('title', 'Ordine #' . $orderCode . ' confermato')
@section('preheader', 'Abbiamo ricevuto il pagamento. La tua spedizione è in lavorazione.')

@section('content')
    <h1 style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.3; color: #095866; font-weight: 700;">
        Ordine #{{ $orderCode }} confermato
    </h1>
    <p style="margin: 0 0 24px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1d2738;">
        Grazie! Abbiamo ricevuto il pagamento e la tua spedizione è in lavorazione.
    </p>

    {{-- RIEPILOGO --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f3ec; border-radius: 10px; margin: 0 0 20px;">
        <tr>
            <td style="padding: 18px 22px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #1d2738;">
                    <tr>
                        <td style="padding: 4px 0; color: #6b7280;">Data ordine</td>
                        <td align="right" style="padding: 4px 0; color: #1d2738; font-weight: 600;">
                            {{ $order->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #6b7280;">Totale pagato</td>
                        <td align="right" style="padding: 4px 0; color: #095866; font-size: 17px; font-weight: 700;">
                            {{ $totalEur }} &euro;
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- INDIRIZZI --}}
    @if($origin || $destination)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
            <tr>
                @if($origin)
                    <td valign="top" width="48%" style="background-color: #ffffff; border: 1px solid #e8e4d3; border-radius: 8px; padding: 14px 16px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0 0 6px; color: #095866; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Origine</p>
                        <p style="margin: 0; font-size: 14px; font-weight: 700; color: #1d2738;">{{ $origin->city }}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            {{ $origin->postal_code }} ({{ $origin->province }})
                        </p>
                    </td>
                @endif
                @if($origin && $destination)
                    <td width="4%">&nbsp;</td>
                @endif
                @if($destination)
                    <td valign="top" width="48%" style="background-color: #ffffff; border: 1px solid #e8e4d3; border-radius: 8px; padding: 14px 16px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0 0 6px; color: #095866; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Destinazione</p>
                        <p style="margin: 0; font-size: 14px; font-weight: 700; color: #1d2738;">{{ $destination->city }}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            {{ $destination->postal_code }} ({{ $destination->province }})
                        </p>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    {{-- TABELLA COLLI --}}
    @if($packages->count() > 0)
        <p style="margin: 0 0 10px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 700; color: #095866; text-transform: uppercase; letter-spacing: 0.5px;">
            Colli ({{ $packages->count() }})
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e8e4d3; border-radius: 8px; overflow: hidden; margin: 0 0 24px; font-family: Arial, Helvetica, sans-serif; font-size: 13px;">
            <tr style="background-color: #f5f3ec;">
                <td style="padding: 10px 14px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; font-size: 11px;">Collo</td>
                <td style="padding: 10px 14px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; font-size: 11px;">Tipo</td>
                <td align="right" style="padding: 10px 14px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; font-size: 11px;">Peso</td>
                <td align="right" style="padding: 10px 14px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; font-size: 11px;">Dimensioni</td>
            </tr>
            @foreach($packages as $i => $pkg)
                <tr style="border-top: 1px solid #f0ecd9;">
                    <td style="padding: 12px 14px; color: #1d2738; font-weight: 600;">#{{ $i + 1 }}</td>
                    <td style="padding: 12px 14px; color: #1d2738;">{{ $pkg->package_type ?? 'Pacco' }}</td>
                    <td align="right" style="padding: 12px 14px; color: #1d2738;">{{ $pkg->weight }} kg</td>
                    <td align="right" style="padding: 12px 14px; color: #1d2738;">
                        {{ $pkg->first_size }}&times;{{ $pkg->second_size }}&times;{{ $pkg->third_size }} cm
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- CTA Traccia (arancione) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $trackingUrl }}" target="_blank" style="display: inline-block; background-color: #E44203; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 999px;">
                    Traccia la spedizione
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #6b7280; line-height: 1.6; text-align: center;">
        Riceverai la fattura in una email separata entro 24 ore.
    </p>
@endsection
