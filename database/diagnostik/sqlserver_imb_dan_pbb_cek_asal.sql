/* ============================================================
 * PEMBANDING SISI SQL SERVER UNTUK sr_imb DAN sr_pbb
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Pasangannya: imb_dan_pbb_cek_asal_database.sql
 *
 * Cara membaca hasilnya, bandingkan dengan jumlah di PostgreSQL:
 *   sr_imb = 21.998 baris     sr_pbb = 15.429 baris
 *
 *   Kalau mendekati jumlah SRIS_PUSAT saja  -> satu database sumber
 *   Kalau mendekati jumlah PUSAT + SERPONG  -> dua database sumber
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Jumlah baris IMB dan PBB pada kedua database sumber.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT'   AS DATABASE_SUMBER, 'IMB' AS TABEL, COUNT(*) AS BARIS
FROM [SRIS_PUSAT].[dbo].[IMB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'IMB', COUNT(*)
FROM [SRIS_SERPONG].[dbo].[IMB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_PUSAT',   'PBB', COUNT(*)
FROM [SRIS_PUSAT].[dbo].[PBB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'PBB', COUNT(*)
FROM [SRIS_SERPONG].[dbo].[PBB] WITH (NOLOCK)
ORDER BY TABEL, DATABASE_SUMBER;


/* ------------------------------------------------------------
 * QUERY 2
 * Rentang SERTIPIKAT_ID pada kedua database sumber.
 *
 * Kalau rentangnya bertumpang tindih, itu memastikan angka yang
 * sama memang dipakai kedua database, yang menjelaskan kenapa
 * 92 persen baris tergolong rancu setelah digabung.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT' AS DATABASE_SUMBER, 'IMB' AS TABEL,
       MIN(SERTIPIKAT_ID) AS TERKECIL, MAX(SERTIPIKAT_ID) AS TERBESAR,
       COUNT(DISTINCT SERTIPIKAT_ID) AS NILAI_BERBEDA
FROM [SRIS_PUSAT].[dbo].[IMB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'IMB',
       MIN(SERTIPIKAT_ID), MAX(SERTIPIKAT_ID), COUNT(DISTINCT SERTIPIKAT_ID)
FROM [SRIS_SERPONG].[dbo].[IMB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_PUSAT', 'PBB',
       MIN(SERTIPIKAT_ID), MAX(SERTIPIKAT_ID), COUNT(DISTINCT SERTIPIKAT_ID)
FROM [SRIS_PUSAT].[dbo].[PBB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_SERPONG', 'PBB',
       MIN(SERTIPIKAT_ID), MAX(SERTIPIKAT_ID), COUNT(DISTINCT SERTIPIKAT_ID)
FROM [SRIS_SERPONG].[dbo].[PBB] WITH (NOLOCK)
ORDER BY TABEL, DATABASE_SUMBER;


/* ------------------------------------------------------------
 * QUERY 3
 * Jumlah baris IMB per unit pada SRIS_PUSAT.
 *
 * Dipakai sebagai kebenaran pembanding untuk QUERY 3 sisi
 * PostgreSQL. Kalau jumlah per unitnya cocok, berarti penyusunan
 * ulang awalan memang menghasilkan laporan yang benar, bukan
 * sekadar menghasilkan sejumlah baris.
 * ------------------------------------------------------------ */
SELECT
    UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) AS KODE_UNIT,
    COUNT(*) AS BARIS_IMB
FROM [SRIS_PUSAT].[dbo].[IMB]        AS IMB        WITH (NOLOCK)
INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = IMB.SERTIPIKAT_ID
INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK       WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
GROUP BY UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN)))
ORDER BY BARIS_IMB DESC;


/* ------------------------------------------------------------
 * QUERY 4
 * Jumlah baris PBB per unit pada SRIS_PUSAT.
 *
 * Kebenaran pembanding untuk QUERY 4 sisi PostgreSQL, sama seperti
 * QUERY 3 dipakai untuk IMB.
 * ------------------------------------------------------------ */
SELECT
    UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) AS KODE_UNIT,
    COUNT(*) AS BARIS_PBB
FROM [SRIS_PUSAT].[dbo].[PBB]              AS PBB        WITH (NOLOCK)
INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = PBB.SERTIPIKAT_ID
INNER JOIN [SRIS_PUSAT].[dbo].[STOK]       AS STOK       WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
GROUP BY UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN)))
ORDER BY BARIS_PBB DESC;
