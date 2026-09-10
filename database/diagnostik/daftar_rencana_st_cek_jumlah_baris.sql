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


/* =====================================================================
 * ==== LANJUTAN — mencari kolom waktu yang sebenarnya ====
 *
 * Hasil QUERY 1: 1757, 1266, 1213, 1213, 1211, 775, 569, 775.
 * t6_web_sebelum 569 sama dengan angka web yang lama, dan t7_web_sesudah 775
 * sama dengan angka web sesudah perbaikan. Selisih keduanya 206, sama dengan
 * jumlah PPJB pada QUERY 3. Bagian join tipe sudah beres.
 *
 * Sisa selisih terhadap desktop tinggal 849 dikurangi 775, yaitu 74 baris,
 * dan penyaring rentang tanggal membuang 436 baris (1211 menjadi 775).
 *
 * Hasil QUERY 2 menunjukkan sebabnya: tipe_waktu_terisi bernilai 0 dari 2854
 * baris sr_tipe. Kolom waktu sama sekali kosong, sehingga tanggal Rencana
 * Serah Terima dihitung sebagai tanggal PPJB ditambah nol bulan.
 *
 * Hasil QUERY 4 memberi petunjuk pentingnya: kd_jenis dan kd_tipe pada
 * sr_stok kosong, sedangkan nilainya ada di kd_jenis_bgn dan kd_tipe_bgn.
 * Pola penamaan yang sama sangat mungkin berlaku juga di sr_tipe, sehingga
 * nilai waktu boleh jadi tersimpan di kolom bernama lain.
 *
 * Tiga query berikut mencarinya. Semuanya tetap hanya membaca.
 * ===================================================================== */


/* =====================================================================
 * QUERY 5 — Seluruh kolom sr_tipe berikut tingkat keterisiannya
 *
 * Dibaca lewat jsonb sehingga tidak perlu tahu nama kolomnya lebih dulu.
 * Cari kolom yang terisinya banyak dan contoh isinya berupa angka bulan
 * seperti 6, 12, atau 24.
 * ===================================================================== */
SELECT
    e.k                                                            AS kolom,
    COUNT(*) FILTER (WHERE e.v IS NOT NULL AND BTRIM(e.v) <> '')   AS terisi,
    COUNT(*)                                                       AS total_baris,
    (ARRAY_AGG(DISTINCT BTRIM(e.v))
        FILTER (WHERE e.v IS NOT NULL AND BTRIM(e.v) <> ''))[1:8]  AS contoh_isi
FROM public.sr_tipe AS t
CROSS JOIN LATERAL jsonb_each_text(to_jsonb(t)) AS e(k, v)
GROUP BY e.k
ORDER BY terisi DESC, kolom;


/* =====================================================================
 * QUERY 6 — Isi lengkap satu baris sr_tipe yang jawabannya sudah diketahui
 *
 * Desktop menampilkan GL/002 dengan PPJB J.0008/DTSA/RMH/2025 tanggal
 * 17-10-2025 dan Rencana Serah Terima 17-04-2026, yaitu tambah enam bulan,
 * sedangkan tgl_rencana_sb-nya kosong. Berarti di SQL Server tipe RMH/R1566
 * mempunyai waktu bernilai 6.
 *
 * Query ini menampilkan seluruh isi baris tipe itu. Kolom yang bernilai 6
 * itulah kolom waktu yang sebenarnya.
 * ===================================================================== */
SELECT
    e.k AS kolom,
    e.v AS isi
FROM public.sr_tipe AS t
CROSS JOIN LATERAL jsonb_each_text(to_jsonb(t)) AS e(k, v)
WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text))) = 'RMH'
  AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))  = 'R1566'
ORDER BY e.k;


