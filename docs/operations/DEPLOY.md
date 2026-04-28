# Deploy SpediamoFacile — Guida Produzione

## 1. Requisiti server

| Software | Versione | Verifica |
|----------|----------|----------|
| PHP | 8.2+ | `php -v` |
| Node.js | 20 LTS | `node -v` |
| MySQL | 8.0+ | `mysql --version` |
| Redis | 7+ | `redis-cli ping` |
| Caddy | 2.x | `caddy version` |

```bash
sudo apt install php8.2-{mbstring,xml,curl,mysql,redis,bcmath,zip} redis-server caddy
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install nodejs
```

## 2. Configurare i file .env

```bash
# Backend
cp apps/api/.env.production.example apps/api/.env
nano apps/api/.env   # compila DB_*, MAIL_*, STRIPE_*, BRT_*
cd apps/api && php artisan key:generate

# Frontend
cp apps/web/.env.production.example apps/web/.env
nano apps/web/.env   # compila API_BASE e STRIPE_KEY
```

## 3. Deploy backend (Laravel)

```bash
cd apps/api
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```

## 4. Deploy frontend (Nuxt)

```bash
cd apps/web
npm ci && npm run build
# Avvia con PM2 per restart automatico
npm install -g pm2
pm2 start .output/server/index.mjs --name spediamofacile-nuxt
pm2 save && pm2 startup
```

## 5. Configurare Caddy e queue worker

```bash
# Caddy — HTTPS automatico con Let's Encrypt
sudo cp Caddyfile.production /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

Crea `/etc/supervisor/conf.d/spediamofacile-worker.conf`:
```ini
[program:spediamofacile-worker]
command=php /percorso/apps/api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/spediamofacile-worker.log
```
```bash
sudo supervisorctl reread && sudo supervisorctl update
```

## 6. Verificare che tutto funzioni

```bash
curl -I https://spediamofacile.it          # HTTPS + certificato valido
curl https://spediamofacile.it/api/health   # API risponde
curl -s https://spediamofacile.it | head -5 # Frontend carica
redis-cli ping                              # PONG
```

## 7. Comandi utili per debug

```bash
tail -f apps/api/storage/logs/laravel.log  # log Laravel
tail -f /var/log/caddy/spediamofacile.log                       # log Caddy
pm2 logs spediamofacile-nuxt                                    # log Nuxt
sudo systemctl status caddy redis mysql                         # stato servizi

# Dopo modifiche .env
cd apps/api && php artisan config:clear && php artisan config:cache

# Aggiornamento completo
cd apps/api && php artisan migrate --force && php artisan config:cache
cd apps/web && npm run build && pm2 restart spediamofacile-nuxt
sudo supervisorctl restart spediamofacile-worker
```
