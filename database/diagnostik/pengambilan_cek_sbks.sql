/* ============================================================
 * CORONG DAFTAR PENGAMBILAN SURAT, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru sama seperti di layar: SBKS, blok A s/d ZZ,
 * semua sektor, Tgl. Terima IMB 01-07-2023 s/d 18-09-2026, lima
 * kotak tanggal lainnya kosong.
 *
 * Karena hanya SATU rentang yang diisi, rentang itu dicari ke
 * SELURUH enam kolom tanggal dokumen, mengikuti aturan desktop.
 *
 * Pasangannya: sqlserver_pengambilan_cek_sbks.sql
 * Jalankan keduanya lalu sandingkan tahap demi tahap.
 * ============================================================ */

WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1
),
t1 AS (SELECT * FROM public.sr_pengambilan),
t2 AS (
    SELECT * FROM t1
    WHERE (tgl_input_imb  >= TIMESTAMP '2023-07-01' AND tgl_input_imb  < TIMESTAMP '2026-09-19')
       OR (tgl_input_ser  >= TIMESTAMP '2023-07-01' AND tgl_input_ser  < TIMESTAMP '2026-09-19')
       OR (tgl_input_akta >= TIMESTAMP '2023-07-01' AND tgl_input_akta < TIMESTAMP '2026-09-19')
       OR (tgl_input_shm  >= TIMESTAMP '2023-07-01' AND tgl_input_shm  < TIMESTAMP '2026-09-19')
       OR (tgl_input_ph   >= TIMESTAMP '2023-07-01' AND tgl_input_ph   < TIMESTAMP '2026-09-19')
       OR (tgl_input_ppjb >= TIMESTAMP '2023-07-01' AND tgl_input_ppjb < TIMESTAMP '2026-09-19')
),
t3 AS (
    SELECT t2.*, sertipikat.stok_id
    FROM t2
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE WHEN BTRIM(CAST(t2.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(t2.sertipikat_id AS TEXT))
                ELSE awalan_unit.awalan || BTRIM(CAST(t2.sertipikat_id AS TEXT)) END
    WHERE sertipikat.stok_id IS NOT NULL
),
t4 AS (
    SELECT t3.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok,
           stok.blok, stok.nomor
    FROM t3
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(t3.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
),
t5 AS (
    SELECT t4.*, BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM t4
    INNER JOIN public.sr_ppjb AS ppjb
        ON BTRIM(CAST(ppjb.stok_id AS TEXT)) = t4.kunci_stok
       AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
       AND ppjb.parent_id IS NULL
),
t6 AS (
    SELECT t5.*, pembeli.nasabah_id
    FROM t5
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t5.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
),
t7 AS (
    SELECT t6.*
    FROM t6
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(t6.nasabah_id AS TEXT))
),
t8 AS (
    SELECT * FROM t7
    WHERE (
            (BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || BTRIM(COALESCE(CAST(nomor AS TEXT), '')) >= 'A'
             AND BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || BTRIM(COALESCE(CAST(nomor AS TEXT), '')) <= 'ZZ')
            OR (blok >= 'A' AND blok <= 'ZZ')
          )
)
SELECT 1 AS urut, 'Tahap 1  seluruh sr_pengambilan'          AS tahap, COUNT(*) AS baris FROM t1
UNION ALL SELECT 2, 'Tahap 2  + rentang tanggal (semua kolom)', COUNT(*) FROM t2
UNION ALL SELECT 3, 'Tahap 3  + ketemu sertipikatnya',          COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + stok SBKS aktif',               COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + PPJB aktif bukan turunan',      COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + pembeli aktif',                 COUNT(*) FROM t6
UNION ALL SELECT 7, 'Tahap 7  + nasabahnya ketemu',             COUNT(*) FROM t7
UNION ALL SELECT 8, 'Tahap 8  + saringan blok = YANG TAMPIL',   COUNT(*) FROM t8
ORDER BY urut;
