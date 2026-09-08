/*
 * DIAGNOSTIK — SP Sudah PPJB (Daftar Rumah Terjual Pertanggal)
 *
 * Dipakai bila jumlah baris di web berbeda dengan desktop.
 * Acuan: periode 01-07-2023 s.d 07-09-2026, unit DTSA, pembayaran 100%.
 *   Desktop : 736 baris
 *   Web     : 565 baris
 *
 * Semua query hanya membaca.
 *
 * CATATAN PENTING
 * Nama kolom kode pada sr_stok berbeda antar database hasil migrasi, jadi
 * setiap kolom seperti itu dibaca memakai to_jsonb(stok) ->> 'nama_kolom',
 * persis seperti yang dilakukan model. Cara ini menghasilkan NULL bila
 * kolomnya tidak ada, bukan error "column does not exist".
 */


/* =====================================================================
 * QUERY 0 — Nama kolom yang benar-benar ada
 * Jalankan ini lebih dulu supaya jelas kolom mana yang tersedia.
 * ===================================================================== */
SELECT
    table_name,
    STRING_AGG(column_name, ', ' ORDER BY ordinal_position) AS kolom
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN (
        'sr_stok', 'sr_ppjb', 'sr_tipe', 'sr_jenis_bangunan',
        'sr_angsuran', 'sr_jadwal_angsuran', 'sr_kode_transaksi',
        'sr_pembeli_ppjb'
      )
GROUP BY table_name
ORDER BY table_name;


/* =====================================================================
 * QUERY 1 — Corong penyaringan, bagian pertama
 * Kolom pertama yang angkanya turun adalah tahap yang membuang baris.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           100::numeric      AS persen
),

/*
 * Normalisasi sr_stok sekali saja, memakai pola dan urutan kandidat kolom
 * yang sama dengan model.
 */
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            ''))) AS kd_tipe_key,

        /* Urutan lama: varian _bgn didahulukan. Dipakai sebagai pembanding. */
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            ''))) AS kd_jenis_key_lama,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            ''))) AS kd_tipe_key_lama,

        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) AS kd_perusahaan_key,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) AS flag_aktif_key,
        to_jsonb(stok) ->> 'blok'  AS blok_text,
        to_jsonb(stok) ->> 'nomor' AS nomor_text
    FROM public.sr_stok AS stok
),

tipe_norm AS (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))  AS kd_tipe_key
    FROM public.sr_tipe AS t
),

tipe_jenis_norm AS (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))  AS kd_tipe_key,
        UPPER(BTRIM(COALESCE(to_jsonb(jb) ->> 'flag_laporan', ''))) AS flag_laporan
    FROM public.sr_tipe AS t
    INNER JOIN public.sr_jenis_bangunan AS jb
            ON UPPER(BTRIM(COALESCE(to_jsonb(jb) ->> 'kd_jenis', '')))
             = UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', '')))
),

