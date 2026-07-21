# Fullstack Engineer Assessment

Repository ini berisi dua task:

- **Task 1:** Laravel JSON API untuk flash-sale order dan inventory concurrency.
- **Task 2:** standalone PHP CLI untuk mencari kemungkinan lokasi hidden item.

Project bersifat API-only. Tidak ada frontend, authentication, cart, payment,
queue, Redis, atau microservice.

## Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL 15+
- Composer 2

## Struktur

```text
app/                    Task 1 controllers and Eloquent models
database/migrations/    PostgreSQL schema
database/seeders/       Demo flash-sale Product
routes/api.php          Business API routes
task2/hidden-item.php   Standalone Task 2 CLI and self-test
docs/docs_api.yaml      Swagger/OpenAPI contract
```

## Asumsi

- Harga disimpan sebagai integer dalam satuan mata uang terkecil; tidak ada
  perhitungan menggunakan float.
- Flash sale aktif pada interval `flash_starts_at <= now < flash_ends_at`.
- Duplicate `product_id` dalam satu request dijumlahkan sebelum transaction.
- Order gagal sepenuhnya apabila satu Product tidak ditemukan atau stok tidak
  mencukupi.
- Task 2 menggunakan koordinat `(x,y)`, 0-based, dengan origin di kiri atas.
- Jarak North, East, dan South adalah integer positif dan seluruh lintasan harus
  melewati cell `.`.

## Arsitektur Task 1

API menggunakan controller Laravel, Eloquent model, dan PostgreSQL. Order dibuat
dalam satu database transaction. Inventory dikurangi menggunakan conditional
atomic update:

```sql
UPDATE products
SET inventory_quantity = inventory_quantity - :quantity
WHERE id = :product_id
  AND inventory_quantity >= :quantity;
```

Hanya request yang memperoleh satu affected row yang dapat membuat Order.
Product ID diurutkan untuk mengurangi risiko deadlock dan transaction dapat
diulang maksimal tiga kali.

### Database schema

| Table | Kolom penting |
|---|---|
| `products` | `sku`, harga reguler/flash, periode flash, `inventory_quantity` |
| `orders` | `total_amount`, timestamps |
| `order_items` | Product reference, snapshot nama/harga, quantity |

PostgreSQL check constraints menjaga harga, periode flash, quantity, total, dan
inventory tetap valid. Kombinasi `order_id` dan `product_id` bersifat unique.

## Instalasi

Clone repository dan install dependency:

```powershell
git clone <PUBLIC_REPOSITORY_URL> fullstack-assessment
cd fullstack-assessment
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Buat database PostgreSQL:

```sql
CREATE DATABASE fullstuckassesment;
```

Lengkapi `.env` lokal:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fullstuckassesment
DB_USERNAME=postgres
DB_PASSWORD=<LOCAL_PASSWORD>
```

Jalankan migration dan demo seeder:

```powershell
php artisan migrate --seed
```

Seeder membuat Product `FLASH-001` dengan regular price `100000`, flash price
`10000`, dan inventory awal `10`.

## Menjalankan API

```powershell
php artisan serve
```

Base URL lokal: `http://127.0.0.1:8000`.

### Health check

```powershell
curl.exe -i http://127.0.0.1:8000/up
```

### Product list

```powershell
curl.exe -i http://127.0.0.1:8000/api/products `
  -H "Accept: application/json"
```

Response `200` berisi harga aktif dan inventory setiap Product.

### Create order

```powershell
curl.exe -i -X POST http://127.0.0.1:8000/api/orders `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -d '{"items":[{"product_id":1,"quantity":2}]}'
```

Status response:

| Status | Kondisi |
|---|---|
| `201` | Order berhasil dibuat |
| `400` | JSON tidak valid |
| `404` | Product tidak ditemukan |
| `409` | Stok tidak mencukupi |
| `415` | Content-Type bukan JSON |
| `422` | Payload gagal validation |

Swagger/OpenAPI contract tersedia di
[`docs/docs_api.yaml`](docs/docs_api.yaml) dan dapat diimpor ke Postman,
Swagger Editor, atau Swagger UI.

## Task 2 Hidden Item

Task 2 tidak melakukan bootstrap Laravel dan tidak membutuhkan Composer.
Panduan penggunaan lengkap tersedia di
[`docs/task2.md`](docs/task2.md).

Tampilkan seluruh kandidat koordinat:

```powershell
php task2\hidden-item.php
```

Expected output:

```text
(5,2)
(6,2)
(5,3)
(6,3)
(3,4)
(5,4)
(6,4)
```

Tampilkan koordinat dan marked grid:

```powershell
php task2\hidden-item.php --mark
```

Jalankan self-test bawaan:

```powershell
php task2\hidden-item.php --self-test
```

Expected:

```text
PASS coordinates=7 marked_grid=PASS
```

## Verifikasi Internal

Sebelum submission, implementasi diverifikasi menggunakan command berikut:

```powershell
composer validate --strict
vendor\bin\pint --test
php artisan test
php scripts\race-test.php
php task2\hidden-item.php --self-test
php artisan route:list --path=api
```

Actual output terakhir:

```text
Composer                  PASS
Pint                      PASS (31 files)
Laravel tests             PASS (19 tests, 94 assertions)
Inventory race test       PASS created=10 conflicts=40 final_stock=0
Task 2 self-test          PASS coordinates=7 marked_grid=PASS
Business API routes       PASS (2 routes)
```

Task 1 test dan race-test artifacts disimpan local-only dan tidak menjadi bagian
dari submission repository.

## Deliberate Omissions

- Tidak ada frontend karena scope assessment adalah JSON API dan CLI.
- Tidak ada authentication, cart, payment, shipping, queue, atau Redis.
- Tidak ada Docker dan public deployment karena bukan kebutuhan wajib.
- Tidak ada generic repository/service abstraction untuk flow yang masih kecil.
