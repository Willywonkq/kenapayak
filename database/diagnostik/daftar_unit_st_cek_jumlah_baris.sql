/*
 * DIAGNOSTIK — Daftar Unit ST dan Migrasi TM
 * Kenapa desktop menampilkan 596 baris sedangkan web hanya 588.
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * CATATAN PENTING TENTANG QUERY DESKTOP
 *
 * Bentuknya berbeda jauh dari tiga laporan sebelumnya:
 *
 * 1. Titik berangkatnya STOK, bukan SERAH_TERIMA maupun PPJB.
 * 2. SERAH_TERIMA disambung dengan LEFT OUTER JOIN dan TANPA dibatasi satu
 *    baris. Jadi PPJB yang punya dua catatan serah terima menghasilkan DUA
 *    baris laporan. Ini paling terasa ketika Sts BAST dipilih Semua.
 * 3. TIPE dan JENIS_BANGUNAN sudah LEFT OUTER JOIN sejak di desktop, dan
 *    JENIS_BANGUNAN disambung langsung ke STOK.KD_JENIS, bukan lewat TIPE.
 * 4. Tidak ada sambungan ke PEMBELI_PPJB maupun NASABAH. Nama pembeli
 *    diambil dari fungsi F_GET_PEMBELI, jadi tidak ada pelipatan baris
 *    karena pembeli.
 * 5. Ada syarat STOK.PARENT_ID IS NULL yang tidak ada di laporan lain.
 * 6. PPJB yang batal ikut tampil bila FLAG_BATAL = 'Y' dan serah terimanya
 *    sudah terealisasi. Baris seperti itu diberi awalan ** pada blok.
 * 7. Kotak berlabel "Tgl. Surat ST" sebenarnya dibandingkan dengan
 *    PPJB.TGL_PPJB, bukan dengan tanggal surat serah terima.
 *
 * Samakan dulu isi CTE param dengan filter di layar, lalu jalankan.
 */


