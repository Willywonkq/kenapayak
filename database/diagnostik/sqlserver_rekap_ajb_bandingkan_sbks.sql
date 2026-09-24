/* ============================================================
 * SISI DESKTOP: REKAP PPAT / AKTA JUAL BELI, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: rekap_ajb_bandingkan_tahap_sbks.sql
 *
 * ------------------------------------------------------------
 * ANGKA SISI WEB, UNTUK DISANDINGKAN
 *
 * Terbaca dari EXPLAIN ANALYZE, jadi jumlah baris yang benar
 * benar mengalir, bukan taksiran:
 *
 *   TAHAP 1   17.407   sr_akta seluruhnya
 *   TAHAP 2      757   akta dalam rentang, SEMUA UNIT
 *   TAHAP 3    9.807   stok SBKS, aktif, blok dan nomor ada
 *   TAHAP 4   49.527   ppjb aktif dan parent kosong, SEMUA UNIT
 *   TAHAP 5    9.560   ppjb yang stoknya milik SBKS
 *   TAHAP 6      546   yang punya akta dalam rentang
 *   TAHAP 7      546   sesudah pembeli aktif   <- yang tampil
 *
 * Saringan: unit SBKS, blok A sampai Z (dibaca A sampai ZZ),
 * semua lokasi, semua sektor, Tanggal AJB 01-07-2023 sampai
 * 24-09-2026, kotak Belum Ttd Akta TIDAK dicentang.
 *
 * Sebelum menjalankan, buka dulu Rekap AJB di desktop dengan
 * saringan yang sama persis dan catat jumlah barisnya.
 * TAHAP 7 di bawah seharusnya sama dengan angka itu; kalau
 * tidak, berarti corong ini belum meniru desktop dengan tepat
 * dan itu sendiri sudah temuan yang berguna.
 *
 * Ganti 'SBKS' kalau unit yang dibuka lain.
 * ============================================================ */

/* ------------------------------------------------------------
 * QUERY 1
 * CORONG TUJUH TAHAP. Sandingkan baris demi baris.
 * ------------------------------------------------------------ */
WITH akta_rentang AS (
    SELECT LTRIM(RTRIM(CAST(AKTA.PPJB_ID AS VARCHAR(50)))) AS kunci_ppjb
    FROM AKTA WITH (NOLOCK)
    WHERE AKTA.TGL_AKTA >= '2023-07-01'
      AND AKTA.TGL_AKTA <  '2026-09-25'
),
stok_unit AS (
    SELECT LTRIM(RTRIM(CAST(STOK.STOK_ID AS VARCHAR(50)))) AS kunci_stok
    FROM STOK WITH (NOLOCK)
    WHERE STOK.FLAG_AKTIF = 'A'
      AND STOK.BLOK  IS NOT NULL
      AND STOK.NOMOR IS NOT NULL
      AND STOK.KD_PERUSAHAAN = 'SBKS'
      AND (
            (
                UPPER(LTRIM(RTRIM(CAST(STOK.BLOK  AS VARCHAR(50))))) + '/'
                + UPPER(LTRIM(RTRIM(CAST(STOK.NOMOR AS VARCHAR(50)))))
                BETWEEN 'A' AND 'ZZ'
            )
            OR UPPER(LTRIM(RTRIM(CAST(STOK.BLOK AS VARCHAR(50)))))
                 BETWEEN 'A' AND 'ZZ'
          )
),
ppjb_aktif AS (
    SELECT
        LTRIM(RTRIM(CAST(PPJB.PPJB_ID AS VARCHAR(50)))) AS kunci_ppjb,
        LTRIM(RTRIM(CAST(PPJB.STOK_ID AS VARCHAR(50)))) AS kunci_stok
    FROM PPJB WITH (NOLOCK)
    WHERE PPJB.FLAG_AKTIF = 'A'
      AND PPJB.PARENT_ID IS NULL
),
ppjb_unit AS (
    SELECT p.kunci_ppjb, p.kunci_stok
    FROM ppjb_aktif AS p
    INNER JOIN stok_unit AS s ON s.kunci_stok = p.kunci_stok
),
ppjb_berakta AS (
    SELECT p.kunci_ppjb
    FROM ppjb_unit AS p
    INNER JOIN akta_rentang AS a ON a.kunci_ppjb = p.kunci_ppjb
),
baris_laporan AS (
    SELECT b.kunci_ppjb
    FROM ppjb_berakta AS b
    INNER JOIN PEMBELI_PPJB AS pembeli WITH (NOLOCK)
        ON LTRIM(RTRIM(CAST(pembeli.PPJB_ID AS VARCHAR(50)))) = b.kunci_ppjb
    WHERE pembeli.FLAG_AKTIF = 'Y'
)
SELECT 1 AS urut, 'TAHAP 1  AKTA seluruhnya' AS tahap,
       (SELECT COUNT(*) FROM AKTA WITH (NOLOCK)) AS baris,
       CAST(NULL AS INT)                         AS ppjb_berbeda
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
 * QUERY 2
 * SEBARAN TAHUN AKTA menurut TGL_AKTA, seluruh unit.
 *
 * Di PostgreSQL sr_akta berhenti Februari 2024. Kalau di sini
 * 2024, 2025, dan 2026 berisi ribuan baris sementara di sana
 * kosong, maka seluruh selisih TAHAP 2 sudah terjelaskan dan
 * ini persoalan migrasi, bukan kode.
 *
 * Perhatikan: kolomnya TGL_AKTA, bukan TGL_INPUT. Catatan
 * terdahulu yang menyebut 638 memakai TGL_INPUT, jadi memang
 * tidak sebanding.
 * ------------------------------------------------------------ */
SELECT
    YEAR(TGL_AKTA) AS tahun,
    COUNT(*)       AS baris
FROM AKTA WITH (NOLOCK)
WHERE TGL_AKTA IS NOT NULL
GROUP BY YEAR(TGL_AKTA)
ORDER BY 1 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * SEBARAN TAHUN khusus akta milik stok SBKS.
 *
 * Kalau TAHAP 2 seluruh unit sudah jauh berbeda, query ini
 * menunjukkan berapa banyak yang seharusnya jatuh ke SBKS,
 * sehingga bisa diperkirakan berapa baris yang hilang dari
 * laporan ini saja.
 * ------------------------------------------------------------ */
SELECT
    YEAR(AKTA.TGL_AKTA) AS tahun,
    COUNT(*)            AS baris
FROM AKTA WITH (NOLOCK)
INNER JOIN PPJB WITH (NOLOCK)
    ON PPJB.PPJB_ID = AKTA.PPJB_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = PPJB.STOK_ID
WHERE AKTA.TGL_AKTA IS NOT NULL
  AND STOK.KD_PERUSAHAAN = 'SBKS'
GROUP BY YEAR(AKTA.TGL_AKTA)
ORDER BY 1 DESC;
