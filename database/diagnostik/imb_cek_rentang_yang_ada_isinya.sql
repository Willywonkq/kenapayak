/* ============================================================
 * RENTANG TANGGAL MANA YANG SEBENARNYA BERISI DATA IMB
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Corong sebelumnya sudah menunjukkan laporan kosong bukan karena
 * modelnya, melainkan karena datanya:
 *
 *   seluruh sr_imb                     21.998
 *   setelah rentang TGL INPUT layar       649   <-- menyusut di sini
 *   setelah stok SBKS yang aktif            0   <-- habis di sini
 *
 * sr_imb berhenti pada akhir 2023, dan 8.154 barisnya bahkan tidak
 * punya TGL_INPUT sama sekali sehingga tidak akan pernah muncul
 * pada rentang tanggal mana pun.
 *
 * Berkas ini menjawab dua hal:
 *   1. rentang tanggal apa yang menghasilkan baris untuk SBKS
 *   2. 649 baris tadi sebenarnya milik unit mana
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Sebaran tahun TGL_INPUT khusus baris yang benar-benar menempel
 * pada stok SBKS yang aktif.
 *
 * Inilah daftar rentang yang akan menghasilkan laporan berisi.
 * Baris (TANGGAL KOSONG) tidak akan pernah muncul di laporan,
 * baik di web maupun di desktop, karena penyaringnya memang
 * memakai TGL_INPUT.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1
    ORDER BY COUNT(*) DESC
    LIMIT 1
),
imb_sbks AS (
    SELECT imb.tgl_input, stok.flag_aktif
    FROM public.sr_imb AS imb
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(imb.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(imb.sertipikat_id AS TEXT))
               ELSE awalan_unit.awalan || BTRIM(CAST(imb.sertipikat_id AS TEXT))
           END
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT))
         = BTRIM(CAST(sertipikat.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
)
SELECT
    COALESCE(CAST(EXTRACT(YEAR FROM tgl_input) AS TEXT), '(TANGGAL KOSONG)')
        AS tahun_tgl_input,
    COUNT(*) AS baris,
    SUM(CASE WHEN UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'A'
             THEN 1 ELSE 0 END) AS stok_aktif
FROM imb_sbks
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 2
 * 649 baris yang masuk rentang layar itu milik unit mana.
 *
 * Kalau SBKS memang tidak ada di daftar ini, terbukti bukan
 * penyaring unitnya yang keliru, melainkan memang tidak ada baris
 * SBKS yang TGL_INPUT-nya jatuh pada rentang itu.
 * ------------------------------------------------------------ */
WITH peta_unit AS (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) AS kode_unit,
        REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
    GROUP BY 1, 2
),
stok_unit AS (
    SELECT
        BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) AS kode_unit,
        UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) AS flag_aktif
    FROM public.sr_stok
)
SELECT
    stok_unit.kode_unit,
    COUNT(*) AS baris,
    SUM(CASE WHEN stok_unit.flag_aktif = 'A' THEN 1 ELSE 0 END) AS stok_aktif
FROM public.sr_imb AS imb
INNER JOIN peta_unit
    ON TRUE
INNER JOIN public.sr_sertipikat AS sertipikat
    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
     = peta_unit.awalan || BTRIM(CAST(imb.sertipikat_id AS TEXT))
INNER JOIN stok_unit
    ON stok_unit.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
   AND stok_unit.kode_unit = peta_unit.kode_unit
WHERE imb.tgl_input >= CAST('2023-07-01' AS TIMESTAMP)
  AND imb.tgl_input <  CAST('2026-09-19' AS TIMESTAMP)
  AND BTRIM(CAST(imb.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
GROUP BY 1
ORDER BY 2 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * Apakah TGL_INPUT yang kosong itu memang kosong sejak di sumber,
 * atau hilang waktu dipindahkan.
 *
 * Jalankan bagian SQL Server di bawah untuk membandingkannya.
 * Kalau di sumber jumlah yang kosong jauh lebih sedikit, berarti
 * 8.154 baris itu kehilangan tanggalnya saat migrasi, dan itu
 * membuat sepertiga tabel tidak akan pernah bisa ditampilkan.
 * ------------------------------------------------------------ */
SELECT
    'PostgreSQL sr_imb' AS sumber,
    COUNT(*) AS baris,
    COUNT(tgl_input) AS tgl_input_terisi,
    COUNT(*) - COUNT(tgl_input) AS tgl_input_kosong,
    ROUND(100.0 * (COUNT(*) - COUNT(tgl_input)) / NULLIF(COUNT(*), 0), 1)
        AS persen_kosong
FROM public.sr_imb
UNION ALL
SELECT
    'PostgreSQL sr_pbb',
    COUNT(*), COUNT(tgl_input), COUNT(*) - COUNT(tgl_input),
    ROUND(100.0 * (COUNT(*) - COUNT(tgl_input)) / NULLIF(COUNT(*), 0), 1)
FROM public.sr_pbb;
