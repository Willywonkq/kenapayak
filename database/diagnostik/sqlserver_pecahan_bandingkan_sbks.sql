/* ============================================================
 * SISI DESKTOP: DAFTAR SERTIPIKAT PECAHAN UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: pecahan_cek_kemutakhiran_idk.sql
 *
 * ------------------------------------------------------------
 * APA YANG DICARI
 *
 * Di PostgreSQL, laporan ini menampilkan 129 baris sedangkan
 * desktop lebih dari 753. Seluruh selisihnya terjadi pada
 * saringan tanggal, dari 4.022 baris siap menjadi 129.
 *
 * Sembilan kolom tanggal sudah dicoba satu per satu di
 * PostgreSQL. Yang paling besar hanya 581, dan keempat kolom
 * pada tabel induk memberi NOL. Sebabnya kelihatan pada sebaran
 * tahunnya: SERTIPIKAT_IDK di PostgreSQL berhenti pada 2022 dan
 * SERTIPIKAT berhenti pada 2024, padahal desktop menampilkan
 * baris 2023 ke atas dengan lancar.
 *
 * Berkas ini memeriksa apakah di SQL Server barisnya memang ada.
 * Kalau ya, maka laporannya bukan salah saring melainkan
 * kekurangan baris, persis seperti yang sudah terjadi pada
 * AKTA, JAMINAN, dan PERALIHAN.
 *
 * Kunci sambungan tidak dibungkus RTRIM. SQL Server mengabaikan
 * spasi di belakang pada perbandingan sama dengan.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * JUMLAH BARIS SIAP dan sebaran per tahun menurut beberapa
 * kolom tanggal, dengan saringan yang sama seperti di web.
 *
 * Bandingkan dengan PostgreSQL:
 *   baris siap sebelum tanggal   4.022
 *   memakai TGL_INPUT_SER          129
 *   memakai TGL_SERTIPIKAT         581
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                        AS siap_sebelum_tanggal,
    SUM(CASE WHEN SERTIPIKAT.TGL_INPUT_SER >= '2023-07-01'
              AND SERTIPIKAT.TGL_INPUT_SER <  '2026-09-24'
             THEN 1 ELSE 0 END)                     AS pakai_tgl_input_ser,
    SUM(CASE WHEN ISNULL(SERTIPIKAT.TGL_INPUT_GABUNG, SERTIPIKAT.TGL_INPUT_SER) >= '2023-07-01'
              AND ISNULL(SERTIPIKAT.TGL_INPUT_GABUNG, SERTIPIKAT.TGL_INPUT_SER) <  '2026-09-24'
             THEN 1 ELSE 0 END)                     AS pakai_gabung_lalu_ser,
    SUM(CASE WHEN SERTIPIKAT.TGL_SERTIPIKAT >= '2023-07-01'
              AND SERTIPIKAT.TGL_SERTIPIKAT <  '2026-09-24'
             THEN 1 ELSE 0 END)                     AS pakai_tgl_sertipikat,
    SUM(CASE WHEN SERTIPIKAT_IDK.TGL_INPUT >= '2023-07-01'
              AND SERTIPIKAT_IDK.TGL_INPUT <  '2026-09-24'
             THEN 1 ELSE 0 END)                     AS pakai_idk_tgl_input
FROM SERTIPIKAT_IDK WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = SERTIPIKAT_IDK.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE STOK.KD_PERUSAHAAN = 'SBKS'
  AND STOK.FLAG_AKTIF = 'A'
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND STOK.KD_JENIS NOT IN ('APT', 'KTR');


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN PER TAHUN pada kumpulan yang sama, supaya kelihatan
 * apakah di sini ada baris 2023 sampai 2026.
 *
 * Di PostgreSQL, TGL_INPUT_SER berhenti pada 2024 dengan 22
 * baris, dan SERTIPIKAT_IDK.TGL_INPUT berhenti pada 2022.
 * ------------------------------------------------------------ */
SELECT
    YEAR(SERTIPIKAT.TGL_INPUT_SER)  AS tahun_input_ser,
    COUNT(*)                        AS baris
FROM SERTIPIKAT_IDK WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = SERTIPIKAT_IDK.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE STOK.KD_PERUSAHAAN = 'SBKS'
  AND STOK.FLAG_AKTIF = 'A'
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL
  AND STOK.KD_JENIS NOT IN ('APT', 'KTR')
  AND SERTIPIKAT.TGL_INPUT_SER IS NOT NULL
GROUP BY YEAR(SERTIPIKAT.TGL_INPUT_SER)
ORDER BY 1 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * JUMLAH BARIS TABELNYA, untuk disandingkan langsung dengan
 * PostgreSQL yang memuat 23.308 baris SERTIPIKAT_IDK.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                AS baris_sertipikat_idk,
    MIN(TGL_INPUT)          AS tgl_input_terawal,
    MAX(TGL_INPUT)          AS tgl_input_terakhir
FROM SERTIPIKAT_IDK WITH (NOLOCK);


/* ------------------------------------------------------------
 * QUERY 4
 * KHUSUS SBKS, berapa baris SERTIPIKAT_IDK yang ada di sini.
 * Di PostgreSQL angkanya 8.892 sebelum saringan jenis, dan
 * 4.022 sesudah APT dan KTR dibuang.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                                AS semua_jenis,
    SUM(CASE WHEN STOK.KD_JENIS NOT IN ('APT','KTR')
             THEN 1 ELSE 0 END)                             AS bukan_apt_ktr
FROM SERTIPIKAT_IDK WITH (NOLOCK)
INNER JOIN SERTIPIKAT WITH (NOLOCK)
    ON SERTIPIKAT.SERTIPIKAT_ID = SERTIPIKAT_IDK.SERTIPIKAT_ID
INNER JOIN STOK WITH (NOLOCK)
    ON STOK.STOK_ID = SERTIPIKAT.STOK_ID
WHERE STOK.KD_PERUSAHAAN = 'SBKS'
  AND STOK.FLAG_AKTIF = 'A'
  AND STOK.BLOK IS NOT NULL
  AND STOK.NOMOR IS NOT NULL;
