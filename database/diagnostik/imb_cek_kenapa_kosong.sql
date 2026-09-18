/* ============================================================
 * KENAPA DAFTAR IMB KOSONG DI WEB, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT       : SBKS
 *   BLOK       : A s/d ZZ
 *   TGL INPUT  : 01-07-2023 s/d 18-09-2026
 *   LOKASI     : Semua
 *
 * Jangan menebak tahap mana yang menghabiskan barisnya. QUERY 1
 * menghitung baris di setiap tahap, jadi tahap yang menyusut
 * tajam itulah penyebabnya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG PENYUSUTAN BARIS.
 *
 * Tahap 4a dan 4b sengaja dipisah. Query desktop menyambung stok
 * ke sertipikat memakai STOK_ID sekaligus BLOK dan NOMOR. Kalau
 * penyusutan terjadi di 4b, berarti isi kolom blok atau nomor
 * pada kedua tabel tidak sama bentuknya setelah migrasi.
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
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
),
t1 AS (SELECT * FROM public.sr_imb),
t2 AS (
    SELECT * FROM t1
    WHERE tgl_input >= CAST('2023-07-01' AS TIMESTAMP)
      AND tgl_input <  CAST('2026-09-19' AS TIMESTAMP)
),
t3 AS (
    SELECT t2.*, sertipikat.stok_id, sertipikat.blok AS ser_blok,
           sertipikat.nomor AS ser_nomor
    FROM t2
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(t2.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(t2.sertipikat_id AS TEXT))
               ELSE awalan_unit.awalan || BTRIM(CAST(t2.sertipikat_id AS TEXT))
           END
    WHERE sertipikat.stok_id IS NOT NULL
),
t4a AS (
    SELECT t3.*, stok.kunci_stok, stok.blok, stok.nomor
    FROM t3
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(t3.stok_id AS TEXT))
),
t4b AS (
    SELECT * FROM t4a
    WHERE BTRIM(COALESCE(CAST(blok AS TEXT), ''))
        = BTRIM(COALESCE(CAST(ser_blok AS TEXT), ''))
      AND BTRIM(COALESCE(CAST(nomor AS TEXT), ''))
        = BTRIM(COALESCE(CAST(ser_nomor AS TEXT), ''))
),
t5 AS (
    SELECT t4b.*, ppjb.ppjb_id
    FROM t4b
    INNER JOIN public.sr_ppjb AS ppjb
        ON BTRIM(CAST(ppjb.stok_id AS TEXT)) = t4b.kunci_stok
       AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
       AND ppjb.parent_id IS NULL
),
t6 AS (
    SELECT t5.*, nasabah.nama
    FROM t5
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = BTRIM(CAST(t5.ppjb_id AS TEXT))
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
)
SELECT 1 AS urut, 'Tahap 1   seluruh sr_imb'                      AS tahap, COUNT(*) AS baris FROM t1
UNION ALL SELECT 2, 'Tahap 2   + rentang TGL INPUT',                COUNT(*) FROM t2
UNION ALL SELECT 3, 'Tahap 3   + ketemu sertipikatnya',             COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4a  + stok SBKS aktif (lewat stok_id)',  COUNT(*) FROM t4a
UNION ALL SELECT 5, 'Tahap 4b  + blok dan nomor harus sama',        COUNT(*) FROM t4b
UNION ALL SELECT 6, 'Tahap 5   + PPJB aktif bukan turunan',         COUNT(*) FROM t5
UNION ALL SELECT 7, 'Tahap 6   + pembeli aktif = YANG TAMPIL',      COUNT(*) FROM t6
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * Sebaran tahun TGL_INPUT pada seluruh sr_imb.
 *
 * Kalau tahun 2023 ke atas kosong atau sangat sedikit, berarti
 * sama seperti sr_jaminan: datanya berhenti pada titik tertentu.
 * ------------------------------------------------------------ */
SELECT
    COALESCE(CAST(EXTRACT(YEAR FROM tgl_input) AS TEXT), '(KOSONG)') AS tahun,
    COUNT(*) AS baris
FROM public.sr_imb
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * Tanggal terakhir pada sr_imb dan sr_pbb, dari beberapa kolom.
 * ------------------------------------------------------------ */
SELECT 'sr_imb' AS tabel, 'tgl_input' AS kolom,
       MIN(tgl_input)::text AS paling_awal, MAX(tgl_input)::text AS paling_akhir,
       COUNT(tgl_input) AS terisi
FROM public.sr_imb
UNION ALL
SELECT 'sr_imb', 'tgl_entry', MIN(tgl_entry)::text, MAX(tgl_entry)::text, COUNT(tgl_entry)
FROM public.sr_imb
UNION ALL
SELECT 'sr_pbb', 'tgl_input', MIN(tgl_input)::text, MAX(tgl_input)::text, COUNT(tgl_input)
FROM public.sr_pbb
UNION ALL
SELECT 'sr_pbb', 'tgl_entry', MIN(tgl_entry)::text, MAX(tgl_entry)::text, COUNT(tgl_entry)
FROM public.sr_pbb;


/* ------------------------------------------------------------
 * QUERY 4
 * Apakah bentuk kolom blok dan nomor pada sr_stok dan
 * sr_sertipikat memang sama.
 *
 * Hanya perlu diperhatikan kalau QUERY 1 menunjukkan penyusutan
 * di tahap 4b. Contoh ketidakcocokan yang biasa terjadi: satu
 * tabel menyimpan 022 sedangkan tabel lain menyimpan 22.
 * ------------------------------------------------------------ */
SELECT
    CASE
        WHEN BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))
           = BTRIM(COALESCE(CAST(sertipikat.blok AS TEXT), ''))
         AND BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
           = BTRIM(COALESCE(CAST(sertipikat.nomor AS TEXT), ''))
        THEN 'cocok'
        ELSE 'TIDAK COCOK'
    END AS keadaan,
    COUNT(*) AS baris,
    MIN(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) AS contoh_stok,
    MIN(BTRIM(COALESCE(CAST(sertipikat.blok AS TEXT), '')) || '/'
        || BTRIM(COALESCE(CAST(sertipikat.nomor AS TEXT), ''))) AS contoh_sertipikat
FROM public.sr_sertipikat AS sertipikat
INNER JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(sertipikat.stok_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
GROUP BY 1
ORDER BY 2 DESC;
