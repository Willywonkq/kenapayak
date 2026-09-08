/*
 * DIAGNOSTIK — SP Sudah PPJB (Daftar Rumah Terjual Pertanggal)
 *
 * Dipakai bila jumlah baris di web masih berbeda dengan desktop.
 * Acuan: periode 01-07-2023 s.d 07-09-2026, unit DTSA, pembayaran 100%.
 *   Desktop : 736 baris
 *   Web     : 565 baris
 *
 * Semua query hanya membaca. Ubah ketiga nilai di CTE param bila periode,
 * unit, atau batas persentasenya berbeda.
 */


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 * Kolom pertama yang angkanya turun adalah tahap yang membuang baris.
 * ===================================================================== */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           100::numeric      AS persen
),
sumber AS (
    SELECT
        ppjb.ppjb_id,
        ppjb.harga_jual,
        NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS text), '')), '') IS NULL
            AS induk_kosong,
        stok.stok_id IS NOT NULL                                   AS stok_ketemu,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) = 'A'
            AS stok_aktif,
        UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
            AS perusahaan_cocok,
        (stok.blok IS NOT NULL AND stok.nomor IS NOT NULL)         AS blok_nomor_ada,
        (
            NULLIF(BTRIM(COALESCE(CAST(stok.blok AS text), '')), '') IS NOT NULL
            AND NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS text), '')), '') IS NOT NULL
        )                                                          AS blok_nomor_tidak_kosong,
        (
            UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS text), ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
        )                                                          AS ppjb_aktif,
        EXISTS (
            SELECT 1 FROM public.sr_tipe AS t
            WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS text), '')))
              AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS text), '')))
        )                                                          AS tipe_cara_desktop,
        EXISTS (
            SELECT 1 FROM public.sr_tipe AS t
            WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text)))
                = UPPER(BTRIM(COALESCE(
                    NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''),
                    CAST(stok.kd_jenis AS text), '')))
              AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))
                = UPPER(BTRIM(COALESCE(
                    NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''),
                    CAST(stok.kd_tipe AS text), '')))
        )                                                          AS tipe_cara_model_lama,
        EXISTS (
            SELECT 1
            FROM public.sr_tipe AS t
            INNER JOIN public.sr_jenis_bangunan AS jb
                    ON UPPER(BTRIM(CAST(jb.kd_jenis AS text)))
                     = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
            WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS text), '')))
              AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS text), '')))
        )                                                          AS jenis_bangunan_ketemu,
        EXISTS (
            SELECT 1
            FROM public.sr_tipe AS t
            INNER JOIN public.sr_jenis_bangunan AS jb
                    ON UPPER(BTRIM(CAST(jb.kd_jenis AS text)))
                     = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
            WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS text), '')))
              AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))
                = UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS text), '')))
              AND UPPER(BTRIM(COALESCE(CAST(jb.flag_laporan AS text), '')))
                  IN ('1', '2', '3', '4', '5')
        )                                                          AS flag_laporan_1_sampai_5
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    LEFT JOIN public.sr_stok AS stok
           ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
),
lolos_dasar AS (
    SELECT * FROM sumber
    WHERE induk_kosong AND stok_ketemu AND stok_aktif AND perusahaan_cocok
      AND blok_nomor_ada AND ppjb_aktif
)
SELECT
    (SELECT COUNT(*) FROM sumber)                                   AS t0_ppjb_pada_periode,
    COUNT(*) FILTER (WHERE induk_kosong)                            AS t1_parent_id_kosong,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu)            AS t2_join_stok,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu AND stok_aktif)
                                                                    AS t3_stok_aktif,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu AND stok_aktif
                       AND perusahaan_cocok)                        AS t4_perusahaan,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu AND stok_aktif
                       AND perusahaan_cocok AND blok_nomor_ada)     AS t5_blok_nomor_tidak_null,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu AND stok_aktif
                       AND perusahaan_cocok AND blok_nomor_tidak_kosong)
                                                                    AS t5b_blok_nomor_tidak_kosong,
    COUNT(*) FILTER (WHERE induk_kosong AND stok_ketemu AND stok_aktif
                       AND perusahaan_cocok AND blok_nomor_ada AND ppjb_aktif)
                                                                    AS t6_ppjb_aktif
FROM sumber;


