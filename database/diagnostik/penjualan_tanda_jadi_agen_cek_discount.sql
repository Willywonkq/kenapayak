/*
 * DIAGNOSTIK — Penjualan per Tanggal Tanda Jadi per Agen
 * Kenapa total DISCOUNT di web lebih besar daripada desktop, padahal
 * barisnya lebih sedikit (796 lawan 805).
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * LATAR BELAKANG
 * Query desktop menyambung BIAYA_DP ke BIAYA tanpa membuang spasi:
 *     WHERE BIAYA_DP.KD_BIAYA = BIAYA.KD_BIAYA AND BIAYA.BALANCE = -1
 * dan mengambil PPN dengan  KD_BIAYA = '  PPN'  (dua spasi di depan).
 * Jadi nilai KD_BIAYA memang menyimpan spasi di depan.
 *
 * Model web menyambungnya memakai UPPER(BTRIM(...)), yang membuang spasi
 * depan maupun belakang. Dua akibat yang mungkin:
 *   1. baris biaya yang di desktop tidak cocok, di web jadi cocok; atau
 *   2. satu baris biaya_dp cocok ke lebih dari satu baris master sr_biaya,
 *      sehingga nilainya terhitung berkali-kali.
 *
 * SQL Server dan PostgreSQL juga berbeda soal spasi di belakang: pada
 * SQL Server 'DSC' = 'DSC  ' bernilai benar, di PostgreSQL tidak. Padanan
 * yang setia untuk '=' milik SQL Server adalah RTRIM di kedua sisi, bukan
 * BTRIM.
 *
 * CARA PAKAI
 * Samakan isi CTE param dengan filter di layar, lalu jalankan berurutan.
 */


/* =====================================================================
 * QUERY 1 — Nilai KD_BIAYA apa adanya
 *
 * Tanda [ ] dipasang supaya spasi di depan dan di belakang kelihatan.
 * cocok_persis / cocok_rtrim / cocok_btrim = berapa baris sr_biaya yang
 * tersambung oleh masing-masing aturan. Nilai lebih dari 1 berarti
 * penjumlahannya berlipat.
 * ===================================================================== */
SELECT
    '[' || COALESCE(CAST(bdp.kd_biaya AS text), '(null)') || ']' AS kd_biaya_di_biaya_dp,
    COUNT(*) AS jumlah_baris_biaya_dp,
    (SELECT COUNT(*) FROM public.sr_biaya AS b
      WHERE CAST(b.kd_biaya AS text) = CAST(bdp.kd_biaya AS text))            AS cocok_persis,
    (SELECT COUNT(*) FROM public.sr_biaya AS b
      WHERE RTRIM(CAST(b.kd_biaya AS text)) = RTRIM(CAST(bdp.kd_biaya AS text))) AS cocok_rtrim,
    (SELECT COUNT(*) FROM public.sr_biaya AS b
      WHERE UPPER(BTRIM(CAST(b.kd_biaya AS text)))
          = UPPER(BTRIM(CAST(bdp.kd_biaya AS text))))                          AS cocok_btrim
FROM public.sr_biaya_dp AS bdp
GROUP BY bdp.kd_biaya
ORDER BY 1;


/* =====================================================================
 * QUERY 2 — Isi tabel master sr_biaya apa adanya
 * ===================================================================== */
SELECT
    '[' || COALESCE(CAST(b.kd_biaya AS text), '(null)') || ']' AS kd_biaya,
    b.balance,
    COUNT(*) AS jumlah_baris
FROM public.sr_biaya AS b
GROUP BY 1, 2
ORDER BY 1, 2;


