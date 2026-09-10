/*
 * DIAGNOSTIK — Penjualan per Tanggal Tanda Jadi per Agen
 * Kenapa desktop 805 unit sedangkan web 570 unit.
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * CARA PAKAI
 * 1. Samakan isi CTE param dengan filter di layar (Periode SP dan unit).
 * 2. Jalankan QUERY 1 lebih dulu. Bandingkan t2 dengan angka desktop dan
 *    t3 dengan angka web sebelum perbaikan.
 * 3. QUERY 2 dan 3 memerinci unit mana yang hilang dan kenapa.
 *
 * Semua kolom kode dibaca dengan to_jsonb(x) ->> 'nama_kolom' memakai urutan
 * yang sama dengan model web, sehingga kolom yang tidak ada menghasilkan NULL
 * dan bukan galat "column does not exist".
 */


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 *
 * t2 = jumlah unit menurut syarat query desktop (tanpa join TIPE).
 * t3 = jumlah unit yang tersisa setelah join TIPE gaya INNER JOIN.
 * t4 = tersisa setelah TIPE juga harus punya pasangan di sr_jenis_bangunan.
 *
 * Selisih t2 dengan t3 dan t4 itulah unit yang hilang di web.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(tipe.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS tipe
),
jenis_norm AS (
    SELECT DISTINCT UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis
    FROM public.sr_jenis_bangunan AS jb
),
sumber AS (
    SELECT
        um.uang_muka_id,
        UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'          AS aktif,
        NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL    AS induk_kosong,
        stok.perusahaan_key = param.perusahaan                                 AS perusahaan_cocok,
        EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
        )                                                                      AS tipe_ketemu,
        EXISTS (
            SELECT 1 FROM tipe_norm AS t
            INNER JOIN jenis_norm AS j ON j.kd_jenis = t.kd_jenis
            WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
        )                                                                      AS tipe_dan_jenis_ketemu
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
)
SELECT
    COUNT(DISTINCT uang_muka_id)                                   AS t0_pada_rentang_tanggal,
    COUNT(DISTINCT uang_muka_id) FILTER (WHERE perusahaan_cocok)   AS t1_perusahaan_cocok,
    COUNT(DISTINCT uang_muka_id) FILTER (
        WHERE perusahaan_cocok AND aktif AND induk_kosong
    )                                                              AS t2_syarat_desktop,
    COUNT(DISTINCT uang_muka_id) FILTER (
        WHERE perusahaan_cocok AND aktif AND induk_kosong AND tipe_ketemu
    )                                                              AS t3_tipe_ketemu,
    COUNT(DISTINCT uang_muka_id) FILTER (
        WHERE perusahaan_cocok AND aktif AND induk_kosong AND tipe_dan_jenis_ketemu
    )                                                              AS t4_tipe_dan_jenis_ketemu
FROM sumber;


/* =====================================================================
 * QUERY 2 — Unit yang hilang, dikelompokkan menurut sebabnya
 *
 * Menjawab: apakah kd_tipe pada sr_stok memang kosong, atau terisi tetapi
 * kodenya belum ada di sr_tipe.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) AS perusahaan_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT
        UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(tipe.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS tipe
),
hilang AS (
    SELECT
        stok.blok,
        stok.jenis_key,
        stok.tipe_key,
        um.uang_muka_id,
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
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
      AND stok.perusahaan_key = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
      AND NOT EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis = stok.jenis_key AND t.kd_tipe = stok.tipe_key
      )
)
SELECT
    sebab,
    COUNT(DISTINCT uang_muka_id)             AS jumlah_unit,
    COUNT(DISTINCT blok)                     AS jumlah_blok,
    STRING_AGG(DISTINCT blok, ', ' ORDER BY blok)           AS daftar_blok,
    STRING_AGG(DISTINCT jenis_key || '/' || tipe_key, ', ') AS contoh_kode
FROM hilang
GROUP BY sebab
ORDER BY jumlah_unit DESC;


/* =====================================================================
 * QUERY 3 — Kelengkapan tabel referensi
 *
 * Berapa banyak pasangan kode yang dipakai sr_stok tetapi belum ada di
 * sr_tipe, dan berapa kd_jenis yang belum ada di sr_jenis_bangunan.
 * ===================================================================== */
