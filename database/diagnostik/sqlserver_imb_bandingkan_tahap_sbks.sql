/* ============================================================
 * CORONG YANG SAMA, SISI SQL SERVER (SRIS_PUSAT)
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Filter sama: SBKS, blok A s/d ZZ, TGL INPUT 01-01-2020 s/d
 * 31-12-2023. Pasangannya imb_bandingkan_tahap_sbks.sql.
 *
 * Jalankan keduanya lalu sandingkan tahap demi tahap. Tahap yang
 * angkanya mulai berbeda jauh itulah letak persoalannya.
 * ============================================================ */

WITH t2 AS (
    SELECT IMB.SERTIPIKAT_ID
    FROM [SRIS_PUSAT].[dbo].[IMB] AS IMB WITH (NOLOCK)
    WHERE IMB.TGL_INPUT >= CONVERT(DATETIME, '20200101', 112)
      AND IMB.TGL_INPUT <  CONVERT(DATETIME, '20240101', 112)
),
t3 AS (
    SELECT SERTIPIKAT.STOK_ID
    FROM t2
    INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
        ON SERTIPIKAT.SERTIPIKAT_ID = t2.SERTIPIKAT_ID
    WHERE SERTIPIKAT.STOK_ID IS NOT NULL
),
t4 AS (
    SELECT STOK.STOK_ID, STOK.BLOK, STOK.NOMOR
    FROM t3
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = t3.STOK_ID
    WHERE UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = 'SBKS'
      AND STOK.FLAG_AKTIF = 'A'
      AND STOK.BLOK IS NOT NULL
      AND STOK.NOMOR IS NOT NULL
),
t5 AS (
    SELECT t4.BLOK, t4.NOMOR, PPJB.PPJB_ID
    FROM t4
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON PPJB.STOK_ID = t4.STOK_ID
       AND PPJB.FLAG_AKTIF = 'A'
       AND PPJB.PARENT_ID IS NULL
),
t6 AS (
    SELECT t5.BLOK, t5.NOMOR, PEMBELI_PPJB.NASABAH_ID
    FROM t5
    INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PEMBELI_PPJB WITH (NOLOCK)
        ON PEMBELI_PPJB.PPJB_ID = t5.PPJB_ID
       AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
),
t7 AS (
    SELECT t6.BLOK, t6.NOMOR
    FROM t6
    INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NASABAH WITH (NOLOCK)
        ON NASABAH.NASABAH_ID = t6.NASABAH_ID
),
t8 AS (
    SELECT * FROM t7
    WHERE (
            ((RTRIM(BLOK) + '/' + NOMOR) >= 'A'
             AND (RTRIM(BLOK) + '/' + NOMOR) <= 'ZZ')
            OR (BLOK >= 'A' AND BLOK <= 'ZZ')
          )
)
SELECT 2 AS URUT, 'Tahap 2  IMB dalam rentang tanggal'      AS TAHAP, COUNT(*) AS BARIS FROM t2
UNION ALL SELECT 3, 'Tahap 3  + ketemu sertipikatnya',         COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + stok SBKS aktif',              COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + PPJB aktif bukan turunan',     COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + pembeli aktif',                COUNT(*) FROM t6
UNION ALL SELECT 7, 'Tahap 7  + nasabahnya ketemu',            COUNT(*) FROM t7
UNION ALL SELECT 8, 'Tahap 8  + saringan blok = YANG TAMPIL',  COUNT(*) FROM t8
ORDER BY URUT;