/* =====================================================================
 * QUERY 3 — Total DISCOUNT, BIAYA, dan PPN dihitung dengan empat aturan
 *
 * Bandingkan tiap kolom dengan baris TOTAL di desktop. Aturan yang angkanya
 * sama dengan desktop itulah yang harus dipakai model.
 *
 * _persis    : sama persis, tanpa membuang spasi sama sekali
 * _rtrim     : hanya membuang spasi belakang (padanan '=' SQL Server)
 * _btrim     : membuang spasi depan dan belakang (cara model sekarang)
 * _btrim_unik: seperti _btrim tetapi master sr_biaya dibuat unik dulu,
 *              untuk memisahkan pengaruh pelipatan baris
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
unit AS (
    SELECT DISTINCT um.uang_muka_id
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
),
dp AS (
    SELECT bdp.kd_biaya, bdp.jumlah
    FROM unit AS u
    INNER JOIN public.sr_biaya_dp AS bdp ON bdp.uang_muka_id = u.uang_muka_id
),
master_unik AS (
    SELECT kode, MIN(balance) AS balance
    FROM (
        SELECT UPPER(BTRIM(CAST(b.kd_biaya AS text))) AS kode, b.balance
        FROM public.sr_biaya AS b
    ) AS t
    GROUP BY kode
)
SELECT
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN public.sr_biaya AS b
              ON CAST(b.kd_biaya AS text) = CAST(dp.kd_biaya AS text)
      WHERE b.balance = -1)                                        AS discount_persis,
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN public.sr_biaya AS b
              ON RTRIM(CAST(b.kd_biaya AS text)) = RTRIM(CAST(dp.kd_biaya AS text))
      WHERE b.balance = -1)                                        AS discount_rtrim,
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN public.sr_biaya AS b
              ON UPPER(BTRIM(CAST(b.kd_biaya AS text)))
               = UPPER(BTRIM(CAST(dp.kd_biaya AS text)))
      WHERE b.balance = -1)                                        AS discount_btrim,
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN master_unik AS b
              ON b.kode = UPPER(BTRIM(CAST(dp.kd_biaya AS text)))
      WHERE b.balance = -1)                                        AS discount_btrim_unik,

    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN public.sr_biaya AS b
              ON CAST(b.kd_biaya AS text) = CAST(dp.kd_biaya AS text)
      WHERE b.balance = 1
        AND CAST(dp.kd_biaya AS text) <> '  PPN')                  AS biaya_persis,
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      INNER JOIN public.sr_biaya AS b
              ON UPPER(BTRIM(CAST(b.kd_biaya AS text)))
               = UPPER(BTRIM(CAST(dp.kd_biaya AS text)))
      WHERE b.balance = 1
        AND UPPER(BTRIM(CAST(dp.kd_biaya AS text))) <> 'PPN')      AS biaya_btrim,

    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      WHERE CAST(dp.kd_biaya AS text) = '  PPN')                   AS ppn_persis_dua_spasi,
    (SELECT COALESCE(SUM(dp.jumlah), 0) FROM dp
      WHERE UPPER(BTRIM(CAST(dp.kd_biaya AS text))) = 'PPN')       AS ppn_btrim;


/* =====================================================================
 * QUERY 4 — Berapa baris biaya_dp yang terhitung lebih dari satu kali
 *
 * Kalau angkanya bukan nol, aturan BTRIM memang melipatgandakan nilai.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
unit AS (
    SELECT DISTINCT um.uang_muka_id
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
),
berlipat AS (
    SELECT bdp.ctid, COUNT(*) AS jumlah_master
    FROM unit AS u
    INNER JOIN public.sr_biaya_dp AS bdp ON bdp.uang_muka_id = u.uang_muka_id
    INNER JOIN public.sr_biaya AS b
            ON UPPER(BTRIM(CAST(b.kd_biaya AS text)))
             = UPPER(BTRIM(CAST(bdp.kd_biaya AS text)))
    GROUP BY bdp.ctid
)
SELECT
    COUNT(*) FILTER (WHERE jumlah_master > 1) AS baris_biaya_dp_terhitung_berkali,
    COALESCE(MAX(jumlah_master), 0)           AS pelipatan_terbanyak
FROM berlipat;


/* =====================================================================
 * QUERY 5 — Rincian per unit untuk blok yang terlihat di layar
 *
 * Bandingkan langsung dengan kolom Discount pada screenshot desktop:
 *   HE/032 = 34.220.000    HG/012 = 23.150.000
 *   HG/020 = -158.907.000  HG/008 = 0
 * ===================================================================== */
SELECT
    UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) || '/' ||
    UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))            AS blok,
    BTRIM(CAST(um.no_uang_muka AS text))                            AS no_uang_muka,
    '[' || COALESCE(CAST(bdp.kd_biaya AS text), '(tidak ada baris biaya)') || ']' AS kd_biaya,
    bdp.jumlah,
    (SELECT STRING_AGG(DISTINCT '[' || CAST(b.kd_biaya AS text) || ']=' || CAST(b.balance AS text), ', ')
       FROM public.sr_biaya AS b
      WHERE RTRIM(CAST(b.kd_biaya AS text)) = RTRIM(CAST(bdp.kd_biaya AS text)))  AS master_rtrim,
    (SELECT STRING_AGG(DISTINCT '[' || CAST(b.kd_biaya AS text) || ']=' || CAST(b.balance AS text), ', ')
       FROM public.sr_biaya AS b
      WHERE UPPER(BTRIM(CAST(b.kd_biaya AS text)))
          = UPPER(BTRIM(CAST(bdp.kd_biaya AS text))))                             AS master_btrim
FROM public.sr_uang_muka AS um
INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
LEFT JOIN public.sr_biaya_dp AS bdp ON bdp.uang_muka_id = um.uang_muka_id
WHERE UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) || '/' ||
      UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))
      IN ('HE/032', 'HG/008', 'HG/012', 'HG/020')
