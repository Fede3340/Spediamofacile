<x-mail::message>
# Recupero password

Ciao,

Hai richiesto di reimpostare la password del tuo account SpediamoFacile.

Clicca sul pulsante qui sotto per scegliere una nuova password:

<x-mail::button :url="config('app.frontend_url') . '/aggiorna-password?token=' . urlencode($token) . '&email=' . urlencode($email)">
Reimposta password
</x-mail::button>

Se non hai richiesto tu il recupero password, puoi ignorare questa email. Il link scadra' automaticamente.

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
