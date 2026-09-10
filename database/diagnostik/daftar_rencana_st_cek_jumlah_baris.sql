/*
 * DIAGNOSTIK — Daftar Rencana Serah Terima
 * Kenapa desktop menampilkan 800-an baris sedangkan web hanya 500-an.
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Samakan dulu isi CTE param dengan filter di layar, lalu jalankan berurutan.
 * Semua kolom dibaca lewat to_jsonb(x) ->> 'kolom' supaya kolom yang tidak ada
 * menghasilkan NULL dan bukan galat "column does not exist".
 */


/* =====================================================================
 * QUERY 0 — Nama kolom yang benar-benar ada
 *
 * Yang perlu dipastikan keberadaannya: tgl_rencana_sb dan waktu_add pada
 * sr_ppjb, waktu pada sr_tipe, serta flag_laporan pada sr_jenis_bangunan.
 * Kalau salah satunya tidak ada, tanggal Rencana Serah Terima dihitung
 * memakai nilai bawaan nol dan hasilnya berbeda dengan desktop.
 * ===================================================================== */
SELECT
    table_name AS tabel,
    STRING_AGG(column_name, ', ' ORDER BY ordinal_position) AS kolom
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN (
      'sr_ppjb', 'sr_stok', 'sr_tipe', 'sr_jenis_bangunan',
      'sr_pembeli_ppjb', 'sr_nasabah', 'sr_serah_terima'
  )
GROUP BY table_name
ORDER BY table_name;


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 *
 * Menghitung BARIS laporan, yaitu satu baris untuk setiap pasangan PPJB
 * dan pembeli aktif, sama seperti desktop yang menyambung PEMBELI_PPJB
 * dengan join lama sehingga PPJB berpembeli dua muncul dua kali.
 *
 * t6_web_sebelum  = perilaku INNER JOIN sr_tipe, yaitu angka web yang lama
 * t7_web_sesudah  = perilaku LEFT JOIN sr_tipe, yaitu angka web sesudah
 *                   perbaikan, dan inilah yang seharusnya sama dengan desktop
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           'A'::text         AS blok_awal,
           'ZZ'::text        AS blok_akhir
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), ''))) AS nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) AS flag_aktif,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis_bgn'), ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe_bgn'), ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(t.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(t.kd_tipe AS text)))  AS kd_tipe,
        CASE WHEN COALESCE(to_jsonb(t) ->> 'waktu', '') ~ '^[+-]?[0-9]+$'
             THEN CAST(to_jsonb(t) ->> 'waktu' AS integer) END AS waktu
    FROM public.sr_tipe AS t
),
jenis_norm AS (
    SELECT
        UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis,
        BTRIM(COALESCE(to_jsonb(jb) ->> 'flag_laporan', '')) AS flag_laporan
    FROM public.sr_jenis_bangunan AS jb
),
baris AS (
    SELECT
        p.ppjb_id,
        stok.flag_aktif = 'A'                                    AS stok_aktif,
        UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A' AS ppjb_aktif,
        NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL AS induk_kosong,
        (stok.blok <> '' AND stok.nomor <> '')                   AS blok_terisi,
        (
            (stok.blok || '/' || stok.nomor BETWEEN param.blok_awal AND param.blok_akhir)
            OR (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)
        )                                                        AS blok_dalam_rentang,
        (t.kd_jenis IS NOT NULL)                                 AS tipe_ketemu,
        COALESCE(jn.flag_laporan, '') <> '2'                     AS non_kavling,
        (
            COALESCE(
                CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '')
                          ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                     THEN CAST(to_jsonb(p) ->> 'tgl_rencana_sb' AS timestamp) END,
                p.tgl_ppjb + (COALESCE(t.waktu, 0) * INTERVAL '1 month')
            ) >= param.tgl_awal
            AND
            COALESCE(
                CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '')
                          ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                     THEN CAST(to_jsonb(p) ->> 'tgl_rencana_sb' AS timestamp) END,
                p.tgl_ppjb + (COALESCE(t.waktu, 0) * INTERVAL '1 month')
            ) < param.tgl_akhir + INTERVAL '1 day'
        )                                                        AS dalam_rentang
    FROM public.sr_ppjb AS p
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    INNER JOIN public.sr_pembeli_ppjb AS pb
            ON pb.ppjb_id = p.ppjb_id
           AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
    LEFT JOIN tipe_norm AS t
           ON t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
    LEFT JOIN jenis_norm AS jn ON jn.kd_jenis = t.kd_jenis
    WHERE stok.perusahaan_key = param.perusahaan
)
SELECT
    COUNT(*)                                                     AS t0_baris_perusahaan,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif)            AS t1_flag_aktif,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong)
                                                                 AS t2_induk_kosong,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang)   AS t3_blok_cocok,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND non_kavling)                          AS t4_non_kavling,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND non_kavling AND dalam_rentang)        AS t5_dalam_rentang,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND non_kavling AND dalam_rentang
                       AND tipe_ketemu)                          AS t6_web_sebelum,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND non_kavling AND dalam_rentang)        AS t7_web_sesudah
