/* ============================================================
 * MEMERIKSA SATU BARIS ITU: RDB/020
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di SQL SERVER.
 *
 * Pasangannya: rencana_st_periksa_rdb020.sql
 *
 * ------------------------------------------------------------
 * BARISNYA SUDAH KETEMU
 *
 *   blok RDB      web 8   desktop 7
 *
 *   RDB/020   A.0018/SBKS/RMH/2024   RM999
 *   tgl_ppjb 2024-01-23, waktu 4, rencana 2024-05-23
 *
 * Desktop memberi 1.518 baik ketika nasabah diwajibkan ada
 * maupun tidak, jadi di desktop baris ini gugur SEBELUM sampai
 * ke nasabah. Sebabnya ada pada PPJB-nya, stoknya, atau
 * pembelinya.
 *
 * Ketiganya diambil TANPA SATU PUN SYARAT. Kalau QUERY 1 tidak
 * memberi baris sama sekali, berarti PPJB itu memang sudah
 * tidak ada di sini, dan urusannya kesegaran data, bukan kode.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * BARIS PPJB-nya, tanpa syarat apa pun.
 * ------------------------------------------------------------ */
SELECT
    PPJB.PPJB_ID,
    PPJB.NO_PPJB,
    PPJB.STOK_ID,
    ISNULL(PPJB.FLAG_AKTIF, '(NULL)')                           AS FLAG_AKTIF,
    ISNULL(CAST(PPJB.PARENT_ID AS VARCHAR(50)), '(NULL)')       AS PARENT_ID,
    CONVERT(VARCHAR(10), PPJB.TGL_PPJB, 120)                    AS TGL_PPJB,
    ISNULL(CONVERT(VARCHAR(10), PPJB.TGL_RENCANA_SB, 120), '(NULL)')
                                                                AS TGL_RENCANA_SB,
    ISNULL(CAST(PPJB.WAKTU AS VARCHAR(20)), '(NULL)')           AS WAKTU,
    ISNULL(CAST(PPJB.WAKTU_ADD AS VARCHAR(20)), '(NULL)')       AS WAKTU_ADD
FROM PPJB WITH (NOLOCK)
WHERE PPJB.NO_PPJB = 'A.0018/SBKS/RMH/2024';


/* ------------------------------------------------------------
 * QUERY 2
 * BARIS STOK-nya, dicari lewat blok dan nomor, tanpa syarat.
 * ------------------------------------------------------------ */
SELECT
    STOK.STOK_ID,
    RTRIM(STOK.BLOK)                                            AS BLOK,
    RTRIM(STOK.NOMOR)                                           AS NOMOR,
    ISNULL(STOK.FLAG_AKTIF, '(NULL)')                           AS FLAG_AKTIF,
    ISNULL(STOK.KD_PERUSAHAAN, '(NULL)')                        AS KD_PERUSAHAAN,
    ISNULL(STOK.KD_SEKTOR, '(NULL)')                            AS KD_SEKTOR,
    ISNULL(STOK.KD_LOKASI, '(NULL)')                            AS KD_LOKASI,
    ISNULL(STOK.KD_JENIS, '(NULL)')                             AS KD_JENIS,
    ISNULL(STOK.KD_TIPE, '(NULL)')                              AS KD_TIPE
FROM STOK WITH (NOLOCK)
WHERE STOK.BLOK = 'RDB'
  AND STOK.NOMOR = '020';


/* ------------------------------------------------------------
 * QUERY 3
 * BARIS PEMBELI-nya, tanpa syarat flag_aktif.
 * ------------------------------------------------------------ */
SELECT
    PEMBELI_PPJB.PPJB_ID,
    PEMBELI_PPJB.NASABAH_ID,
    ISNULL(PEMBELI_PPJB.FLAG_AKTIF, '(NULL)')                   AS FLAG_AKTIF
FROM PEMBELI_PPJB WITH (NOLOCK)
WHERE PEMBELI_PPJB.PPJB_ID IN (
    SELECT PPJB.PPJB_ID
    FROM PPJB WITH (NOLOCK)
    WHERE PPJB.NO_PPJB = 'A.0018/SBKS/RMH/2024'
);
