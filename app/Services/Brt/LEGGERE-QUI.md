# Services/Brt - Leggere Qui

Questa cartella contiene i servizi adapter che comunicano con le API del corriere **BRT (Bartolini)**.

Importante: l'orchestrazione order-centric del fulfillment non vive qui. Il boundary canonico e':

- `app/Services/OrderBrtFulfillmentService.php` -> decide come un ordine persistito diventa una spedizione BRT
- `app/Services/ShipmentExecutionService.php` -> completa pickup, bordero e documenti dopo la label

I file in `Services/Brt/` restano carrier-specific adapters: nessun controller dovrebbe ricostruire qui il business dell'ordine.

## File principali

1. **ShipmentService.php** - Crea, conferma e cancella spedizioni BRT. Genera etichette PDF (base64). E' il file piu critico.
2. **PudoService.php** - Cerca i punti di ritiro/consegna PUDO per indirizzo o coordinate GPS.
3. **BrtPayloadBuilder.php** - Costruisce il payload JSON da inviare a BRT (dati mittente, destinatario, colli).
4. **BrtConfig.php** - Legge le credenziali BRT dal file `.env` (client_id, password, account).
5. **TrackingService.php** - Interroga lo stato di una spedizione (tracking).
6. **PickupService.php** - Richiede il ritiro a domicilio BRT.
7. **AddressNormalizer.php** - Normalizza indirizzi italiani (abbreviazioni, province, CAP).
8. **ErrorTranslator.php** - Traduce i codici errore BRT in messaggi italiani leggibili.
9. **FilialeLookup.php** - Trova la filiale BRT di partenza/arrivo in base al CAP.
10. **PudoPointMapper.php** - Mappa la risposta PUDO di BRT nel formato usato dal frontend.

## Ordine di lettura consigliato

1. `BrtConfig.php` - Capire come si configurano le credenziali
2. `BrtPayloadBuilder.php` - Capire cosa viene inviato a BRT
3. `ShipmentService.php` - Il cuore della logica di spedizione
4. `PudoService.php` - Ricerca punti di ritiro

## Quale file modificare per...

| Esigenza | File |
|----------|------|
| Cambiare le credenziali BRT | `.env` (non toccare BrtConfig.php) |
| Aggiungere un campo al payload spedizione | `BrtPayloadBuilder.php` |
| Cambiare la logica di creazione spedizione | `ShipmentService.php` |
| Migliorare la normalizzazione indirizzi | `AddressNormalizer.php` |
| Cambiare i messaggi di errore BRT | `ErrorTranslator.php` |
