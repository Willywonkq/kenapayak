/* ============================================================
 * PEMBANDING TGL_INPUT PADA SUMBER
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK) seperti query
 * aplikasi desktop, dan tidak memuat subquery di dalam agregat
 * sehingga aman dari Error 130.
 *
 * Pasangannya: imb_cek_rentang_yang_ada_isinya.sql QUERY 3.
 *
 * Di PostgreSQL, 8.154 dari 21.998 baris sr_imb tidak punya
 * TGL_INPUT, yaitu 37 persen. Baris tanpa TGL_INPUT tidak akan
 * pernah muncul di laporan mana pun karena penyaringnya memakai
 * kolom itu. Yang perlu dipastikan: apakah memang begitu sejak di
 * sumbernya, atau hilang waktu dipindahkan.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Jumlah TGL_INPUT yang kosong pada sumber.
 * ------------------------------------------------------------ */
SELECT 'SRIS_PUSAT IMB' AS SUMBER,
       COUNT(*) AS BARIS,
       COUNT(TGL_INPUT) AS TGL_INPUT_TERISI,
       COUNT(*) - COUNT(TGL_INPUT) AS TGL_INPUT_KOSONG
FROM [SRIS_PUSAT].[dbo].[IMB] WITH (NOLOCK)
UNION ALL
SELECT 'SRIS_PUSAT PBB',
       COUNT(*), COUNT(TGL_INPUT), COUNT(*) - COUNT(TGL_INPUT)
FROM [SRIS_PUSAT].[dbo].[PBB] WITH (NOLOCK);


/* ------------------------------------------------------------
 * QUERY 2
 * Sebaran tahun TGL_INPUT pada IMB di sumber.
 *
 * Dibandingkan dengan QUERY 2 sisi PostgreSQL. Tahun yang ada di
 * sini tetapi kosong di PostgreSQL adalah tahun yang belum ikut
 * termigrasi. Diukur dari tgl_entry, sr_imb berhenti pada
 * 28 Desember 2023.
 * ------------------------------------------------------------ */
SELECT
    CASE WHEN TGL_INPUT IS NULL
         THEN '(KOSONG)'
         ELSE CAST(YEAR(TGL_INPUT) AS VARCHAR(10))
    END AS TAHUN,
    COUNT(*) AS BARIS
FROM [SRIS_PUSAT].[dbo].[IMB] WITH (NOLOCK)
GROUP BY
    CASE WHEN TGL_INPUT IS NULL
         THEN '(KOSONG)'
         ELSE CAST(YEAR(TGL_INPUT) AS VARCHAR(10))
    END
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * Jumlah baris IMB unit SBKS dengan filter tanggal yang sama
 * seperti di layar, sebagai kebenaran pembanding.
 *
 * Inilah jumlah yang seharusnya tampil kalau datanya lengkap.
 * ------------------------------------------------------------ */
SELECT COUNT(*) AS BARIS_SEHARUSNYA
FROM [SRIS_PUSAT].[dbo].[IMB]              AS IMB        WITH (NOLOCK)
INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = IMB.SERTIPIKAT_ID
INNER JOIN [SRIS_PUSAT].[dbo].[STOK]       AS STOK       WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
   AND STOK.BLOK = SERTIPIKAT.BLOK
   AND STOK.NOMOR = SERTIPIKAT.NOMOR
WHERE UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = 'SBKS'
  AND STOK.FLAG_AKTIF = 'A'
  AND IMB.TGL_INPUT >= CONVERT(DATETIME, '20230701', 112)
  AND IMB.TGL_INPUT <  CONVERT(DATETIME, '20260919', 112);