FROM baris;


/* =====================================================================
 * QUERY 2 — Kelengkapan bahan penghitung tanggal Rencana Serah Terima
 *
 * Desktop memakai rumus
 *     ISNULL(TGL_RENCANA_SB, DATEADD(month, ISNULL(waktu, 0), TGL_PPJB))
 * untuk menyaring rentang tanggal. Kalau tgl_rencana_sb kosong dan waktu
 * kosong, tanggal rencananya sama dengan tanggal PPJB, dan rentang tanggal
 * menangkap kumpulan PPJB yang sama sekali berbeda dengan desktop.
 * ===================================================================== */
SELECT
    (SELECT COUNT(*) FROM public.sr_ppjb)                                  AS ppjb_semua,
    (SELECT COUNT(*) FROM public.sr_ppjb AS p
      WHERE COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '') <> '')          AS ppjb_tgl_rencana_sb_terisi,
    (SELECT COUNT(*) FROM public.sr_ppjb AS p
      WHERE COALESCE(to_jsonb(p) ->> 'waktu_add', '') <> '')               AS ppjb_waktu_add_terisi,
    (SELECT COUNT(*) FROM public.sr_tipe)                                  AS tipe_semua,
    (SELECT COUNT(*) FROM public.sr_tipe AS t
      WHERE COALESCE(to_jsonb(t) ->> 'waktu', '') <> '')                   AS tipe_waktu_terisi,
    (SELECT COUNT(*) FROM public.sr_tipe AS t
      WHERE COALESCE(to_jsonb(t) ->> 'waktu', '') ~ '^[+-]?[0-9]+$'
        AND CAST(to_jsonb(t) ->> 'waktu' AS integer) > 0)                  AS tipe_waktu_lebih_dari_nol;


/* =====================================================================
 * QUERY 3 — Unit yang hilang, dikelompokkan menurut sebabnya
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) AS blok,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis_bgn'), ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe_bgn'), ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(t.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(t.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS t
),
hilang AS (
    SELECT
        stok.blok,
        stok.jenis_key,
        stok.tipe_key,
        p.ppjb_id,
        CASE
            WHEN stok.tipe_key = '' AND stok.jenis_key = ''
                THEN '1. kd_jenis dan kd_tipe pada sr_stok kosong'
            WHEN stok.tipe_key = ''
                THEN '2. kd_tipe pada sr_stok kosong'
            WHEN stok.jenis_key = ''
                THEN '3. kd_jenis pada sr_stok kosong'
            WHEN NOT EXISTS (SELECT 1 FROM tipe_norm t WHERE t.kd_jenis = stok.jenis_key)
                THEN '4. kd_jenis belum ada di sr_tipe'
            ELSE '5. pasangan kd_jenis + kd_tipe belum ada di sr_tipe'
        END AS sebab
    FROM public.sr_ppjb AS p
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    WHERE stok.perusahaan_key = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
      AND NOT EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
      )
)
SELECT
    sebab,
    COUNT(DISTINCT ppjb_id)                                 AS jumlah_ppjb,
    COUNT(DISTINCT blok)                                    AS jumlah_blok,
    STRING_AGG(DISTINCT blok, ', ' ORDER BY blok)           AS daftar_blok,
    STRING_AGG(DISTINCT jenis_key || '/' || tipe_key, ', ') AS contoh_kode
FROM hilang
GROUP BY sebab
ORDER BY jumlah_ppjb DESC;


/* =====================================================================
 * QUERY 4 — Pembanding satu unit dengan desktop
 *
 * Di desktop, GL/002 dengan PPJB J.0008/DTSA/RMH/2025 tanggal 17-10-2025
 * menampilkan Rencana Serah Terima 17-04-2026, yaitu tanggal PPJB ditambah
 * enam bulan. Query ini memperlihatkan bahan hitungannya di PostgreSQL.
 * ===================================================================== */