WITH stok_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            ''))) AS jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            ''))) AS tipe_key
    FROM public.sr_stok AS stok
),
tipe_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) AS kd_jenis,
        UPPER(BTRIM(CAST(tipe.kd_tipe AS text)))  AS kd_tipe
    FROM public.sr_tipe AS tipe
),
jenis_norm AS (
    SELECT DISTINCT UPPER(BTRIM(CAST(jb.kd_jenis AS text))) AS kd_jenis
    FROM public.sr_jenis_bangunan AS jb
)
SELECT
    (SELECT COUNT(*) FROM tipe_norm)                       AS pasangan_di_sr_tipe,
    (SELECT COUNT(*) FROM jenis_norm)                      AS kd_jenis_di_sr_jenis_bangunan,
    (SELECT COUNT(*) FROM stok_norm)                       AS pasangan_dipakai_sr_stok,
    (SELECT COUNT(*) FROM stok_norm s
      WHERE NOT EXISTS (SELECT 1 FROM tipe_norm t
                        WHERE t.kd_jenis = s.jenis_key AND t.kd_tipe = s.tipe_key)
    )                                                      AS pasangan_belum_ada_di_sr_tipe,
    (SELECT COUNT(*) FROM tipe_norm t
      WHERE NOT EXISTS (SELECT 1 FROM jenis_norm j WHERE j.kd_jenis = t.kd_jenis)
    )                                                      AS tipe_tanpa_jenis_bangunan;


/* =====================================================================
 * CARA MEMBACA
 *
 * - t2 = 805 dan t3 = 570
 *   -> persis seperti dugaan: join TIPE yang membuang 235 unit. Perbaikan
 *      pada model sudah mengganti join itu menjadi LEFT JOIN, jadi setelah
 *      model diperbarui angka di web harus menjadi sama dengan t2.
 *
 * - t2 juga jauh di bawah 805
 *   -> berarti bukan join TIPE penyebabnya, melainkan data unit DTSA di
 *      PostgreSQL belum selengkap SQL Server. Kirimkan hasilnya ke saya.
 *
 * - t3 dan t4 berbeda
 *   -> ada tipe yang kd_jenis-nya belum ada di sr_jenis_bangunan. Ini juga
 *      sudah ditangani, karena join ke sr_jenis_bangunan pun kini LEFT JOIN.
 *
 * - QUERY 2 memberi tahu apakah harus menambah baris di sr_tipe atau mengisi
 *   kd_tipe pada sr_stok. Selama itu belum dilakukan, unitnya tetap tampil
 *   tetapi kolom Tipe pada laporan kosong.
 * ===================================================================== */


/* =====================================================================
 * ==== LANJUTAN — sisa sembilan unit ====
 *
 * Posisi sejauh ini:
 *   desktop 805 unit, web 796 sesudah join sr_tipe diperbaiki, kurang 9.
 *   HG/008 sudah terbukti tidak ada sama sekali di PostgreSQL.
 *
 * Petunjuk baru datang dari laporan lain. Pada Daftar Serah Terima dan
 * Daftar Unit ST sudah terbukti sr_serah_terima di PostgreSQL berhenti pada
 * 4 Juli 2026 sedangkan SQL Server berlanjut sampai 30 Juli 2026.
 *
 * Sekarang perhatikan HG/008 pada tangkapan layar desktop laporan ini:
 * tanggal tanda jadinya 26 Juli 2026, juga sesudah 4 Juli 2026.
 *
 * Dugaannya, salinan PostgreSQL diambil sekitar awal Juli 2026, sehingga
 * seluruh catatan yang masuk sesudah itu belum ada, di banyak tabel
 * sekaligus. Kalau benar, sembilan unit yang kurang di sini adalah surat
 * pesanan bertanggal sesudah batas tersebut, dan sumbernya sama dengan
 * sembilan baris di Daftar Serah Terima maupun delapan baris di Daftar
 * Unit ST.
 *
 * Dua query berikut mengujinya. Tetap hanya membaca.
 * ===================================================================== */


