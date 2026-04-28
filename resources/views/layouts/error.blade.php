{{--
    LAYOUT BASE ERROR PAGES (resources/views/layouts/error.blade.php)
    SCOPO: wrapper HTML minimale per le pagine errore 403/404/500/503.

    DOVE SI USA: extended da resources/views/errors/*.blade.php.
    VINCOLI:
        - WCAG AA: lang="it", doctype, title dinamico, no script inline.
        - Brand-coherent: palette teal+arancione via /css/errors.css.
        - Nessun asset JS richiesto (fail-safe, funziona anche se FE e down).
--}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Errore - SpediamoFacile')</title>
    <link rel="icon" type="image/png" href="/img/logo-spedizionefacile.png">
    <link rel="stylesheet" href="/css/errors.css">
</head>
<body class="error-body">
    <main class="error-main" role="main">
        @yield('content')
    </main>
</body>
</html>
