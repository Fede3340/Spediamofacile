@extends('layouts.error')

@section('title', '503 - Manutenzione in corso | SpediamoFacile')

@section('content')
<div class="error-card" data-variant="503">
    <a href="/" class="error-logo" aria-label="Torna alla home">
        <img src="/img/logo-spedizionefacile.png" alt="SpediamoFacile" width="140" height="40">
    </a>

    <p class="error-status">503</p>
    <h1 class="error-heading">Manutenzione in corso</h1>
    <p class="error-desc">
        Stiamo migliorando il servizio. Torneremo online a breve.
        Grazie per la pazienza.
    </p>

    <div class="error-actions">
        <a href="/" class="error-cta error-cta--primary">Riprova</a>
        <a href="/contatti" class="error-cta error-cta--ghost">Resta in contatto</a>
    </div>
</div>
@endsection
