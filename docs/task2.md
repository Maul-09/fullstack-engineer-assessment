# Task 2 - Hidden Item

Program ini mencari kemungkinan lokasi item setelah bergerak ke North, East,
lalu South. Task 2 berjalan langsung dengan PHP dan tidak memakai Laravel atau
database.

## Input

Grid sudah ditulis di dalam `task2/hidden-item.php`:

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
- `#` adalah penghalang dan `.` adalah cell kosong.
- Jarak North, East, dan South harus berupa integer positif.
- Seluruh lintasan harus melalui cell kosong.
- Hasil diurutkan berdasarkan `y`, kemudian `x`.

## Menjalankan

Pada Windows PowerShell:

```powershell
php task2\hidden-item.php
```

Pada Linux/macOS:

```bash
php task2/hidden-item.php
```

Output:

```text
(5,2)
(6,2)
(5,3)
(6,3)
(3,4)
(5,4)
(6,4)
```

## Menandai Hasil

Gunakan `--mark` untuk menandai semua hasil dengan `$`:

```powershell
php task2\hidden-item.php --mark
```

Output grid:

```text
########
#......#
#.###$$#
#...#$$#
#X#$.$$#
########
```

## Pengecekan

Gunakan `--self-test` untuk mengecek koordinat dan grid:

```powershell
php task2\hidden-item.php --self-test
```

Output:

```text
PASS coordinates=7 marked_grid=PASS
```

## Algoritma

1. Temukan koordinat `X`.
2. Telusuri cell kosong ke arah North.
3. Dari setiap posisi North, telusuri ke arah East.
4. Dari setiap posisi East, telusuri ke arah South.
5. Simpan setiap posisi South, hapus duplikat, lalu urutkan hasilnya.
