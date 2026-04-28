@extends('layouts.error')

@section('title', '403 - Accesso non autorizzato | SpediamoFacile')

@section('content')
<div class="error-card" data-variant="403">
    <a href="/" class="error-logo" aria-label="Torna alla home">
        <img src="/img/logo-spedizionefacile.png" alt="SpediamoFacile" width="140" height="40">
    </a>

    <p class="error-status">403</p>
    <h1 class="error-heading">Accesso non autorizzato</h1>
    <p class="error-desc">
        Non hai i permessi per vedere questa pagina.
        Accedi con le tue credenziali o contatta l'assistenza per ricevere aiuto.
    </p>

    <div class="error-actions">
        <a href="/accedi" class="error-cta error-cta--primary">Accedi</a>
        <a href="/" class="error-cta error-cta--ghost">Torna alla home</a>
        <a href="/contatti" class="error-cta error-cta--ghost">Contatta assistenza</a>
    </div>
</div>
@endsection