/* =====================================================================
 * QUERY 5 — Sebaran per bulan pada tahun terakhir
 *
 * Melihat sampai tanggal berapa surat pesanan DTSA tercatat. Kalau
 * berhenti pada awal Juli 2026, dugaan di atas terbukti.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
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
    TO_CHAR(um.tgl_uang_muka, 'YYYY-MM')        AS bulan,
    COUNT(DISTINCT um.uang_muka_id)             AS jumlah_unit,
    MIN(um.tgl_uang_muka)::date                 AS paling_awal,
    MAX(um.tgl_uang_muka)::date                 AS paling_akhir
FROM public.sr_uang_muka AS um
CROSS JOIN param
INNER JOIN stok_norm AS stok ON stok.stok_id = um.stok_id
WHERE stok.perusahaan_key = param.perusahaan
  AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
  AND um.tgl_uang_muka >= DATE '2026-01-01'
GROUP BY 1
ORDER BY 1;


/* =====================================================================
 * QUERY 6 — Batas waktu salinan pada beberapa tabel sekaligus
 *
 * Menampilkan catatan terbaru pada tiap tabel yang dipakai laporan-laporan
 * ini. Kalau seluruhnya berhenti di sekitar tanggal yang sama, berarti
 * memang salinan PostgreSQL-nya yang tertinggal, bukan satu tabel saja.
 *
 * Dibaca lewat to_jsonb sehingga tabel yang tidak punya kolom tgl_entry
 * tidak menyebabkan galat.
 * ===================================================================== */
SELECT 'sr_uang_muka'      AS tabel,
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date AS entry_terakhir,
       COUNT(*) AS jumlah_baris
FROM public.sr_uang_muka AS t
UNION ALL
SELECT 'sr_ppjb',
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date,
       COUNT(*)
FROM public.sr_ppjb AS t
UNION ALL
SELECT 'sr_serah_terima',
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date,
       COUNT(*)
FROM public.sr_serah_terima AS t
UNION ALL
SELECT 'sr_stok',
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date,
       COUNT(*)
FROM public.sr_stok AS t
UNION ALL
SELECT 'sr_bayar_uang_muka',
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date,
       COUNT(*)
