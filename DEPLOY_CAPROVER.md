# Deploy Arventa POS to CapRover

Arventa POS is deployed as one multi-tenant Laravel backend/web admin app. Each buyer store is a `pos_instances` tenant, and store data is separated with `pos_instance_id`.

This repository keeps the Laravel app in `arventa-pos-backend`, while CapRover builds from the repository root using:

- `captain-definition`
- `Dockerfile`
- `.dockerignore`
- `docker/entrypoint.sh`

The container serves Laravel from `public/` on port `80`.

## CapRover App Setup

Create or use this app in CapRover:

```text
App name: arventa
Public URL: https://arventa.arventa.my.id
```

Database app:

```text
Database app name: arventa-db
Internal host: srv-captain--arventa-db
Port: 3306
Database: arventa_pos
User: arventa_user
Password: arventa
```

## Required Environment Variables

Set these in CapRover under **Apps > arventa > App Configs > Environmental Variables**.

```env
APP_NAME="Arventa POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://arventa.arventa.my.id
APP_KEY=base64:CHANGE_THIS

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=srv-captain--arventa-db
DB_PORT=3306
DB_DATABASE=arventa_pos
DB_USERNAME=arventa_user
DB_PASSWORD=arventa

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public

ARVENTA_PUBLIC_BASE_DOMAIN=arventa.my.id
ARVENTA_APP_PUBLIC_HOST=arventa.arventa.my.id
ARVENTA_DEPLOYMENT_MODE=manual
```

Generate `APP_KEY` locally or in the deployed container:

```bash
php artisan key:generate --show
```

Copy the generated value into CapRover as `APP_KEY`. Do not commit `.env` files.

## Deploy

Recommended path:

1. Push the repository to GitHub/GitLab.
2. In CapRover, open app `arventa`.
3. Use **Deployment > Method 3: Deploy from GitHub/Bitbucket/GitLab** or deploy with CapRover CLI.
4. Make sure the app uses the root `captain-definition`.
5. Deploy.

The image build will:

- install PHP 8.3 Apache
- install Laravel PHP extensions
- run `composer install --no-dev`
- run `npm ci && npm run build`
- serve `/var/www/html/public`
- listen on port `80`
- consume `CAPROVER_GIT_COMMIT_SHA` to invalidate Docker cache for changed Laravel files and Vite assets

If the admin page appears as plain unstyled HTML, the Vite CSS was not loaded. Check:

```text
https://arventa.arventa.my.id/build/manifest.json
```

Then redeploy the latest commit. During a good rebuild, the layers after `CAPROVER_GIT_COMMIT_SHA` should not stay stuck on old cached app files.

## Run Migrations and Seeders

After the first successful deploy, run this inside the `arventa` app container:

```bash
php artisan migrate --seed --force
```

The Docker entrypoint does not run migrations automatically. This keeps deploys from modifying the database before you have checked the runtime configuration.

In CapRover:

1. Open **Apps > arventa**.
2. Open **Deployment** or **App Configs** depending on your CapRover version.
3. Use the app terminal/console feature if available.
4. Run the command above.

If your CapRover UI does not expose a terminal, SSH into the server and run:

```bash
docker exec -it $(docker ps --filter "name=srv-captain--arventa" --format "{{.ID}}" | head -n 1) php artisan migrate --seed --force
```

The `/admin` route reads the `store_settings`, `products`, and `sales` tables. If those tables do not exist yet, or the seed data has not created a `store_settings` row, Laravel can return a 500 error in production. The seeders are safe to run more than once.

## Multi-Tenant Buyer URL Automation

The developer panel at `/developer/pos` creates one tenant record for each buyer. Deploying a tenant does not create a second Laravel app or a second physical database. It:

- creates or updates a DNS record for the buyer domain
- attaches that domain to the existing CapRover app `arventa`
- enables SSL for the buyer domain
- keeps all buyer data inside the shared Laravel database using `pos_instance_id`

Set these env values to enable automatic deployment:

