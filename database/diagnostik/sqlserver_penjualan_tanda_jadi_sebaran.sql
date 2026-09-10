/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * PENTING SEBELUM MENJALANKAN
 *
 * Pilih dulu database SRIS-nya, jangan master. Pada judul aplikasi desktop
 * tertulis SRIS_PUSAT, jadi kemungkinan besar itu namanya. Bisa juga dengan
 * membuka tanda komentar pada baris USE di bawah.
 *
 * Nilai tanggal ditulis langsung, bukan lewat DECLARE maupun tanda titik
 * dua, supaya tiap query bisa dijalankan sendiri-sendiri dan DBeaver tidak
 * memunculkan kotak Bind parameter.
 *
 * Pasangannya di PostgreSQL adalah QUERY 5 pada berkas
 * database/diagnostik/penjualan_tanda_jadi_agen_cek_jumlah_unit.sql, yang
 * menghasilkan Januari 38, Februari 17, Maret 11, April 15, Mei 13,
 * Juni 40, dan Juli hanya 1 dengan tanggal terakhir 2 Juli 2026.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * QUERY 1 — Sebaran surat pesanan per bulan sepanjang 2026
 *
 * Syaratnya disalin apa adanya dari query Penjualan per Tanggal Tanda Jadi
 * per Agen versi desktop.
 *
 * Bandingkan tiap bulannya dengan hasil PostgreSQL. Bulan yang jumlahnya
 * lebih besar di sini itulah letak sembilan unit yang belum tersalin.
 * ===================================================================== */
SELECT
    CONVERT(VARCHAR(7), UANG_MUKA.TGL_UANG_MUKA, 120)  AS bulan,
    COUNT(*)                                           AS jumlah_unit,
    CAST(MIN(UANG_MUKA.TGL_UANG_MUKA) AS DATE)         AS paling_awal,
    CAST(MAX(UANG_MUKA.TGL_UANG_MUKA) AS DATE)         AS paling_akhir
FROM UANG_MUKA WITH (NOLOCK),
     STOK WITH (NOLOCK),
     TIPE WITH (NOLOCK),
     JENIS_BANGUNAN WITH (NOLOCK)
WHERE ( STOK.STOK_ID = UANG_MUKA.STOK_ID ) and
      ( TIPE.KD_JENIS = STOK.KD_JENIS ) and
      ( TIPE.KD_TIPE = STOK.KD_TIPE ) and
      ( TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS ) and
      ( UANG_MUKA.TGL_UANG_MUKA >= '2026-01-01' ) AND
      ( UANG_MUKA.TGL_UANG_MUKA <= '2026-09-08' ) AND
      ( STOK.KD_PERUSAHAAN = 'DTSA' ) AND
      ( UANG_MUKA.FLAG_AKTIF = 'A' )  AND
      ( UANG_MUKA.PARENT_ID IS NULL )
GROUP BY CONVERT(VARCHAR(7), UANG_MUKA.TGL_UANG_MUKA, 120)
ORDER BY 1;


/* =====================================================================
 * QUERY 2 — Surat pesanan mulai 3 Juli 2026
 *
 * Salinan PostgreSQL berhenti pada 2 Juli 2026, jadi seluruh baris di sini
 * adalah yang belum ada di sana. Jumlahnya seharusnya sembilan.
 * ===================================================================== */
SELECT
    RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR)      AS blok_nomor,
    UANG_MUKA.NO_UANG_MUKA,
    CAST(UANG_MUKA.TGL_UANG_MUKA AS DATE)           AS tgl_tanda_jadi,
    UANG_MUKA.HARGA_RUMAH,
    UANG_MUKA.HARGA_JUAL
FROM UANG_MUKA WITH (NOLOCK),
     STOK WITH (NOLOCK),
     TIPE WITH (NOLOCK),
     JENIS_BANGUNAN WITH (NOLOCK)
WHERE ( STOK.STOK_ID = UANG_MUKA.STOK_ID ) and
      ( TIPE.KD_JENIS = STOK.KD_JENIS ) and
      ( TIPE.KD_TIPE = STOK.KD_TIPE ) and
      ( TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS ) and
      ( UANG_MUKA.TGL_UANG_MUKA >= '2026-07-03' ) AND
      ( UANG_MUKA.TGL_UANG_MUKA <= '2026-09-08' ) AND
      ( STOK.KD_PERUSAHAAN = 'DTSA' ) AND
      ( UANG_MUKA.FLAG_AKTIF = 'A' )  AND
      ( UANG_MUKA.PARENT_ID IS NULL )
