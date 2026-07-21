# Task 2 - Hidden Item CLI

Task 2 adalah program PHP standalone untuk mencari seluruh kemungkinan endpoint
hidden item setelah bergerak North, East, lalu South melalui cell yang kosong.
Program ini tidak menggunakan Laravel, Composer, database, atau API server.

## Prasyarat

- PHP 8.3 atau lebih baru.
- Terminal dijalankan dari root repository.

Periksa versi PHP:

```powershell
php -v
```

## Input

Grid berikut sudah tertanam di dalam `task2/hidden-item.php` sesuai soal:

```text
########
#......#
#.###..#
#...#..#
#X#....#
########
```

Asumsi yang digunakan:

- Koordinat menggunakan format `(x,y)`.
- Koordinat dimulai dari `0` dengan origin di kiri atas.
- `X` adalah posisi awal.
- `#` adalah obstacle dan `.` adalah cell kosong.
- Jarak North, East, dan South harus berupa integer positif.
- Seluruh lintasan harus melalui cell kosong.
- Endpoint diurutkan berdasarkan `y`, kemudian `x`.

## Menjalankan Program

Pada Windows PowerShell:

```powershell
php task2\hidden-item.php
```

Pada Linux/macOS:

```bash
php task2/hidden-item.php
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

## Marked Grid

Gunakan `--mark` untuk menampilkan koordinat dan salinan grid dengan seluruh
endpoint ditandai `$`:

```powershell
php task2\hidden-item.php --mark
```

Expected marked grid:

```text
########
#......#
#.###$$#
#...#$$#
#X#$.$$#
########
```

## Self-Test

User atau reviewer dapat memverifikasi koordinat dan marked grid tanpa test
framework tambahan:

```powershell
php task2\hidden-item.php --self-test
```

Expected output:

```text
PASS coordinates=7 marked_grid=PASS
```

Exit code `0` berarti seluruh assertion self-test berhasil.

## Exit Codes

| Exit code | Arti |
|---|---|
| `0` | Program atau self-test berhasil |
| `1` | Validasi internal atau self-test gagal |
| `2` | Opsi CLI tidak dikenali |

Contoh opsi tidak valid:

```powershell
php task2\hidden-item.php --invalid
```

Program akan menampilkan error singkat dan petunjuk penggunaan tanpa stack
trace.

## Ringkasan Algoritma

1. Temukan koordinat `X`.
2. Scan seluruh cell kosong ke arah North.
3. Dari setiap North pivot, scan ke arah East.
4. Dari setiap East pivot, scan ke arah South.
5. Simpan setiap South cell sebagai kandidat endpoint.
6. Deduplikasi kandidat dan urutkan berdasarkan `y`, lalu `x`.

