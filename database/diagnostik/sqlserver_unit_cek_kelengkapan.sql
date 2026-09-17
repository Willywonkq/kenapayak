/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pilih dulu database SRIS-nya, jangan master. Pada judul aplikasi desktop
 * tertulis SRIS_PUSAT.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * PERTANYAANNYA
 *
 * Pada PostgreSQL, unit DTSA punya 1.869 baris STOK tetapi nol AKTA dan
 * nol baris pada SERTIPIKAT_IDK. Perlu dipastikan apakah di sumber
 * aslinya memang begitu, atau datanya tertinggal saat migrasi.
 *
 * Jalankan kedua query di bawah, lalu bandingkan angkanya dengan hasil
 * unit_cek_kelengkapan_migrasi.sql yang dijalankan di PostgreSQL.
 *
 *   angkanya sama       -> memang belum ada dokumennya, aplikasinya benar
 *   SQL Server lebih    -> datanya belum ikut termigrasi
 * ===================================================================== */


/* =====================================================================
 * QUERY 1 — Isi tiap tabel per unit, di sumber aslinya
 *
 * Bentuk sebelumnya ditolak SQL Server dengan galat 130, karena
 * subquery tidak boleh berada di dalam fungsi agregat. Pemeriksaannya
 * dipindah ke CROSS APPLY supaya SUM hanya menjumlah angka biasa.
 * ===================================================================== */
SELECT
    UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) AS unit,
    COUNT(*)                                          AS stok,
    SUM(CASE WHEN UPPER(LTRIM(RTRIM(ISNULL(s.FLAG_AKTIF, '')))) = 'A'
             THEN 1 ELSE 0 END)                       AS stok_aktif,
    SUM(d.punya_ppjb)                                 AS stok_punya_ppjb,
    SUM(d.punya_sertipikat)                           AS stok_punya_sertipikat
FROM STOK s WITH (NOLOCK)
CROSS APPLY (
    SELECT
        CASE WHEN EXISTS (
                 SELECT 1 FROM PPJB p WITH (NOLOCK)
                 WHERE LTRIM(RTRIM(p.STOK_ID)) = LTRIM(RTRIM(s.STOK_ID))
             ) THEN 1 ELSE 0 END AS punya_ppjb,
        CASE WHEN EXISTS (
                 SELECT 1 FROM SERTIPIKAT x WITH (NOLOCK)
                 WHERE LTRIM(RTRIM(x.STOK_ID)) = LTRIM(RTRIM(s.STOK_ID))
             ) THEN 1 ELSE 0 END AS punya_sertipikat
) AS d
GROUP BY UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, ''))))
ORDER BY COUNT(*) DESC;


/* =====================================================================
 * QUERY 2 — Khusus DTSA: berapa akta dan berapa sertipikat induknya
 *
 * Inilah angka penentunya. Kalau di sini ada isinya sementara di
 * PostgreSQL nol, berarti migrasinya yang belum lengkap.
 * ===================================================================== */
SELECT
    (SELECT COUNT(*)
     FROM STOK s WITH (NOLOCK)
     WHERE UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) = 'DTSA')
        AS stok_dtsa,

    (SELECT COUNT(*)
     FROM AKTA a WITH (NOLOCK)
     INNER JOIN PPJB p WITH (NOLOCK)
         ON LTRIM(RTRIM(p.PPJB_ID)) = LTRIM(RTRIM(a.PPJB_ID))
     INNER JOIN STOK s WITH (NOLOCK)
         ON LTRIM(RTRIM(s.STOK_ID)) = LTRIM(RTRIM(p.STOK_ID))
     WHERE UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) = 'DTSA')
        AS akta_dtsa,

    (SELECT COUNT(*)
     FROM SERTIPIKAT x WITH (NOLOCK)
     INNER JOIN STOK s WITH (NOLOCK)
         ON LTRIM(RTRIM(s.STOK_ID)) = LTRIM(RTRIM(x.STOK_ID))
     WHERE UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) = 'DTSA')
        AS sertipikat_dtsa,

    (SELECT COUNT(*)
     FROM SERTIPIKAT_IDK i WITH (NOLOCK)
     INNER JOIN SERTIPIKAT x WITH (NOLOCK)
         ON LTRIM(RTRIM(x.SERTIPIKAT_ID)) = LTRIM(RTRIM(i.SERTIPIKAT_ID))
     INNER JOIN STOK s WITH (NOLOCK)
         ON LTRIM(RTRIM(s.STOK_ID)) = LTRIM(RTRIM(x.STOK_ID))
     WHERE UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) = 'DTSA')
        AS sertipikat_idk_dtsa;


/* =====================================================================
 * QUERY 3 — Besarnya celah AKTA dan SERTIPIKAT_IDK, per unit
 *
 * Pada PostgreSQL, 16 dari 26 unit tidak punya AKTA sama sekali,
 * termasuk SSPG yang PPJB-nya 13.212 dan SPCK yang 6.050. Query ini
 * menghitung angka sebenarnya di sumbernya, supaya besarnya celah bisa
 * dilaporkan dengan angka, bukan dugaan.
 *
 * Bandingkan kolom akta dengan kolom akta pada QUERY 1 PostgreSQL, dan
 * kolom sertipikat_idk dengan QUERY 4 PostgreSQL.
 * ===================================================================== */
SELECT
    unit,
    SUM(akta)           AS akta,
    SUM(sertipikat_idk) AS sertipikat_idk
FROM (
    SELECT
        UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) AS unit,
        1                                                AS akta,
        0                                                AS sertipikat_idk
    FROM AKTA a WITH (NOLOCK)
    INNER JOIN PPJB p WITH (NOLOCK)
        ON LTRIM(RTRIM(p.PPJB_ID)) = LTRIM(RTRIM(a.PPJB_ID))
    INNER JOIN STOK s WITH (NOLOCK)
        ON LTRIM(RTRIM(s.STOK_ID)) = LTRIM(RTRIM(p.STOK_ID))

    UNION ALL

    SELECT
        UPPER(LTRIM(RTRIM(ISNULL(s.KD_PERUSAHAAN, '')))) AS unit,
        0                                                AS akta,
        1                                                AS sertipikat_idk
    FROM SERTIPIKAT_IDK i WITH (NOLOCK)
    INNER JOIN SERTIPIKAT x WITH (NOLOCK)
        ON LTRIM(RTRIM(x.SERTIPIKAT_ID)) = LTRIM(RTRIM(i.SERTIPIKAT_ID))
    INNER JOIN STOK s WITH (NOLOCK)
        ON LTRIM(RTRIM(s.STOK_ID)) = LTRIM(RTRIM(x.STOK_ID))
) AS gabungan
GROUP BY unit
ORDER BY SUM(akta) DESC, SUM(sertipikat_idk) DESC;
