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


/* =====================================================================
 * ==== LANJUTAN — dipakai bila QUERY 1 turun tajam dari t3 ke t4 ====
 *
 * Berkas ini tetap hanya membaca. Tidak ada perintah yang mengubah data
 * maupun struktur database.
 * ===================================================================== */


/* =====================================================================
 * QUERY 4 — Semua kode perusahaan yang ada, berikut rentang tanggalnya
 *
 * Menjawab: apakah surat pesanan tahun 2024 ke atas benar-benar tidak ada,
 * atau ada tetapi kode perusahaannya bukan 'DTSA'.
 *
 * Kolom kode_perusahaan dihitung dengan rumus yang sama persis dengan model
 * web: COALESCE(kd_perusahaan, kd_unit, kd_pt).
 * ===================================================================== */
WITH um_norm AS (
    SELECT
        um.uang_muka_id,
        um.stok_id,
        um.tgl_uang_muka,
        to_jsonb(um) ->> 'tgl_entry' AS tgl_entry_teks
    FROM public.sr_uang_muka AS um
    WHERE NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
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
)
SELECT
    CASE WHEN stok.perusahaan_key = '' THEN '(kosong)' ELSE stok.perusahaan_key END
                                                              AS kode_perusahaan,
    COUNT(DISTINCT um.uang_muka_id)                           AS jumlah_unit,
    MIN(um.tgl_uang_muka)::date                               AS tgl_paling_awal,
    MAX(um.tgl_uang_muka)::date                               AS tgl_paling_akhir,
    COUNT(DISTINCT um.uang_muka_id)
        FILTER (WHERE um.tgl_uang_muka >= DATE '2024-03-01')  AS unit_mulai_maret_2024,
    COUNT(DISTINCT um.uang_muka_id)
        FILTER (WHERE um.tgl_uang_muka >= DATE '2026-01-01')  AS unit_tahun_2026
FROM um_norm AS um
INNER JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
GROUP BY 1
ORDER BY jumlah_unit DESC;


/* =====================================================================
 * QUERY 5 — Contoh surat pesanan setelah Februari 2024
 *
 * Menampilkan 40 baris beserta nilai mentah kolom kode di sr_stok, supaya
 * kelihatan kolom mana yang kosong dan kode apa yang sebenarnya terisi.
 * ===================================================================== */
SELECT
    to_jsonb(um) ->> 'no_uang_muka'    AS no_surat_pesanan,
    um.tgl_uang_muka::date             AS tgl_surat_pesanan,
    um.stok_id,
    to_jsonb(stok) ->> 'blok'          AS blok,
    to_jsonb(stok) ->> 'nomor'         AS nomor,
    to_jsonb(stok) ->> 'kd_perusahaan' AS kd_perusahaan_mentah,
    to_jsonb(stok) ->> 'kd_unit'       AS kd_unit_mentah,
    to_jsonb(stok) ->> 'kd_pt'         AS kd_pt_mentah,
    to_jsonb(stok) ->> 'kd_sektor'     AS kd_sektor_mentah,
    to_jsonb(stok) ->> 'kd_proyek'     AS kd_proyek_mentah,
    to_jsonb(stok) ->> 'kd_lokasi'     AS kd_lokasi_mentah
FROM public.sr_uang_muka AS um
INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
WHERE NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
  AND UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
  AND um.tgl_uang_muka >= DATE '2024-03-01'
ORDER BY um.tgl_uang_muka DESC
LIMIT 40;


/* =====================================================================
 * QUERY 6 — Kalau penyaringan unit memakai master sektor, bukan sr_stok
 *
 * Desktop mungkin menentukan unit lewat sr_sektor.kd_perusahaan, bukan lewat
 * sr_stok. Query ini menghitung jumlah unit bila penyaringannya dari sana.
 * Bandingkan jumlah_unit_lewat_sektor dengan angka desktop.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-09' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_sektor', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_proyek', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_cluster', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_lokasi', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_lv2', ''),
            ''))) AS sektor_key
    FROM public.sr_stok AS stok
),
sektor_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(s) ->> 'kd_sektor', ''),
            NULLIF(to_jsonb(s) ->> 'kd_proyek', ''),
            NULLIF(to_jsonb(s) ->> 'kd_cluster', ''),
            NULLIF(to_jsonb(s) ->> 'kd_lokasi', ''),
            NULLIF(to_jsonb(s) ->> 'kd_lv2', ''),
            ''))) AS kode,
        UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_perusahaan', ''))) AS kd_perusahaan
    FROM public.sr_sektor AS s
)
SELECT
    COUNT(DISTINCT um.uang_muka_id)
        FILTER (WHERE stok.perusahaan_key = param.perusahaan)    AS jumlah_unit_lewat_stok,
    COUNT(DISTINCT um.uang_muka_id)
        FILTER (WHERE sektor.kd_perusahaan = param.perusahaan)   AS jumlah_unit_lewat_sektor,
    COUNT(DISTINCT um.uang_muka_id) FILTER (
        WHERE stok.perusahaan_key = param.perusahaan
           OR sektor.kd_perusahaan = param.perusahaan
    )                                                            AS jumlah_unit_salah_satu
FROM public.sr_uang_muka AS um
CROSS JOIN param
INNER JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
LEFT JOIN sektor_norm AS sektor ON sektor.kode = stok.sektor_key
WHERE NULLIF(BTRIM(COALESCE(to_jsonb(um) ->> 'parent_id', '')), '') IS NULL
  AND UPPER(BTRIM(COALESCE(to_jsonb(um) ->> 'flag_aktif', ''))) = 'A'
  AND um.tgl_uang_muka >= param.tgl_awal
  AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day';