/* =====================================================================
 * QUERY 1 — Corong penyaringan, mengikuti urutan syarat desktop
 *
 * jumlah_baris dihitung apa adanya, dan jumlah_ppjb sebagai pembanding.
 * Bila keduanya berbeda pada tahap akhir, berarti ada PPJB yang punya
 * lebih dari satu catatan serah terima.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           DATE '2023-07-01' AS tgl_st1,
           DATE '2026-09-10' AS tgl_st2,
           'DTSA'::text      AS perusahaan,
           'A'::text         AS blok_awal,
           'Z'::text         AS blok_akhir,
           '*'::text         AS st_aktif
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), ''))) AS nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) AS flag_aktif,
        NULLIF(BTRIM(COALESCE(CAST(stok.parent_id AS text), '')), '') IS NULL
                                                             AS induk_kosong,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_sektor'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_proyek'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_cluster'), ''),
            ''))) AS sektor_key
    FROM public.sr_stok AS stok
),
baris AS (
    SELECT
        p.ppjb_id,
        stok.flag_aktif = 'A'                                        AS stok_aktif,
        stok.induk_kosong                                            AS stok_induk_kosong,
        (stok.blok <> '' AND stok.nomor <> '')                       AS blok_terisi,
        (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)     AS blok_dalam_rentang,
        NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
                                                                     AS ppjb_induk_kosong,
        UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), '')))       AS ppjb_flag_aktif,
        UPPER(BTRIM(COALESCE(to_jsonb(p) ->> 'flag_batal', 'T')))    AS ppjb_flag_batal,
        p.tgl_ppjb,
        CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_batal', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(to_jsonb(p) ->> 'tgl_batal' AS timestamp) END AS ppjb_tgl_batal,
        st.tgl_serah_terima,
        UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS text), 'A')))     AS st_flag_aktif
    FROM public.sr_stok AS stok_raw
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = stok_raw.stok_id
    INNER JOIN public.sr_ppjb AS p ON p.stok_id = stok.stok_id
    LEFT JOIN public.sr_serah_terima AS st ON st.ppjb_id = p.ppjb_id
    WHERE stok.perusahaan_key = param.perusahaan
)
SELECT
    COUNT(*)                                                   AS t0_baris_perusahaan,
    COUNT(*) FILTER (WHERE stok_aktif)                         AS t1_stok_aktif,
    COUNT(*) FILTER (WHERE stok_aktif AND stok_induk_kosong)   AS t2_stok_induk_kosong,
    COUNT(*) FILTER (WHERE stok_aktif AND stok_induk_kosong
                       AND blok_terisi AND blok_dalam_rentang) AS t3_blok_cocok,
    COUNT(*) FILTER (WHERE stok_aktif AND stok_induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND ppjb_induk_kosong)                  AS t4_ppjb_induk_kosong,
    COUNT(*) FILTER (WHERE stok_aktif AND stok_induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND ppjb_induk_kosong
                       AND (
                             (ppjb_flag_aktif = 'A'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day')
                          OR (ppjb_flag_aktif = 'T'
                              AND ppjb_flag_batal = 'Y'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
                              AND ppjb_tgl_batal > param.tgl_akhir
                              AND tgl_serah_terima IS NOT NULL)
                       ))                                      AS t5_syarat_ppjb,
    COUNT(*) FILTER (WHERE stok_aktif AND stok_induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND ppjb_induk_kosong
                       AND (
                             (ppjb_flag_aktif = 'A'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day')
                          OR (ppjb_flag_aktif = 'T'
                              AND ppjb_flag_batal = 'Y'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
                              AND ppjb_tgl_batal > param.tgl_akhir
                              AND tgl_serah_terima IS NOT NULL)
                       )
                       AND tgl_serah_terima >= param.tgl_st1
                       AND tgl_serah_terima <  param.tgl_st2 + INTERVAL '1 day')
                                                               AS t6_rentang_realisasi,
    COUNT(DISTINCT ppjb_id) FILTER (WHERE stok_aktif AND stok_induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND ppjb_induk_kosong
                       AND (
                             (ppjb_flag_aktif = 'A'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day')
                          OR (ppjb_flag_aktif = 'T'
                              AND ppjb_flag_batal = 'Y'
                              AND tgl_ppjb >= param.tgl_awal
                              AND tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
                              AND ppjb_tgl_batal > param.tgl_akhir
                              AND tgl_serah_terima IS NOT NULL)
                       )
                       AND tgl_serah_terima >= param.tgl_st1
                       AND tgl_serah_terima <  param.tgl_st2 + INTERVAL '1 day')
                                                               AS t6_jumlah_ppjb
FROM baris
CROSS JOIN param;


/* =====================================================================
 * QUERY 2 — PPJB yang punya lebih dari satu catatan serah terima
 *
 * Desktop menyambung SERAH_TERIMA tanpa membatasi satu baris, jadi PPJB
 * seperti ini menghasilkan lebih dari satu baris laporan. Kalau model web
 * hanya mengambil satu serah terima per PPJB, selisihnya muncul di sini.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_st1,
           DATE '2026-09-10' AS tgl_st2,
           'DTSA'::text      AS perusahaan
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), ''))) AS nomor,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key
    FROM public.sr_stok AS stok
)
SELECT
    stok.blok || '/' || stok.nomor                       AS blok_nomor,
    p.ppjb_id,
    COUNT(*)                                             AS jumlah_serah_terima,
    STRING_AGG(
        COALESCE(BTRIM(CAST(st.no_surat AS text)), '(tanpa nomor)')
        || ' pada ' || COALESCE(st.tgl_serah_terima::date::text, '(belum realisasi)')
        || ' status ' || UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS text), 'A'))),
        ' | ' ORDER BY st.tgl_serah_terima
    )                                                    AS rincian
FROM public.sr_stok AS stok_raw
CROSS JOIN param
INNER JOIN stok_norm AS stok ON stok.stok_id = stok_raw.stok_id
INNER JOIN public.sr_ppjb AS p ON p.stok_id = stok.stok_id
INNER JOIN public.sr_serah_terima AS st ON st.ppjb_id = p.ppjb_id
WHERE stok.perusahaan_key = param.perusahaan
  AND UPPER(BTRIM(COALESCE(CAST(stok_raw.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
  AND st.tgl_serah_terima >= param.tgl_st1
  AND st.tgl_serah_terima <  param.tgl_st2 + INTERVAL '1 day'
GROUP BY 1, 2
HAVING COUNT(*) > 1
ORDER BY 1;


/* =====================================================================
 * QUERY 3 — Sebaran per cluster
 *
 * Desktop mengurutkan laporan ini dengan ORDER BY NAMA_CLUSTER, BLOK_NOMOR,
 * jadi bandingkan jumlah tiap cluster dengan yang tampil di desktop.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           DATE '2023-07-01' AS tgl_st1,
           DATE '2026-09-10' AS tgl_st2,
           'DTSA'::text      AS perusahaan,
           'A'::text         AS blok_awal,
           'Z'::text         AS blok_akhir
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), ''))) AS nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) AS flag_aktif,
        NULLIF(BTRIM(COALESCE(CAST(stok.parent_id AS text), '')), '') IS NULL AS induk_kosong,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_sektor'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_proyek'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_cluster'), ''),
            ''))) AS sektor_key
    FROM public.sr_stok AS stok
),
sektor_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) AS kode,
        BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))        AS deskripsi
    FROM public.sr_sektor AS s
)
SELECT
    COALESCE(NULLIF(sek.deskripsi, ''), stok.sektor_key, '(tanpa cluster)') AS nama_cluster,
    COUNT(*)                    AS jumlah_baris,
    COUNT(DISTINCT p.ppjb_id)   AS jumlah_ppjb
FROM public.sr_stok AS stok_raw
CROSS JOIN param
INNER JOIN stok_norm AS stok ON stok.stok_id = stok_raw.stok_id
INNER JOIN public.sr_ppjb AS p ON p.stok_id = stok.stok_id
LEFT JOIN public.sr_serah_terima AS st ON st.ppjb_id = p.ppjb_id
LEFT JOIN sektor_norm AS sek ON sek.kode = stok.sektor_key
WHERE stok.perusahaan_key = param.perusahaan
  AND stok.flag_aktif = 'A'
  AND stok.induk_kosong
  AND stok.blok <> '' AND stok.nomor <> ''
  AND stok.blok BETWEEN param.blok_awal AND param.blok_akhir
  AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
  AND (
        (UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
         AND p.tgl_ppjb >= param.tgl_awal
         AND p.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day')
     OR (UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'T'
         AND UPPER(BTRIM(COALESCE(to_jsonb(p) ->> 'flag_batal', 'T'))) = 'Y'
         AND p.tgl_ppjb >= param.tgl_awal
         AND p.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
         AND CASE WHEN COALESCE(to_jsonb(p) ->> 'tgl_batal', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                  THEN CAST(to_jsonb(p) ->> 'tgl_batal' AS timestamp) END > param.tgl_akhir
         AND st.tgl_serah_terima IS NOT NULL)
  )
  AND st.tgl_serah_terima >= param.tgl_st1
  AND st.tgl_serah_terima <  param.tgl_st2 + INTERVAL '1 day'
GROUP BY 1
ORDER BY 1;


/* =====================================================================
 * CARA MEMBACA
 *
 * - t6_rentang_realisasi sama dengan 596
 *   -> syarat desktop sudah terwakili, dan selisihnya ada di model web.
 *      Kirimkan berkas modelnya supaya bisa saya bandingkan.
 *
 * - t6_rentang_realisasi lebih besar daripada t6_jumlah_ppjb
 *   -> ada PPJB yang punya lebih dari satu catatan serah terima, dan
 *      desktop memang menampilkannya sebagai baris terpisah. QUERY 2
 *      menunjukkan yang mana saja. Kalau selisihnya delapan, itulah
 *      penyebab 596 lawan 588.
 *
 * - t6_rentang_realisasi sudah 588
 *   -> berarti datanya yang berbeda, bukan modelnya. Bandingkan QUERY 3
 *      dengan jumlah per cluster di desktop untuk mencari letaknya.
 * ===================================================================== */
