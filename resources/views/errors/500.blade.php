@extends('layouts.error')

@section('title', '500 - Errore server | SpediamoFacile')

@section('content')
<div class="error-card" data-variant="500">
    <a href="/" class="error-logo" aria-label="Torna alla home">
        <img src="/img/logo-spedizionefacile.png" alt="SpediamoFacile" width="140" height="40">
    </a>

    <p class="error-status">500</p>
    <h1 class="error-heading">Qualcosa e andato storto</h1>
    <p class="error-desc">
        Si e verificato un errore imprevisto. Il nostro team e stato notificato e stiamo risolvendo.
        Prova a riaggiornare la pagina o torna piu tardi.
    </p>

    <div class="error-actions">
        <a href="/" class="error-cta error-cta--primary">Torna alla home</a>
        <a href="/contatti" class="error-cta error-cta--ghost">Contatta assistenza</a>
    </div>
</div>
@endsection
