/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * PENTING SEBELUM MENJALANKAN
 *
 * Galat "Invalid object name 'SERAH_TERIMA'" berarti skripnya sedang jalan
 * di database yang salah, biasanya master. Tabel SERAH_TERIMA memang tidak
 * ada di sana.
 *
 * Pilih dulu database SRIS-nya. Di DBeaver ada dua kotak pilihan di baris
 * atas, ubah yang kirinya dari master menjadi database SRIS. Pada judul
 * aplikasi desktop tertulis
 *     SRIS [v1.0] | 172.16.0.100 | SRIS_PUSAT | ...
 * jadi nama databasenya kemungkinan besar SRIS_PUSAT.
 *
 * Bisa juga dengan menghapus tanda komentar pada baris USE di bawah ini,
 * lalu menyesuaikan namanya. Kalau masih ragu, jalankan QUERY 0 lebih dulu.
 *
 * Semua nama tabel dan kolom di sini disalin apa adanya dari query Daftar
 * Serah Terima versi desktop. Nilai tanggal ditulis langsung, bukan lewat
 * DECLARE, supaya tiap query bisa dijalankan sendiri-sendiri tanpa harus
 * menyorot seluruh berkas.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * QUERY 0 — Memastikan databasenya sudah benar
 *
 * Bagian pertama mendaftar seluruh database di server ini.
 * Bagian kedua memeriksa apakah ketujuh tabel yang dipakai laporan ada di
 * database yang sedang aktif. Kalau hasilnya tujuh baris, sudah benar.
 * Perhatikan juga kolom skema, kalau bukan dbo maka nama tabelnya perlu
 * diawali nama skema itu.
 * ===================================================================== */
SELECT name AS nama_database
FROM sys.databases
ORDER BY name;

SELECT
    TABLE_CATALOG AS database_aktif,
    TABLE_SCHEMA  AS skema,
    TABLE_NAME    AS tabel
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME IN (
    'SERAH_TERIMA', 'PPJB', 'STOK', 'TIPE',
    'JENIS_BANGUNAN', 'PEMBELI_PPJB', 'NASABAH'
)
ORDER BY TABLE_NAME;


/* =====================================================================
 * QUERY 1 — Sebaran per tahun realisasi memakai syarat desktop
 *
 * Syaratnya disalin apa adanya dari query Daftar Serah Terima desktop.
 * Pasangannya di PostgreSQL adalah QUERY 5 pada berkas
 * database/diagnostik/daftar_st_cek_jumlah_baris.sql.
 *
 * Jumlah seluruh baris di sini harus 1027, sama dengan angka di layar
 * desktop, sedangkan PostgreSQL menghasilkan 1018.
 * ===================================================================== */
