@extends('layouts.error')

@section('title', '404 - Pagina non trovata | SpediamoFacile')

@section('content')
<div class="error-card" data-variant="404">
    <a href="/" class="error-logo" aria-label="Torna alla home">
        <img src="/img/logo-spedizionefacile.png" alt="SpediamoFacile" width="140" height="40">
    </a>

    <p class="error-status">404</p>
    <h1 class="error-heading">Pagina non trovata</h1>
    <p class="error-desc">
        La pagina che stavi cercando non esiste o e stata spostata. Ti aiutiamo a ritrovare la strada.
    </p>

    <div class="error-actions">
        <a href="/" class="error-cta error-cta--primary">Torna alla home</a>
        <a href="/traccia-spedizione" class="error-cta error-cta--ghost">Traccia spedizione</a>
        <a href="/contatti" class="error-cta error-cta--ghost">Contatti</a>
    </div>
</div>
@endsection
