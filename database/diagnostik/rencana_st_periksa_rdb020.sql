/* ============================================================
 * MEMERIKSA SATU BARIS ITU: RDB/020
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * Pasangannya: sqlserver_rencana_st_periksa_rdb020.sql
 *
 * ------------------------------------------------------------
 * BARISNYA SUDAH KETEMU
 *
 *   blok RDB      web 8   desktop 7
 *
 *   RDB/020   A.0018/SBKS/RMH/2024   RM999
 *   tgl_ppjb 2024-01-23, waktu 4, rencana 2024-05-23
 *
 * Dua puluh tiga baris lain di persimpangan RD, RMH, 2024 cocok
 * persis sampai ke kolom waktu dan tanggal rencananya.
 *
 * ------------------------------------------------------------
 * YANG SUDAH BISA DISIMPULKAN
 *
 * Pada pemeriksaan sebelumnya, desktop memberi 1.518 baik ketika
 * nasabah diwajibkan ada maupun tidak. Artinya di desktop baris
 * ini gugur SEBELUM sampai ke nasabah. Jadi sebabnya ada di
 * salah satu dari: PPJB-nya sendiri, stoknya, atau pembelinya.
 *
 * Berkas ini mengambil ketiganya TANPA SATU PUN SYARAT, supaya
 * kelihatan kolom mana yang berbeda isinya antara dua basis
 * data itu. Jalankan di kedua sisi, lalu sandingkan kolom demi
 * kolom.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * BARIS PPJB-nya, tanpa syarat apa pun.
 * Yang diperhatikan: flag_aktif, parent_id, tgl_batal bila ada.
 * ------------------------------------------------------------ */
SELECT
    BTRIM(CAST(ppjb.ppjb_id AS TEXT))                           AS ppjb_id,
    BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))             AS no_ppjb,
    BTRIM(CAST(ppjb.stok_id AS TEXT))                           AS stok_id,
    COALESCE(CAST(ppjb.flag_aktif AS TEXT), '(NULL)')           AS flag_aktif,
    COALESCE(CAST(ppjb.parent_id AS TEXT), '(NULL)')            AS parent_id,
    TO_CHAR(ppjb.tgl_ppjb, 'YYYY-MM-DD')                        AS tgl_ppjb,
    COALESCE(TO_CHAR(ppjb.tgl_rencana_sb, 'YYYY-MM-DD'), '(NULL)')
                                                                AS tgl_rencana_sb,
    COALESCE(CAST(ppjb.waktu AS TEXT), '(NULL)')                AS waktu,
    COALESCE(to_jsonb(ppjb) ->> 'waktu_add', '(tidak ada kolom)')
                                                                AS waktu_add,
    COALESCE(to_jsonb(ppjb) ->> 'tgl_batal', '(NULL)')          AS tgl_batal
FROM public.sr_ppjb AS ppjb
WHERE BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')) = 'A.0018/SBKS/RMH/2024';


/* ------------------------------------------------------------
 * QUERY 2
 * BARIS STOK-nya, dicari lewat blok dan nomor, tanpa syarat.
 * Yang diperhatikan: flag_aktif, kd_perusahaan, kd_jenis,
 * kd_tipe.
 * ------------------------------------------------------------ */
SELECT
    BTRIM(CAST(stok.stok_id AS TEXT))                           AS stok_id,
    BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))                AS blok,
    BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))               AS nomor,
    COALESCE(CAST(stok.flag_aktif AS TEXT), '(NULL)')           AS flag_aktif,
    COALESCE(CAST(stok.kd_perusahaan AS TEXT), '(NULL)')        AS kd_perusahaan,
    COALESCE(to_jsonb(stok) ->> 'kd_sektor', '(NULL)')          AS kd_sektor,
    COALESCE(to_jsonb(stok) ->> 'kd_lokasi', '(NULL)')          AS kd_lokasi,
    COALESCE(
        to_jsonb(stok) ->> 'kd_jenis_bgn',
        to_jsonb(stok) ->> 'kd_jenis',
        '(NULL)')                                               AS kd_jenis,
    COALESCE(
        to_jsonb(stok) ->> 'kd_tipe_bgn',
        to_jsonb(stok) ->> 'kd_tipe',
        '(NULL)')                                               AS kd_tipe
FROM public.sr_stok AS stok
WHERE UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) = 'RDB'
  AND UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) = '020';


/* ------------------------------------------------------------
 * QUERY 3
 * BARIS PEMBELI-nya, tanpa syarat flag_aktif.
 * Yang diperhatikan: flag_aktif harus Y agar ikut laporan.
 * ------------------------------------------------------------ */
SELECT
    BTRIM(CAST(pembeli.ppjb_id AS TEXT))                        AS ppjb_id,
    BTRIM(CAST(pembeli.nasabah_id AS TEXT))                     AS nasabah_id,
    COALESCE(CAST(pembeli.flag_aktif AS TEXT), '(NULL)')        AS flag_aktif
FROM public.sr_pembeli_ppjb AS pembeli
WHERE BTRIM(CAST(pembeli.ppjb_id AS TEXT)) IN (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT))
    FROM public.sr_ppjb AS ppjb
    WHERE BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')) = 'A.0018/SBKS/RMH/2024'
);