sumber AS (
    SELECT
        ppjb.ppjb_id,
        NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
            AS induk_kosong,
        stok.stok_id IS NOT NULL                       AS stok_ketemu,
        stok.flag_aktif_key = 'A'                      AS stok_aktif,
        stok.kd_perusahaan_key = param.perusahaan      AS perusahaan_cocok,
        (stok.blok_text IS NOT NULL AND stok.nomor_text IS NOT NULL)
                                                       AS blok_nomor_tidak_null,
        (
            NULLIF(BTRIM(COALESCE(stok.blok_text, '')), '') IS NOT NULL
            AND NULLIF(BTRIM(COALESCE(stok.nomor_text, '')), '') IS NOT NULL
        )                                              AS blok_nomor_tidak_kosong,
        (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
        )                                              AS ppjb_aktif,
        EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis_key = stok.kd_jenis_key
              AND t.kd_tipe_key  = stok.kd_tipe_key
        )                                              AS tipe_ketemu,
        EXISTS (
            SELECT 1 FROM tipe_norm AS t
            WHERE t.kd_jenis_key = stok.kd_jenis_key_lama
              AND t.kd_tipe_key  = stok.kd_tipe_key_lama
        )                                              AS tipe_ketemu_urutan_lama,
        EXISTS (
            SELECT 1 FROM tipe_jenis_norm AS tj
            WHERE tj.kd_jenis_key = stok.kd_jenis_key
              AND tj.kd_tipe_key  = stok.kd_tipe_key
        )                                              AS jenis_bangunan_ketemu,
        EXISTS (
            SELECT 1 FROM tipe_jenis_norm AS tj
            WHERE tj.kd_jenis_key = stok.kd_jenis_key
              AND tj.kd_tipe_key  = stok.kd_tipe_key
              AND tj.flag_laporan IN ('1', '2', '3', '4', '5')
        )                                              AS flag_laporan_1_sampai_5
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    LEFT JOIN stok_norm AS stok
           ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
)
SELECT
    COUNT(*)                                                        AS t0_ppjb_pada_periode,
    COUNT(*) FILTER (WHERE induk_kosong)                            AS t1_parent_id_kosong,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu)            AS t2_join_stok,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif)                              AS t3_stok_aktif,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok)         AS t4_perusahaan,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null)                   AS t5_blok_nomor_tidak_null,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_kosong)                 AS t5b_blok_nomor_tidak_kosong,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null AND ppjb_aktif)    AS t6_ppjb_aktif,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null AND ppjb_aktif
                       AND tipe_ketemu)                             AS t7_tipe_ketemu,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null AND ppjb_aktif
                       AND tipe_ketemu_urutan_lama)                 AS t7b_tipe_ketemu_urutan_lama,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null AND ppjb_aktif
                       AND jenis_bangunan_ketemu)                   AS t8_jenis_bangunan_ketemu,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu
                       AND stok_aktif AND perusahaan_cocok
                       AND blok_nomor_tidak_null AND ppjb_aktif
                       AND flag_laporan_1_sampai_5)                 AS t9_flag_laporan_1_sampai_5
FROM sumber;


