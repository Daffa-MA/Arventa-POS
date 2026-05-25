# Arventa POS Backend

Backend Laravel untuk Web Admin, Developer Console, dan API aplikasi kasir Android.

## Stack

- Laravel
- MySQL atau SQLite lokal
- Laravel Sanctum
- Tailwind CSS
- Alpine.js
- Vite

## Desain Deployment

Arventa sekarang memakai model multi-tenant satu aplikasi:

- Satu Laravel app
- Satu database utama
- Banyak toko/POS
- Data tiap toko dipisah dengan `pos_instance_id`
- Domain/subdomain menentukan tenant aktif untuk login admin
- Pairing Android mengikat perangkat kasir ke tenant yang benar

Field `database_name` pada POS instance dipakai sebagai tenant key/metadata, bukan perintah membuat database fisik baru untuk setiap pembeli.

## Flow Pembeli Baru

1. Developer login ke `/developer/login`.
2. Buka `/developer/pos`.
3. Generate POS dengan nama toko, pembeli, kontak, subdomain/domain, package app, dan akun admin.
4. Klik Deploy.
5. Sistem automation membuat DNS, attach domain ke app CapRover yang sama, dan enable SSL.
6. Pembeli buka `https://domain-pembeli/admin/login`.
7. Pembeli login memakai admin username/password hasil generate.
8. Pembeli pairing Android dari menu Perangkat Kasir.
9. Android sync setting, katalog, dan transaksi untuk tenant tersebut.

## Environment Production

Contoh env utama:

```env
APP_NAME="Arventa POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://arventa.arventa.my.id

DB_CONNECTION=mysql
DB_HOST=srv-captain--arventa-db
DB_PORT=3306
DB_DATABASE=arventa_pos
DB_USERNAME=arventa_user
DB_PASSWORD=arventa

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
FILESYSTEM_DISK=public
```

Developer login sebaiknya dibuat dari env, lalu jalankan seeder:

```env
ARVENTA_DEVELOPER_NAME="Arventa Developer"
ARVENTA_DEVELOPER_USERNAME=developer
ARVENTA_DEVELOPER_EMAIL=developer@arventa.local
ARVENTA_DEVELOPER_PASSWORD=change-this-password
```

## Automation DNS dan CapRover

Aktifkan hanya setelah credential siap:

```env
ARVENTA_DEPLOYMENT_MODE=automatic
ARVENTA_PUBLIC_BASE_DOMAIN=arventa.my.id
ARVENTA_APP_PUBLIC_HOST=arventa.arventa.my.id

ARVENTA_DNS_PROVIDER=cloudflare
ARVENTA_DNS_RECORD_TYPE=CNAME
ARVENTA_DNS_RECORD_CONTENT=arventa.arventa.my.id
ARVENTA_DNS_TTL=1
ARVENTA_DNS_PROXIED=false
CLOUDFLARE_API_TOKEN=...
CLOUDFLARE_ZONE_ID=...

CAPROVER_AUTOMATION_ENABLED=true
CAPROVER_BASE_URL=https://captain.your-domain.com
CAPROVER_PASSWORD=...
CAPROVER_AUTH_TOKEN=
CAPROVER_NAMESPACE=captain
CAPROVER_APP_NAME=arventa
CAPROVER_ENABLE_SSL=true
```

Jika `ARVENTA_DEPLOYMENT_MODE` masih `manual`, tombol Deploy akan menyimpan status `failed` dengan pesan konfigurasi yang harus dilengkapi. Ini sengaja supaya UI tidak memalsukan deploy.

## Install Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Web admin lokal:

```text
http://127.0.0.1:8000/admin
```

Developer console lokal:

```text
http://127.0.0.1:8000/developer/pos
```

## Admin Panel

Admin toko login lewat:

```text
/admin/login
```

Admin hanya bisa mengakses tenant/POS miliknya. Ketika domain pembeli cocok dengan `pos_instances.domain`, login admin dibatasi ke POS instance tersebut.

Menu penting:

- `/admin/settings`
- `/admin/products`
- `/admin/app-preview`
- `/admin/devices`
- `/admin/transactions`

## Pairing Android

Masuk ke:

```text
/admin/devices
```

Flow:

1. Generate QR pairing.
2. Masukkan nama kasir dan label perangkat.
3. Scan QR dari app Android.
4. App menerima token Sanctum.
5. Token terikat ke `pos_instance_id`.
6. App sync setting toko, katalog, dan transaksi untuk tenant tersebut.

Endpoint pairing:

```http
POST /api/pairing/connect
```

Body:

```json
{
  "code": "123456",
  "device_name": "Tablet Kasir 1",
  "device_uid": "optional-device-id"
}
```

Request berikutnya memakai:

```http
Authorization: Bearer TOKEN_DARI_PAIRING
```

## API Sync Android

```http
GET /api/sync
```

Mengembalikan data tenant yang sesuai token:

- setting toko
- layout app
- warna tema
- produk aktif
- foto produk via `image_url`

## Perintah Production

Setelah deploy:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Debug runtime:

```bash
tail -n 100 storage/logs/laravel.log
php artisan route:list --path=developer
php artisan route:list --path=admin
```

## Checklist Serah Terima Tenant

- POS instance berhasil dibuat dari Developer Console
- Domain pembeli mengarah ke app Laravel yang sama
- SSL aktif
- Pembeli bisa login `/admin/login`
- Setting toko benar
- Produk bisa ditambah
- QR pairing berhasil
- App kasir bisa sync
- Transaksi masuk ke Web Admin tenant yang benar