SELECT
    UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) || '/' ||
    UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))       AS blok,
    BTRIM(CAST(p.no_ppjb AS text))                             AS no_ppjb,
    p.tgl_ppjb::date                                           AS tgl_ppjb,
    to_jsonb(p) ->> 'tgl_rencana_sb'                           AS tgl_rencana_sb,
    to_jsonb(p) ->> 'waktu_add'                                AS waktu_add,
    '[' || COALESCE(to_jsonb(stok) ->> 'kd_jenis', '(kosong atau kolomnya tidak ada)') || ']'  AS kd_jenis,
    '[' || COALESCE(to_jsonb(stok) ->> 'kd_jenis_bgn', '(kosong atau kolomnya tidak ada)') || ']' AS kd_jenis_bgn,
    '[' || COALESCE(to_jsonb(stok) ->> 'kd_tipe', '(kosong atau kolomnya tidak ada)') || ']'   AS kd_tipe,
    '[' || COALESCE(to_jsonb(stok) ->> 'kd_tipe_bgn', '(kosong atau kolomnya tidak ada)') || ']' AS kd_tipe_bgn,
    (SELECT to_jsonb(t) ->> 'waktu'
       FROM public.sr_tipe AS t
      WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text))) = UPPER(BTRIM(COALESCE(
                NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis'), ''),
                NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis_bgn'), ''), '')))
        AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))  = UPPER(BTRIM(COALESCE(
                NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe'), ''),
                NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe_bgn'), ''), '')))
      LIMIT 1)                                                 AS waktu_dari_sr_tipe
FROM public.sr_ppjb AS p
INNER JOIN public.sr_stok AS stok ON stok.stok_id = p.stok_id
WHERE BTRIM(CAST(p.no_ppjb AS text)) IN (
    'J.0008/DTSA/RMH/2025',
    'L.0018/DTSA/RMH/2025',
    'K.0001/DTSA/RMH/2024'
)
ORDER BY 1;


/* =====================================================================
 * CARA MEMBACA
 *
 * - t6_web_sebelum sama dengan angka web yang lama, dan t7_web_sesudah
 *   mendekati angka desktop
 *   -> penyebabnya join sr_tipe, dan perbaikan pada model sudah menanganinya.
 *
 * - t7_web_sesudah masih jauh di bawah desktop
 *   -> lihat t5_dalam_rentang dibandingkan t4_non_kavling. Kalau banyak yang
 *      gugur di situ, tanggal Rencana Serah Terima-nya salah hitung. Periksa
 *      QUERY 2: bila tipe_waktu_terisi bernilai 0, kolom waktu pada sr_tipe
 *      belum tersalin, sehingga tanggal rencana menjadi sama dengan tanggal
 *      PPJB dan rentang tanggal menangkap PPJB yang berbeda dari desktop.
 *
 * - QUERY 4 membandingkan satu unit secara langsung. Bila waktu_dari_sr_tipe
 *   kosong padahal desktop menghitung enam bulan, berarti kolom waktu pada
 *   sr_tipe memang belum terisi.
 * ===================================================================== */
