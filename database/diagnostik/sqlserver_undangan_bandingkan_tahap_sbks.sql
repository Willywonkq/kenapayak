/* ============================================================
 * CORONG YANG SAMA, SISI SQL SERVER (SRIS_PUSAT)
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop. Tidak ada satu pun subquery di dalam agregat,
 * jadi aman dari Error 130: semua tanda "ada pembeli" dan "ada
 * nasabah" dihitung lebih dulu lewat OUTER APPLY, baru
 * dijumlahkan.
 *
 * Filter sama persis dengan sisi PostgreSQL:
 *   UNIT         : SBKS
 *   BLOK         : *  s/d  *   (tidak menyaring apa pun)
 *   PERIODE      : 01-07-2023 s/d 21-09-2026
 *   JENIS REPORT : Undangan PPJB (PPSRS), yaitu JENIS_SURAT = 1
 *   SEKTOR       : Semua
 *
 * Kalau ternyata SBKS tersimpan di SRIS_SERPONG dan bukan di
 * SRIS_PUSAT, ganti seluruh [SRIS_PUSAT] menjadi [SRIS_SERPONG]
 * dan jalankan ulang. QUERY 0 di bawah ini menjawabnya lebih
 * dulu supaya tidak perlu ditebak.
 *
 * Pasangannya: undangan_bandingkan_tahap_sbks.sql
 * Sandingkan tahap demi tahap. Tahap yang angkanya mulai
 * berbeda jauh, di situlah persoalannya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 0
 * SBKS ada di database yang mana.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT' AS DATABASENYA, COUNT(*) AS STOK_SBKS
FROM [SRIS_PUSAT].[dbo].[STOK] WITH (NOLOCK)
WHERE UPPER(RTRIM(LTRIM(KD_PERUSAHAAN))) = 'SBKS';
GO


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG UTAMA.
 *
 * Tahap 6 dan 7 dipisah dengan maksud yang sama seperti di sisi
 * PostgreSQL: tahap 6 hanya menuntut ada pembeli aktif, tahap 7
 * menuntut pembeli itu ketemu orangnya di NASABAH.
 *
 * Tahap 9 memakai sambungan sungguhan sehingga satu rumah dengan
 * dua pembeli terhitung dua baris, persis seperti yang tampil di
 * layar desktop.
 * ------------------------------------------------------------ */