/* =====================================================================
 * QUERY 2 — Tahap terakhir: batas persentase pembayaran
 * Menghitung berapa baris yang lolos batas pembayaran, sekaligus nilai
 * yang seharusnya muncul di baris TOTAL laporan.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           100::numeric      AS persen
),
stok_norm AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            ''))) AS kd_tipe_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) AS kd_perusahaan_key,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) AS flag_aktif_key,
        to_jsonb(stok) ->> 'blok'  AS blok_text,
        to_jsonb(stok) ->> 'nomor' AS nomor_text
    FROM public.sr_stok AS stok
),
tipe_jenis_norm AS (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))  AS kd_tipe_key
    FROM public.sr_tipe AS t
    INNER JOIN public.sr_jenis_bangunan AS jb
            ON UPPER(BTRIM(COALESCE(to_jsonb(jb) ->> 'kd_jenis', '')))
             = UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', '')))
),
dasar AS (
    SELECT DISTINCT
        ppjb.ppjb_id,
        COALESCE(ppjb.harga_jual, 0) AS harga_jual
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN stok_norm AS stok
            ON stok.stok_id = ppjb.stok_id
    INNER JOIN tipe_jenis_norm AS tj
            ON tj.kd_jenis_key = stok.kd_jenis_key
           AND tj.kd_tipe_key  = stok.kd_tipe_key
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
      AND stok.flag_aktif_key = 'A'
      AND stok.kd_perusahaan_key = param.perusahaan
      AND stok.blok_text IS NOT NULL
      AND stok.nomor_text IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
hitung AS (
    SELECT
        d.ppjb_id,
        d.harga_jual,
        d.harga_jual + COALESCE((
            SELECT SUM(ja.jumlah)
            FROM public.sr_jadwal_angsuran AS ja
            INNER JOIN public.sr_kode_transaksi AS kt
                    ON UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'kd_transaksi', '')))
                     = UPPER(BTRIM(COALESCE(to_jsonb(ja) ->> 'kd_transaksi', '')))
            WHERE CAST(ja.ppjb_id AS text) = CAST(d.ppjb_id AS text)
              AND (
                    (UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'flag_hitung', ''))) = 'Y'
                     AND UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'flag_pajak', ''))) = 'Y')
                    OR UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'kd_transaksi', ''))) = 'DCB'
                  )
              AND UPPER(BTRIM(COALESCE(to_jsonb(ja) ->> 'kd_transaksi', '')))
                  NOT IN ('ANG', 'UMK')
        ), 0) AS harga_setelah_ppjb,
        COALESCE((
            SELECT SUM(a.jumlah_bayar)
            FROM public.sr_angsuran AS a
            INNER JOIN public.sr_kode_transaksi AS kt
                    ON UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'kd_transaksi', '')))
                     = UPPER(BTRIM(COALESCE(to_jsonb(a) ->> 'kd_transaksi', '')))
            WHERE CAST(a.ppjb_id AS text) = CAST(d.ppjb_id AS text)
              AND (
                    (UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'flag_hitung', ''))) = 'Y'
                     AND UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'flag_pajak', ''))) = 'Y')
                    OR UPPER(BTRIM(COALESCE(to_jsonb(kt) ->> 'kd_transaksi', ''))) = 'DCB'
                  )
              AND UPPER(BTRIM(COALESCE(to_jsonb(a) ->> 'flag_aktif', ''))) = 'A'
        ), 0) AS jml_bayar
    FROM dasar AS d
)
SELECT
    COUNT(*)                                            AS sebelum_batas_persen,
    COUNT(*) FILTER (
        WHERE harga_setelah_ppjb > 0
          AND (jml_bayar / harga_setelah_ppjb) * 100 >= (SELECT persen FROM param)
    )                                                   AS lolos_batas_persen,
    COALESCE(SUM(harga_jual) FILTER (
        WHERE harga_setelah_ppjb > 0
          AND (jml_bayar / harga_setelah_ppjb) * 100 >= (SELECT persen FROM param)
    ), 0)                                               AS total_harga_jual,
    COALESCE(SUM(jml_bayar) FILTER (
        WHERE harga_setelah_ppjb > 0
          AND (jml_bayar / harga_setelah_ppjb) * 100 >= (SELECT persen FROM param)
    ), 0)                                               AS total_jml_bayar
FROM hitung;


/* =====================================================================
 * QUERY 3 — Nama pembeli
 * Menghitung berapa PPJB yang datanya ketemu memakai ppjb_id apa adanya
 * dan berapa yang baru ketemu memakai versi angkanya saja.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT DISTINCT
        CAST(ppjb.ppjb_id AS text) AS ppjb_id_text,
        NULLIF(REGEXP_REPLACE(CAST(ppjb.ppjb_id AS text), '[^0-9]', '', 'g'), '')
            AS ppjb_id_angka
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
)
SELECT
    COUNT(*) AS jumlah_ppjb,
    COUNT(*) FILTER (WHERE EXISTS (
        SELECT 1 FROM public.sr_pembeli_ppjb AS pp
        WHERE CAST(pp.ppjb_id AS text) = d.ppjb_id_text
    ))       AS ketemu_id_asli,
    COUNT(*) FILTER (WHERE d.ppjb_id_angka IS NOT NULL AND EXISTS (
        SELECT 1 FROM public.sr_pembeli_ppjb AS pp
        WHERE CAST(pp.ppjb_id AS text) = d.ppjb_id_angka
    ))       AS ketemu_versi_angka
FROM dasar AS d;


/* =====================================================================
 * CARA MEMBACA
 *
 * QUERY 0 memastikan nama kolom yang sebenarnya. Bila sr_stok ternyata
 * memakai nama lain lagi (bukan kd_jenis maupun kd_jenis_bgn), beri tahu
 * saya karena model pun perlu disesuaikan.
 *
 * Pada QUERY 1, bandingkan tiap kolom dengan tetangganya di sebelah kiri:
 *
 *   t5 vs t5b  -> bila berbeda, ada blok atau nomor berisi spasi kosong.
 *                 Desktop hanya menolak NULL, jadi model harus mengikuti t5.
 *   t7 vs t7b  -> membandingkan urutan kandidat kolom kd_jenis/kd_tipe.
 *                 Bila t7 lebih besar, urutan baru memang lebih tepat.
 *   t8 vs t7   -> selisihnya adalah stok yang menemukan tipe tetapi tidak
 *                 menemukan jenis bangunan.
 *   t9 vs t8   -> selisihnya adalah jenis bangunan dengan flag_laporan di
 *                 luar '1'..'5'. Batasan itu sudah dihapus dari model,
 *                 jadi angka yang benar adalah t8.
 *
 * QUERY 2 memberi angka akhir. lolos_batas_persen seharusnya sama dengan
 * jumlah baris pada laporan desktop, dan total_harga_jual serta
 * total_jml_bayar seharusnya sama dengan kedua angka pada baris TOTAL.
 *
 * QUERY 3: bila ketemu_versi_angka jauh lebih besar daripada
 * ketemu_id_asli, berarti sr_pembeli_ppjb menyimpan PPJB dalam format
 * angka saja. Model sudah mencari dengan kedua bentuk itu.
 * ===================================================================== */


