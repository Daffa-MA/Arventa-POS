# Arventa POS Backend

Backend Laravel untuk web admin dan API aplikasi kasir Android.

## Stack

- Laravel
- MySQL atau SQLite lokal
- Laravel Sanctum
- Tailwind CSS
- Alpine.js
- Vite

## Install untuk Toko Baru

Gunakan panduan ini saat kamu menjual Arventa POS ke pembeli baru.

## 1. Buat Database

Untuk single-tenant, buat satu database per toko.

Contoh nama database:

```text
arventa_pos_parfume
arventa_pos_bakso_amin
arventa_pos_barbershop
```

Contoh SQL:

```sql
CREATE DATABASE arventa_pos_parfume;
```

## 2. Setup Domain/Subdomain

Contoh:

```text
https://parfume.arventapos.com
```

Document root server harus mengarah ke:

```text
arventa-pos-backend/public
```

Pastikan domain sudah memakai HTTPS.

## 3. Setup `.env`

Copy file env:

```bash
cp .env.example .env
```

Contoh konfigurasi:

```env
APP_NAME="Parfume POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://parfume.arventapos.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arventa_pos_parfume
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

FILESYSTEM_DISK=public
```

Untuk lokal sementara, boleh pakai SQLite:

```env
DB_CONNECTION=sqlite
```

## 4. Install Dependency

```bash
composer install --no-dev --optimize-autoloader
npm install
```

## 5. Generate App Key

```bash
php artisan key:generate
```

## 6. Migrate dan Seed

Untuk toko baru:

```bash
php artisan migrate --seed
```

Jika ingin reset database dari awal:

```bash
php artisan migrate:fresh --seed
```

Hati-hati: `migrate:fresh` menghapus seluruh data.

## 7. Storage Link

Agar foto produk bisa tampil:

```bash
php artisan storage:link
```

Foto produk disimpan di:

```text
storage/app/public/products
```

URL publiknya:

```text
/storage/products/...
```

## 8. Build Asset

```bash
npm run build
```

## 9. Cache Production

Untuk server production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika `.env` berubah:

```bash
php artisan config:clear
php artisan config:cache
```

## 10. Jalankan Lokal

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Web admin:

```text
http://127.0.0.1:8000/admin
```

## 11. Setup Toko di Web Admin

Masuk ke:

```text
/admin/settings
```

Atur:

- nama toko
- jenis usaha
- alamat
- footer struk
- pajak
- service charge
- mata uang
- warna Web Admin
- warna App Kasir
- layout App Kasir

## 12. Tambah Produk

Masuk ke:

```text
/admin/products
```

Produk mendukung:

- foto produk
- SKU opsional
- tipe produk/layanan
- satuan `pcs`, `ml`, `gram`, `kg`, `meter`
- stok decimal

## 13. Hubungkan App Kasir

Masuk ke:

```text
/admin/devices
```

Flow:

1. Klik generate QR pairing.
2. Masukkan nama kasir.
3. Masukkan label perangkat opsional.
4. Scan QR dari app Android.
5. App menerima token Sanctum.
6. App sync setting toko dan katalog.

Setiap kode pairing:

- berlaku 10 menit
- hanya bisa dipakai 1 kali
- cocok untuk multi kasir/multi perangkat

## 14. API Pairing untuk App

Endpoint:

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

Response:

```json
{
  "token_type": "Bearer",
  "token": "...",
  "cashier": {
    "id": 2,
    "name": "Kasir Shift Pagi",
    "username": "cashier_xxxxx",
    "role": "cashier"
  },
  "device": {
    "id": 1,
    "device_name": "Tablet Kasir 1"
  }
}
```

Gunakan token untuk request berikutnya:

```http
Authorization: Bearer TOKEN_DARI_PAIRING
```

## 15. API Sync App Kasir

```http
GET /api/sync
```

Mengembalikan:

- setting toko
- layout app
- warna tema
- produk aktif
- foto produk via `image_url`

## 16. Checklist Sebelum Serah Terima

- Domain sudah HTTPS
- `.env` sudah benar
- Database toko sudah benar
- `php artisan migrate --seed` berhasil
- `php artisan storage:link` berhasil
- `npm run build` berhasil
- Web admin bisa dibuka
- Produk bisa ditambah
- Foto produk tampil
- QR pairing berhasil
- App kasir bisa sync
- Transaksi masuk ke web admin