WITH t1 AS (
    SELECT U.PPJB_ID, U.URUT, U.NO_SURAT, U.TGL_SURAT, U.JENIS_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
),
t2 AS (
    SELECT * FROM t1
    WHERE RTRIM(LTRIM(ISNULL(CAST(JENIS_SURAT AS VARCHAR(10)), ''))) = '1'
),
t3 AS (
    SELECT * FROM t2
    WHERE TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
t4 AS (
    SELECT t3.*, PPJB.STOK_ID
    FROM t3
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON RTRIM(PPJB.PPJB_ID) = RTRIM(t3.PPJB_ID)
    WHERE UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) = 'A'
      AND PPJB.PARENT_ID IS NULL
),
t5 AS (
    SELECT t4.*, STOK.BLOK, STOK.NOMOR
    FROM t4
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON RTRIM(STOK.STOK_ID) = RTRIM(t4.STOK_ID)
    WHERE UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, '')))) = 'SBKS'
      AND UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) = 'A'
),
t6 AS (
    SELECT * FROM t5
    WHERE EXISTS (
        SELECT 1
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(t5.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    )
),
t7 AS (
    SELECT * FROM t6
    WHERE EXISTS (
        SELECT 1
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
            ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(t6.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    )
),
t8 AS (
    SELECT * FROM t7
    WHERE BLOK IS NOT NULL AND NOMOR IS NOT NULL
),
t9 AS (
    SELECT t8.*, NS.NAMA
    FROM t8
    INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        ON RTRIM(PB.PPJB_ID) = RTRIM(t8.PPJB_ID)
       AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
)
            SELECT 1 AS URUT, 'Tahap 1  seluruh UNDANGAN_PPJB'          AS TAHAP, COUNT(*) AS BARIS FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + JENIS_SURAT = 1',                       COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + rentang TGL_SURAT',                     COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + PPJB aktif dan bukan turunan',          COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + stok SBKS yang aktif',                  COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + ada pembeli aktif',                     COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + pembelinya ketemu di NASABAH',          COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  + BLOK dan NOMOR terisi',                 COUNT(*) FROM t8
UNION ALL   SELECT 9,         'Tahap 9  digandakan pembeli = YANG TAMPIL',        COUNT(*) FROM t9
ORDER BY URUT;
GO


/* ------------------------------------------------------------
 * QUERY 2
 * RINCIAN PER SEKTOR pada hasil akhir.
 *
 * Inilah daftar yang disandingkan langsung dengan QUERY 2 sisi
 * PostgreSQL. Sektor yang ada di sini tetapi tidak ada di sana
 * adalah sektor yang hilang di web.
 * ------------------------------------------------------------ */
WITH SEKTOR_REF AS (
    SELECT UPPER(RTRIM(LTRIM(ISNULL(KD_SEKTOR, '')))) AS KODE,
           MIN(RTRIM(ISNULL(DESKRIPSI, ''))) AS DESKRIPSI
    FROM [SRIS_PUSAT].[dbo].[SEKTOR] WITH (NOLOCK)
    GROUP BY UPPER(RTRIM(LTRIM(ISNULL(KD_SEKTOR, ''))))
),
SURAT AS (
    SELECT U.PPJB_ID
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
    WHERE RTRIM(LTRIM(ISNULL(CAST(U.JENIS_SURAT AS VARCHAR(10)), ''))) = '1'
      AND U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
)
SELECT
    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))) )          AS KD_SEKTOR,
    ISNULL(SEKTOR_REF.DESKRIPSI, '(TIDAK ADA DI MASTER)')     AS NAMA_SEKTOR,
    COUNT(*)                                                  AS BARIS
FROM SURAT
INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
    ON RTRIM(PPJB.PPJB_ID) = RTRIM(SURAT.PPJB_ID)
   AND UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) = 'A'
   AND PPJB.PARENT_ID IS NULL
INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
    ON RTRIM(STOK.STOK_ID) = RTRIM(PPJB.STOK_ID)
   AND UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, '')))) = 'SBKS'
   AND UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) = 'A'
INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
    ON RTRIM(PB.PPJB_ID) = RTRIM(SURAT.PPJB_ID)
   AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
    ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
LEFT JOIN SEKTOR_REF
    ON SEKTOR_REF.KODE = UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))))
WHERE STOK.BLOK IS NOT NULL AND STOK.NOMOR IS NOT NULL
GROUP BY
    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, '')))),
    ISNULL(SEKTOR_REF.DESKRIPSI, '(TIDAK ADA DI MASTER)')
ORDER BY 2, 1;
GO


/* ------------------------------------------------------------
 * QUERY 3
 * SEKTOR YANG HILANG, ditelusuri tahap demi tahap.
 *
 * Tanda ADA_PEMBELI dan ADA_NASABAH dihitung lebih dulu lewat
 * OUTER APPLY, bukan di dalam SUM, supaya tidak kena Error 130.
 * ------------------------------------------------------------ */
