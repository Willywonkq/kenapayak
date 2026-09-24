/* ============================================================
 * SISI DESKTOP: DAFTAR REKAP PBB, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: pbb_cari_sebab_kosong.sql
 *
 * ------------------------------------------------------------
 * ANGKA SISI WEB, SUDAH DIUKUR
 *
 *   TAHAP A  15.429   sr_pbb seluruhnya
 *   TAHAP B  15.428   kuncinya ketemu di sr_sertipikat
 *   TAHAP C   8.100   stoknya milik SBKS
 *   TAHAP D       0   + tahun_pbb dan tgl_input  <- yang tampil
 *
 * Desktop menampilkan 826 baris dengan saringan yang sama.
 *
 * Sebab TAHAP D nol: sr_pbb.tgl_input BERHENTI PADA 2023.
 * Sebaran tahunnya 2023 sebanyak 966, lalu 2024, 2025, dan
 * 2026 kosong sama sekali. Dari 966 baris tahun 2023 itu,
 * hanya 312 yang jatuh pada 01-07-2023 ke atas, dan tidak
 * satu pun miliknya SBKS.
 *
 * Saringan: unit SBKS, blok A s/d Z, semua sektor, Tahun PBB
 * 2000 s/d 2026, Tgl Input 01-07-2023 s/d 24-09-2026, kotak
 * Belum Ada PBB tidak dicentang.
 *
 * Ganti 'SBKS' kalau unit yang dibuka lain.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG EMPAT TAHAP. Sandingkan dengan angka web di atas.
 *
 * TAHAP D seharusnya keluar 826, sama dengan yang tampil di
 * desktop. Kalau tidak, corong ini belum meniru desktop dengan
 * tepat, dan itu sendiri sudah temuan.
 * ------------------------------------------------------------ */
WITH pbb_kunci AS (
    SELECT
        LTRIM(RTRIM(CAST(PBB.SERTIPIKAT_ID AS VARCHAR(50)))) AS kunci_sertipikat,
        PBB.TAHUN_PBB                                        AS tahun_pbb,
        PBB.TGL_INPUT                                        AS tgl_input
    FROM PBB WITH (NOLOCK)
),
sertipikat AS (
    SELECT
        LTRIM(RTRIM(CAST(x.SERTIPIKAT_ID AS VARCHAR(50)))) AS kunci_sertipikat,
        LTRIM(RTRIM(CAST(x.STOK_ID AS VARCHAR(50))))       AS kunci_stok
    FROM SERTIPIKAT AS x WITH (NOLOCK)
),
stok_unit AS (
    SELECT LTRIM(RTRIM(CAST(s.STOK_ID AS VARCHAR(50)))) AS kunci_stok
    FROM STOK AS s WITH (NOLOCK)
    WHERE s.KD_PERUSAHAAN = 'SBKS'
)
SELECT 1 AS urut, 'TAHAP A  PBB seluruhnya' AS tahap,
       (SELECT COUNT(*) FROM pbb_kunci) AS baris
UNION ALL
SELECT 2, 'TAHAP B  kuncinya ketemu di SERTIPIKAT',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat)
UNION ALL
SELECT 3, 'TAHAP C  stoknya milik SBKS',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat
        INNER JOIN stok_unit AS u ON u.kunci_stok = s.kunci_stok)
UNION ALL
SELECT 4, 'TAHAP D  + tahun_pbb dan tgl_input  <- yang tampil',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat
        INNER JOIN stok_unit AS u ON u.kunci_stok = s.kunci_stok
        WHERE p.tahun_pbb >= 2000 AND p.tahun_pbb <= 2026
          AND p.tgl_input >= '2023-07-01'
          AND p.tgl_input <  '2026-09-25')
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN TAHUN TGL_INPUT, SELURUH UNIT.
 *
 * Di PostgreSQL berhenti pada 2023 dengan 966 baris, lalu
 * 2024, 2025, dan 2026 kosong. Kalau di sini ketiganya berisi
 * ribuan, seluruh selisihnya sudah terjelaskan dan ini
 * persoalan migrasi, bukan kode.
 * ------------------------------------------------------------ */
SELECT
    YEAR(TGL_INPUT) AS tahun,
    COUNT(*)        AS baris
FROM PBB WITH (NOLOCK)
WHERE TGL_INPUT IS NOT NULL
GROUP BY YEAR(TGL_INPUT)
ORDER BY 1 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * SEBARAN TAHUN TGL_INPUT khusus stok SBKS.
 *
 * Ini yang memberi angka pasti berapa baris yang seharusnya
 * ada di laporan ini tetapi belum termigrasi.
 * ------------------------------------------------------------ */
SELECT
    YEAR(PBB.TGL_INPUT) AS tahun,
    COUNT(*)            AS baris
FROM PBB WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = PBB.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE PBB.TGL_INPUT IS NOT NULL
  AND STOK.KD_PERUSAHAAN = 'SBKS'
GROUP BY YEAR(PBB.TGL_INPUT)
ORDER BY 1 DESC;


/* ------------------------------------------------------------
 * QUERY 4
 * JUMLAH BARIS PBB, seluruh unit dan khusus SBKS.
 *
 * Di PostgreSQL 15.429 seluruhnya dan 8.100 milik SBKS.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*) AS pbb_semua_unit,
    SUM(CASE WHEN STOK.KD_PERUSAHAAN = 'SBKS' THEN 1 ELSE 0 END) AS pbb_sbks
FROM PBB WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = PBB.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID;
