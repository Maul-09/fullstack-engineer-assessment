# Plan A — Implementasi Sampai Submission

## Target

Satu repository, dua modul:

- Task 1: Laravel JSON API + PostgreSQL.
- Task 2: standalone PHP CLI.

Plan berhenti pada scope brief. Tidak ada frontend, auth, cart, payment, queue, Redis, atau microservice.

## Definition of Done

```text
composer validate --strict                PASS
vendor\bin\pint --test                    PASS
php artisan test                          PASS
php scripts\race-test.php                 PASS
php task2\hidden-item.php --self-test     PASS
GET /up                                   HTTP 200
GET /api/products                         HTTP 200 JSON
POST /api/orders                          HTTP 201/4xx JSON
inventory_quantity                        never negative
repository                                public + reproducible README
```

---

# PHASE 0 — Baseline Bersih

## Status

Sudah disiapkan:

- PHP 8.3.32.
- Composer 2.10.2.
- Laravel 13.20.0.
- PostgreSQL 18 berjalan pada port 5432.
- API routing melalui `routes/api.php`.
- Frontend/auth/user scaffold yang tidak dipakai dihapus.
- Composer autoload berhasil.
- `/up` pernah diverifikasi HTTP 200.

## Sisa tindakan

1. Isi password PostgreSQL di `.env`.
2. Buat database development dan testing.
3. Reload VS Code setelah cache Intelephense dibersihkan.

## Gate

```powershell
php -v
composer --version
composer validate --strict
php artisan about --only=environment
php artisan route:list
```

Semua command harus selesai tanpa exception.

## Commit

```text
chore: stabilize api-only Laravel structure
```

---

# PHASE 1 — PostgreSQL Development dan Testing

## 1.1 Buat database

```powershell
psql -U postgres -h 127.0.0.1
```

```sql
CREATE DATABASE fullstack_assessment;
CREATE DATABASE fullstack_assessment_test;
\q
```

## 1.2 Lengkapi `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fullstackAssessment
DB_USERNAME=postgres
DB_PASSWORD=<PASSWORD_LOKAL>
```

Jangan commit `.env`.

## 1.3 Siapkan `.env.testing`

Salin `.env.example`, lalu ubah:

```env
APP_ENV=testing
APP_DEBUG=false
DB_DATABASE=fullstack_assessment_test
DB_PASSWORD=<PASSWORD_LOKAL>
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

Jangan commit `.env.testing`.

## 1.4 Ubah `phpunit.xml`

- Ganti DB test dari SQLite ke PostgreSQL.
- Jangan simpan password pada `phpunit.xml`.
- Credential dibaca dari `.env.testing`.

## Gate

```powershell
php artisan db:show
php artisan migrate:fresh --seed
php artisan test
```

Expected awal: migration kosong berhasil; test boleh melaporkan belum ada test hanya pada Phase 1.

## Commit

Tidak perlu commit credential. Commit hanya perubahan aman pada `phpunit.xml` bila dibuat.

---

# PHASE 2 — Schema dan Model Task 1

## 2.1 Generate file minimum

```powershell
php artisan make:model Product -m
php artisan make:model Order -m
php artisan make:model OrderItem -m
```

File final:

```text
app/Models/Product.php
app/Models/Order.php
app/Models/OrderItem.php
database/migrations/*_create_products_table.php
database/migrations/*_create_orders_table.php
database/migrations/*_create_order_items_table.php
```

Tidak membuat repository, interface, DTO, service, event, atau observer.

## 2.2 Tabel `products`

```text
id                      bigint PK
sku                     varchar UNIQUE
name                    varchar
regular_price           bigint >= 0
flash_price             bigint nullable, >= 0, < regular_price
flash_starts_at         timestamptz nullable
flash_ends_at           timestamptz nullable
inventory_quantity      integer >= 0
timestamps
```

DB constraints wajib:

```text
regular_price >= 0
inventory_quantity >= 0
flash_price IS NULL OR flash_price < regular_price
flash period NULL bersama atau starts_at < ends_at
```

## 2.3 Tabel `orders`

```text
id              bigint PK
total_amount    bigint >= 0
timestamps
```

Tidak menambah customer, status payment, shipping, atau token.

## 2.4 Tabel `order_items`

```text
id              bigint PK
order_id        FK orders ON DELETE CASCADE
product_id      FK products ON DELETE RESTRICT
product_name    varchar snapshot
quantity        integer > 0
unit_price      bigint >= 0
timestamps
UNIQUE(order_id, product_id)
```