ORDER BY 1, 3;


/* =====================================================================
 * QUERY 6 — Sembilan unit yang ada di desktop tetapi tidak di web
 *
 * HG/008 terlihat hilang dari web. Query ini menampilkan baris mentahnya
 * supaya kelihatan syarat mana yang membuatnya tersaring, atau apakah
 * unitnya memang belum ada di PostgreSQL.
 * ===================================================================== */
SELECT
    UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) || '/' ||
    UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))               AS blok,
    BTRIM(CAST(um.no_uang_muka AS text))                               AS no_uang_muka,
    um.tgl_uang_muka::date                                             AS tgl_uang_muka,
    '[' || COALESCE(CAST(um.flag_aktif AS text), '(null)') || ']'      AS flag_aktif,
    '[' || COALESCE(CAST(um.parent_id AS text), '(null)') || ']'       AS parent_id,
    '[' || COALESCE(CAST(stok.kd_perusahaan AS text), '(null)') || ']' AS kd_perusahaan
FROM public.sr_uang_muka AS um
INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
WHERE UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), ''))) || '/' ||
      UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))
      IN ('HG/008', 'HE/020', 'HE/022', 'HE/026')
ORDER BY 1, 3;


/* =====================================================================
 * CARA MEMBACA
 *
 * - discount_rtrim atau discount_persis sama dengan -29.590.361.876
 *   -> penyebabnya BTRIM. Model harus berhenti membuang spasi depan.
 *
 * - discount_btrim_unik jauh lebih kecil daripada discount_btrim
 *   -> penyebabnya pelipatan baris karena master sr_biaya ganda.
 *     QUERY 4 memastikannya.
 *
 * - keempat angka discount sama semua
 *   -> bukan soal spasi maupun pelipatan. Berarti nilainya memang berbeda
 *     antara PostgreSQL dan SQL Server. Kirimkan hasilnya ke saya.
 *
 * Ingat, angka desktop dihitung dari 805 unit dan angka web dari 796 unit,
 * jadi tidak akan sama persis. Yang dicari adalah aturan yang paling
 * mendekati, terutama karena sembilan unit yang hilang seharusnya membuat
 * total web LEBIH KECIL, bukan lebih besar.
 * ===================================================================== */


/* =====================================================================
 * ==== LANJUTAN — sesudah QUERY 1 sampai 6 dijalankan ====
 *
 * Hasil QUERY 1 sampai 4 membantah dugaan soal spasi:
 *   - cocok_rtrim selalu sama dengan cocok_btrim, dan tidak pernah lebih
 *     dari 1, jadi tidak ada baris yang terhitung berulang.
 *   - discount_rtrim, discount_btrim, dan discount_btrim_unik ketiganya
 *     bernilai sama, yaitu -30.291.280.656.
 *   - QUERY 4 melaporkan 0 baris yang terhitung berkali-kali.
 *
 * Yang sebenarnya terjadi terlihat pada QUERY 1 dan 5. Ada 12 kode biaya
 * yang dipakai sr_biaya_dp tetapi tidak punya baris master di sr_biaya:
 *   D50, DCB2, DCG, DCK, DIRGR, DSF, DSH, DSL, DST, DUT, DXT, PHJ2
 * Semuanya cocok_rtrim = 0 dan cocok_btrim = 0.
 *
 * QUERY 5 memperlihatkan akibatnya. Pada HE/032 ada baris [DSL] senilai
 * 34.220.000 tanpa master, sehingga model menghitung discount unit itu
 * sebagai 0, sedangkan desktop menampilkan 34.220.000. Karena query
 * desktop menyambung BIAYA dengan INNER JOIN, berarti di SQL Server kode
 * DSL memang ada di tabel BIAYA dengan BALANCE = -1.
 *
 * Perhatikan juga tandanya: nilai discount bisa POSITIF. DSL 34.220.000
 * dan DPBL 23.150.000 dua-duanya positif. Jadi baris yang hilang justru
 * membuat total web lebih minus daripada desktop, bukan sebaliknya.
 *
 * Dua query berikut mengukur seberapa besar pengaruhnya.
 * ===================================================================== */