FROM public.sr_bayar_uang_muka AS t
UNION ALL
SELECT 'sr_biaya_dp',
       MAX(CASE WHEN COALESCE(to_jsonb(t) ->> 'tgl_entry', '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                THEN CAST(to_jsonb(t) ->> 'tgl_entry' AS timestamp) END)::date,
       COUNT(*)
FROM public.sr_biaya_dp AS t
ORDER BY 1;


/* =====================================================================
 * CARA MEMBACA LANJUTAN
 *
 * - QUERY 5 berhenti pada awal Juli 2026
 *   -> sembilan unit yang kurang adalah surat pesanan sesudah tanggal itu,
 *      sama sumbernya dengan yang kurang pada dua laporan serah terima.
 *      Tidak ada yang perlu diperbaiki di model.
 *
 * - QUERY 5 masih berlanjut sampai Agustus atau September 2026
 *   -> berarti bukan soal batas waktu salinan, dan sembilan unit itu hilang
 *      karena sebab lain. Kirimkan hasilnya ke saya.
 *
 * - QUERY 6 menunjukkan entry_terakhir yang seragam di semua tabel
 *   -> menegaskan seluruh salinan PostgreSQL berhenti pada tanggal yang
 *      sama, jadi cukup satu kali penyalinan ulang untuk menutup selisih di
 *      ketiga laporan sekaligus.
 * ===================================================================== */


/* =====================================================================
 * ==== KESIMPULAN — batas waktu salinan sudah terbukti ====
 *
 * Hasil QUERY 5, surat pesanan DTSA per bulan sepanjang 2026:
 *
 *     2026-01   38      2026-05   13
 *     2026-02   17      2026-06   40
 *     2026-03   11      2026-07    1   <-- berhenti pada 2 Juli 2026
 *     2026-04   15
 *
 * Juni masih 40 unit lalu Juli tiba-tiba hanya 1, dan tanggal terakhirnya
 * 2 Juli 2026. Itu bukan penurunan yang wajar, melainkan pemotongan.
 *
 * Hasil QUERY 6, catatan terbaru pada tiap tabel:
 *
 *     sr_stok              2026-07-02    61.994 baris
 *     sr_uang_muka         2026-07-06    56.006
 *     sr_bayar_uang_muka   2026-07-06    56.411
 *     sr_biaya_dp          2026-07-07   119.278
 *     sr_ppjb              2026-07-07    62.328
 *     sr_serah_terima      2026-07-07    41.009
 *
 * Keenam tabel berhenti dalam rentang lima hari, yaitu 2 sampai 7 Juli 2026.
 * Jadi salinan PostgreSQL memang diambil sekitar 7 Juli 2026, dan seluruh
 * catatan sesudah itu belum ada, di semua tabel sekaligus.
 *
 * Inilah satu sebab yang menjelaskan seluruh selisih yang tersisa:
 *
 *     Penjualan per Tgl Tanda Jadi   796 lawan 805   kurang 9
 *     Daftar Serah Terima           1018 lawan 1027  kurang 9
 *     Daftar Unit ST                 588 lawan  596  kurang 8
 *
 * Tidak ada yang perlu diperbaiki di model ketiganya. Yang diperlukan hanya
 * menyalin ulang data dari SQL Server, dan ketiga laporan akan cocok
 * sekaligus.
 *
 * Untuk memastikan dari sisi SQL Server, jalankan
 * database/diagnostik/sqlserver_penjualan_tanda_jadi_sebaran.sql. QUERY 2
 * pada berkas itu mendaftar surat pesanan mulai 3 Juli 2026, dan jumlahnya
 * seharusnya tepat sembilan, termasuk HG/008 tanggal 26 Juli 2026.
 * ===================================================================== */


/* =====================================================================
 * ==== PERHITUNGAN TUTUP — perbandingan dengan SQL Server ====
 *
 * Sebaran per bulan tahun 2026 pada kedua sisi:
 *
 *     bulan      PostgreSQL   SQL Server   selisih   akhir PG      akhir SQL
 *     2026-01    38           38           0         2026-01-31    2026-01-31
 *     2026-02    17           17           0         2026-02-28    2026-02-28
 *     2026-03    11           11           0         2026-03-30    2026-03-30
 *     2026-04    15           13          -2         2026-04-30    2026-04-27
 *     2026-05    13           13           0         2026-05-31    2026-05-31
 *     2026-06    40           39          -1         2026-06-30    2026-06-30
 *     2026-07     1           13         +12         2026-07-02    2026-07-27
 *     TOTAL     135          144          +9
 *
 * Arah selisihnya ternyata tidak seragam, dan dugaan sebelumnya yang
 * menyebut sembilan unit itu semata surat pesanan sesudah 2 Juli 2026
 * ternyata belum lengkap. Uraiannya:
 *
 *     12 unit ada di SQL Server tetapi belum ada di PostgreSQL
 *      3 unit masih terhitung di PostgreSQL tetapi tidak masuk hasil desktop
 *     --
 *      9 selisih bersih, tepat sesuai 805 lawan 796
 *
 * Dua belas unit yang pertama sudah terdaftar lengkap oleh QUERY 2 pada
 * berkas sqlserver_penjualan_tanda_jadi_sebaran.sql, bertanggal 4 sampai 27
 * Juli 2026, dan HG/008 tanggal 26 Juli 2026 memang ada di dalamnya sesuai
 * perkiraan.
 *
 * Tiga unit yang kedua kemungkinan besar dibatalkan atau direvisi sesudah
 * salinan diambil pada 7 Juli 2026, sehingga salinan itu masih menyimpan
 * keadaan lamanya. Perhatikan tanggal terakhir April: PostgreSQL sampai
 * 30 April sedangkan SQL Server hanya sampai 27 April, jadi setidaknya satu
 * di antaranya bertanggal antara 28 dan 30 April. QUERY 3 pada berkas SQL
 * Server mendaftar ketiganya berikut alasannya.
 *
 * Kesimpulannya tetap sama: tidak ada yang perlu diperbaiki di model.
 * Seluruh selisih berasal dari salinan PostgreSQL yang tertinggal sejak
 * 7 Juli 2026, baik berupa catatan baru yang belum masuk maupun perubahan
 * yang belum ikut tersalin.
 * ===================================================================== */