/* =====================================================================
 * QUERY 7 — Kolom mana yang membuat jumlah barisnya menjadi 849
 *
 * Untuk setiap kolom sr_tipe yang isinya berupa angka wajar bagi jumlah
 * bulan, query ini menghitung ulang berapa baris yang masuk rentang tanggal
 * bila kolom itu dipakai sebagai waktu.
 *
 * Kolom yang menghasilkan angka mendekati 849 itulah yang harus dipakai
 * model. Baris kolom_waktu bertuliskan (tanpa waktu) adalah keadaan
 * sekarang, dan angkanya harus 775.
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
jenis_norm AS (
    SELECT
        UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis,
        BTRIM(COALESCE(to_jsonb(jb) ->> 'flag_laporan', '')) AS flag_laporan
    FROM public.sr_jenis_bangunan AS jb
),
baris AS (
    SELECT
        p.tgl_ppjb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '')
                  ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(to_jsonb(p) ->> 'tgl_rencana_sb' AS timestamp) END AS tgl_rencana_sb,
        to_jsonb(t) AS tipe_json
    FROM public.sr_ppjb AS p
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    INNER JOIN public.sr_pembeli_ppjb AS pb
            ON pb.ppjb_id = p.ppjb_id
           AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
    LEFT JOIN public.sr_tipe AS t
           ON UPPER(BTRIM(CAST(t.kd_jenis AS text))) = stok.jenis_key
          AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))  = stok.tipe_key
    LEFT JOIN jenis_norm AS jn
           ON jn.kd_jenis = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
    WHERE stok.perusahaan_key = param.perusahaan
      AND stok.flag_aktif = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
      AND stok.blok <> '' AND stok.nomor <> ''
      AND (
            (stok.blok || '/' || stok.nomor BETWEEN param.blok_awal AND param.blok_akhir)
            OR (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)
      )
      AND COALESCE(jn.flag_laporan, '') <> '2'
),
kandidat AS (
    SELECT e.k AS kolom
    FROM public.sr_tipe AS t
    CROSS JOIN LATERAL jsonb_each_text(to_jsonb(t)) AS e(k, v)
    WHERE e.v ~ '^[0-9]{1,3}$'
      AND CAST(e.v AS integer) BETWEEN 1 AND 120
    GROUP BY e.k

    UNION ALL

    SELECT '(tanpa waktu)'
)
SELECT
    kandidat.kolom AS kolom_waktu,
    COUNT(*) FILTER (
        WHERE hitung.rencana >= param.tgl_awal
          AND hitung.rencana <  param.tgl_akhir + INTERVAL '1 day'
    ) AS baris_dalam_rentang
FROM baris
CROSS JOIN param
CROSS JOIN kandidat
CROSS JOIN LATERAL (
    SELECT COALESCE(
        baris.tgl_rencana_sb,
        baris.tgl_ppjb + (
            CASE
                WHEN kandidat.kolom <> '(tanpa waktu)'
                 AND COALESCE(baris.tipe_json ->> kandidat.kolom, '') ~ '^[0-9]{1,3}$'
                THEN CAST(baris.tipe_json ->> kandidat.kolom AS integer)
                ELSE 0
            END * INTERVAL '1 month'
        )
    ) AS rencana
) AS hitung
GROUP BY kandidat.kolom
ORDER BY baris_dalam_rentang DESC, kolom_waktu;


/* =====================================================================
 * ==== LANJUTAN — kolom waktu memang tidak ada di sr_tipe ====
 *
 * QUERY 5 mendaftar seluruh 20 kolom sr_tipe:
 *     flag_aktif, kd_jenis, kd_mata_uang, kd_perusahaan, kd_tipe,
 *     tgl_entry, user_entry, status, deskripsi, ukuran_kav, luas_tanah,
 *     luas_bangunan, listrik, harga_cash, harga, harga_tahap, harga_kpr,
 *     jml_lantai, jml_kamar, harga_selisih_luas
 * Tidak ada kolom waktu, dan tidak ada kolom berakhiran _bgn.
 *
 * QUERY 6 menampilkan seluruh isi baris tipe RMH/R1566, yaitu tipe milik
 * GL/002. Desktop menghitung tanggal PPJB ditambah enam bulan untuk unit itu,
 * tetapi tidak ada satu pun kolom yang bernilai 6.
 *
 * QUERY 7 mencoba setiap kolom yang isinya angka wajar sebagai pengganti
 * waktu. Semuanya menghasilkan 775, sama dengan keadaan tanpa waktu, jadi
 * tidak ada kolom yang bisa menggantikannya.
 *
 * Kesimpulannya kolom WAKTU pada tabel TIPE di SQL Server belum ikut
 * tersalin ke sr_tipe. Tidak ada yang bisa diperbaiki di model, karena
 * nilainya memang tidak tersedia di PostgreSQL.
 *
 * Tiga query berikut memastikan nilainya tidak tersimpan di tempat lain,
 * dan mengukur seberapa luas dampaknya. Semuanya tetap hanya membaca.
 * ===================================================================== */


