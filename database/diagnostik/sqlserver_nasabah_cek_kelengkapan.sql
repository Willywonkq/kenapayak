/* ============================================================
 * PEMBANDING JUMLAH NASABAH PADA SUMBER
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Pasangannya: nasabah_cek_kelengkapan.sql
 *
 * Di PostgreSQL sr_nasabah berisi 42.464 baris, dan 28.249 dari
 * 62.326 baris pembeli aktif menunjuk ke nasabah yang tidak ada.
 * Query ini menghitung berapa seharusnya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Jumlah baris NASABAH dan PEMBELI_PPJB pada kedua sumber.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT'   AS SUMBER, 'NASABAH' AS TABEL, COUNT(*) AS BARIS
FROM [SRIS_PUSAT].[dbo].[NASABAH] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'NASABAH', COUNT(*)
FROM [SRIS_SERPONG].[dbo].[NASABAH] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_PUSAT',   'PEMBELI_PPJB', COUNT(*)
FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'PEMBELI_PPJB', COUNT(*)
FROM [SRIS_SERPONG].[dbo].[PEMBELI_PPJB] WITH (NOLOCK)
ORDER BY TABEL, SUMBER;


/* ------------------------------------------------------------
 * QUERY 2
 * Rentang NASABAH_ID pada kedua sumber.
 *
 * Dibandingkan dengan QUERY 1 sisi PostgreSQL, untuk melihat
 * bagian mana yang tidak ikut terbawa.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT' AS SUMBER,
       MIN(NASABAH_ID) AS TERKECIL, MAX(NASABAH_ID) AS TERBESAR,
       COUNT(DISTINCT NASABAH_ID) AS NILAI_BERBEDA
FROM [SRIS_PUSAT].[dbo].[NASABAH] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG',
       MIN(NASABAH_ID), MAX(NASABAH_ID), COUNT(DISTINCT NASABAH_ID)
FROM [SRIS_SERPONG].[dbo].[NASABAH] WITH (NOLOCK);