```env
ARVENTA_DEPLOYMENT_MODE=automatic
ARVENTA_PUBLIC_BASE_DOMAIN=arventa.my.id
ARVENTA_APP_PUBLIC_HOST=arventa.arventa.my.id

ARVENTA_DNS_PROVIDER=cloudflare
ARVENTA_DNS_RECORD_TYPE=CNAME
ARVENTA_DNS_RECORD_CONTENT=arventa.arventa.my.id
ARVENTA_DNS_TTL=1
ARVENTA_DNS_PROXIED=false
CLOUDFLARE_API_TOKEN=CHANGE_THIS
CLOUDFLARE_ZONE_ID=CHANGE_THIS

CAPROVER_AUTOMATION_ENABLED=true
CAPROVER_BASE_URL=https://captain.arventa.my.id
CAPROVER_AUTH_TOKEN=CHANGE_THIS
# Or use CAPROVER_PASSWORD instead of CAPROVER_AUTH_TOKEN
CAPROVER_APP_NAME=arventa
CAPROVER_NAMESPACE=captain
CAPROVER_ENABLE_SSL=true
```

After changing these values:

```bash
php artisan optimize:clear
```

Buyer flow:

1. Developer logs into `/developer/login`.
2. Developer creates a POS tenant in `/developer/pos`.
3. Developer clicks **Deploy**.
4. The generated URL becomes `https://SUBDOMAIN.arventa.my.id/admin/login` or the custom domain filled in the form.
5. Buyer logs into admin using the generated admin username/password.
6. Buyer opens **Perangkat Kasir**, creates a pairing QR, and pairs the Android app.

If deployment fails, the row stores `deployment_status=failed` and `deployment_error` with the runtime message. Check:

```bash
tail -n 100 storage/logs/laravel.log
```

## Debug 500 Runtime Errors

SSH into the CapRover server and find the running app container:

```bash
docker ps
```

Open a shell in the `srv-captain--arventa` container:

```bash
docker exec -it CONTAINER_ID bash
```

Run these inside the container:

```bash
cd /var/www/html
tail -n 100 storage/logs/laravel.log
php artisan migrate --seed
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link
```

Use `--force` for migrations in production if Laravel asks for confirmation:

```bash
php artisan migrate --seed --force
```

Common 500 causes:

- Missing or invalid `APP_KEY`.
- Wrong `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, or `DB_PASSWORD`.
- Migrations have not been run, so `/admin` cannot query its tables.
- `storage` or `bootstrap/cache` is not writable by `www-data`.
- Old cached config still points to previous environment values.

## Laravel Cache Commands

When changing environment variables:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

To cache production config after env variables are correct:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If something behaves strangely after changing env values, clear cache again:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

The container entrypoint runs these clear commands on startup so CapRover env changes are picked up safely.

## Storage Link

The Docker image creates:

```text
public/storage -> storage/app/public
```

If you need to recreate it manually:

```bash
php artisan storage:link
```

## Database Troubleshooting

Check these first:

```env
DB_CONNECTION=mysql
DB_HOST=srv-captain--arventa-db
DB_PORT=3306
DB_DATABASE=arventa_pos
DB_USERNAME=arventa_user
DB_PASSWORD=arventa
```

Common issues:

- `SQLSTATE[HY000] [2002] Connection refused`
  - The database container is not running, the internal host is wrong, or MySQL is not ready yet.

- `Access denied for user`
  - `DB_USERNAME` or `DB_PASSWORD` does not match the MySQL app credentials.

- `Unknown database`
  - The database `arventa_pos` has not been created in the MySQL app.

- App still uses old DB values
  - Run:

    ```bash
    php artisan optimize:clear
    ```

To test DB connectivity from the app container:

```bash
php artisan tinker
DB::connection()->getPdo();
```

## Expected URLs

```text
Web app:        https://arventa.arventa.my.id
Test admin:     https://arventa.arventa.my.id/admin
Buyer admin:    https://{tenant}.arventa.my.id/admin/login
API:            https://arventa.arventa.my.id/api
```

## Notes

- Keep this deployment multi-tenant in one Laravel app and one database.
- Do not commit `.env`, `.env.production`, or local secrets.
- Set all production values through CapRover environment variables.
- For a 1GB VPS, keep the app simple: one Laravel container plus one MySQL container is enough for the current stage.
