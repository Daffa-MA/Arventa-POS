# Arventa POS

Arventa POS adalah sistem Point of Sale dinamis untuk UKOM dan siap dikembangkan menjadi produk jualan. Project terdiri dari:

- `arventa-pos-backend`: Laravel web admin + API
- `app`: Android Kotlin Jetpack Compose untuk kasir

## Model Jualan yang Dipakai

Untuk tahap awal, Arventa POS memakai model **single-tenant per pembeli**:

```text
1 pembeli = 1 web admin = 1 database = 1 domain/subdomain
```

Contoh:

```text
parfume.arventapos.com    -> database arventa_pos_parfume
bakso.arventapos.com      -> database arventa_pos_bakso
barber.arventapos.com     -> database arventa_pos_barber
```

Model ini lebih aman untuk awal produk karena data tiap toko tidak saling bercampur.

## Flow Setup Pembeli Baru

1. Siapkan data toko:
   - nama toko
   - nama owner
   - domain/subdomain
   - logo dan warna brand
   - jenis usaha

2. Buat database baru untuk toko.

3. Install/migrate backend Laravel untuk toko tersebut.

4. Login ke web admin dan atur:
   - identitas toko
   - produk/layanan
   - pajak
   - service charge
   - tampilan web admin
   - tampilan app kasir

5. Install APK kasir Android.

6. Hubungkan app kasir lewat:

```text
Web Admin -> Perangkat Kasir -> Generate QR Pairing -> Scan dari app
```

## Dokumen Teknis

Panduan install/migrate Laravel untuk toko baru ada di:

```text
arventa-pos-backend/README.md
```

