/* ============================================================
 * CORONG DAFTAR PBB, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT       : SBKS
 *   BLOK       : A s/d Z
 *   TAHUN PBB  : 2000 s/d 2026
 *   TGL INPUT  : 01-01-2020 s/d 31-12-2023
 *   SEKTOR     : Semua
 *   tanpa centang Nama & Alamat WP, tanpa centang Belum Ada PBB
 *
 * Yang sudah diketahui:
 *   web menampilkan        228
 *   desktop menampilkan  1.178
 *
 * Susunan join pada model sudah diperiksa dan setia pada desktop:
 * hanya sertipikat dan stok yang INNER JOIN, sisanya LEFT JOIN,
 * jadi PPJB dan pembeli TIDAK bisa membuang baris pada laporan
 * PBB. Berarti kehilangannya ada di salah satu dari tiga penyaring
 * berikut, dan corong ini memisahkannya satu per satu.
 *
 * Pasangannya: sqlserver_pbb_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG, memakai sambungan yang sama persis dengan model.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1
),
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
),
t1 AS (SELECT * FROM public.sr_pbb),
t2 AS (
    SELECT * FROM t1
    WHERE tahun_pbb >= 2000 AND tahun_pbb <= 2026
),
t3 AS (
    SELECT * FROM t2
    WHERE tgl_input >= CAST('2020-01-01' AS TIMESTAMP)
      AND tgl_input <  CAST('2024-01-01' AS TIMESTAMP)
),
t4 AS (
    SELECT t3.*, sertipikat.stok_id
    FROM t3
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE WHEN BTRIM(CAST(t3.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(t3.sertipikat_id AS TEXT))
                ELSE awalan_unit.awalan || BTRIM(CAST(t3.sertipikat_id AS TEXT)) END
),
t5 AS (
    SELECT t4.*, stok.blok, stok.nomor
    FROM t4
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(t4.stok_id AS TEXT))
),
t6 AS (
    SELECT * FROM t5
    WHERE (
            (BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || BTRIM(COALESCE(CAST(nomor AS TEXT), '')) >= 'A'
             AND BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || BTRIM(COALESCE(CAST(nomor AS TEXT), '')) <= 'Z')
            OR (blok >= 'A' AND blok <= 'Z')
          )
)
SELECT 1 AS urut, 'Tahap 1  seluruh sr_pbb'                  AS tahap, COUNT(*) AS baris FROM t1
UNION ALL SELECT 2, 'Tahap 2  + rentang TAHUN PBB',            COUNT(*) FROM t2
UNION ALL SELECT 3, 'Tahap 3  + rentang TGL INPUT',            COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + ketemu sertipikatnya',         COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + stok SBKS',                    COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + saringan blok = YANG TAMPIL',  COUNT(*) FROM t6
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * Sebaran tahun TGL_INPUT dan TAHUN_PBB pada baris sr_pbb yang
 * menempel ke stok SBKS, tanpa saringan tanggal sama sekali.
 *
 * Gunanya melihat apakah barisnya memang tidak ada pada rentang
 * yang diminta, atau ada tetapi tersaring oleh sesuatu.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1
),
pbb_sbks AS (
    SELECT pbb.tahun_pbb, pbb.tgl_input
    FROM public.sr_pbb AS pbb
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE WHEN BTRIM(CAST(pbb.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(pbb.sertipikat_id AS TEXT))
                ELSE awalan_unit.awalan || BTRIM(CAST(pbb.sertipikat_id AS TEXT)) END
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT))
         = BTRIM(CAST(sertipikat.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
)
SELECT
    COALESCE(CAST(EXTRACT(YEAR FROM tgl_input) AS TEXT), '(TGL INPUT KOSONG)')
        AS tahun_tgl_input,
    COUNT(*) AS baris,
    MIN(tahun_pbb) AS tahun_pbb_terkecil,
    MAX(tahun_pbb) AS tahun_pbb_terbesar
FROM pbb_sbks
GROUP BY 1
ORDER BY 1;