/* =====================================================================
 * HASIL PENGUKURAN 08-09-2026
 *
 * QUERY 1 : 7226, 5453, 5453, 5453, 822, 822, 822, 777, 571, 571, 571, 571
 * QUERY 2 : 571 -> 565, total 1.437.904.434.788 / 1.438.313.933.420,50
 * QUERY 3 : 1109 PPJB, 1109 ketemu id asli, 0 versi angka
 *
 * Pembacaan:
 *   t6 = 777 baris lolos seluruh filter dasar.
 *   t7 = 571, jadi 206 baris gugur saat menyambung sr_tipe.
 *   t7b = t7, jadi urutan kandidat kd_jenis/kd_tipe bukan penyebabnya.
 *   t8 = t9 = t7, jadi jenis bangunan dan flag_laporan bukan penyebabnya.
 *
 * Desktop menghasilkan 736 baris. Bila join sr_tipe di web berperilaku
 * sama dengan desktop, t7 seharusnya sekitar 740 dan bukan 571. Jadi
 * kunci penyambungan sr_stok ke sr_tipe itulah yang belum tepat.
 *
 * QUERY 3 juga menunjukkan seluruh PPJB menemukan baris di
 * sr_pembeli_ppjb memakai ppjb_id apa adanya, sehingga kolom Nama Pembeli
 * yang berisi '-' bukan karena format id.
 *
 * QUERY 4 sampai 7 di bawah dipakai untuk menutup kedua pertanyaan itu.
 * ===================================================================== */


/* =====================================================================
 * QUERY 4 — Kolom sr_stok mana yang sebenarnya cocok dengan sr_tipe
 *
 * Setiap kolom sr_stok diuji satu per satu terhadap daftar KD_JENIS dan
 * KD_TIPE pada sr_tipe. Kolom dengan angka cocok tertinggi adalah kolom
 * yang seharusnya dipakai model.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT DISTINCT ON (stok.stok_id) stok.*
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
      AND to_jsonb(stok) ->> 'blok'  IS NOT NULL
      AND to_jsonb(stok) ->> 'nomor' IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
tipe_jenis AS (
    SELECT DISTINCT UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS nilai
    FROM public.sr_tipe AS t
),
tipe_tipe AS (
    SELECT DISTINCT UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', ''))) AS nilai
    FROM public.sr_tipe AS t
),
nilai_stok AS (
    SELECT
        d.stok_id,
        kv.key                                   AS nama_kolom,
        UPPER(BTRIM(COALESCE(kv.value, '')))     AS nilai
    FROM dasar AS d,
         LATERAL jsonb_each_text(to_jsonb(d)) AS kv
    WHERE COALESCE(kv.value, '') <> ''
)
SELECT
    nama_kolom,
    COUNT(DISTINCT stok_id) FILTER (
        WHERE nilai IN (SELECT nilai FROM tipe_jenis)
    ) AS cocok_dengan_kd_jenis,
    COUNT(DISTINCT stok_id) FILTER (
        WHERE nilai IN (SELECT nilai FROM tipe_tipe)
    ) AS cocok_dengan_kd_tipe
FROM nilai_stok
GROUP BY nama_kolom
HAVING COUNT(DISTINCT stok_id) FILTER (WHERE nilai IN (SELECT nilai FROM tipe_jenis)) > 0
    OR COUNT(DISTINCT stok_id) FILTER (WHERE nilai IN (SELECT nilai FROM tipe_tipe)) > 0
ORDER BY cocok_dengan_kd_tipe DESC, cocok_dengan_kd_jenis DESC;


/* =====================================================================
 * QUERY 5 — Contoh pasangan yang gagal menyambung sr_tipe
 * Memakai kunci yang dipakai model sekarang.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            ''))) AS kd_tipe_key
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
      AND to_jsonb(stok) ->> 'blok'  IS NOT NULL
      AND to_jsonb(stok) ->> 'nomor' IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
tipe_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))  AS kd_tipe_key
    FROM public.sr_tipe AS t
)
SELECT
    d.kd_jenis_key,
    d.kd_tipe_key,
    COUNT(*)                                                      AS jumlah_ppjb,
    EXISTS (SELECT 1 FROM tipe_norm tn WHERE tn.kd_jenis_key = d.kd_jenis_key)
                                                                  AS jenis_ada_di_sr_tipe,
    EXISTS (SELECT 1 FROM tipe_norm tn WHERE tn.kd_tipe_key  = d.kd_tipe_key)
                                                                  AS tipe_ada_di_sr_tipe
FROM dasar AS d
WHERE NOT EXISTS (
        SELECT 1 FROM tipe_norm AS tn
        WHERE tn.kd_jenis_key = d.kd_jenis_key
          AND tn.kd_tipe_key  = d.kd_tipe_key
      )
GROUP BY d.kd_jenis_key, d.kd_tipe_key
ORDER BY jumlah_ppjb DESC
LIMIT 40;


/* =====================================================================
 * QUERY 6 — Isi sr_tipe sebagai pembanding
 * ===================================================================== */
