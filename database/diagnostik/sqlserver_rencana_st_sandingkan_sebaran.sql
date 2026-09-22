/* ============================================================
 * MENYANDINGKAN SEBARAN WEB DAN DESKTOP
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: rencana_st_sandingkan_sebaran.sql
 *
 * ------------------------------------------------------------
 * Keterangannya ada di berkas pasangan. Ringkasnya: web memuat
 * 1.519 PPJB berbeda, desktop 1.518, dan tidak ada baris yang
 * tergandakan di kedua sisi. Berarti ada SATU PPJB yang dimuat
 * web dan tidak dimuat desktop.
 *
 * Keduanya dihitung per kelompok. Kelompok yang selisihnya satu
 * itulah yang ditelusuri lebih lanjut.
 *
 * Kunci sambungan sengaja tidak dibungkus RTRIM. SQL Server
 * mengabaikan spasi di belakang pada perbandingan sama dengan,
 * jadi RTRIM hanya mematikan pemakaian indeks tanpa menambah
 * kebenaran apa pun.
 * ------------------------------------------------------------ */

WITH baris AS (
    SELECT
        LEFT(RTRIM(STOK.BLOK), 2) AS awal_blok,
        STOK.KD_JENIS             AS kd_jenis,
        PPJB.TGL_PPJB             AS tgl_ppjb
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
      AND ISNULL(PPJB.TGL_RENCANA_SB,
                 DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) >= '2023-07-01'
      AND ISNULL(PPJB.TGL_RENCANA_SB,
                 DATEADD(MONTH, ISNULL(PPJB.WAKTU, 0), PPJB.TGL_PPJB)) <= '2026-09-22'
      AND (
            ( (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) >= 'A'
              AND (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) <= 'ZZ' )
            OR ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'ZZ' )
          )
)
SELECT 'awal_blok' AS dimensi, CAST(awal_blok AS VARCHAR(20)) AS nilai, COUNT(*) AS jumlah
FROM baris GROUP BY CAST(awal_blok AS VARCHAR(20))
UNION ALL
SELECT 'jenis', CAST(kd_jenis AS VARCHAR(20)), COUNT(*)
FROM baris GROUP BY CAST(kd_jenis AS VARCHAR(20))
UNION ALL
SELECT 'tahun_ppjb', CAST(YEAR(tgl_ppjb) AS VARCHAR(20)), COUNT(*)
FROM baris GROUP BY CAST(YEAR(tgl_ppjb) AS VARCHAR(20))
UNION ALL
SELECT 'JUMLAH', '(semua)', COUNT(*)
FROM baris
ORDER BY 1, 2;
