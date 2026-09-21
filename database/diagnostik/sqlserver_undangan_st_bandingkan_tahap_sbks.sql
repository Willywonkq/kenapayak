/* ============================================================
 * CORONG YANG SAMA, SISI SQL SERVER (SRIS_PUSAT)
 * JENIS "UNDANGAN SERAH TERIMA", UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Filter sama persis dengan sisi PostgreSQL:
 *   UNIT    : SBKS
 *   PERIODE : 01-07-2023 s/d 21-09-2026
 *   tanpa penyaring blok dan tanpa penyaring sektor
 *
 * Nama tabel suratnya saya tulis UNDANGAN_ST, mengikuti nama
 * yang dipakai model. QUERY 0 memastikannya lebih dulu supaya
 * tidak perlu ditebak. Kalau namanya ternyata lain, ganti di
 * QUERY 1 dan QUERY 2.
 *
 * Pasangannya: undangan_st_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 0
 * NAMA TABEL UNDANGAN yang sebenarnya ada.
 * ------------------------------------------------------------ */
SELECT TABLE_NAME
FROM [SRIS_PUSAT].[INFORMATION_SCHEMA].[TABLES]
WHERE TABLE_NAME LIKE '%UNDANGAN%'
ORDER BY TABLE_NAME;
GO


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG UTAMA.
 *
 * Tahap 8 seharusnya keluar 1.293, sama dengan angka di layar
 * desktop. Kalau bukan, berarti pemahaman saya atas query
 * desktop yang keliru dan itu urusan saya, bukan soal data.
 * ------------------------------------------------------------ */
WITH t1 AS (
    SELECT U.PPJB_ID, U.TGL_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] AS U WITH (NOLOCK)
),
t2 AS (
    SELECT * FROM t1
    WHERE TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
t3 AS (
    SELECT t2.*, PPJB.STOK_ID
    FROM t2
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON RTRIM(PPJB.PPJB_ID) = RTRIM(t2.PPJB_ID)
    WHERE UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) = 'A'
      AND PPJB.PARENT_ID IS NULL
),
t4 AS (
    SELECT t3.*, STOK.BLOK, STOK.NOMOR
    FROM t3
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON RTRIM(STOK.STOK_ID) = RTRIM(t3.STOK_ID)
    WHERE UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, '')))) = 'SBKS'
      AND UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) = 'A'
),
t5 AS (
    SELECT * FROM t4
    WHERE EXISTS (
        SELECT 1
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(t4.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    )
),
t6 AS (
    SELECT * FROM t5
    WHERE EXISTS (
        SELECT 1
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
            ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(t5.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    )
),
t7 AS (
    SELECT * FROM t6
    WHERE BLOK IS NOT NULL AND NOMOR IS NOT NULL
),
t8 AS (
    SELECT t7.*, NS.NAMA
    FROM t7
    INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        ON RTRIM(PB.PPJB_ID) = RTRIM(t7.PPJB_ID)
       AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
)
            SELECT 1 AS URUT, 'Tahap 1  seluruh UNDANGAN_ST'            AS TAHAP, COUNT(*) AS BARIS FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + rentang TGL_SURAT',                     COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + PPJB aktif dan bukan turunan',          COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + stok SBKS yang aktif',                  COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + ada pembeli aktif',                     COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + pembelinya ketemu di NASABAH',          COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + BLOK dan NOMOR terisi',                 COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  digandakan pembeli = YANG TAMPIL',        COUNT(*) FROM t8
ORDER BY URUT;
GO


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN PER TAHUN.
 *
 * Tanda ada pembeli dan ada nasabah dihitung lebih dulu lewat
 * OUTER APPLY, bukan di dalam SUM, supaya tidak kena Error 130.
 * ------------------------------------------------------------ */
WITH SURAT AS (
    SELECT U.PPJB_ID, U.TGL_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] AS U WITH (NOLOCK)
    WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
PUNYA_SBKS AS (
    SELECT
        SURAT.TGL_SURAT,
        STOK.BLOK,
        STOK.NOMOR,
        UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) AS FLAG_AKTIF,
        CASE WHEN P1.ADA IS NULL THEN 0 ELSE 1 END AS ADA_PEMBELI,
        CASE WHEN P2.ADA IS NULL THEN 0 ELSE 1 END AS ADA_NASABAH
    FROM SURAT
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON RTRIM(PPJB.PPJB_ID) = RTRIM(SURAT.PPJB_ID)
       AND UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) = 'A'
       AND PPJB.PARENT_ID IS NULL
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON RTRIM(STOK.STOK_ID) = RTRIM(PPJB.STOK_ID)
       AND UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, '')))) = 'SBKS'
    OUTER APPLY (
        SELECT TOP (1) 1 AS ADA
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(PPJB.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    ) AS P1
    OUTER APPLY (
        SELECT TOP (1) 1 AS ADA
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
            ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(PPJB.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    ) AS P2
)
SELECT
    YEAR(TGL_SURAT) AS TAHUN,
    COUNT(*)        AS SURAT_SBKS,
    SUM(CASE WHEN ADA_PEMBELI = 1 THEN 1 ELSE 0 END) AS ADA_PEMBELI,
    SUM(CASE WHEN ADA_NASABAH = 1 THEN 1 ELSE 0 END) AS ADA_NASABAH,
    SUM(CASE WHEN ADA_PEMBELI = 1 AND ADA_NASABAH = 0
             THEN 1 ELSE 0 END) AS PEMBELI_TANPA_NASABAH,
    SUM(CASE WHEN FLAG_AKTIF <> 'A' THEN 1 ELSE 0 END) AS STOK_TIDAK_AKTIF,
    SUM(CASE WHEN ADA_NASABAH = 1 AND FLAG_AKTIF = 'A'
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN 1 ELSE 0 END) AS LOLOS_SEMUA
FROM PUNYA_SBKS
GROUP BY YEAR(TGL_SURAT)
ORDER BY 1;
GO


/* ------------------------------------------------------------
 * QUERY 3
 * KEMUTAKHIRAN UNDANGAN_ST.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)       AS BARIS,
    MIN(TGL_SURAT) AS TGL_PALING_AWAL,
    MAX(TGL_SURAT) AS TGL_PALING_AKHIR
FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] WITH (NOLOCK);
GO
