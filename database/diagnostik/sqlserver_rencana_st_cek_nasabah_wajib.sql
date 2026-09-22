/* ============================================================
 * MEMERIKSA SISI DESKTOP: APAKAH NASABAH IKUT MEMBUANG BARIS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER, bukan PostgreSQL.
 *
 * ------------------------------------------------------------
 * MENGAPA PERLU
 *
 * Di PostgreSQL selisihnya tinggal satu baris, dan keempat
 * dugaan yang bisa diuji di sana sudah mati semua. Berarti
 * jawabannya harus dicari di sisi desktop.
 *
 * Query desktop menyambung nasabah dengan koma,
 *     ( PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID )
 * dan koma di SQL lama berarti INNER JOIN. Jadi kalau ada SATU
 * pembeli yang nasabahnya tidak ada di master SQL Server pun,
 * desktop akan membuang barisnya sedangkan web tetap
 * menampilkannya dengan nama '-'.
 *
 * Kalau benar, QUERY 1 akan memberi 1.518 dan 1.519.
 *
 * ------------------------------------------------------------
 * CATATAN KECEPATAN
 *
 * Kunci sambungan sengaja TIDAK dibungkus RTRIM. SQL Server
 * mengabaikan spasi di belakang pada perbandingan sama dengan,
 * jadi RTRIM tidak menambah kebenaran apa pun tetapi mematikan
 * pemakaian indeks. Pelajaran ini sudah dua kali kita bayar
 * mahal pada diagnosis sebelumnya.
 *
 * Syarat serah terima pada query asli tidak disalin, sebab
 * dengan Versi Management tidak dicentang nilainya 'T' dan
 * seluruh syarat itu selalu benar.
 *
 * ------------------------------------------------------------
 * PARAMETER, samakan dengan yang dipakai di web
 *
 *   TGL_AWAL    2023-07-01
 *   TGL_AKHIR   2026-09-22
 *   PERUSAHAAN  SBKS
 *   BLOK        A sampai ZZ
 *   SEKTOR      semua
 *   JENIS       Non Kavling
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * DUA ANGKA SEKALIGUS.
 *
 *   cara_desktop_inner   nasabah wajib ada, harusnya 1.518
 *   kalau_nasabah_left   nasabah boleh kosong, harusnya 1.519
 *
 * Kalau selisih keduanya 1, penyebab sisa satu baris itu
 * ketemu, dan web-lah yang perlu menyesuaikan diri.
 * Kalau keduanya sama, dugaan ini mati dan kita cari lagi.
 * ------------------------------------------------------------ */
SELECT
    SUM(CASE WHEN NASABAH.NASABAH_ID IS NOT NULL THEN 1 ELSE 0 END)
                                                    AS cara_desktop_inner,
    COUNT(*)                                        AS kalau_nasabah_left
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
LEFT JOIN NASABAH WITH (NOLOCK)
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
      );


/* ------------------------------------------------------------
 * QUERY 2
 * KALAU ADA, INILAH BARISNYA. Paling banyak 20.
 *
 * Nama pembeli sengaja tidak diambil, sebab memang tidak ada.
 * Blok dan nomor PPJB sudah cukup untuk menelusurinya.
 * ------------------------------------------------------------ */
SELECT TOP 20
    RTRIM(STOK.BLOK) + '/' + STOK.NOMOR             AS BLOK_NOMOR,
    PPJB.NO_PPJB,
    STOK.KD_JENIS,
    STOK.KD_TIPE,
    PEMBELI_PPJB.NASABAH_ID                         AS NASABAH_ID_YANG_HILANG
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
LEFT JOIN NASABAH WITH (NOLOCK)
    ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
WHERE NASABAH.NASABAH_ID IS NULL
  AND STOK.FLAG_AKTIF = 'A'
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
ORDER BY BLOK_NOMOR;


/* ------------------------------------------------------------
 * QUERY 3
 * BATAS NASABAH DI SQL SERVER, untuk disandingkan dengan
 * PostgreSQL yang berhenti di 45.255. Ini menjelaskan 505 baris
 * bernama '-' di web, yang di desktop namanya lengkap.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                AS jumlah_nasabah,
    MIN(NASABAH_ID)         AS nasabah_id_terkecil,
    MAX(NASABAH_ID)         AS nasabah_id_terbesar
FROM NASABAH WITH (NOLOCK);
