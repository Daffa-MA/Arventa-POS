# Arventa POS

Sistem Point of Sale multi-tenant untuk UMKM Indonesia. Terdiri dari backend Laravel (web admin + API) dan aplikasi Android kasir.

```
1 backend Laravel + 1 database = banyak toko (multi-tenant)
Data tiap toko dipisah dengan pos_instance_id
```

Contoh tenant:
- `parfume.arventapos.com`
- `bakso.arventapos.com`
- `barber.arventapos.com`

---

## Arsitektur

```
                          +-----------------------+
                          |   Web Browser         |
                          |   (admin login/page)   |
                          +----------+------------+
                                     |
                          Host: {tenant}.domain.com
                                     |
                                     v
                          +-----------------------+
                          |   Laravel App         |
                          |   (single instance)   |
                          +----------+------------+
                                     |
                    +----------------+----------------+
                    |                |                |
                    v                v                v
              Web Admin (/admin)  API (/api)   Developer (/developer)
              Login by subdomain  Token-based   Console for creating
              + session           + Sanctum     POS tenants
                    |                |
                    v                v
              +----------------------------+
              |      MySQL Database        |
              |  - pos_instances           |
              |  - store_settings          |
              |  - products                |
              |  - sales / sale_items      |
              |  - cashier_devices         |
              |  - users                   |
              |  Semua data dipisah oleh   |
              |  pos_instance_id           |
              +----------------------------+
```

---

## Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.3, Laravel 13, Laravel Sanctum |
| Database | MySQL (prod) / SQLite (local) |
| Frontend Web | Blade, Tailwind CSS, Alpine.js, Vite |
| Android | Kotlin, Jetpack Compose, CameraX, ML Kit |
| Deployment | Docker, CapRover |
| DNS/SSL | Cloudflare API (opsional), wildcard DNS |

---

## Struktur Project

```
D:\ArventaPOS
├── app/                          # Android app (Kotlin + Jetpack Compose)
│   ├── build.gradle.kts
│   └── src/main/java/com/example/arventapos/
│       └── MainActivity.kt       # Seluruh app kasir (2560 baris)
├── arventa-pos-backend/          # Laravel backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/        # Web admin panel
│   │   │   │   ├── Api/          # API untuk Android
│   │   │   │   └── Developer/    # Developer console
│   │   │   └── Middleware/       # Admin & developer auth
│   │   ├── Models/               # 8 Eloquent models
│   │   └── Services/
│   │       └── Deployment/       # CapRover + Cloudflare automation
│   ├── routes/
│   │   ├── web.php               # ~30 web routes
│   │   ├── api.php               # 5 API endpoints
│   │   └── console.php           # Artisan commands
│   ├── database/migrations/      # 23 migration files
│   └── resources/views/          # Blade templates
├── Dockerfile                    # Docker image for CapRover
├── captain-definition            # CapRover deployment config
├── docker/entrypoint.sh          # Container entrypoint
└── DEPLOY_CAPROVER.md            # Panduan deploy lengkap
```

---

## Install Lokal (Development)

### Prerequisites

- PHP 8.3+
- Composer 2
- Node.js 22+
- MySQL atau SQLite
- Android Studio (untuk Android app)

### Backend

```bash
cd arventa-pos-backend

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Setup environment
cp .env.example .env
# Edit .env: atur DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate app key
php artisan key:generate

# Run migration + seeder
php artisan migrate --seed

# Storage link
php artisan storage:link

# Build frontend assets
npm run build

# Jalankan dev server
php artisan serve --host=127.0.0.1 --port=8000
```

Atau pake command `setup` yang sudah disediakan:

```bash
cd arventa-pos-backend
composer run setup
```

### Environment Variables (Minimal)