/* =====================================================================
 * QUERY 8 — Cari kolom bernuansa lama waktu di seluruh skema
 *
 * Barangkali nilainya tersimpan di tabel lain dengan nama berbeda.
 * ===================================================================== */
SELECT
    table_name  AS tabel,
    column_name AS kolom,
    data_type   AS tipe_data
FROM information_schema.columns
WHERE table_schema = 'public'
  AND (
        column_name ILIKE '%waktu%'
     OR column_name ILIKE '%lama%'
     OR column_name ILIKE '%bulan%'
     OR column_name ILIKE '%durasi%'
     OR column_name ILIKE '%tempo%'
     OR column_name ILIKE '%jangka%'
     OR column_name ILIKE '%month%'
  )
ORDER BY table_name, column_name;


/* =====================================================================
 * QUERY 9 — Tabel yang mungkin menyimpan master tipe bangunan
 * ===================================================================== */
SELECT
    table_name AS tabel,
    COUNT(*)   AS jumlah_kolom,
    STRING_AGG(column_name, ', ' ORDER BY ordinal_position) AS kolom
FROM information_schema.columns
WHERE table_schema = 'public'
  AND (
        table_name ILIKE '%tipe%'
     OR table_name ILIKE '%bangun%'
     OR table_name ILIKE '%serah%'
  )
GROUP BY table_name
ORDER BY table_name;


