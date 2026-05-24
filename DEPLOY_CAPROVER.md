# Deploy Arventa POS to CapRover

Arventa POS is deployed as a single-tenant Laravel backend/web admin app. The Android POS app connects to this backend through the REST API.

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

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=srv-captain--arventa-db
DB_PORT=3306
DB_DATABASE=arventa_pos
DB_USERNAME=arventa_user
DB_PASSWORD=arventa

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
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

## Run Migrations and Seeders

After the first successful deploy, run this inside the `arventa` app container:

```bash
php artisan migrate --seed --force
```

In CapRover:

1. Open **Apps > arventa**.
2. Open **Deployment** or **App Configs** depending on your CapRover version.
3. Use the app terminal/console feature if available.
4. Run the command above.

If your CapRover UI does not expose a terminal, SSH into the server and run:

```bash
docker exec -it $(docker ps --filter "name=srv-captain--arventa" --format "{{.ID}}" | head -n 1) php artisan migrate --seed --force
```

## Laravel Cache Commands

When changing environment variables:

```bash
php artisan optimize:clear
```

To cache production config after env variables are correct:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If something behaves strangely after changing env values, clear cache again:

```bash
php artisan optimize:clear
```

The container entrypoint runs `php artisan optimize:clear` on startup so CapRover env changes are picked up safely.

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
Web app: https://arventa.arventa.my.id
Admin:   https://arventa.arventa.my.id/admin
API:     https://arventa.arventa.my.id/api
```

## Notes

- Keep this deployment single-tenant.
- Do not commit `.env`, `.env.production`, or local secrets.
- Set all production values through CapRover environment variables.
- For a 1GB VPS, keep the app simple: one Laravel container plus one MySQL container is enough for the current stage.
