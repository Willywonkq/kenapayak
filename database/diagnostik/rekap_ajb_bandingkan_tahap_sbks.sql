/* ============================================================
 * CORONG REKAP PPAT / AKTA JUAL BELI, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL, dan tidak ada CREATE INDEX. Dijalankan di
 * POSTGRESQL.
 *
 * Pasangannya: sqlserver_rekap_ajb_bandingkan_sbks.sql
 * Jalankan keduanya, lalu sandingkan tahap demi tahap.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN
 *
 *   web      546 baris
 *   desktop  belum diketahui
 *
 * Saringan: unit SBKS, blok A sampai Z (dibaca A sampai ZZ),
 * semua lokasi, semua sektor, Tanggal AJB 01-07-2023 sampai
 * 24-09-2026, kotak Belum Ttd Akta TIDAK dicentang.
 *
 * ------------------------------------------------------------
 * YANG SUDAH DIUKUR DI WEB
 *
 * Angka di bawah ini terbaca dari EXPLAIN ANALYZE, jadi bukan
 * taksiran melainkan jumlah baris yang benar benar mengalir:
 *
 *   17.407  sr_akta seluruhnya
 *      757  akta dengan tgl_akta dalam rentang, SEMUA UNIT
 *    9.807  stok SBKS, aktif, blok dan nomor ada
 *   49.527  ppjb aktif dan parent_id kosong, SEMUA UNIT
 *    9.560  ppjb yang stoknya milik SBKS
 *      546  yang punya akta dalam rentang
 *      546  sesudah disambung pembeli aktif   <- yang tampil
 *
 * ------------------------------------------------------------
 * APA YANG DICARI
 *
 * Kalau seluruh selisihnya jatuh di TAHAP 2, berarti murni
 * kekurangan baris sr_akta dan bukan cacat kode. Itu dugaan
 * yang paling kuat, sebab sr_akta sudah tercatat berhenti
 * Februari 2024 sementara rentang yang diminta sampai 2026.
 *
 * Kalau selisihnya muncul di TAHAP 3 atau TAHAP 5, berarti
 * saringan unit atau blok yang berbeda, dan itu cacat kode
 * yang harus diperbaiki.
 *
 * Kalau nisbah TAHAP 6 ke TAHAP 7 berbeda di kedua sisi,
 * berarti penggandaan oleh pembeli berbeda perlakuannya.
 *
 * Ganti 'SBKS' kalau unit yang dibuka lain, dan kd_perusahaan
 * kalau nama kolomnya kd_unit atau kd_pt.
 * ============================================================ */

WITH akta_rentang AS (
    SELECT
        BTRIM(CAST(akta.ppjb_id AS TEXT)) AS kunci_ppjb,
        CASE
            WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(akta.tgl_akta AS TIMESTAMP)
        END AS tgl_akta_valid
    FROM public.sr_akta AS akta
    WHERE CASE
              WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(akta.tgl_akta AS TIMESTAMP)
          END >= CAST('2023-07-01' AS DATE)
      AND CASE
              WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(akta.tgl_akta AS TIMESTAMP)
          END <  CAST('2026-09-25' AS DATE)
),
stok_unit AS (
    SELECT BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND (
            (
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ'
            )
            OR
            (
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ'
            )
          )
),
ppjb_aktif AS (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_ppjb AS ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
ppjb_unit AS (
    SELECT p.kunci_ppjb, p.kunci_stok
    FROM ppjb_aktif AS p
    INNER JOIN stok_unit AS s ON s.kunci_stok = p.kunci_stok
),
ppjb_berakta AS (
    SELECT p.kunci_ppjb, a.tgl_akta_valid
    FROM ppjb_unit AS p
    INNER JOIN akta_rentang AS a ON a.kunci_ppjb = p.kunci_ppjb
),
baris_laporan AS (
    SELECT b.kunci_ppjb
    FROM ppjb_berakta AS b
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = b.kunci_ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
)
SELECT 1 AS urut, 'TAHAP 1  sr_akta seluruhnya'              AS tahap,
       (SELECT COUNT(*) FROM public.sr_akta)                 AS baris,
       NULL::bigint                                          AS ppjb_berbeda
UNION ALL
SELECT 2, 'TAHAP 2  akta dalam rentang, semua unit',
       (SELECT COUNT(*) FROM akta_rentang),
       (SELECT COUNT(DISTINCT kunci_ppjb) FROM akta_rentang)
UNION ALL
SELECT 3, 'TAHAP 3  stok unit, aktif, blok dan nomor ada',
       (SELECT COUNT(*) FROM stok_unit), NULL
UNION ALL
SELECT 4, 'TAHAP 4  ppjb aktif dan parent kosong, semua unit',
       (SELECT COUNT(*) FROM ppjb_aktif), NULL
UNION ALL
SELECT 5, 'TAHAP 5  ppjb yang stoknya milik unit',
       (SELECT COUNT(*) FROM ppjb_unit),
       (SELECT COUNT(DISTINCT kunci_ppjb) FROM ppjb_unit)
UNION ALL
SELECT 6, 'TAHAP 6  yang punya akta dalam rentang',
       (SELECT COUNT(*) FROM ppjb_berakta),
       (SELECT COUNT(DISTINCT kunci_ppjb) FROM ppjb_berakta)
UNION ALL
SELECT 7, 'TAHAP 7  sesudah pembeli aktif  <- yang tampil',
       (SELECT COUNT(*) FROM baris_laporan),
       (SELECT COUNT(DISTINCT kunci_ppjb) FROM baris_laporan)
ORDER BY urut;


/* ------------------------------------------------------------
 * SEBARAN TAHUN sr_akta menurut tgl_akta
 *
 * Kolom tanggalnya tgl_akta, BUKAN tgl_input. Daftar Pengajuan
 * Balik Nama memakai tgl_input, jadi angkanya memang tidak
 * akan sama dengan catatan terdahulu yang menyebut 638.
 * ------------------------------------------------------------ */
SELECT
    EXTRACT(YEAR FROM t.tgl_akta_valid)::int AS tahun,
    COUNT(*)                                 AS baris
FROM (
    SELECT
        CASE
            WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(akta.tgl_akta AS TIMESTAMP)
        END AS tgl_akta_valid
    FROM public.sr_akta AS akta
) AS t
WHERE t.tgl_akta_valid IS NOT NULL
GROUP BY 1
ORDER BY 1 DESC;
