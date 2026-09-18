/* ============================================================
 * CORONG YANG SAMA, SISI SQL SERVER (SRIS_PUSAT)
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Filter sama: SBKS, blok A s/d Z, TAHUN PBB 2000 s/d 2026,
 * TGL INPUT 01-01-2020 s/d 31-12-2023.
 *
 * Pasangannya: pbb_bandingkan_tahap_sbks.sql.
 * Sandingkan tahap demi tahap; tahap yang angkanya mulai berbeda
 * jauh itulah letak persoalannya.
 * ============================================================ */

WITH t1 AS (
    SELECT SERTIPIKAT_ID, TAHUN_PBB, TGL_INPUT
    FROM [SRIS_PUSAT].[dbo].[PBB] WITH (NOLOCK)
),
t2 AS (
    SELECT * FROM t1 WHERE TAHUN_PBB >= 2000 AND TAHUN_PBB <= 2026
),
t3 AS (
    SELECT * FROM t2
    WHERE TGL_INPUT >= CONVERT(DATETIME, '20200101', 112)
      AND TGL_INPUT <  CONVERT(DATETIME, '20240101', 112)
),
t4 AS (
    SELECT SERTIPIKAT.STOK_ID
    FROM t3
    INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
        ON SERTIPIKAT.SERTIPIKAT_ID = t3.SERTIPIKAT_ID
),
t5 AS (
    SELECT STOK.BLOK, STOK.NOMOR
    FROM t4
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = t4.STOK_ID
    WHERE UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = 'SBKS'
),
t6 AS (
    SELECT * FROM t5
    WHERE (
            ((RTRIM(BLOK) + '/' + RTRIM(NOMOR)) >= 'A'
             AND (RTRIM(BLOK) + '/' + RTRIM(NOMOR)) <= 'Z')
            OR (BLOK >= 'A' AND BLOK <= 'Z')
          )
)
SELECT 1 AS URUT, 'Tahap 1  seluruh PBB'                   AS TAHAP, COUNT(*) AS BARIS FROM t1
UNION ALL SELECT 2, 'Tahap 2  + rentang TAHUN PBB',           COUNT(*) FROM t2
UNION ALL SELECT 3, 'Tahap 3  + rentang TGL INPUT',           COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + ketemu sertipikatnya',        COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + stok SBKS',                   COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + saringan blok = YANG TAMPIL', COUNT(*) FROM t6
ORDER BY URUT;