SELECT
    UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis,
    COUNT(*)                                               AS jumlah_baris,
    COUNT(DISTINCT UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))) AS jumlah_kd_tipe,
    MIN(UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))) AS contoh_kd_tipe_terkecil,
    MAX(UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))) AS contoh_kd_tipe_terbesar
FROM public.sr_tipe AS t
GROUP BY 1
ORDER BY jumlah_baris DESC;


/* =====================================================================
 * QUERY 7 — Kenapa kolom Nama Pembeli berisi '-'
 *
 * QUERY 3 sudah membuktikan barisnya ketemu, jadi yang diperiksa di sini
 * adalah penyaring flag_aktif dan penyambungan ke sr_nasabah.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT DISTINCT CAST(ppjb.ppjb_id AS text) AS ppjb_id_text
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
),
pembeli AS (
    SELECT
        d.ppjb_id_text,
        UPPER(BTRIM(COALESCE(to_jsonb(pp) ->> 'flag_aktif', '')))  AS flag_aktif_pembeli,
        NULLIF(UPPER(BTRIM(COALESCE(to_jsonb(pp) ->> 'nasabah_id', ''))), '') AS nasabah_id_key,
        NULLIF(BTRIM(COALESCE(
            to_jsonb(pp) ->> 'nama',
            to_jsonb(pp) ->> 'nama_nasabah',
            to_jsonb(pp) ->> 'nama_pembeli',
            '')), '')                                              AS nama_inline
    FROM dasar AS d
    INNER JOIN public.sr_pembeli_ppjb AS pp
            ON CAST(pp.ppjb_id AS text) = d.ppjb_id_text
)
SELECT
    COUNT(*)                                                       AS baris_pembeli,
    COUNT(*) FILTER (
        WHERE COALESCE(NULLIF(flag_aktif_pembeli, ''), 'Y') IN ('A', 'Y')
    )                                                              AS lolos_filter_flag_aktif,
    COUNT(*) FILTER (WHERE nama_inline IS NOT NULL)                AS punya_nama_inline,
    COUNT(*) FILTER (WHERE nasabah_id_key IS NOT NULL)             AS punya_nasabah_id,
    COUNT(*) FILTER (WHERE EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
        WHERE NULLIF(UPPER(BTRIM(COALESCE(to_jsonb(n) ->> 'nasabah_id', ''))), '')
              = pembeli.nasabah_id_key
    ))                                                             AS nasabah_ketemu
FROM pembeli;


/* Sebaran nilai flag_aktif pada sr_pembeli_ppjb. */
SELECT
    UPPER(BTRIM(COALESCE(to_jsonb(pp) ->> 'flag_aktif', '(kolom tidak ada / NULL)'))) AS flag_aktif,
    COUNT(*) AS jumlah
