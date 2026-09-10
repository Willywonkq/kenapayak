/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pasangan dari QUERY 5 pada daftar_st_cek_jumlah_baris.sql. Syaratnya
 * disalin apa adanya dari query Daftar Serah Terima versi desktop, lalu
 * hasilnya dikelompokkan per tahun realisasi.
 *
 * Bandingkan baris per baris dengan hasil QUERY 5. Tahun yang jumlahnya
 * berbeda itulah letak sembilan baris yang belum ada di PostgreSQL.
 */

DECLARE @TGL_AWAL   DATE = '2023-07-01';
DECLARE @TGL_AKHIR  DATE = '2026-09-10';
DECLARE @TGL_ST1    DATE = '2023-07-01';
DECLARE @TGL_ST2    DATE = '2026-09-10';
DECLARE @BLOK_AWAL  VARCHAR(20) = 'A';
DECLARE @BLOK_AKHIR VARCHAR(20) = 'Z';
DECLARE @PERUSAHAAN VARCHAR(10) = 'DTSA';

SELECT
    YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)          AS tahun_realisasi,
    COUNT(*)                                     AS jumlah_baris,
    COUNT(DISTINCT SERAH_TERIMA.SERAH_TERIMA_ID) AS jumlah_serah_terima,
    CAST(MIN(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_awal,
    CAST(MAX(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_akhir
FROM SERAH_TERIMA WITH (NOLOCK),
     PPJB WITH (NOLOCK),
     STOK WITH (NOLOCK),
     TIPE WITH (NOLOCK),
     JENIS_BANGUNAN WITH (NOLOCK),
     PEMBELI_PPJB WITH (NOLOCK),
     NASABAH WITH (NOLOCK)
WHERE ( SERAH_TERIMA.PPJB_ID = PPJB.PPJB_ID ) AND
      ( STOK.STOK_ID = PPJB.STOK_ID ) AND
      ( PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID ) AND
      ( PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID ) AND
      ( TIPE.KD_JENIS = STOK.KD_JENIS ) AND
      ( TIPE.KD_TIPE = STOK.KD_TIPE ) AND
      ( TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS ) AND
      ( SERAH_TERIMA.TGL_SURAT >= @TGL_AWAL AND SERAH_TERIMA.TGL_SURAT <= @TGL_AKHIR ) AND
      ( SERAH_TERIMA.TGL_SERAH_TERIMA >= @TGL_ST1 AND SERAH_TERIMA.TGL_SERAH_TERIMA <= @TGL_ST2 ) AND
      ( STOK.BLOK >= @BLOK_AWAL AND STOK.BLOK <= @BLOK_AKHIR ) AND
      ( STOK.FLAG_AKTIF = 'A' ) AND
      ( PPJB.FLAG_AKTIF = 'A' ) AND
      ( PEMBELI_PPJB.FLAG_AKTIF = 'Y' ) AND
      ( STOK.KD_PERUSAHAAN = @PERUSAHAAN ) AND
      STOK.BLOK IS NOT NULL AND
      STOK.NOMOR IS NOT NULL AND
      PPJB.PARENT_ID IS NULL AND
      ISNULL(SERAH_TERIMA.FLAG_AKTIF,'A') = 'A'
GROUP BY YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)
ORDER BY 1;


/*
 * CARA MEMBACA
 *
 * Jumlah seluruh baris pada hasil ini harus 1027, sama dengan angka di
 * layar desktop. Bandingkan tiap tahunnya dengan QUERY 5 versi PostgreSQL
 * yang totalnya 1018.
 *
 * - Selisihnya menumpuk pada satu tahun
 *   -> serah terima tahun itu belum tersalin ke PostgreSQL.
 *
 * - Selisihnya menyebar sedikit-sedikit di banyak tahun
 *   -> kemungkinan bukan barisnya yang hilang, melainkan sr_pembeli_ppjb
 *      atau sr_nasabah yang belum lengkap sehingga jumlah pasangannya
 *      berbeda. Kirimkan hasilnya ke saya.
 */