/* =====================================================================
 * QUERY 10 — Seberapa luas dampaknya pada kolom Rencana Serah Terima
 *
 * Ketika tgl_rencana_sb terisi, tanggal rencana diambil dari sana dan
 * hasilnya sudah benar. Ketika kosong, tanggal rencana jatuh menjadi sama
 * dengan tanggal PPJB karena waktu tidak tersedia, dan itu keliru.
 *
 * Query ini menghitung keduanya khusus untuk baris yang tampil di laporan.
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
jenis_norm AS (
    SELECT
        UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis,
        BTRIM(COALESCE(to_jsonb(jb) ->> 'flag_laporan', '')) AS flag_laporan
    FROM public.sr_jenis_bangunan AS jb
),
baris AS (
    SELECT
        p.tgl_ppjb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '')
                  ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(to_jsonb(p) ->> 'tgl_rencana_sb' AS timestamp) END AS tgl_rencana_sb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'waktu_add', '') ~ '^[+-]?[0-9]+$'
             THEN CAST(to_jsonb(p) ->> 'waktu_add' AS integer) END AS waktu_add
    FROM public.sr_ppjb AS p
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    INNER JOIN public.sr_pembeli_ppjb AS pb
            ON pb.ppjb_id = p.ppjb_id
           AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
    LEFT JOIN public.sr_tipe AS t
           ON UPPER(BTRIM(CAST(t.kd_jenis AS text))) = stok.jenis_key
          AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))  = stok.tipe_key
    LEFT JOIN jenis_norm AS jn
           ON jn.kd_jenis = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
    WHERE stok.perusahaan_key = param.perusahaan
      AND stok.flag_aktif = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
      AND stok.blok <> '' AND stok.nomor <> ''
      AND (
            (stok.blok || '/' || stok.nomor BETWEEN param.blok_awal AND param.blok_akhir)
            OR (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)
      )
      AND COALESCE(jn.flag_laporan, '') <> '2'
      AND COALESCE(p.tgl_rencana_sb, p.tgl_ppjb) >= param.tgl_awal
      AND COALESCE(p.tgl_rencana_sb, p.tgl_ppjb) <  param.tgl_akhir + INTERVAL '1 day'
)
SELECT
    COUNT(*)                                              AS baris_tampil,
    COUNT(*) FILTER (WHERE tgl_rencana_sb IS NOT NULL)    AS tanggal_rencana_benar,
    COUNT(*) FILTER (WHERE tgl_rencana_sb IS NULL
                       AND waktu_add IS NOT NULL)         AS memakai_waktu_add,
    COUNT(*) FILTER (WHERE tgl_rencana_sb IS NULL
                       AND waktu_add IS NULL)             AS jatuh_ke_tanggal_ppjb
FROM baris;


/* =====================================================================
 * CARA MEMBACA LANJUTAN
 *
 * - QUERY 8 dan 9 tidak menemukan kolom lama waktu di mana pun
 *   -> nilainya memang belum tersalin. Yang perlu dilakukan adalah menyalin
 *      kolom WAKTU dari tabel TIPE di SQL Server ke sr_tipe. Itu perubahan
 *      struktur dan data, harus dikerjakan oleh yang berwenang.
 *
 * - QUERY 8 menemukan kolomnya di tabel lain
 *   -> kirimkan hasilnya, modelnya tinggal diarahkan ke sana.
 *
 * - jatuh_ke_tanggal_ppjb pada QUERY 10 menunjukkan berapa baris yang kolom
 *   Rencana Serah Terima-nya sekarang keliru, yaitu menampilkan tanggal PPJB
 *   dan bukan tanggal rencana yang sebenarnya.
 *
 * Model tidak perlu diubah untuk ini. reportSafeInteger sudah memeriksa
 * keberadaan kolom lebih dulu dan memakai nol bila tidak ada, jadi begitu
 * kolom waktu ditambahkan ke sr_tipe dan diisi, laporannya langsung benar
 * dengan sendirinya.
 * ===================================================================== */


/* =====================================================================
 * ==== KESIMPULAN — kolomnya ada, tetapi di tabel yang berbeda ====
 *
 * QUERY 8 menemukannya:
 *     sr_ppjb   bulan           numeric
 *     sr_ppjb   jangka_waktu    numeric
 *     sr_ppjb   waktu           numeric   <-- ini yang dicari
 *     sr_ppjb   waktu_add       numeric
 *     sr_ppjb   waktu_ppn_dtp   numeric
 *
 * QUERY 9 memastikan sr_tipe hanya punya 20 kolom dan tidak ada waktu di
 * antaranya.
 *
 * Pada query desktop nama kolomnya ditulis tanpa nama tabel:
 *     ISNULL(TGL_RENCANA_SB, DATEADD(month, ISNULL(waktu, 0), TGL_PPJB))
 * sehingga sempat dikira milik TIPE. SQL Server menolak nama kolom yang ada
 * di lebih dari satu tabel pada FROM, dan query itu berjalan normal, jadi
 * hanya satu tabel yang memilikinya. Karena sr_tipe tidak punya dan sr_ppjb
 * punya, yang dimaksud adalah PPJB.WAKTU, satu tabel dengan TGL_RENCANA_SB
 * dan waktu_add pada rumus yang sama.
 *
 * Model sudah diperbaiki untuk membaca sr_ppjb.waktu. Jadi ini bukan data
 * yang belum tersalin, melainkan model yang membaca tabel yang salah.
 *
 * QUERY 10 menunjukkan luas dampaknya sebelum perbaikan:
 *     775 baris tampil, hanya 1 yang tanggal rencananya benar,
 *     dan 774 jatuh menjadi tanggal PPJB.
 * ===================================================================== */