/* Lanjutan corong: tahap join referensi dan batas pembayaran. */
WITH param AS (
    SELECT DATE '2023-07-01' AS tgl_awal,
           DATE '2026-09-07' AS tgl_akhir,
           'DTSA'::text      AS perusahaan,
           100::numeric      AS persen
),
dasar AS (
    SELECT ppjb.ppjb_id, ppjb.harga_jual, stok.*
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN param
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS text), '')), '') IS NULL
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
      AND (
            UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS text), ''))) = 'A'
            OR ppjb.tgl_batal > param.tgl_akhir
          )
),
dengan_tipe AS (
    SELECT d.ppjb_id, d.harga_jual, jb.flag_laporan
    FROM dasar AS d
    INNER JOIN public.sr_tipe AS t
            ON UPPER(BTRIM(CAST(t.kd_jenis AS text)))
             = UPPER(BTRIM(COALESCE(CAST(d.kd_jenis AS text), '')))
           AND UPPER(BTRIM(CAST(t.kd_tipe AS text)))
             = UPPER(BTRIM(COALESCE(CAST(d.kd_tipe AS text), '')))
    INNER JOIN public.sr_jenis_bangunan AS jb
            ON UPPER(BTRIM(CAST(jb.kd_jenis AS text)))
             = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
),
bayar AS (
    SELECT
        dt.ppjb_id,
        dt.flag_laporan,
        COALESCE(dt.harga_jual, 0) + COALESCE((
            SELECT SUM(ja.jumlah)
            FROM public.sr_jadwal_angsuran AS ja
            INNER JOIN public.sr_kode_transaksi AS kt
                    ON UPPER(BTRIM(CAST(kt.kd_transaksi AS text)))
                     = UPPER(BTRIM(CAST(ja.kd_transaksi AS text)))
            WHERE CAST(ja.ppjb_id AS text) = CAST(dt.ppjb_id AS text)
              AND (
                    (UPPER(BTRIM(COALESCE(CAST(kt.flag_hitung AS text), ''))) = 'Y'
                     AND UPPER(BTRIM(COALESCE(CAST(kt.flag_pajak AS text), ''))) = 'Y')
                    OR UPPER(BTRIM(CAST(kt.kd_transaksi AS text))) = 'DCB'
                  )
              AND UPPER(BTRIM(CAST(ja.kd_transaksi AS text))) NOT IN ('ANG', 'UMK')
        ), 0) AS harga_setelah_ppjb,
        COALESCE((
            SELECT SUM(a.jumlah_bayar)
            FROM public.sr_angsuran AS a
            INNER JOIN public.sr_kode_transaksi AS kt
                    ON UPPER(BTRIM(CAST(kt.kd_transaksi AS text)))
                     = UPPER(BTRIM(CAST(a.kd_transaksi AS text)))
            WHERE CAST(a.ppjb_id AS text) = CAST(dt.ppjb_id AS text)
              AND (
                    (UPPER(BTRIM(COALESCE(CAST(kt.flag_hitung AS text), ''))) = 'Y'
                     AND UPPER(BTRIM(COALESCE(CAST(kt.flag_pajak AS text), ''))) = 'Y')
                    OR UPPER(BTRIM(CAST(kt.kd_transaksi AS text))) = 'DCB'
                  )
              AND UPPER(BTRIM(COALESCE(CAST(a.flag_aktif AS text), ''))) = 'A'
        ), 0) AS jml_bayar
    FROM dengan_tipe AS dt
)
SELECT
    (SELECT COUNT(*) FROM dasar)                        AS t6_ppjb_aktif,
    (SELECT COUNT(*) FROM dengan_tipe)                  AS t7_join_tipe_dan_jenis_desktop,
    COUNT(*) FILTER (
        WHERE UPPER(BTRIM(COALESCE(CAST(flag_laporan AS text), '')))
              IN ('1', '2', '3', '4', '5')
    )                                                   AS t8_flag_laporan_1_sampai_5,
    COUNT(*) FILTER (
        WHERE harga_setelah_ppjb > 0
          AND (jml_bayar / harga_setelah_ppjb) * 100 >= (SELECT persen FROM param)
    )                                                   AS t9_pembayaran_mencapai_persen,
    COALESCE(SUM(jml_bayar) FILTER (
        WHERE harga_setelah_ppjb > 0
          AND (jml_bayar / harga_setelah_ppjb) * 100 >= (SELECT persen FROM param)
    ), 0)                                               AS total_jml_bayar
FROM bayar;


/* =====================================================================
 * QUERY 2 — Nama pembeli
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
    INNER JOIN public.sr_stok AS stok ON stok.stok_id = ppjb.stok_id
    WHERE ppjb.tgl_ppjb >= param.tgl_awal
      AND ppjb.tgl_ppjb <  param.tgl_akhir + INTERVAL '1 day'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = param.perusahaan
)
SELECT
    COUNT(*)                                                     AS jumlah_ppjb,
    COUNT(*) FILTER (WHERE EXISTS (
        SELECT 1 FROM public.sr_pembeli_ppjb AS pp
        WHERE CAST(pp.ppjb_id AS text) = d.ppjb_id_text
    ))                                                           AS ketemu_id_asli,
    COUNT(*) FILTER (WHERE d.ppjb_id_angka IS NOT NULL AND EXISTS (
        SELECT 1 FROM public.sr_pembeli_ppjb AS pp
        WHERE CAST(pp.ppjb_id AS text) = d.ppjb_id_angka
    ))                                                           AS ketemu_versi_angka
FROM dasar AS d;


/* =====================================================================
 * CARA MEMBACA
 *
 * Bandingkan angka terakhir corong (t9_pembayaran_mencapai_persen) dengan
 * jumlah baris di web.
 *
 * - t5 dan t5b berbeda  -> ada blok atau nomor yang berisi spasi kosong.
 *   Desktop hanya menolak NULL, jadi model harus mengikuti t5.
 * - t7 jauh lebih kecil daripada t6 -> banyak stok yang tidak menemukan
 *   pasangan di sr_tipe. Desktop pun membuangnya, jadi ini wajar.
 * - t8 lebih kecil daripada t7 -> ada jenis bangunan dengan flag_laporan di
 *   luar '1'..'5'. Batasan itu sudah dihapus dari model.
 * - t9 lebih kecil daripada jumlah desktop -> periksa kembali perhitungan
 *   JML_BAYAR; nilai total_jml_bayar pada query ini adalah angka yang
 *   seharusnya muncul di baris TOTAL laporan.
 *
 * Pada QUERY 2, bila ketemu_versi_angka jauh lebih besar daripada
 * ketemu_id_asli, berarti sr_pembeli_ppjb menyimpan PPJB dalam format angka
 * saja. Model sudah mencari dengan kedua bentuk tersebut.
 * ===================================================================== */
