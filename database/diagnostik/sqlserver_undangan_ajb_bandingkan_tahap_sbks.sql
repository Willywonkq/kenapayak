/* ============================================================
 * CORONG YANG SAMA, SISI SQL SERVER (SRIS_PUSAT)
 * JENIS "UNDANGAN AJB", UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK), tidak ada
 * subquery di dalam agregat sehingga aman dari Error 130, dan
 * kunci penyambungnya dibiarkan telanjang supaya index bisa
 * dipakai dan pemeriksaannya tidak berat.
 *
 * Filter sama persis dengan sisi PostgreSQL:
 *   UNIT    : SBKS
 *   PERIODE : 01-07-2023 s/d 21-09-2026
 *   tanpa penyaring blok dan tanpa penyaring sektor
 *
 * Tahap 8 seharusnya keluar 4.621, sama dengan angka di layar
 * desktop. Kalau bukan, berarti pemahaman saya atas query
 * desktop untuk jenis ini keliru, dan itu urusan saya.
 *
 * Tahap 3 di sini pasti sama dengan tahap 2, sebab di SQL
 * Server kunci PPJB tidak pernah kehilangan awalan. Tahap itu
 * tetap ditulis supaya nomornya sejajar dengan sisi PostgreSQL
 * dan mudah disandingkan.
 *
 * Pasangannya: undangan_ajb_bandingkan_tahap_sbks.sql
 * ============================================================ */

WITH PEMBELI_RINGKAS AS (
    SELECT
        PB.PPJB_ID,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                 THEN 1 ELSE 0 END) AS N_AKTIF,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                  AND NS.NASABAH_ID IS NOT NULL
                 THEN 1 ELSE 0 END) AS N_AKTIF_BERNASABAH
    FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
    LEFT JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON NS.NASABAH_ID = PB.NASABAH_ID
    GROUP BY PB.PPJB_ID
),
t1 AS (
    SELECT U.PPJB_ID, U.TGL_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_AJB] AS U WITH (NOLOCK)
),
t2 AS (
    SELECT * FROM t1
    WHERE TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
t3 AS (
    SELECT t2.TGL_SURAT, PPJB.PPJB_ID, PPJB.STOK_ID,
           UPPER(LTRIM(ISNULL(PPJB.FLAG_AKTIF, ''))) AS PPJB_AKTIF,
           PPJB.PARENT_ID AS PPJB_PARENT
    FROM t2
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON PPJB.PPJB_ID = t2.PPJB_ID
),
t4 AS (
    SELECT * FROM t3
    WHERE PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
),
t5 AS (
    SELECT t4.*, STOK.BLOK, STOK.NOMOR
    FROM t4
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = t4.STOK_ID
    WHERE UPPER(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, ''))) = 'SBKS'
      AND UPPER(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T'))) = 'A'
),
t6 AS (
    SELECT t5.*, P.N_AKTIF, P.N_AKTIF_BERNASABAH
    FROM t5
    INNER JOIN PEMBELI_RINGKAS AS P ON P.PPJB_ID = t5.PPJB_ID
    WHERE P.N_AKTIF > 0
),
t7 AS (
    SELECT * FROM t6 WHERE N_AKTIF_BERNASABAH > 0
),
t8 AS (
    SELECT * FROM t7 WHERE BLOK IS NOT NULL AND NOMOR IS NOT NULL
)
            SELECT 1 AS URUT, 'Tahap 1  seluruh UNDANGAN_AJB'             AS TAHAP, COUNT(*) AS BARIS FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + rentang TGL_SURAT',                       COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + kuncinya ketemu di PPJB',                 COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + PPJB aktif dan bukan turunan',            COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + stok SBKS yang aktif',                    COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + ada pembeli aktif',                       COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + pembelinya ketemu di NASABAH',            COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  + BLOK dan NOMOR terisi',                   COUNT(*) FROM t8
UNION ALL   SELECT 9,         'Tahap 9  digandakan pembeli = YANG TAMPIL',
                              SUM(N_AKTIF_BERNASABAH)                    FROM t8
ORDER BY URUT;
GO


/* ------------------------------------------------------------
 * SEBARAN PER TAHUN.
 * ------------------------------------------------------------ */
WITH PEMBELI_RINGKAS AS (
    SELECT
        PB.PPJB_ID,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                 THEN 1 ELSE 0 END) AS N_AKTIF,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                  AND NS.NASABAH_ID IS NOT NULL
                 THEN 1 ELSE 0 END) AS N_AKTIF_BERNASABAH
    FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
    LEFT JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON NS.NASABAH_ID = PB.NASABAH_ID
    GROUP BY PB.PPJB_ID
),
SURAT AS (
    SELECT U.PPJB_ID, U.TGL_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_AJB] AS U WITH (NOLOCK)
    WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
DINILAI AS (
    SELECT
        SURAT.TGL_SURAT,
        CASE WHEN PPJB.PPJB_ID IS NULL THEN 1 ELSE 0 END AS KUNCI_TIDAK_KETEMU,
        UPPER(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))        AS PPJB_AKTIF,
        PPJB.PARENT_ID                                   AS PPJB_PARENT,
        CASE WHEN STOK.STOK_ID IS NULL THEN 0 ELSE 1 END AS STOK_SBKS,
        UPPER(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))       AS STOK_AKTIF,
        STOK.BLOK,
        STOK.NOMOR,
        ISNULL(P.N_AKTIF, 0)            AS N_AKTIF,
        ISNULL(P.N_AKTIF_BERNASABAH, 0) AS N_AKTIF_BERNASABAH
    FROM SURAT
    LEFT JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON PPJB.PPJB_ID = SURAT.PPJB_ID
    LEFT JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = PPJB.STOK_ID
       AND UPPER(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, ''))) = 'SBKS'
    LEFT JOIN PEMBELI_RINGKAS AS P
        ON P.PPJB_ID = PPJB.PPJB_ID
)
SELECT
    YEAR(TGL_SURAT) AS TAHUN,
    COUNT(*)        AS SURAT,
    SUM(KUNCI_TIDAK_KETEMU) AS KUNCI_TIDAK_KETEMU,
    SUM(STOK_SBKS)          AS MILIK_SBKS,
    SUM(CASE WHEN STOK_SBKS = 1 AND PPJB_AKTIF = 'A'
              AND PPJB_PARENT IS NULL
             THEN 1 ELSE 0 END) AS PPJB_AKTIF,
    SUM(CASE WHEN STOK_SBKS = 1 AND PPJB_AKTIF = 'A'
              AND PPJB_PARENT IS NULL
              AND N_AKTIF > 0 AND N_AKTIF_BERNASABAH = 0
             THEN 1 ELSE 0 END) AS PEMBELI_TANPA_NASABAH,
    SUM(CASE WHEN STOK_SBKS = 1 AND PPJB_AKTIF = 'A'
              AND PPJB_PARENT IS NULL AND STOK_AKTIF = 'A'
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN N_AKTIF_BERNASABAH ELSE 0 END) AS LOLOS_SEMUA
FROM DINILAI
GROUP BY YEAR(TGL_SURAT)
ORDER BY 1;
GO


/* ------------------------------------------------------------
 * KEMUTAKHIRAN UNDANGAN_AJB.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)       AS BARIS,
    MIN(TGL_SURAT) AS TGL_PALING_AWAL,
    MAX(TGL_SURAT) AS TGL_PALING_AKHIR
FROM [SRIS_PUSAT].[dbo].[UNDANGAN_AJB] WITH (NOLOCK);
GO