/* =====================================================================
 * QUERY 11 — Pastikan jumlah barisnya menjadi 849
 *
 * Menghitung ulang jumlah baris laporan dengan tiga cara sekaligus, supaya
 * hasil perbaikan bisa dipastikan sebelum membuka web.
 *
 *   tanpa_waktu        = keadaan sebelum perbaikan, harus 775
 *   pakai_ppjb_waktu   = sesudah perbaikan, harus mendekati 849
 *   pakai_waktu_add    = bila waktu_add ikut diutamakan, sebagai pembanding
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
jenis_norm AS (
    SELECT
        UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis,
        BTRIM(COALESCE(to_jsonb(jb) ->> 'flag_laporan', '')) AS flag_laporan
    FROM public.sr_jenis_bangunan AS jb
),
baris AS (
    SELECT
        p.tgl_ppjb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_rencana_sb', '')
                  ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(to_jsonb(p) ->> 'tgl_rencana_sb' AS timestamp) END AS tgl_rencana_sb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'waktu', '') ~ '^[+-]?[0-9]+([.][0-9]+)?$'
             THEN CAST(CAST(to_jsonb(p) ->> 'waktu' AS numeric) AS integer) END AS waktu,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'waktu_add', '') ~ '^[+-]?[0-9]+([.][0-9]+)?$'
             THEN CAST(CAST(to_jsonb(p) ->> 'waktu_add' AS numeric) AS integer) END AS waktu_add
    FROM public.sr_ppjb AS p
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    INNER JOIN public.sr_pembeli_ppjb AS pb
            ON pb.ppjb_id = p.ppjb_id
           AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
    LEFT JOIN public.sr_tipe AS t
           ON UPPER(BTRIM(CAST(t.kd_jenis AS text))) = stok.jenis_key
          AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))  = stok.tipe_key
    LEFT JOIN jenis_norm AS jn
           ON jn.kd_jenis = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
    WHERE stok.perusahaan_key = param.perusahaan
      AND stok.flag_aktif = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
      AND stok.blok <> '' AND stok.nomor <> ''
      AND (
            (stok.blok || '/' || stok.nomor BETWEEN param.blok_awal AND param.blok_akhir)
            OR (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)
      )
      AND COALESCE(jn.flag_laporan, '') <> '2'
)
SELECT
    COUNT(*) FILTER (
        WHERE COALESCE(tgl_rencana_sb, tgl_ppjb) >= param.tgl_awal
          AND COALESCE(tgl_rencana_sb, tgl_ppjb) <  param.tgl_akhir + INTERVAL '1 day'
    ) AS tanpa_waktu,
    COUNT(*) FILTER (
        WHERE COALESCE(tgl_rencana_sb,
                       tgl_ppjb + (COALESCE(waktu, 0) * INTERVAL '1 month')) >= param.tgl_awal
          AND COALESCE(tgl_rencana_sb,
                       tgl_ppjb + (COALESCE(waktu, 0) * INTERVAL '1 month'))
                  <  param.tgl_akhir + INTERVAL '1 day'
    ) AS pakai_ppjb_waktu,
    COUNT(*) FILTER (
        WHERE COALESCE(tgl_rencana_sb,
                       tgl_ppjb + (COALESCE(waktu_add, waktu, 0) * INTERVAL '1 month')) >= param.tgl_awal
          AND COALESCE(tgl_rencana_sb,
                       tgl_ppjb + (COALESCE(waktu_add, waktu, 0) * INTERVAL '1 month'))
                  <  param.tgl_akhir + INTERVAL '1 day'
    ) AS pakai_waktu_add,
    COUNT(*) FILTER (WHERE waktu IS NOT NULL) AS baris_waktu_terisi,
    MIN(waktu) AS waktu_terkecil,
    MAX(waktu) AS waktu_terbesar
FROM baris
CROSS JOIN param;