ORDER BY UANG_MUKA.TGL_UANG_MUKA, 1;


/*
 * CARA MEMBACA
 *
 * QUERY 1, bandingkan tiap bulan dengan hasil PostgreSQL. Selisih seluruh
 * bulan seharusnya sembilan, sesuai selisih 805 lawan 796 di layar.
 *
 * QUERY 2 memberi daftarnya secara rinci. HG/008 bertanggal 26 Juli 2026
 * seharusnya ada di daftar ini, karena unit itu sudah terbukti tidak ada di
 * PostgreSQL.
 *
 * Kalau jumlahnya tepat sembilan, laporan ini selesai dan tidak ada yang
 * perlu diperbaiki di kode. Yang tersisa tinggal menyalin ulang datanya.
 */


/* =====================================================================
 * QUERY 3 — Tiga unit April dan Juni yang justru ada di PostgreSQL
 *
 * Hasil QUERY 1 menunjukkan arah selisih yang tidak seragam:
 *
 *     bulan      PostgreSQL   SQL Server
 *     2026-04    15           13          PostgreSQL lebih 2
 *     2026-06    40           39          PostgreSQL lebih 1
 *     2026-07     1           13          SQL Server lebih 12
 *
 * Jadi bukan hanya PostgreSQL yang ketinggalan. Ada tiga unit April dan
 * Juni yang masih terhitung di PostgreSQL tetapi sudah tidak masuk hasil
 * desktop. Dua belas dikurangi tiga sama dengan sembilan, tepat sesuai
 * selisih 805 lawan 796.
 *
 * Penjelasan yang paling masuk akal, ketiga unit itu dibatalkan atau
 * direvisi sesudah salinan PostgreSQL diambil pada 7 Juli 2026. Salinan itu
 * masih menyimpan keadaan lamanya, yaitu FLAG_AKTIF masih A dan PARENT_ID
 * masih kosong.
 *
 * Query ini mendaftar surat pesanan April sampai Juni 2026 yang TIDAK masuk
 * hasil desktop, berikut alasannya. Kalau yang muncul tiga baris, dan
 * salah satunya bertanggal antara 28 dan 30 April, perhitungannya tutup.
 * ===================================================================== */
SELECT
    RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR)     AS blok_nomor,
    UANG_MUKA.NO_UANG_MUKA,
    CAST(UANG_MUKA.TGL_UANG_MUKA AS DATE)          AS tgl_tanda_jadi,
    ISNULL(UANG_MUKA.FLAG_AKTIF, '(kosong)')       AS flag_aktif,
    CASE WHEN UANG_MUKA.PARENT_ID IS NULL
         THEN '(kosong)' ELSE 'terisi' END         AS parent_id,
    CASE WHEN EXISTS (
             SELECT 1
             FROM TIPE WITH (NOLOCK), JENIS_BANGUNAN WITH (NOLOCK)
             WHERE TIPE.KD_JENIS = STOK.KD_JENIS
               AND TIPE.KD_TIPE = STOK.KD_TIPE
               AND TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS
         ) THEN 'ada' ELSE 'tidak ada' END         AS pasangan_tipe,
    CASE
        WHEN UANG_MUKA.FLAG_AKTIF <> 'A'          THEN 'flag aktif bukan A'
        WHEN UANG_MUKA.PARENT_ID IS NOT NULL      THEN 'sudah direvisi, parent id terisi'
        ELSE 'pasangan tipe tidak ada'
    END                                            AS alasan
FROM UANG_MUKA WITH (NOLOCK),
     STOK WITH (NOLOCK)
WHERE ( STOK.STOK_ID = UANG_MUKA.STOK_ID ) AND
      ( STOK.KD_PERUSAHAAN = 'DTSA' ) AND
      ( UANG_MUKA.TGL_UANG_MUKA >= '2026-04-01' ) AND
      ( UANG_MUKA.TGL_UANG_MUKA <= '2026-06-30' ) AND
      (
        UANG_MUKA.FLAG_AKTIF <> 'A'
        OR UANG_MUKA.PARENT_ID IS NOT NULL
        OR NOT EXISTS (
             SELECT 1
             FROM TIPE WITH (NOLOCK), JENIS_BANGUNAN WITH (NOLOCK)
             WHERE TIPE.KD_JENIS = STOK.KD_JENIS
               AND TIPE.KD_TIPE = STOK.KD_TIPE
               AND TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS
           )
      )
ORDER BY UANG_MUKA.TGL_UANG_MUKA, 1;