SELECT
    YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)              AS tahun_realisasi,
    COUNT(*)                                         AS jumlah_baris,
    COUNT(DISTINCT PPJB.PPJB_ID)                     AS jumlah_ppjb,
    CAST(MIN(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_awal,
    CAST(MAX(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_akhir
FROM SERAH_TERIMA WITH (NOLOCK),
     PPJB WITH (NOLOCK),
     STOK WITH (NOLOCK),
     TIPE WITH (NOLOCK),
     JENIS_BANGUNAN WITH (NOLOCK),
     PEMBELI_PPJB WITH (NOLOCK),
     NASABAH WITH (NOLOCK)
WHERE ( SERAH_TERIMA.PPJB_ID = PPJB.PPJB_ID ) AND
      ( STOK.STOK_ID = PPJB.STOK_ID ) and
      ( PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID ) and
      ( PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID ) and
      ( TIPE.KD_JENIS = STOK.KD_JENIS ) and
      ( TIPE.KD_TIPE = STOK.KD_TIPE ) AND
      ( TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS ) and
      ( SERAH_TERIMA.TGL_SURAT >= '2023-07-01' AND SERAH_TERIMA.TGL_SURAT <= '2026-09-10' ) AND
      ( SERAH_TERIMA.TGL_SERAH_TERIMA >= '2023-07-01' AND SERAH_TERIMA.TGL_SERAH_TERIMA <= '2026-09-10' ) AND
      ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'Z' ) AND
      ( STOK.FLAG_AKTIF = 'A' ) AND
      ( PPJB.FLAG_AKTIF = 'A' ) AND
      ( PEMBELI_PPJB.FLAG_AKTIF = 'Y' ) and
      ( STOK.KD_PERUSAHAAN = 'DTSA' )
      AND STOK.BLOK IS NOT NULL
      AND STOK.NOMOR IS NOT NULL
      AND PPJB.PARENT_ID IS NULL
      AND ISNULL(SERAH_TERIMA.FLAG_AKTIF,'A') = 'A'
GROUP BY YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)
ORDER BY 1;


/* =====================================================================
 * QUERY 2 — PPJB yang punya lebih dari satu pembeli aktif
 *
 * Di PostgreSQL, jumlah baris selalu sama dengan jumlah PPJB, jadi tidak
 * ada satu pun PPJB dalam laporan ini yang punya lebih dari satu pembeli
 * aktif. Kalau di sini ada, misalnya suami dan istri, desktop menghasilkan
 * dua baris untuk satu serah terima sedangkan PostgreSQL hanya satu, dan
 * itu bisa menjelaskan sembilan baris yang selisih.
 * ===================================================================== */
SELECT TOP 50
    PEMBELI_PPJB.PPJB_ID,
    COUNT(*) AS jumlah_pembeli_aktif
FROM PEMBELI_PPJB WITH (NOLOCK),
     PPJB WITH (NOLOCK),
     STOK WITH (NOLOCK)
WHERE ( PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID ) AND
      ( STOK.STOK_ID = PPJB.STOK_ID ) AND
      ( STOK.KD_PERUSAHAAN = 'DTSA' ) AND
      ( PEMBELI_PPJB.FLAG_AKTIF = 'Y' ) AND
      ( PPJB.FLAG_AKTIF = 'A' ) AND
      PPJB.PARENT_ID IS NULL
GROUP BY PEMBELI_PPJB.PPJB_ID
HAVING COUNT(*) > 1
ORDER BY 2 DESC;


/* =====================================================================
 * QUERY 3 — Apakah kode tipe yang hilang di PostgreSQL ada di sini
 *
 * Di PostgreSQL ada 1292 pasangan KD_JENIS dan KD_TIPE yang dipakai STOK
 * tetapi tidak ada di sr_tipe, dan untuk laporan ini berdampak pada 37
 * serah terima di blok HA sampai HG.
 *
 * Kalau kode di bawah ini ADA di sini, berarti tabel TIPE memang belum
 * tersalin lengkap ke PostgreSQL, dan LEFT JOIN pada model sudah benar
 * sebagai penggantinya. Kalau TIDAK ada, berarti desktop pun membuang unit
 * itu, dan LEFT JOIN justru menambah baris yang tidak ada di desktop.
 * ===================================================================== */
SELECT
    LTRIM(RTRIM(TIPE.KD_JENIS))  AS kd_jenis,
    LTRIM(RTRIM(TIPE.KD_TIPE))   AS kd_tipe,
    LTRIM(RTRIM(TIPE.DESKRIPSI)) AS deskripsi
FROM TIPE WITH (NOLOCK)
WHERE LTRIM(RTRIM(TIPE.KD_TIPE)) IN (
    'R2075', 'R2076', 'R2077',
    'R2137', 'R2138', 'R2139',
    'RK784', 'RK785'
)
ORDER BY 1, 2;


/*
 * CARA MEMBACA
 *
 * QUERY 1, jumlahkan kolom jumlah_baris seluruh tahun, harus 1027.
 * Bandingkan tiap tahunnya dengan QUERY 5 versi PostgreSQL yang totalnya
 * 1018, yaitu 2024 sebanyak 77, 2025 sebanyak 738, dan 2026 sebanyak 203.
 *
 * Yang paling menentukan adalah membandingkan jumlah_baris dengan
 * jumlah_ppjb pada tiap tahun. Di PostgreSQL keduanya selalu sama. Kalau
 * di sini jumlah_baris lebih besar daripada jumlah_ppjb, berarti ada PPJB
 * yang punya lebih dari satu pembeli aktif sehingga desktop menghasilkan
 * baris ganda, dan itulah sembilan baris yang selisih. QUERY 2
 * memastikannya sekaligus menunjukkan PPJB mana saja.
 *
 * Kalau jumlah_baris sama dengan jumlah_ppjb di kedua sisi tetapi angkanya
 * tetap berbeda, berarti memang ada serah terima yang belum tersalin ke
 * PostgreSQL. Lihat tahun mana yang selisih.
 */
