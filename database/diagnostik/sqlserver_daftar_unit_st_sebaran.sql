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
 * Pilih dulu database SRIS-nya, jangan master. Galat "Invalid object name"
 * berarti skripnya sedang jalan di database yang salah. Pada judul aplikasi
 * desktop tertulis SRIS_PUSAT, jadi kemungkinan besar itu namanya. Bisa juga
 * dengan membuka tanda komentar pada baris USE di bawah.
 *
 * Nilai tanggal ditulis langsung, bukan lewat DECLARE maupun tanda titik
 * dua, supaya tiap query bisa dijalankan sendiri-sendiri dan DBeaver tidak
 * memunculkan kotak Bind parameter.
 *
 * Pasangannya di PostgreSQL adalah QUERY 4 pada berkas
 * database/diagnostik/daftar_unit_st_cek_jumlah_baris.sql, yang menghasilkan
 * 2024 sebanyak 77, 2025 sebanyak 310, dan 2026 sebanyak 201, seluruhnya 588
 * dengan tanggal realisasi paling akhir 4 Juli 2026.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * QUERY 1 — Sebaran per tahun realisasi memakai syarat desktop
 *
 * Syaratnya disalin apa adanya dari query Daftar Unit ST dan Migrasi TM
 * versi desktop, dengan Sts BAST dipilih Semua.
 *
 * Jumlah seluruh barisnya harus 596, sama dengan angka di layar desktop.
 * Yang dicari adalah tahun 2026: bila di sini 209 sedangkan PostgreSQL 201,
 * dan tanggal paling akhirnya melewati 4 Juli 2026, maka delapan baris yang
 * selisih memang serah terima yang belum tersalin.
 * ===================================================================== */
SELECT
    YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)              AS tahun_realisasi,
    COUNT(*)                                         AS jumlah_baris,
    COUNT(DISTINCT PPJB.PPJB_ID)                     AS jumlah_ppjb,
    CAST(MIN(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_awal,
    CAST(MAX(SERAH_TERIMA.TGL_SERAH_TERIMA) AS DATE) AS paling_akhir
FROM STOK
     INNER JOIN PPJB
     ON  STOK.STOK_ID = PPJB.STOK_ID
     AND PPJB.PARENT_ID IS NULL
     LEFT OUTER JOIN JENIS_BANGUNAN
     ON  STOK.KD_JENIS = JENIS_BANGUNAN.KD_JENIS
     LEFT OUTER JOIN TIPE
     ON  STOK.KD_JENIS = TIPE.KD_JENIS
     AND STOK.KD_TIPE = TIPE.KD_TIPE
     LEFT OUTER JOIN SERAH_TERIMA
     ON  PPJB.PPJB_ID = SERAH_TERIMA.PPJB_ID
WHERE 1 = 1
  AND STOK.FLAG_AKTIF = 'A'
  AND STOK.PARENT_ID IS NULL
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND ( STOK.KD_PERUSAHAAN = 'DTSA' )
  AND ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'Z' )
  AND (
        (
          (
            ( PPJB.FLAG_AKTIF = 'A' AND PPJB.TGL_PPJB >= '2023-07-01' AND PPJB.TGL_PPJB <= '2026-09-10' )
            OR ( PPJB.FLAG_AKTIF = 'T' AND PPJB.FLAG_BATAL = 'Y' AND PPJB.TGL_PPJB >= '2023-07-01' AND PPJB.TGL_PPJB <= '2026-09-10' AND PPJB.TGL_BATAL > '2026-09-10' AND SERAH_TERIMA.TGL_SERAH_TERIMA IS NOT NULL )
          ) AND 'T' = 'T'
        )
      )
  AND ( SERAH_TERIMA.TGL_SERAH_TERIMA >= '2023-07-01' AND SERAH_TERIMA.TGL_SERAH_TERIMA <= '2026-09-10' )
GROUP BY YEAR(SERAH_TERIMA.TGL_SERAH_TERIMA)
ORDER BY 1;


/* =====================================================================
 * QUERY 2 — Baris yang realisasinya mulai Juni 2026
 *
 * Pasangan QUERY 5 versi PostgreSQL, yang berhenti pada HC/032 tanggal
 * 4 Juli 2026. Baris di sini yang tanggalnya lewat dari itu adalah baris
 * yang belum tersalin.
 * ===================================================================== */
SELECT
    RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR)         AS blok_nomor,
    SERAH_TERIMA.NO_SURAT                              AS no_bast,
    CAST(SERAH_TERIMA.TGL_SERAH_TERIMA AS DATE)        AS tgl_st,
    CAST(PPJB.TGL_PPJB AS DATE)                        AS tgl_ppjb,
    ISNULL(SERAH_TERIMA.FLAG_AKTIF, 'A')               AS status_bast
FROM STOK
     INNER JOIN PPJB
     ON  STOK.STOK_ID = PPJB.STOK_ID
     AND PPJB.PARENT_ID IS NULL
     INNER JOIN SERAH_TERIMA
     ON  PPJB.PPJB_ID = SERAH_TERIMA.PPJB_ID
WHERE 1 = 1
  AND STOK.FLAG_AKTIF = 'A'
  AND STOK.PARENT_ID IS NULL
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND ( STOK.KD_PERUSAHAAN = 'DTSA' )
  AND ( STOK.BLOK >= 'A' AND STOK.BLOK <= 'Z' )
  AND ( SERAH_TERIMA.TGL_SERAH_TERIMA >= '2026-06-01' AND SERAH_TERIMA.TGL_SERAH_TERIMA <= '2026-09-10' )
ORDER BY SERAH_TERIMA.TGL_SERAH_TERIMA, 1;


/*
 * CARA MEMBACA
 *
 * QUERY 1, jumlahkan kolom jumlah_baris seluruh tahun, harus 596.
 * Bandingkan tahun 2026 dengan PostgreSQL yang bernilai 201, dan perhatikan
 * kolom paling_akhir.
 *
 * - 2026 bernilai 209 dan paling_akhir melewati 4 Juli 2026
 *   -> terbukti, delapan baris itu serah terima yang belum tersalin ke
 *      PostgreSQL. Tidak ada yang perlu diperbaiki di model.
 *
 * - 2026 juga bernilai 201
 *   -> berarti selisihnya bukan di sana. Kirimkan hasilnya ke saya.
 *
 * QUERY 2 memberi daftar barisnya secara rinci, supaya bisa dicocokkan satu
 * per satu dengan QUERY 5 versi PostgreSQL.
 */
