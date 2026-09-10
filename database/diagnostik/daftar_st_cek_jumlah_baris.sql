/*
 * DIAGNOSTIK — Daftar Serah Terima
 * Kenapa desktop menampilkan 1027 baris sedangkan web hanya 1000.
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Samakan dulu isi CTE param dengan filter di layar, lalu jalankan berurutan.
 * Jumlah yang dihitung adalah BARIS laporan, yaitu satu baris untuk setiap
 * pasangan serah terima dan pembeli aktif, sama seperti desktop yang
 * menyambung PEMBELI_PPJB dengan join lama.
 */


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 *
 * Setiap tahap memakai syarat yang sama persis dengan query desktop,
 * kecuali dua kolom terakhir yang sengaja dipisah untuk membandingkan
 * perilaku model.
 *
 * t4_aktif_desktop   = syarat desktop, hanya flag_aktif = 'A'
 * t4b_aktif_model    = syarat model lama, ditambah tgl_batal IS NULL
 * t7_tipe_ketemu     = perilaku INNER JOIN sr_tipe, yaitu angka web lama
 * t8_tanpa_syarat_tipe = perilaku LEFT JOIN, yaitu angka web sesudah perbaikan
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           DATE '2023-07-01' AS tgl_st_awal,
           DATE '2026-09-10' AS tgl_st_akhir,
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
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis_bgn'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis'), ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe_bgn'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe'), ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(t.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(t.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS t
),
baris AS (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS text), 'A'))) AS st_flag_aktif,
        st.tgl_batal,
        st.tgl_surat,
        st.tgl_serah_terima,
        stok.flag_aktif = 'A'                                        AS stok_aktif,
        UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A' AS ppjb_aktif,
        NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL AS induk_kosong,
        (stok.blok <> '' AND stok.nomor <> '')                       AS blok_terisi,
        (stok.blok BETWEEN param.blok_awal AND param.blok_akhir)     AS blok_dalam_rentang,
        EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
        )                                                            AS tipe_ketemu
    FROM public.sr_serah_terima AS st
    CROSS JOIN param
    INNER JOIN public.sr_ppjb AS p ON p.ppjb_id = st.ppjb_id
    INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
    INNER JOIN public.sr_pembeli_ppjb AS pb
            ON pb.ppjb_id = p.ppjb_id
           AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
    WHERE stok.perusahaan_key = param.perusahaan
)
SELECT
    COUNT(*)                                                   AS t0_baris_perusahaan,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif)          AS t1_flag_aktif,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong)
                                                               AS t2_induk_kosong,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang) AS t3_blok_cocok,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A')                AS t4_aktif_desktop,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A'
                       AND tgl_batal IS NULL)                  AS t4b_aktif_model,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A'
                       AND tgl_surat >= param.tgl_awal
                       AND tgl_surat < param.tgl_akhir + INTERVAL '1 day')
                                                               AS t5_tgl_surat,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A'
                       AND tgl_surat >= param.tgl_awal
                       AND tgl_surat < param.tgl_akhir + INTERVAL '1 day'
                       AND tgl_serah_terima >= param.tgl_st_awal
                       AND tgl_serah_terima < param.tgl_st_akhir + INTERVAL '1 day')
                                                               AS t6_tgl_realisasi,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A'
                       AND tgl_surat >= param.tgl_awal
                       AND tgl_surat < param.tgl_akhir + INTERVAL '1 day'
                       AND tgl_serah_terima >= param.tgl_st_awal
                       AND tgl_serah_terima < param.tgl_st_akhir + INTERVAL '1 day'
                       AND tipe_ketemu)                        AS t7_tipe_ketemu,
    COUNT(*) FILTER (WHERE stok_aktif AND ppjb_aktif AND induk_kosong
                       AND blok_terisi AND blok_dalam_rentang
                       AND st_flag_aktif = 'A'
                       AND tgl_surat >= param.tgl_awal
                       AND tgl_surat < param.tgl_akhir + INTERVAL '1 day'
                       AND tgl_serah_terima >= param.tgl_st_awal
                       AND tgl_serah_terima < param.tgl_st_akhir + INTERVAL '1 day')
                                                               AS t8_tanpa_syarat_tipe