```env
APP_NAME="Arventa POS"
APP_URL=http://127.0.0.1:8000
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arventa_pos
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Environment Variables (Developer Account)

```env
ARVENTA_DEVELOPER_NAME="Arventa Developer"
ARVENTA_DEVELOPER_USERNAME=developer
ARVENTA_DEVELOPER_EMAIL=developer@arventa.local
ARVENTA_DEVELOPER_PASSWORD=change-this-password
```

### Environment Variables (Multi-Tenant Domain)

```env
ARVENTA_DEPLOYMENT_MODE=manual
ARVENTA_POS_BASE_DOMAIN=arventa.my.id
ARVENTA_APP_PUBLIC_HOST=
ARVENTA_DNS_PROVIDER=wildcard
```

### Akses Lokal

| URL | Keterangan |
|-----|-----------|
| `http://127.0.0.1:8000/admin` | Redirect ke login admin |
| `http://127.0.0.1:8000/admin/login` | Login admin toko |
| `http://127.0.0.1:8000/developer/login` | Login developer |
| `http://127.0.0.1:8000/developer/pos` | Developer console |

> **Catatan**: Di lokal, login admin menggunakan akun developer. Setelah login sebagai developer, buka `/developer/pos` untuk membuat POS instance baru. Admin toko bisa login setelah POS instance dibuat.

### Dev Server dengan Hot Reload

```bash
cd arventa-pos-backend
composer run dev
```

Ini menjalankan 4 process concurrently:
- `php artisan serve` (Laravel server)
- `php artisan queue:listen` (Queue worker)
- `php artisan pail` (Log viewer)
- `npm run dev` (Vite HMR)

---

## Android App

### Setup

1. Buka folder `app/` di Android Studio.
2. Sync Gradle.
3. Pastikan `ARVENTA_PAIRING_BASE_URL` di `app/build.gradle.kts` mengarah ke server backend.
4. Build APK dan install di perangkat Android (min SDK 24).

### Pairing Flow

```
Web Admin -> Perangkat Kasir -> Generate QR Pairing
     -> Scan QR dari app Android
     -> App terima token Sanctum
     -> App sync setting toko, produk, dan transaksi
```

---

## Deploy ke Production (CapRover)

Panduan lengkap ada di [`DEPLOY_CAPROVER.md`](./DEPLOY_CAPROVER.md).

### Ringkasan

1. Buat app `arventa` di CapRover dengan domain `https://arventa.apps.domain.com`
2. Setup MySQL database (misal: `srv-captain--arventa-db:3306`)
3. Set environment variables di CapRover
4. Deploy dari GitHub atau CapRover CLI
5. Jalankan migrasi:
   ```bash
   php artisan migrate --seed --force
   ```
6. Login ke `/developer/login`, buat POS tenant, dan deploy tenant

### Multi-Tenant

Wildcard DNS `*.domain.com` mengarah ke server yang sama. Tiap tenant punya subdomain sendiri:

```
https://{tenant}.domain.com/admin/login
```

Saat developer create + deploy tenant:
- DNS wildcard sudah menangani routing
- CapRover attach domain ke app yang sama
- SSL diaktifkan otomatis
- Tenant bisa login dengan akun admin yang digenerate

---

## API (untuk Android)

| Method | Endpoint | Auth | Keterangan |
|--------|----------|------|------------|
| POST | `/api/pairing/connect` | - | QR pairing dengan kode 6 digit |
| GET | `/api/sync` | Sanctum | Sync setting + produk |
| POST | `/api/transactions` | Sanctum | Kirim transaksi |
| POST | `/api/login` | - | Login kasir |
| POST | `/api/logout` | Sanctum | Revoke token |

---

## Tabel Database

| Tabel | Keterangan |
|-------|-----------|
| `pos_instances` | Data tenant (subdomain, domain, status) |
| `store_settings` | Setting tampilan + pajak tiap toko |
| `products` | Produk/layanan tiap toko |
| `sales` | Transaksi penjualan |
| `sale_items` | Item per transaksi |
| `cashier_devices` | Perangkat kasir ter-pairing |
| `cashier_pairing_codes` | Kode QR pairing |
| `users` | Akun (developer, admin, cashier) |

Semua tabel tenant (kecuali `pos_instances` dan `users` role developer) memiliki kolom `pos_instance_id` untuk isolasi data multi-tenant.

---

## Perintah Penting

```bash
# Debug deployment
php artisan arventa:deployment-debug

# Repair domain tenant
php artisan arventa:repair-pos-domains

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear cache
php artisan optimize:clear
```
