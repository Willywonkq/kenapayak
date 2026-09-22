/* ============================================================
 * MENUNJUKKAN BARIS YANG LEBIH ITU
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: rencana_st_tunjukkan_baris_itu.sql
 *
 * ------------------------------------------------------------
 * PENYANDINGAN SEBARAN SUDAH MENGURUNGNYA
 *
 *   awal_blok RD      web 273   desktop 272
 *   jenis RMH         web 869   desktop 868
 *   tahun_ppjb 2024   web 417   desktop 416
 *
 * Kelompok lain sama persis, jadi barisnya pasti berblok RD,
 * berjenis RMH, dan PPJB-nya tahun 2024.
 *
 * Kunci sambungan sengaja tidak dibungkus RTRIM. SQL Server
 * mengabaikan spasi di belakang pada perbandingan sama dengan.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * HITUNGAN PER BLOK PENUH di persimpangan itu.
 * ------------------------------------------------------------ */
SELECT
    RTRIM(STOK.BLOK) AS BLOK,
    COUNT(*)         AS JUMLAH
FROM PPJB WITH (NOLOCK)
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = PPJB.STOK_ID
INNER JOIN TIPE WITH (NOLOCK)
    ON TIPE.KD_JENIS = STOK.KD_JENIS
   AND TIPE.KD_TIPE  = STOK.KD_TIPE
INNER JOIN JENIS_BANGUNAN WITH (NOLOCK)
    ON JENIS_BANGUNAN.KD_JENIS = TIPE.KD_JENIS
INNER JOIN PEMBELI_PPJB WITH (NOLOCK)
    ON PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
   AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
INNER JOIN NASABAH WITH (NOLOCK)
    ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
WHERE STOK.FLAG_AKTIF = 'A'
  AND PPJB.FLAG_AKTIF = 'A'
  AND STOK.KD_PERUSAHAAN = 'SBKS'
  AND JENIS_BANGUNAN.FLAG_LAPORAN <> 2
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND PPJB.PARENT_ID IS NULL
  AND STOK.BLOK LIKE 'RD%'
  AND STOK.KD_JENIS = 'RMH'
  AND YEAR(PPJB.TGL_PPJB) = 2024
  AND ISNULL(PPJB.TGL_RENCANA_SB,
             DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) >= '2023-07-01'
  AND ISNULL(PPJB.TGL_RENCANA_SB,
             DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) <= '2026-09-22'
  AND (
        ( (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) >= 'A'
          AND (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) <= 'ZZ' )
        OR ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'ZZ' )
      )
GROUP BY RTRIM(STOK.BLOK)
ORDER BY BLOK;


/* ------------------------------------------------------------
 * QUERY 2
 * DAFTAR LENGKAP di persimpangan itu.
 *
 * Kolom TGL_RENCANA_SB dan WAKTU ikut dibawa sebab kalau
 * ternyata barisnya ADA di kedua sisi, perbedaannya pasti di
 * salah satu kolom itu.
 * ------------------------------------------------------------ */
SELECT
    RTRIM(STOK.BLOK) + '/' + STOK.NOMOR                     AS BLOK_NOMOR,
    PPJB.NO_PPJB,
    STOK.KD_TIPE,
    CONVERT(VARCHAR(10), PPJB.TGL_PPJB, 120)                AS TGL_PPJB,
    ISNULL(CONVERT(VARCHAR(10), PPJB.TGL_RENCANA_SB, 120), '(kosong)')
                                                            AS TGL_RENCANA_SB,
    ISNULL(CAST(PPJB.WAKTU AS VARCHAR(20)), '(kosong)')     AS WAKTU,
    CONVERT(VARCHAR(10),
            ISNULL(PPJB.TGL_RENCANA_SB,
                   DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)), 120)
                                                            AS TGL_RENCANA_DIPAKAI
FROM PPJB WITH (NOLOCK)
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = PPJB.STOK_ID
INNER JOIN TIPE WITH (NOLOCK)
    ON TIPE.KD_JENIS = STOK.KD_JENIS
   AND TIPE.KD_TIPE  = STOK.KD_TIPE
INNER JOIN JENIS_BANGUNAN WITH (NOLOCK)
    ON JENIS_BANGUNAN.KD_JENIS = TIPE.KD_JENIS
INNER JOIN PEMBELI_PPJB WITH (NOLOCK)
    ON PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
   AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
INNER JOIN NASABAH WITH (NOLOCK)
    ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
WHERE STOK.FLAG_AKTIF = 'A'
  AND PPJB.FLAG_AKTIF = 'A'
  AND STOK.KD_PERUSAHAAN = 'SBKS'
  AND JENIS_BANGUNAN.FLAG_LAPORAN <> 2
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND PPJB.PARENT_ID IS NULL
  AND STOK.BLOK LIKE 'RD%'
  AND STOK.KD_JENIS = 'RMH'
  AND YEAR(PPJB.TGL_PPJB) = 2024
  AND ISNULL(PPJB.TGL_RENCANA_SB,
             DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) >= '2023-07-01'
  AND ISNULL(PPJB.TGL_RENCANA_SB,
             DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) <= '2026-09-22'
  AND (
        ( (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) >= 'A'
          AND (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) <= 'ZZ' )
        OR ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'ZZ' )
      )
ORDER BY BLOK_NOMOR, PPJB.NO_PPJB;