FROM baris
CROSS JOIN param;


/* =====================================================================
 * QUERY 2 — Sebaran nilai flag_aktif dan tgl_batal pada sr_serah_terima
 *
 * Desktop menyaring dengan ISNULL(FLAG_AKTIF,'A') = 'A' saja. Model lama
 * menambahkan tgl_batal IS NULL, dan juga menerima nilai Y yang tidak
 * dikenal desktop. Query ini menghitung berapa baris yang terpengaruh.
 * ===================================================================== */
SELECT
    '[' || COALESCE(CAST(st.flag_aktif AS text), '(null)') || ']' AS flag_aktif,
    COUNT(*)                                             AS jumlah_baris,
    COUNT(*) FILTER (WHERE st.tgl_batal IS NOT NULL)     AS tgl_batal_terisi
FROM public.sr_serah_terima AS st
GROUP BY 1
ORDER BY jumlah_baris DESC;


/* =====================================================================
 * QUERY 3 — Sambungan ke sr_nasabah
 *
 * Model lama membuang karakter bukan angka dari nasabah_id sebelum
 * membandingkan, sehingga nilai seperti N1 tidak pernah cocok dan kolom
 * Nama Pembeli menjadi tanda hubung. Query ini membandingkan tiga aturan
 * dan sekaligus memeriksa apakah ada nasabah_id ganda yang bisa
 * melipatgandakan jumlah baris.
 * ===================================================================== */
SELECT
    (SELECT COUNT(*) FROM public.sr_pembeli_ppjb)                       AS pembeli_semua,
    (SELECT COUNT(*) FROM public.sr_pembeli_ppjb AS pb
      WHERE EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
         WHERE BTRIM(CAST(pb.nasabah_id AS text)) = BTRIM(CAST(n.nasabah_id AS text))
      ))                                                                AS cocok_teks,
    (SELECT COUNT(*) FROM public.sr_pembeli_ppjb AS pb
      WHERE EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
         WHERE NULLIF(REGEXP_REPLACE(COALESCE(CAST(pb.nasabah_id AS text), ''), '[^0-9]', '', 'g'), '')
             = CAST(n.nasabah_id AS text)
      ))                                                                AS cocok_cara_lama,
    (SELECT COUNT(*) FROM (
        SELECT BTRIM(CAST(n.nasabah_id AS text)) AS id
        FROM public.sr_nasabah AS n
        GROUP BY 1 HAVING COUNT(*) > 1
     ) AS ganda)                                                        AS nasabah_id_ganda;


/* =====================================================================
 * QUERY 4 — Unit yang hilang karena tipenya belum ada di sr_tipe
 * ===================================================================== */
