/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pasangan dari QUERY 5 pada daftar_st_cek_jumlah_baris.sql. Syaratnya
 * disalin apa adanya dari query Daftar Serah Terima versi desktop, lalu
 * hasilnya dikelompokkan per tahun realisasi.
 *
 * Bandingkan baris per baris dengan hasil QUERY 5. Tahun yang jumlahnya
 * berbeda itulah letak sembilan baris yang belum ada di PostgreSQL.
 */

DECLARE @TGL_AWAL   DATE = '2023-07-01';
DECLARE @TGL_AKHIR  DATE = '2026-09-10';
DECLARE @TGL_ST1    DATE = '2023-07-01';
DECLARE @TGL_ST2    DATE = '2026-09-10';
DECLARE @BLOK_AWAL  VARCHAR(20) = 'A';
DECLARE @BLOK_AKHIR VARCHAR(20) = 'Z';
DECLARE @PERUSAHAAN VARCHAR(10) = 'DTSA';

SELECT
    YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)          AS tahun_realisasi,
    COUNT(*)                                     AS jumlah_baris,
    COUNT(DISTINCT SERAH_TERIMA.SERAH_TERIMA_ID) AS jumlah_serah_terima,
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
      ( STOK.STOK_ID = PPJB.STOK_ID ) AND
      ( PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID ) AND
      ( PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID ) AND
      ( TIPE.KD_JENIS = STOK.KD_JENIS ) AND
      ( TIPE.KD_TIPE = STOK.KD_TIPE ) AND
      ( TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS ) AND
      ( SERAH_TERIMA.TGL_SURAT >= @TGL_AWAL AND SERAH_TERIMA.TGL_SURAT <= @TGL_AKHIR ) AND
      ( SERAH_TERIMA.TGL_SERAH_TERIMA >= @TGL_ST1 AND SERAH_TERIMA.TGL_SERAH_TERIMA <= @TGL_ST2 ) AND
      ( STOK.BLOK >= @BLOK_AWAL AND STOK.BLOK <= @BLOK_AKHIR ) AND
      ( STOK.FLAG_AKTIF = 'A' ) AND
      ( PPJB.FLAG_AKTIF = 'A' ) AND
      ( PEMBELI_PPJB.FLAG_AKTIF = 'Y' ) AND
      ( STOK.KD_PERUSAHAAN = @PERUSAHAAN ) AND
      STOK.BLOK IS NOT NULL AND
      STOK.NOMOR IS NOT NULL AND
      PPJB.PARENT_ID IS NULL AND
      ISNULL(SERAH_TERIMA.FLAG_AKTIF,'A') = 'A'
GROUP BY YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)
ORDER BY 1;


/*
 * CARA MEMBACA
 *
 * Jumlah seluruh baris pada hasil ini harus 1027, sama dengan angka di
 * layar desktop. Bandingkan tiap tahunnya dengan QUERY 5 versi PostgreSQL
 * yang totalnya 1018.
 *
 * - Selisihnya menumpuk pada satu tahun
 *   -> serah terima tahun itu belum tersalin ke PostgreSQL.
 *
 * - Selisihnya menyebar sedikit-sedikit di banyak tahun
 *   -> kemungkinan bukan barisnya yang hilang, melainkan sr_pembeli_ppjb
 *      yang belum lengkap sehingga jumlah pasangannya berbeda.
 *
 * Yang paling menentukan adalah membandingkan jumlah_baris dengan
 * jumlah_serah_terima pada tiap tahun. Di PostgreSQL keduanya selalu sama,
 * yaitu 77, 738, dan 203. Kalau di sini jumlah_baris lebih besar daripada
 * jumlah_serah_terima, berarti ada PPJB yang punya lebih dari satu pembeli
 * aktif sehingga desktop menghasilkan baris ganda, dan itulah sembilan
 * baris yang selisih. QUERY 2 memastikannya.
 */


/* =====================================================================
 * QUERY 2 — Apakah satu PPJB bisa punya lebih dari satu pembeli aktif
 *
 * Pada PostgreSQL, hasil QUERY 5 menunjukkan jumlah_baris selalu sama
 * dengan jumlah_serah_terima untuk setiap tahun, yaitu 77, 738, dan 203.
 * Artinya tidak ada satu pun PPJB dalam laporan ini yang punya lebih dari
 * satu pembeli aktif.
 *
 * Kalau di SQL Server ada yang punya dua pembeli, misalnya suami dan
 * istri, desktop menghasilkan dua baris untuk satu serah terima sedangkan
 * PostgreSQL hanya satu. Itu bisa menjelaskan sembilan baris yang selisih.
 * ===================================================================== */
DECLARE @PERUSAHAAN2 VARCHAR(10) = 'DTSA';

SELECT TOP 50
    PEMBELI_PPJB.PPJB_ID,
    COUNT(*) AS jumlah_pembeli_aktif
FROM PEMBELI_PPJB WITH (NOLOCK),
     PPJB WITH (NOLOCK),
     STOK WITH (NOLOCK)
WHERE PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
  AND STOK.STOK_ID = PPJB.STOK_ID
  AND STOK.KD_PERUSAHAAN = @PERUSAHAAN2
  AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
  AND PPJB.FLAG_AKTIF = 'A'
  AND PPJB.PARENT_ID IS NULL
GROUP BY PEMBELI_PPJB.PPJB_ID
HAVING COUNT(*) > 1
ORDER BY jumlah_pembeli_aktif DESC;


/* =====================================================================
 * QUERY 3 — Apakah kode tipe yang hilang di PostgreSQL ada di sini
 *
 * Pada PostgreSQL ada 1292 pasangan KD_JENIS dan KD_TIPE yang dipakai
 * STOK tetapi tidak ada di sr_tipe, dan untuk laporan ini berdampak pada
 * 37 serah terima di blok HA sampai HG.
 *
 * Kalau kode di bawah ini ADA di SQL Server, berarti tabel TIPE memang
 * belum tersalin lengkap, dan LEFT JOIN pada model sudah benar sebagai
 * penggantinya. Kalau TIDAK ada, berarti desktop pun membuang unit itu
 * dan LEFT JOIN justru menambah baris yang tidak ada di desktop.
 * ===================================================================== */
SELECT
    LTRIM(RTRIM(TIPE.KD_JENIS)) AS kd_jenis,
    LTRIM(RTRIM(TIPE.KD_TIPE))  AS kd_tipe,
    LTRIM(RTRIM(TIPE.DESKRIPSI)) AS deskripsi
FROM TIPE WITH (NOLOCK)
WHERE LTRIM(RTRIM(TIPE.KD_TIPE)) IN (
    'R2075', 'R2076', 'R2077',
    'R2137', 'R2138', 'R2139',
    'RK784', 'RK785'
)
ORDER BY 1, 2;
