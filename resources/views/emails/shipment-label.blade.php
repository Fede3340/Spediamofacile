<x-mail::message>
# La tua spedizione e' stata creata

Ciao **{{ $order->user->name ?? 'Cliente' }}**,

La tua spedizione per l'ordine **#{{ $order->id }}** e' stata creata con successo tramite BRT.

**Dettagli spedizione:**

- **Codice BRT (Parcel ID):** {{ $order->brt_parcel_id }}
- **Numero ordine:** SF-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
@if($order->is_cod)
- **Contrassegno:** {{ number_format($order->cod_amount / 100, 2, ',', '.') }} EUR
@endif
@if($order->brt_pudo_id)
- **Punto di ritiro PUDO:** {{ $order->brt_pudo_id }}
@endif

@if($order->brt_tracking_url)
<x-mail::button :url="$order->brt_tracking_url">
Traccia la tua spedizione
</x-mail::button>
@endif

In allegato trovi l'etichetta BRT in formato PDF da stampare e applicare sul pacco.

Se hai bisogno di assistenza, contattaci rispondendo a questa email.

Grazie,<br>
{{ config('app.name') }}

---
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center;">
    <a href="{{ url('/account/notifiche') }}" style="color: #095866; text-decoration: underline;">Gestisci preferenze notifiche</a>
    &middot; <a href="{{ url('/privacy-policy') }}" style="color: #095866; text-decoration: underline;">Privacy Policy</a>
</p>
</x-mail::message>