FROM public.sr_pembeli_ppjb AS pp
GROUP BY 1
ORDER BY jumlah DESC;


/* =====================================================================
 * HASIL QUERY 4 — 08-09-2026
 *
 *   kd_tipe_bgn    cocok_kd_jenis = 0     cocok_kd_tipe = 571
 *   blok           0                      14
 *   kd_jenis_bgn   777                    0
 *
 * Pembacaan:
 *   kd_jenis_bgn cocok untuk SELURUH 777 baris, jadi sisi jenis sudah benar.
 *   kd_tipe_bgn adalah satu-satunya kolom yang memuat kode tipe, dan hanya
 *   571 dari 777 nilainya ditemukan di sr_tipe. Kecocokan 14 pada kolom blok
 *   hanya kebetulan.
 *
 * Jadi bukan salah kolom. 206 kode tipe milik stok memang TIDAK ADA di
 * sr_tipe pada PostgreSQL, sementara tabel TIPE di SQL Server memuatnya
 * sehingga desktop tetap menampilkan barisnya.
 *
 * Model sudah disesuaikan: join ke sr_tipe dan sr_jenis_bangunan diubah
 * menjadi LEFT JOIN. QUERY 8 dan 9 di bawah dipakai untuk memastikan
 * apakah sr_tipe memang kurang lengkap atau kodenya tersimpan di kolom lain.
 * ===================================================================== */


/* =====================================================================
 * QUERY 8 — Kolom mana di sr_tipe yang memuat kode tipe milik stok
 * Kebalikan dari QUERY 4: kali ini setiap kolom sr_tipe yang diuji.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT DISTINCT ON (stok.stok_id)
        stok.stok_id,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            ''))) AS kd_tipe_key
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
      AND to_jsonb(stok) ->> 'blok'  IS NOT NULL
      AND to_jsonb(stok) ->> 'nomor' IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
nilai_tipe AS (
    SELECT
        kv.key                               AS nama_kolom,
        UPPER(BTRIM(COALESCE(kv.value, ''))) AS nilai
    FROM public.sr_tipe AS t,
         LATERAL jsonb_each_text(to_jsonb(t)) AS kv
    WHERE COALESCE(kv.value, '') <> ''
)
SELECT
    nt.nama_kolom,
    COUNT(DISTINCT d.stok_id) AS stok_yang_kode_tipenya_ketemu
FROM dasar AS d
INNER JOIN nilai_tipe AS nt
        ON nt.nilai = d.kd_tipe_key
GROUP BY nt.nama_kolom
ORDER BY stok_yang_kode_tipenya_ketemu DESC;


/* =====================================================================
 * QUERY 9 — Kode tipe milik stok yang tidak ada di sr_tipe
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan
),
dasar AS (
    SELECT
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
            ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
            ''))) AS kd_tipe_key
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok
            ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'parent_id', '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> 'flag_aktif', ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(
            NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
            NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
            ''))) = param.perusahaan
      AND to_jsonb(stok) ->> 'blok'  IS NOT NULL
      AND to_jsonb(stok) ->> 'nomor' IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(to_jsonb(ppjb) ->> 'flag_aktif', ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
tipe_norm AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_jenis', ''))) AS kd_jenis_key,
        UPPER(BTRIM(COALESCE(to_jsonb(t) ->> 'kd_tipe', '')))  AS kd_tipe_key
    FROM public.sr_tipe AS t
)
SELECT
    d.kd_jenis_key,
    CASE WHEN d.kd_tipe_key = '' THEN '(kosong)' ELSE d.kd_tipe_key END AS kd_tipe_stok,
    COUNT(*) AS jumlah_ppjb
FROM dasar AS d
WHERE NOT EXISTS (
        SELECT 1 FROM tipe_norm AS tn
        WHERE tn.kd_jenis_key = d.kd_jenis_key
          AND tn.kd_tipe_key  = d.kd_tipe_key
      )
GROUP BY 1, 2
ORDER BY jumlah_ppjb DESC
LIMIT 40;
