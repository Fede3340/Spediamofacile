<x-mail::message>
# Conferma il tuo indirizzo email

Grazie per esserti registrato su **SpedizioneFacile**.

Per completare l'attivazione del tuo account, inserisci il seguente codice di verifica nella pagina di login:

<x-mail::panel>
<div style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #095866;">
{{ $code }}
</div>
</x-mail::panel>

Il codice scade tra **30 minuti**.

Se non hai richiesto la registrazione, ignora questa email.

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
