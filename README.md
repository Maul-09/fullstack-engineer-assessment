# Fullstack Engineer Assessment

Satu repository, dua modul:

- **Task 1:** Laravel JSON API untuk order flash sale dan inventory concurrency.
- **Task 2:** standalone PHP CLI untuk hidden-item solver.

## Struktur

```text
app/                    Task 1 application code
database/               migrations and seeders
routes/api.php          Task 1 API routes
task2/                  Task 2 CLI and self-check
docs/docs_api.yaml      Swagger/OpenAPI API contract
```

## Prasyarat

- PHP 8.3+
- Composer
- PostgreSQL 15+

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Lengkapi konfigurasi PostgreSQL pada `.env`, termasuk `DB_DATABASE`, sebelum
menjalankan migration.

## API

Jalankan development server:

```bash
php artisan serve
```

Endpoint yang tersedia:

```text
GET  /up
GET  /api/products
POST /api/orders
```

Dokumentasi Swagger/OpenAPI tersedia di
[`docs/docs_api.yaml`](docs/docs_api.yaml) dan dapat diimpor ke Swagger Editor
atau Swagger UI.
