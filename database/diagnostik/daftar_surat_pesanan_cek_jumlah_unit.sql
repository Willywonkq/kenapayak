/*
 * DIAGNOSTIK — Daftar Surat Pesanan: kenapa jumlah unitnya berbeda
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada perintah yang mengubah data maupun
 * struktur database. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * CARA PAKAI
 * 1. Samakan dulu isi CTE param di setiap query dengan filter yang Anda pakai
 *    di web. Nilainya bisa dilihat pada header laporan: baris Tanggal, dan
 *    baris Unit. Ubah flag_tgl menjadi '1' bila header menulis
 *    "Tanggal Entry SP", atau '2' bila menulis "Tanggal Surat Pesanan".
 * 2. Jalankan QUERY 0 lebih dulu untuk memastikan nama kolomnya.
 * 3. Jalankan QUERY 1, 2, dan 3, lalu bandingkan dengan angka di laporan.
 *
 * Semua kolom kode dibaca memakai to_jsonb(x) ->> 'nama_kolom' seperti yang
 * dilakukan model, sehingga kolom yang tidak ada menghasilkan NULL dan bukan
 * galat "column does not exist".
 */


/* =====================================================================
 * QUERY 0 — Nama kolom yang benar-benar ada
 * ===================================================================== */
SELECT
    table_name AS tabel,
    STRING_AGG(column_name, ', ' ORDER BY ordinal_position) AS kolom
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('sr_uang_muka', 'sr_stok', 'sr_bayar_uang_muka')
GROUP BY table_name
ORDER BY table_name;


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 *
 * Setiap kolom menghitung SURAT PESANAN yang masih tersisa pada tahap itu,
 * memakai urutan penyaringan yang sama dengan model. Kolom pertama yang
 * angkanya turun tajam adalah tahap yang membuang data.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-09' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           '2'::text         AS flag_tgl
),
um_norm AS (
    SELECT
        um.uang_muka_id,
        CASE WHEN param.flag_tgl = '1' THEN um.tgl_entry ELSE um.tgl_uang_muka END
            AS tgl_dipakai,
        um.stok_id,
        NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
            AS induk_kosong,
        UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
            AS aktif
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) AS perusahaan_key
    FROM public.sr_stok AS stok
),
sumber AS (
    SELECT
        um.uang_muka_id,
        um.induk_kosong,
        um.aktif,
        stok.stok_id IS NOT NULL                       AS stok_ketemu,
        stok.perusahaan_key = param.perusahaan         AS perusahaan_cocok,
        EXISTS (
            SELECT 1 FROM public.sr_bayar_uang_muka AS bum
            WHERE BTRIM(CAST(bum.uang_muka_id AS text))
                = BTRIM(CAST(um.uang_muka_id AS text))
        )                                              AS ada_bayar
    FROM um_norm AS um
    CROSS JOIN param
    LEFT JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_dipakai >= param.tgl_awal
      AND um.tgl_dipakai <  param.tgl_akhir + INTERVAL '1 day'
)
SELECT
    COUNT(DISTINCT uang_muka_id)                                    AS t0_pada_rentang_tanggal,
    COUNT(DISTINCT uang_muka_id) FILTER (WHERE induk_kosong)        AS t1_parent_id_kosong,
    COUNT(DISTINCT uang_muka_id) FILTER (WHERE induk_kosong AND aktif)
                                                                    AS t2_flag_aktif_a,
    COUNT(DISTINCT uang_muka_id) FILTER (WHERE induk_kosong AND aktif AND stok_ketemu)
                                                                    AS t3_stok_ketemu,
    COUNT(DISTINCT uang_muka_id) FILTER (
        WHERE induk_kosong AND aktif AND stok_ketemu AND perusahaan_cocok
    )                                                               AS t4_perusahaan_cocok,
    COUNT(DISTINCT uang_muka_id) FILTER (
        WHERE induk_kosong AND aktif AND stok_ketemu AND perusahaan_cocok AND ada_bayar
    )                                                               AS t5_punya_baris_bayar
FROM sumber;


/* =====================================================================
 * QUERY 2 — Jumlah unit dan total nominal yang seharusnya tampil
 *
 * Angka t4 pada QUERY 1 adalah yang seharusnya muncul pada baris TOTAL
 * laporan. Query ini menghitungnya sekali lagi berikut nominalnya, supaya
 * bisa dibandingkan langsung dengan angka di layar.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-09' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           '2'::text         AS flag_tgl
),
terpilih AS (
    SELECT DISTINCT
        um.uang_muka_id,
        COALESCE(um.dpp, 0)        AS dpp,
        COALESCE(um.harga_jual, 0) AS harga_jual
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = um.stok_id
    WHERE (CASE WHEN param.flag_tgl = '1' THEN um.tgl_entry ELSE um.tgl_uang_muka END)
              >= param.tgl_awal
      AND (CASE WHEN param.flag_tgl = '1' THEN um.tgl_entry ELSE um.tgl_uang_muka END)
              <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
)
SELECT
    COUNT(*)              AS jumlah_unit,
    SUM(dpp)              AS total_harga_dpp,
    SUM(harga_jual)       AS total_harga_jual
FROM terpilih;


/* =====================================================================
 * QUERY 3 — Sebaran per tahun
 *
 * Paling berguna untuk melihat apakah ada periode yang datanya belum ikut
 * tersalin. Bandingkan dengan sebaran pada laporan desktop.
 * ===================================================================== */
WITH param AS (
    SELECT 'DTSA'::text AS perusahaan
)
SELECT
    EXTRACT(YEAR FROM um.tgl_uang_muka)::integer AS tahun,
    COUNT(DISTINCT um.uang_muka_id)              AS jumlah_unit,
    MIN(um.tgl_uang_muka)::date                  AS tanggal_paling_awal,
    MAX(um.tgl_uang_muka)::date                  AS tanggal_paling_akhir
FROM public.sr_uang_muka AS um
CROSS JOIN param
INNER JOIN public.sr_stok AS stok
        ON stok.stok_id = um.stok_id
WHERE NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
  AND UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
  AND UPPER(BTRIM(COALESCE(
        NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
        NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
        NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
        ''))) = param.perusahaan
  AND um.tgl_uang_muka IS NOT NULL
GROUP BY 1
ORDER BY 1;


/* =====================================================================
 * CARA MEMBACA
 *
 * Bandingkan t4 pada QUERY 1 dengan angka TOTAL di laporan web.
 *
 * - t4 sama dengan angka di web, tetapi jauh di bawah desktop
 *   -> penyaringannya sudah benar dan datanya memang segitu. Lihat QUERY 3
 *      untuk mencari tahun mana yang datanya kurang.
 *
 * - t4 jauh lebih besar daripada angka di web
 *   -> ada tahap sesudah penyaringan yang membuang baris. Kirimkan hasilnya
 *      ke saya, karena berarti masih ada yang perlu diperbaiki di model.
 *
 * - t1 atau t2 yang turun tajam
 *   -> banyak surat pesanan berstatus revisi atau tidak aktif. Bandingkan
 *      dengan desktop, karena desktop memakai syarat yang sama.
 *
 * - t3 turun
 *   -> ada surat pesanan yang stok_id-nya tidak ditemukan di sr_stok.
 *
 * - t4 turun tajam dari t3
 *   -> sebagian besar stok bukan milik unit tersebut. Pastikan nilai
 *      perusahaan pada CTE param sama dengan baris Unit di header laporan.
 * ===================================================================== */
