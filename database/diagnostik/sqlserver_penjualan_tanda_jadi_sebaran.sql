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