/* =====================================================================
 * QUERY 7 — Nilai yang terbuang karena kode biayanya tidak punya master
 *
 * Dikelompokkan per kode, hanya untuk unit yang masuk laporan.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
unit AS (
    SELECT DISTINCT um.uang_muka_id
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
)
SELECT
    '[' || COALESCE(CAST(bdp.kd_biaya AS text), '(null)') || ']' AS kd_biaya,
    COUNT(*)                        AS jumlah_baris,
    COUNT(DISTINCT bdp.uang_muka_id) AS jumlah_unit,
    SUM(COALESCE(bdp.jumlah, 0))    AS total_nilai
FROM unit AS u
INNER JOIN public.sr_biaya_dp AS bdp ON bdp.uang_muka_id = u.uang_muka_id
WHERE NOT EXISTS (
    SELECT 1 FROM public.sr_biaya AS b
     WHERE RTRIM(CAST(b.kd_biaya AS text)) = RTRIM(CAST(bdp.kd_biaya AS text))
)
GROUP BY bdp.kd_biaya
ORDER BY total_nilai DESC;


/* =====================================================================
 * QUERY 8 — Ringkasan satu baris
 *
 * total_terbuang adalah nilai yang desktop hitung sebagai discount tetapi
 * web lewatkan. Bandingkan dengan selisih dua laporan, yaitu
 *     -29.590.361.876 dikurangi -30.291.280.656 = 700.918.780
 *
 * Kalau total_terbuang mendekati angka itu, seluruh selisih discount sudah
 * terjelaskan oleh baris master sr_biaya yang belum tersalin. Sisanya
 * tinggal sumbangan sembilan unit yang memang belum ada di PostgreSQL.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-08' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
unit AS (
    SELECT DISTINCT um.uang_muka_id
    FROM public.sr_uang_muka AS um
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = um.stok_id
    WHERE um.tgl_uang_muka >= param.tgl_awal
      AND um.tgl_uang_muka <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
      AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
      AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
),
terbuang AS (
    SELECT bdp.uang_muka_id, bdp.jumlah
    FROM unit AS u
    INNER JOIN public.sr_biaya_dp AS bdp ON bdp.uang_muka_id = u.uang_muka_id
    WHERE NOT EXISTS (
        SELECT 1 FROM public.sr_biaya AS b
         WHERE RTRIM(CAST(b.kd_biaya AS text)) = RTRIM(CAST(bdp.kd_biaya AS text))
    )
)
SELECT
    COALESCE(SUM(jumlah), 0)                       AS total_terbuang,
    COUNT(*)                                       AS jumlah_baris,
    COUNT(DISTINCT uang_muka_id)                   AS jumlah_unit_terdampak,
    COALESCE(SUM(jumlah) FILTER (WHERE jumlah > 0), 0) AS bagian_positif,
    COALESCE(SUM(jumlah) FILTER (WHERE jumlah < 0), 0) AS bagian_negatif
FROM terbuang;


/* =====================================================================
 * ==== KESIMPULAN — sesudah QUERY 7 dan 8 dijalankan ====
 *
 * Hasil QUERY 7:
 *     [DSL]  22 baris  22 unit  495.728.780
 *     [D50]   3 baris   3 unit  167.960.000
 * Hasil QUERY 8:
 *     total_terbuang 663.688.780, 25 baris, 25 unit,
 *     bagian_positif 663.688.780, bagian_negatif 0
 *
 * Jadi dari 12 kode tanpa master, hanya DSL dan D50 yang menyentuh laporan
 * ini, dan seluruh nilainya positif.
 *
 * Selisih discount antara desktop dan web terurai habis:
 *
 *     desktop -29.590.361.876  dikurangi  web -30.291.280.656
 *         =  700.918.780
 *
 *     663.688.780  (94,7%)  baris DSL dan D50 yang tidak punya master
 *      37.230.000  ( 5,3%)  discount milik sembilan unit yang belum ada
 *     -----------
 *     700.918.780
 *
 * Kalau kedua baris master ditambahkan ke sr_biaya, discount web menjadi
 * -29.627.591.876. Sisa selisih terhadap desktop tinggal 37.230.000, yang
 * memang milik sembilan unit yang belum tersalin ke PostgreSQL.
 *
 * YANG PERLU DILAKUKAN
 * 1. Ambil nilai BALANCE untuk DSL dan D50 dari SQL Server memakai
 *    database/diagnostik/sqlserver_cek_master_biaya.sql
 * 2. Tambahkan kedua baris master itu ke sr_biaya. Ini perubahan data,
 *    harus dikerjakan oleh yang berwenang.
 *
 * Model web tidak perlu diubah. Begitu baris masternya ada, rumus yang
 * sekarang langsung menghitungnya dengan benar.
 *
 * BALANCE-nya tidak boleh ditebak dari kodenya. Untuk DSL memang sudah
 * terbukti bernilai -1, karena HE/032 di desktop menampilkan Discount
 * 34.220.000 sedangkan satu-satunya baris biaya yang bernilai segitu
 * berkode DSL, dan subquery DISCOUNT milik desktop mensyaratkan
 * BALANCE = -1. Untuk D50 belum ada bukti sekuat itu, jadi tetap harus
 * dibaca dari SQL Server.
 * ===================================================================== */