WITH SEKTOR_REF AS (
    SELECT UPPER(RTRIM(LTRIM(ISNULL(KD_SEKTOR, '')))) AS KODE,
           MIN(RTRIM(ISNULL(DESKRIPSI, ''))) AS DESKRIPSI
    FROM [SRIS_PUSAT].[dbo].[SEKTOR] WITH (NOLOCK)
    GROUP BY UPPER(RTRIM(LTRIM(ISNULL(KD_SEKTOR, ''))))
),
SURAT AS (
    SELECT U.PPJB_ID
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
    WHERE RTRIM(LTRIM(ISNULL(CAST(U.JENIS_SURAT AS VARCHAR(10)), ''))) = '1'
      AND U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
GABUNG AS (
    SELECT
        UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, '')))) AS KD_SEKTOR,
        UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) AS STOK_AKTIF,
        UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) AS PPJB_AKTIF,
        PPJB.PARENT_ID,
        STOK.BLOK,
        STOK.NOMOR,
        CASE WHEN P1.ADA IS NULL THEN 0 ELSE 1 END AS ADA_PEMBELI,
        CASE WHEN P2.ADA IS NULL THEN 0 ELSE 1 END AS ADA_NASABAH
    FROM SURAT
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON RTRIM(PPJB.PPJB_ID) = RTRIM(SURAT.PPJB_ID)
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
    KD_SEKTOR,
    ISNULL(SEKTOR_REF.DESKRIPSI, '(TIDAK ADA DI MASTER)') AS NAMA_SEKTOR,
    COUNT(*) AS PUNYA_SURAT,
    SUM(CASE WHEN PPJB_AKTIF = 'A' THEN 1 ELSE 0 END) AS PPJB_AKTIF,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PARENT_ID IS NULL
             THEN 1 ELSE 0 END) AS BUKAN_TURUNAN,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PARENT_ID IS NULL
              AND STOK_AKTIF = 'A' THEN 1 ELSE 0 END) AS STOK_AKTIF,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PARENT_ID IS NULL
              AND STOK_AKTIF = 'A' AND ADA_PEMBELI = 1
             THEN 1 ELSE 0 END) AS ADA_PEMBELI,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PARENT_ID IS NULL
              AND STOK_AKTIF = 'A' AND ADA_NASABAH = 1
             THEN 1 ELSE 0 END) AS ADA_NASABAH,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PARENT_ID IS NULL
              AND STOK_AKTIF = 'A' AND ADA_NASABAH = 1
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN 1 ELSE 0 END) AS LOLOS_SEMUA
FROM GABUNG
LEFT JOIN SEKTOR_REF ON SEKTOR_REF.KODE = GABUNG.KD_SEKTOR
GROUP BY KD_SEKTOR, ISNULL(SEKTOR_REF.DESKRIPSI, '(TIDAK ADA DI MASTER)')
ORDER BY 2, 1;
GO


/* ------------------------------------------------------------
 * QUERY 4
 * KEMUTAKHIRAN UNDANGAN_PPJB.
 *
 * Disandingkan dengan QUERY 4 sisi PostgreSQL. Kalau jumlah
 * barisnya sama dan tanggal terakhirnya sama, tabel ini
 * termigrasi utuh dan sebab selisihnya bukan migrasi tabel ini.
 * ------------------------------------------------------------ */
SELECT
    RTRIM(LTRIM(ISNULL(CAST(JENIS_SURAT AS VARCHAR(10)), '(kosong)'))) AS JENIS_SURAT,
    COUNT(*)       AS BARIS,
    MIN(TGL_SURAT) AS TGL_PALING_AWAL,
    MAX(TGL_SURAT) AS TGL_PALING_AKHIR
FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] WITH (NOLOCK)
GROUP BY RTRIM(LTRIM(ISNULL(CAST(JENIS_SURAT AS VARCHAR(10)), '(kosong)')))
ORDER BY 1;
GO


/* ------------------------------------------------------------
 * QUERY 5
 * SEBARAN PER TAHUN untuk jenis 1.
 *
 * Disandingkan dengan QUERY 5 sisi PostgreSQL. Kalau di sini
 * tahun 2025 dan 2026 penuh sedangkan di sana kosong, berarti
 * yang kurang adalah datanya, bukan modelnya.
 * ------------------------------------------------------------ */
WITH SURAT AS (
    SELECT U.PPJB_ID, U.TGL_SURAT
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
    WHERE RTRIM(LTRIM(ISNULL(CAST(U.JENIS_SURAT AS VARCHAR(10)), ''))) = '1'
      AND U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
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