`subtotal` dihitung; tidak disimpan.

## 2.5 Relasi model

- `Product::orderItems()`.
- `Order::items()`.
- `OrderItem::order()`.
- `OrderItem::product()`.
- Product memiliki satu method kecil `currentPrice(CarbonInterface $at): int`.

## 2.6 Seeder fixture

`DatabaseSeeder` membuat satu Product:

```text
sku: FLASH-001
regular_price: 100000
flash_price: 10000
inventory_quantity: 10
flash period: aktif saat test/demo
```

## Gate

```powershell
php artisan migrate:fresh --seed
php artisan db:table products
php artisan db:table orders
php artisan db:table order_items
```

Verifikasi constraint secara nyata:

```sql
UPDATE products SET inventory_quantity = -1 WHERE sku = 'FLASH-001';
```

Expected: PostgreSQL menolak.

## Commit

```text
feat(task1): add product and order schema
```

---

# PHASE 3 — API Contract Minimum

## Endpoint final

```text
GET  /api/products
POST /api/orders
```

Tidak membuat CRUD penuh.

## 3.1 Generate controller

```powershell
php artisan make:controller ProductController
php artisan make:controller OrderController
```

## 3.2 `GET /api/products`

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "sku": "FLASH-001",
      "name": "Flash Sale Product",
      "regular_price": 100000,
      "current_price": 10000,
      "is_flash_sale_active": true,
      "inventory_quantity": 10
    }
  ]
}
```

List dibuat lean. Tidak mengirim internal timestamps bila tidak berguna.

## 3.3 `POST /api/orders`

Request:

```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

Inline validation pada controller cukup:

```text
items                    required array min:1 max:100
items.*.product_id       required integer min:1
items.*.quantity         required integer min:1 max:1000
```

Duplicate Product ID dinormalisasi menjadi satu total quantity sebelum transaction.

## 3.4 Error contract

```json
{
  "error": {
    "code": "OUT_OF_STOCK",
    "message": "Stok produk tidak mencukupi.",
    "details": {
      "product_id": 1
    }
  }
}
```

Status:

```text
201 order created
404 PRODUCT_NOT_FOUND
409 OUT_OF_STOCK
422 validation error
500 generic internal error
```

Laravel default validation JSON boleh dipakai. Tidak membuat global response wrapper yang tidak diminta.

## Gate

```powershell
php artisan route:list --path=api
```

Expected hanya dua route bisnis.

## Commit

```text
feat(task1): expose product and order API contracts
```

---

# PHASE 4 — Atomic Order Transaction

## Root invariant

```text
inventory_quantity >= 0
stock akhir = stock awal - quantity seluruh order sukses
order gagal tidak meninggalkan decrement atau row parsial
```

## 4.1 Flow controller

1. Validate JSON.
2. Group duplicate Product ID.
3. Sum quantity per Product.
4. Sort by Product ID.
5. Simpan satu `$now` untuk seluruh pricing decision.
6. Masuk `DB::transaction`.
7. Untuk setiap item, jalankan atomic decrement.
8. Jika affected row `0`, cek existence:
   - tidak ada: throw HTTP `404`;
   - ada: throw HTTP `409`.
9. Ambil Product snapshot.
10. Hitung unit price dan subtotal memakai integer.
11. Buat Order.
12. Buat seluruh Order Item.
13. Commit otomatis.
14. Return `201`.

## 4.2 SQL inti

```sql
UPDATE products
SET inventory_quantity = inventory_quantity - :quantity,
    updated_at = CURRENT_TIMESTAMP
WHERE id = :product_id
  AND inventory_quantity >= :quantity;
```

Gunakan Query Builder/Eloquent `decrement` dengan condition yang sama. Jangan memakai pola `SELECT stock` lalu `save()`.

## 4.3 Transaction retry

Laravel transaction attempts `3` cukup untuk deadlock langka:

```text
DB::transaction(callback, attempts: 3)
```

Sort Product ID mengurangi deadlock order multi-product.

## 4.4 Scope file

Pertahankan logic pada `OrderController` selama masih terbaca. Ekstrak satu action hanya jika controller menjadi sulit diuji/dibaca. Jangan membuat service/repository sebelum masalah itu nyata.

## Gate manual

