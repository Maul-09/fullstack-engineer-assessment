# Fullstack Engineer Assessment

Satu repository, dua modul:

- **Task 1:** Laravel JSON API untuk order flash sale dan inventory concurrency.
- **Task 2:** standalone PHP CLI untuk hidden-item solver.

Tahapan implementasi: [`docs/PLAN_A_IMPLEMENTATION.md`](docs/PLAN_A_IMPLEMENTATION.md).

## Struktur

```text
app/                    Task 1 application code
database/               migrations and seeders
routes/api.php          Task 1 API routes
scripts/                Task 1 functional race test
task2/                  Task 2 CLI and self-check
tests/                  Task 1 automated tests
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
php artisan test
```

Implementasi fitur belum dimulai; repository ini baru berisi struktur API-only.
