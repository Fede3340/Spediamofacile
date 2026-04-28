# Data

Cartella per dataset di supporto. **Vuota nel repo** — i dataset reali stanno fuori.

## Dataset esterni

- `geonames-postalcodes/` — GeoNames IT/GR (~48 MB) spostato in `../spedizionefacile-offline-data/`. Importato in DB tramite `php artisan import:locations` (vedi `apps/api/app/Console/Commands/ImportLocations.php`).

## Regole

- Niente dataset >5 MB qui. Per file grandi, usa `../spedizionefacile-offline-data/`.
- Niente log, export temporanei o dump casuali.