WITH param AS (
    SELECT 'DTSA'::text AS perusahaan
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
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis_bgn'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_jenis'), ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe_bgn'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_tipe'), ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(t.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(t.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS t
)
SELECT
    COUNT(DISTINCT st.serah_terima_id)                          AS jumlah_serah_terima,
    COUNT(DISTINCT stok.blok)                                   AS jumlah_blok,
    STRING_AGG(DISTINCT stok.blok, ', ' ORDER BY stok.blok)     AS daftar_blok,
    STRING_AGG(DISTINCT stok.jenis_key || '/' || stok.tipe_key, ', ') AS contoh_kode
FROM public.sr_serah_terima AS st
CROSS JOIN param
INNER JOIN public.sr_ppjb AS p ON p.ppjb_id = st.ppjb_id
INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
WHERE stok.perusahaan_key = param.perusahaan
  AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
  AND NOT EXISTS (
        SELECT 1 FROM tipe_norm AS t
        WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
  );


/* =====================================================================
 * CARA MEMBACA
 *
 * - t7_tipe_ketemu sama dengan angka web yang lama, yaitu 1000
 *   -> join sr_tipe memang membuang baris, dan perbaikan LEFT JOIN pada
 *      model sudah menanganinya. t8_tanpa_syarat_tipe adalah angka yang
 *      seharusnya muncul sesudah perbaikan.
 *
 * - selisih t4_aktif_desktop dengan t4b_aktif_model
 *   -> sebanyak itulah baris yang terbuang hanya karena model menambahkan
 *      syarat tgl_batal IS NULL yang tidak ada pada desktop. Syarat itu
 *      sudah dihapus.
 *
 * - t8_tanpa_syarat_tipe sama dengan 1027
 *   -> kedua laporan sudah sama.
 *
 * - t8_tanpa_syarat_tipe lebih besar daripada 1027
 *   -> kemungkinan penyaring tanggal pada model masih lebih longgar. Model
 *      memberi jalur tambahan ketika tgl_surat atau tgl_serah_terima kosong,
 *      memakai tanggal rencana, sedangkan desktop langsung membuang baris
 *      yang tanggalnya kosong. Kirimkan hasilnya ke saya.
 *
 * - QUERY 3, kalau cocok_cara_lama jauh lebih kecil daripada cocok_teks
 *   -> itu sebabnya kolom Nama Pembeli menjadi tanda hubung. Sudah
 *      diperbaiki. Perhatikan juga nasabah_id_ganda, karena nasabah_id yang
 *      muncul lebih dari sekali akan melipatkan baris pada desktop.
 * ===================================================================== */


/* =====================================================================
 * ==== LANJUTAN — sesudah QUERY 1 sampai 4 dijalankan ====
 *
 * Hasil QUERY 1: 1042, 1037, 1037, 1037, 1037, 1037, 1037, 1018, 982, 1018.
 *
 *   t4_aktif_desktop sama dengan t4b_aktif_model, keduanya 1037. Syarat
 *   tgl_batal ternyata tidak membuang baris apa pun, jadi dugaan tentang
 *   syarat itu keliru. QUERY 2 menjelaskan kenapa: flag_aktif hanya bernilai
 *   A sebanyak 40552 baris dengan tgl_batal seluruhnya kosong, dan T
 *   sebanyak 457 baris dengan tgl_batal seluruhnya terisi.
 *
 *   t5_tgl_surat 1037 sama dengan angka web, dan t6_tgl_realisasi 1018.
 *   Selisih 19 baris itulah kelebihan web, yaitu serah terima yang belum
 *   punya tanggal realisasi tetapi ditarik masuk oleh jalur cadangan pada
 *   model. Jalur cadangan itu sudah dihapus.
 *
 *   t7 982 dan t8 1018, jadi join sr_tipe berpengaruh 36 baris.
 *
 * QUERY 3 memastikan sambungan nasabah: dari 63447 baris sr_pembeli_ppjb,
 * 34560 cocok bila dibandingkan sebagai teks dan 0 cocok dengan cara lama.
 * Itu sebabnya seluruh kolom Nama Pembeli berisi tanda hubung. Tidak ada
 * nasabah_id ganda, jadi sambungan itu tidak melipatgandakan baris.
 *
 * Sesudah perbaikan, web seharusnya menampilkan 1018 baris. Desktop 1027,
 * jadi masih kurang 9. Dua query berikut mencari letaknya.
 * ===================================================================== */


/* =====================================================================
 * QUERY 5 — Sebaran per tahun memakai aturan desktop
 *
 * Jalankan juga pasangannya di SQL Server, yaitu
 * database/diagnostik/sqlserver_daftar_st_sebaran.sql, lalu bandingkan
 * baris per baris. Tahun yang jumlahnya berbeda itulah letak sembilan
 * baris yang belum ada di PostgreSQL.
 *
 * jumlah_ppjb dipakai sebagai pembanding, bukan serah_terima_id, karena
 * kolom itu tidak dipakai query desktop sehingga sisi SQL Server bisa
 * memakai kolom yang sama persis. Kalau jumlah_baris lebih besar daripada
 * jumlah_ppjb, berarti ada PPJB dengan lebih dari satu pembeli aktif.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           DATE '2023-07-01' AS tgl_st_awal,
           DATE '2026-09-10' AS tgl_st_akhir,
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
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key
    FROM public.sr_stok AS stok
)
SELECT
    EXTRACT(YEAR FROM st.tgl_serah_terima)::integer AS tahun_realisasi,
    COUNT(*)                                        AS jumlah_baris,
    COUNT(DISTINCT p.ppjb_id)                       AS jumlah_ppjb,
    MIN(st.tgl_serah_terima)::date                  AS paling_awal,
    MAX(st.tgl_serah_terima)::date                  AS paling_akhir
FROM public.sr_serah_terima AS st
CROSS JOIN param
INNER JOIN public.sr_ppjb AS p ON p.ppjb_id = st.ppjb_id
INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
INNER JOIN public.sr_pembeli_ppjb AS pb
        ON pb.ppjb_id = p.ppjb_id
       AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
WHERE stok.perusahaan_key = param.perusahaan
  AND stok.flag_aktif = 'A'
  AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
  AND stok.blok <> '' AND stok.nomor <> ''
  AND stok.blok BETWEEN param.blok_awal AND param.blok_akhir
  AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS text), 'A'))) = 'A'
  AND st.tgl_surat        >= param.tgl_awal
  AND st.tgl_surat        <  param.tgl_akhir + INTERVAL '1 day'
  AND st.tgl_serah_terima >= param.tgl_st_awal
  AND st.tgl_serah_terima <  param.tgl_st_akhir + INTERVAL '1 day'
GROUP BY 1
ORDER BY 1;


/* =====================================================================
 * QUERY 6 — Sembilan belas baris yang tadinya kelebihan
 *
 * Serah terima yang lolos penyaring Tgl Surat tetapi tanggal realisasinya
 * kosong atau di luar rentang. Inilah baris yang ditarik masuk oleh jalur
 * cadangan pada model dan sekarang sudah tidak ikut lagi.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-10' AS tgl_akhir,
           DATE '2023-07-01' AS tgl_st_awal,
           DATE '2026-09-10' AS tgl_st_akhir,
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
        UPPER(BTRIM(COALESCE(
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_perusahaan'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_unit'), ''),
            NULLIF(BTRIM(to_jsonb(stok) ->> 'kd_pt'), ''),
            ''))) AS perusahaan_key
    FROM public.sr_stok AS stok
)
SELECT
    stok.blok || '/' || stok.nomor          AS blok_nomor,
    BTRIM(CAST(st.no_surat AS text))        AS no_surat,
    st.tgl_surat::date                      AS tgl_surat,
    st.tgl_serah_terima::date               AS tgl_serah_terima,
    CASE
        WHEN st.tgl_serah_terima IS NULL THEN 'belum ada tanggal realisasi'
        ELSE 'tanggal realisasi di luar rentang'
    END                                     AS sebab
FROM public.sr_serah_terima AS st
CROSS JOIN param
INNER JOIN public.sr_ppjb AS p ON p.ppjb_id = st.ppjb_id
INNER JOIN stok_norm AS stok ON stok.stok_id = p.stok_id
INNER JOIN public.sr_pembeli_ppjb AS pb
        ON pb.ppjb_id = p.ppjb_id
       AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS text), ''))) = 'Y'
WHERE stok.perusahaan_key = param.perusahaan
  AND stok.flag_aktif = 'A'
  AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
  AND stok.blok <> '' AND stok.nomor <> ''
  AND stok.blok BETWEEN param.blok_awal AND param.blok_akhir
  AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS text), 'A'))) = 'A'
  AND st.tgl_surat >= param.tgl_awal
  AND st.tgl_surat <  param.tgl_akhir + INTERVAL '1 day'
  AND (
        st.tgl_serah_terima IS NULL
     OR st.tgl_serah_terima <  param.tgl_st_awal
     OR st.tgl_serah_terima >= param.tgl_st_akhir + INTERVAL '1 day'
  )
ORDER BY 1;
