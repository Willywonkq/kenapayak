/* ============================================================
 * SISI DESKTOP: DAFTAR PENGAJUAN SERTIPIKAT BALIK NAMA, SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * ------------------------------------------------------------
 * HASIL CORONG DI POSTGRESQL
 *
 *   638  baris AKTA dalam rentang tanggal, SELURUH UNIT
 *   638  kuncinya ketemu di sertipikat
 *   232  belum balik nama dan punya stok
 *   151  stoknya milik SBKS
 *    87  lolos saringan blok CARA LAMA
 *   151  lolos saringan blok CARA BENAR
 *   155  sesudah disambung ke tabel induk
 *
 * Desktop menampilkan 966 baris.
 *
 * Saringan blok yang rusak sudah diperbaiki dan mengembalikan
 * 64 baris, dari 87 menjadi 151. Tetapi 966 tetap jauh.
 *
 * Yang menentukan: seluruh tabel sr_akta di PostgreSQL hanya
 * memuat 638 baris pada rentang itu UNTUK SELURUH UNIT, padahal
 * desktop menampilkan 966 untuk SBKS saja. Jadi berapa pun
 * saringan yang dilonggarkan, angkanya tidak mungkin tercapai.
 *
 * Sebaran tahun sr_akta memperlihatkan sebabnya:
 *
 *   2022  1.513      2023  929      2024  12
 *   2025      0      2026    0
 *
 * Berhenti pada awal 2024, persis seperti yang sudah tercatat.
 *
 * Berkas ini memastikan angkanya di sumber.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * JUMLAH BARIS AKTA dalam rentang, seluruh unit dan khusus SBKS.
 * Bandingkan dengan 638 di PostgreSQL.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                        AS akta_semua_unit,
    SUM(CASE WHEN STOK.KD_PERUSAHAAN = 'SBKS' THEN 1 ELSE 0 END)
                                                    AS akta_sbks
FROM AKTA WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = AKTA.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE AKTA.TGL_INPUT >= '2023-07-01'
  AND AKTA.TGL_INPUT <  '2026-09-24';


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN TAHUN AKTA, seluruh unit.
 * Di PostgreSQL berhenti pada 2024 dengan 12 baris.
 * ------------------------------------------------------------ */
SELECT
    YEAR(TGL_INPUT) AS tahun,
    COUNT(*)        AS baris
FROM AKTA WITH (NOLOCK)
WHERE TGL_INPUT IS NOT NULL
GROUP BY YEAR(TGL_INPUT)
ORDER BY 1 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * CORONG SISI DESKTOP, supaya tiap tahap bisa disandingkan.
 *
 * Bandingkan berurutan dengan 638, 232, 151, dan 151.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                            AS t1_akta_dalam_tanggal,
    SUM(CASE WHEN SERTIPIKAT.STATUS_BLK_NM = 'T'
               OR SERTIPIKAT.STATUS_BLK_NM IS NULL
             THEN 1 ELSE 0 END)                         AS t3_belum_balik_nama,
    SUM(CASE WHEN (SERTIPIKAT.STATUS_BLK_NM = 'T'
                OR SERTIPIKAT.STATUS_BLK_NM IS NULL)
              AND STOK.KD_PERUSAHAAN = 'SBKS'
             THEN 1 ELSE 0 END)                         AS t4_unit_sbks,
    SUM(CASE WHEN (SERTIPIKAT.STATUS_BLK_NM = 'T'
                OR SERTIPIKAT.STATUS_BLK_NM IS NULL)
              AND STOK.KD_PERUSAHAAN = 'SBKS'
              AND STOK.BLOK IS NOT NULL
              AND STOK.NOMOR IS NOT NULL
             THEN 1 ELSE 0 END)                         AS t5_blok_nomor_ada
FROM AKTA WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = AKTA.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE AKTA.TGL_INPUT >= '2023-07-01'
  AND AKTA.TGL_INPUT <  '2026-09-24';


/* ------------------------------------------------------------
 * QUERY 4
 * APAKAH DESKTOP MEWAJIBKAN TABEL INDUK.
 *
 * Di PostgreSQL, menyambung ke SERTIPIKAT_IDK tidak membuang
 * satu baris pun, 151 lawan 151, tetapi MENAMBAH 4 baris
 * menjadi 155 karena sebagian sertipikat punya lebih dari satu
 * induk. Angka di sini menunjukkan apakah desktop pun begitu.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                    AS baris_dengan_induk,
    COUNT(DISTINCT SERTIPIKAT.SERTIPIKAT_ID)    AS sertipikat_berbeda
FROM AKTA WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = AKTA.SERTIPIKAT_ID
INNER JOIN SERTIPIKAT_IDK WITH (NOLOCK)
    ON SERTIPIKAT_IDK.SERTIPIKAT_ID = SERTIPIKAT.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE AKTA.TGL_INPUT >= '2023-07-01'
  AND AKTA.TGL_INPUT <  '2026-09-24'
  AND STOK.KD_PERUSAHAAN = 'SBKS'
  AND (SERTIPIKAT.STATUS_BLK_NM = 'T' OR SERTIPIKAT.STATUS_BLK_NM IS NULL)
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL;