```powershell
curl.exe -i -X POST http://127.0.0.1:8000/api/orders `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -d '{"items":[{"product_id":1,"quantity":1}]}'
```

Expected: `201`, stock turun satu, Order dan Order Item tersimpan.

## Commit

```text
feat(task1): create orders with atomic inventory updates
```

---

# PHASE 5 — Feature Test Task 1

## Satu file cukup

```powershell
php artisan make:test OrderApiTest
```

File:

```text
tests/Feature/OrderApiTest.php
```

Gunakan `RefreshDatabase`.

## Kasus wajib

1. `GET /api/products` mengembalikan JSON lean.
2. Order satu item sukses.
3. `items=[]` menghasilkan `422`.
4. Quantity `0`, negatif, string menghasilkan `422`.
5. Product tidak ada menghasilkan `404`.
6. Quantity lebih besar dari stock menghasilkan `409`.
7. Quantity sama dengan stock sukses; stock akhir `0`.
8. Dua item; item kedua gagal; stock item pertama rollback.
9. Duplicate Product ID diagregasi.
10. Flash belum mulai memakai regular price.
11. Flash aktif memakai flash price.
12. Tepat `flash_ends_at` memakai regular price.
13. Nama dan harga Order Item tetap sebagai snapshot.

## Assertions minimum

```text
assertStatus
assertJsonPath
assertDatabaseHas
assertDatabaseMissing
assertDatabaseCount
```

Jangan mock DB. Race behavior diuji terpisah terhadap PostgreSQL nyata.

## Gate

```powershell
php artisan test --filter=OrderApiTest
```

Expected: seluruh test hijau.

## Commit

```text
test(task1): cover order validation pricing and rollback
```

---

# PHASE 6 — Functional Race Test CLI

## Tujuan

Buktikan HTTP API aman ketika beberapa worker membeli Product sama secara bersamaan.

## Satu file

```text
scripts/race-test.php
```

Gunakan PHP stdlib/native:

- `proc_open` untuk menjalankan beberapa `php artisan serve` pada port berbeda;
- `curl_multi` untuk concurrent HTTP requests;
- Laravel bootstrap/Model untuk setup dan final DB assertions.

Tidak menambah package load-test.

## Flow script

1. Bootstrap Laravel.
2. Pastikan DB driver `pgsql`; fail bila bukan PostgreSQL.
3. Bersihkan order fixture sebelumnya.
4. Buat Product unik dengan stock `10`.
5. Start beberapa server process pada port berbeda.
6. Poll `/up` sampai seluruh worker ready.
7. Kirim 50 order request quantity `1`, round-robin ke worker.
8. Tunggu semua response.
9. Assert:
   - `201`: tepat `10`;
   - `409`: tepat `40`;
   - status lain: `0`;
   - stock akhir: `0`;
   - Order Item sukses: `10`;
   - query stock negatif: `0` row.
10. Terminate seluruh child server pada `finally`.
11. Bersihkan fixture bila perlu.
12. Exit `0` saat PASS; non-zero saat gagal.

## Expected output

```text
PASS requests=50 created=10 conflicts=40 unexpected=0 final_stock=0
```

Masukkan output ke README hanya setelah command benar-benar dijalankan.

## Gate

```powershell
php scripts\race-test.php
$LASTEXITCODE
```

Expected exit code `0`.

## Commit

```text
test(task1): add concurrent HTTP inventory race check
```

---

# PHASE 7 — Task 2 Hidden Item

## Satu file cukup

```text
task2/hidden-item.php
```

Tidak memakai Laravel. Tidak menambah package.

## Grid

```text
########
#......#
#.###..#
#...#..#
#X#....#
########
```

## Asumsi output

- Koordinat `(x,y)`.
- 0-based.
- Origin kiri-atas.
- A/B/C integer positif.
- Seluruh lintasan harus clear.
- Item berada pada endpoint setelah North → East → South.

## Algoritma

1. Temukan `X`.
2. Scan North sampai obstacle/batas.
3. Dari setiap North pivot, scan East.
4. Dari setiap East pivot, scan South.
5. Setiap South cell adalah endpoint kandidat.
6. Deduplikasi memakai associative set.
7. Sort berdasarkan `y`, lalu `x`.
8. Print coordinates.
9. Bila `--mark`, print salinan grid dengan `$`.
10. Bila `--self-test`, assert expected coordinates + marked grid.

## Expected coordinates

```text
(5,2)
(6,2)
(5,3)
(6,3)
(3,4)
(5,4)
(6,4)
```

## Expected marked grid

```text
########
#......#
#.###$$#
#...#$$#
#X#$.$$#
########
```

## Gate

```powershell
php task2\hidden-item.php
php task2\hidden-item.php --mark
php task2\hidden-item.php --self-test
```

Semua command sukses; self-test exit `0`.

## Commit

```text
feat(task2): implement hidden-item solver and self-check
```

---

# PHASE 8 — Formatting dan Error Hygiene

## Jalankan

```powershell
vendor\bin\pint
composer dump-autoload --optimize
php artisan optimize:clear
```

## Review manual

- Tidak ada `dd`, `dump`, `var_dump`, raw exception, atau stack trace response.
- Tidak ada float money.
- Tidak ada secret pada Git.
- Tidak ada endpoint reset test.
- Tidak ada dead import/class.
- Tidak ada generated cache pada Git.
- Semua error API JSON.

## Gate

```powershell
vendor\bin\pint --test
composer validate --strict
composer audit
php artisan test
```

## Commit

```text
chore: format and harden assessment code
```

---

# PHASE 9 — README Reviewer Runbook

## Isi wajib

1. Scope dua task.
2. Stack/version.
3. Explicit assumptions.
4. Arsitektur singkat.
5. Database schema.
6. Atomic race-condition decision.
7. Setup PostgreSQL.
8. Installation commands.
9. Migration + seed.
10. API contracts + curl.
11. Feature test command.
12. Race test command.
13. Task 2 commands.
14. Actual test output.
15. Deliberate omissions.
16. Public API URL bila bonus selesai.

## Fresh clone check

Clone ke folder kosong. Jangan mengandalkan `.env`, junction vendor, atau cache lokal saat membuktikan repository dapat dipakai reviewer.

```powershell
git clone <PUBLIC_REPO_URL> verify-assessment
cd verify-assessment
composer install
Copy-Item .env.example .env
php artisan key:generate
# isi credential PostgreSQL
php artisan migrate:fresh --seed
php artisan test
php scripts\race-test.php
php task2\hidden-item.php --self-test
```

## Commit

```text
docs: add reproducible setup contracts and test proof
```

---

# PHASE 10 — Final Submission Gate

## Code gate

```powershell
composer validate --strict
composer audit
vendor\bin\pint --test
php artisan migrate:fresh --seed
php artisan test
php scripts\race-test.php
php task2\hidden-item.php --self-test
php artisan route:list --path=api
```

## Git gate

```powershell
git status --short
git log --oneline --decorate -10
git ls-files .env
```

Expected:

- working tree clean;
- `.env` tidak tracked;
- `composer.lock` tracked;
- `vendor` tidak tracked;
- commit history bermakna.

## Runtime gate

```powershell
php artisan serve
```

Terminal lain:

```powershell
curl.exe -i http://127.0.0.1:8000/up
curl.exe -i http://127.0.0.1:8000/api/products
```

Expected: `200`.

## Repository gate

1. Push branch `main`.
2. Buka repository melalui incognito.
3. Pastikan public.
4. Pastikan README tampil benar.
5. Jalankan clone verification sekali lagi bila ada perubahan final.

## Bonus public API

Dikerjakan hanya setelah seluruh gate lokal hijau.

- Deploy Laravel + PostgreSQL.
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- Jalankan migration production.
- Curl public `/up` dan `/api/products`.
- Tambahkan URL ke README.

Tidak menambah Docker/deployment bila waktu assessment sempit atau belum bisa diverifikasi.

---

# Urutan Commit Final

```text
chore: stabilize api-only Laravel structure
feat(task1): add product and order schema
feat(task1): expose product and order API contracts
feat(task1): create orders with atomic inventory updates
test(task1): cover order validation pricing and rollback
test(task1): add concurrent HTTP inventory race check
feat(task2): implement hidden-item solver and self-check
chore: format and harden assessment code
docs: add reproducible setup contracts and test proof
```

---

# Stop Rule

Jangan mulai phase berikut sebelum gate phase aktif hijau. Jika waktu sempit, urutan yang tidak boleh dipotong:

```text
schema → atomic order → feature test → real race test → Task 2 → README → public push
```

Yang boleh dipotong:

```text
public deployment, Docker, extra endpoint, generic abstraction
```
