/* ============================================================
 * PEMBANDING KEMUTAKHIRAN PENGAMBILAN PADA SUMBER
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Pasangannya: pengambilan_cek_kemutakhiran.sql
 *
 * Bandingkan sebaran tahunnya. Tahun yang ada di sini tetapi
 * kosong atau jauh lebih sedikit di PostgreSQL adalah tahun yang
 * belum ikut termigrasi.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Tanggal paling akhir pada tiap kolom tanggal PENGAMBILAN.
 * ------------------------------------------------------------ */
SELECT 'TGL_INPUT_IMB' AS KOLOM, MIN(TGL_INPUT_IMB) AS PALING_AWAL,
       MAX(TGL_INPUT_IMB) AS PALING_AKHIR, COUNT(TGL_INPUT_IMB) AS TERISI
FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK)
UNION ALL SELECT 'TGL_INPUT_SER',  MIN(TGL_INPUT_SER),  MAX(TGL_INPUT_SER),  COUNT(TGL_INPUT_SER)  FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK)
UNION ALL SELECT 'TGL_INPUT_AKTA', MIN(TGL_INPUT_AKTA), MAX(TGL_INPUT_AKTA), COUNT(TGL_INPUT_AKTA) FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK)
UNION ALL SELECT 'TGL_INPUT_SHM',  MIN(TGL_INPUT_SHM),  MAX(TGL_INPUT_SHM),  COUNT(TGL_INPUT_SHM)  FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK)
UNION ALL SELECT 'TGL_INPUT_PH',   MIN(TGL_INPUT_PH),   MAX(TGL_INPUT_PH),   COUNT(TGL_INPUT_PH)   FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK)
UNION ALL SELECT 'TGL_INPUT_PPJB', MIN(TGL_INPUT_PPJB), MAX(TGL_INPUT_PPJB), COUNT(TGL_INPUT_PPJB) FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] WITH (NOLOCK);


/* ------------------------------------------------------------
 * QUERY 2
 * Sebaran tahun pada sumber, disusun dengan cara yang sama.
 * ------------------------------------------------------------ */
SELECT
    CASE WHEN TGL_TERAWAL IS NULL THEN '(SEMUA KOSONG)'
         ELSE CAST(YEAR(TGL_TERAWAL) AS VARCHAR(10)) END AS TAHUN,
    COUNT(*) AS BARIS
FROM (
    SELECT (
        SELECT MIN(T.TGL) FROM (VALUES
            (PENGAMBILAN.TGL_INPUT_IMB), (PENGAMBILAN.TGL_INPUT_SER),
            (PENGAMBILAN.TGL_INPUT_AKTA), (PENGAMBILAN.TGL_INPUT_SHM),
            (PENGAMBILAN.TGL_INPUT_PH), (PENGAMBILAN.TGL_INPUT_PPJB)
        ) AS T(TGL)
    ) AS TGL_TERAWAL
    FROM [SRIS_PUSAT].[dbo].[PENGAMBILAN] AS PENGAMBILAN WITH (NOLOCK)
) AS D
GROUP BY CASE WHEN TGL_TERAWAL IS NULL THEN '(SEMUA KOSONG)'
              ELSE CAST(YEAR(TGL_TERAWAL) AS VARCHAR(10)) END
ORDER BY 1;
