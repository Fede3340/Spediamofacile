# PUDO FALLBACK - Istruzioni di Attivazione

## Cosa fa questa soluzione

Quando l'API BRT per i punti PUDO non funziona o non restituisce risultati, il sistema usa automaticamente un database locale con 45+ punti PUDO nelle principali città italiane.

## Attivazione (3 comandi)

```bash
# 1. Crea la tabella pudo_points nel database
php artisan migrate

# 2. Popola la tabella con i dati mock (45+ punti PUDO)
php artisan db:seed --class=PudoPointSeeder

# 3. Verifica che i dati siano stati inseriti
php artisan tinker
>>> App\Models\PudoPoint::count()
>>> exit
```

## Come funziona

### Flusso automatico
1. L'utente cerca PUDO per città/CAP o coordinate GPS
2. Il sistema prova prima l'API BRT
3. Se l'API fallisce o non restituisce risultati → **FALLBACK AUTOMATICO** al database locale
4. L'utente vede i punti PUDO senza accorgersi del fallback

### Endpoint API (già esistenti)
- `POST /api/brt/pudo/search` - Cerca per città/CAP
- `POST /api/brt/pudo/nearby` - Cerca per coordinate GPS

### Città coperte (45+ punti)
- Roma (5 punti)
- Milano (5 punti)
- Torino (4 punti)
- Napoli (4 punti)
- Firenze (3 punti)
- Bologna (3 punti)
- Genova (3 punti)
- Palermo (3 punti)
- Bari (3 punti)
- Verona (2 punti)
- Padova (2 punti)
- Catania (2 punti)
- Venezia/Mestre (3 punti)

## Test manuale

### Test 1: Cerca PUDO a Roma
```bash
curl -X POST http://localhost:8000/api/brt/pudo/search \
  -H "Content-Type: application/json" \
  -d '{
    "city": "Roma",
    "zip_code": "00186"
  }'
```

**Risultato atteso**: Lista di 5 punti PUDO a Roma

### Test 2: Cerca PUDO per coordinate (Milano Duomo)
```bash
curl -X POST http://localhost:8000/api/brt/pudo/nearby \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 45.4642,
    "longitude": 9.1900
  }'
```

**Risultato atteso**: Lista di punti PUDO vicini a Milano

### Test 3: Verifica fallback (città non coperta)
```bash
curl -X POST http://localhost:8000/api/brt/pudo/search \
  -H "Content-Type: application/json" \
  -d '{
    "city": "Aosta",
    "zip_code": "11100"
  }'
```

**Risultato atteso**: Array vuoto (città non nel database mock)

## Aggiungere nuovi punti PUDO

### Opzione 1: Via database
```sql
INSERT INTO pudo_points (pudo_id, name, address, city, zip_code, province, latitude, longitude, phone, is_active, created_at, updated_at)
VALUES ('PUDO_AO_001', 'Tabaccheria Centrale', 'Piazza Chanoux 1', 'Aosta', '11100', 'AO', 45.7376, 7.3203, '0165-123456', 1, NOW(), NOW());
```

### Opzione 2: Via Seeder
Modifica `database/seeders/PudoPointSeeder.php` e aggiungi nuovi punti nell'array `$pudoPoints`, poi:
```bash
php artisan db:seed --class=PudoPointSeeder
```

## File modificati

1. **database/migrations/2026_03_03_100000_create_pudo_points_table.php** - Tabella database
2. **app/Models/PudoPoint.php** - Model con metodi di ricerca
3. **database/seeders/PudoPointSeeder.php** - Dati mock (45+ punti)
4. **app/Services/BrtService.php** - Logica fallback automatico
5. **database/seeders/DatabaseSeeder.php** - Include PudoPointSeeder

## Vantaggi

✅ **Sempre disponibile**: Anche se l'API BRT è offline, gli utenti vedono punti PUDO
✅ **Trasparente**: Il fallback è automatico, nessun errore visibile all'utente
✅ **Espandibile**: Facile aggiungere nuovi punti PUDO al database
✅ **Performance**: Database locale più veloce dell'API BRT
✅ **Logging**: Tutti i fallback sono registrati nei log per monitoraggio

## Monitoraggio

Controlla i log per vedere quando viene usato il fallback:
```bash
tail -f storage/logs/laravel.log | grep "PUDO fallback"
```

## Note

- I dati mock hanno orari standard (Lun-Ven 09:00-19:00, Sab 09:00-13:00)
- Le coordinate GPS sono reali e permettono il calcolo della distanza
- Il campo `fallback: true` nella risposta API indica che è stato usato il database locale
- I PUDO_ID nel database locale sono diversi da quelli BRT reali (prefisso PUDO_)
