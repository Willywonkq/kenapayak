/*
 * DIAGNOSTIK — Daftar Surat Pesanan
 * Mencari penyebab jumlah unit di web lebih sedikit daripada desktop.
 *
 * Kasus acuan: tanggal surat pesanan 04-06-2026.
 *   Desktop : 20 unit
 *   Web     :  5 unit (DTSA/2026-F/0107, 0112, 0117, 0119, 0125)
 *
 * Jalankan ketiga query di bawah pada database PostgreSQL yang dipakai web,
 * lalu cocokkan hasilnya dengan panduan pembacaan di bagian bawah berkas ini.
 * Semua query hanya membaca; tidak ada perubahan data.
 */


/* =====================================================================
 * QUERY 1 — Corong penyaringan
 * Menghitung berapa surat pesanan yang tersisa di setiap tahap filter
 * yang dipakai model. Kolom yang angkanya tiba-tiba turun adalah tahap
 * yang membuang data.
 * ===================================================================== */
WITH sumber AS (
    SELECT um.*
    FROM public.sr_uang_muka AS um
    WHERE um.tgl_uang_muka >= DATE '2026-06-04'
      AND um.tgl_uang_muka <  DATE '2026-06-04' + INTERVAL '1 day'
),
diperiksa AS (
    SELECT
        um.uang_muka_id,
        NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
            AS induk_kosong,
        UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
            AS aktif,
        EXISTS (
            SELECT 1 FROM public.sr_stok AS s
            WHERE s.stok_id = um.stok_id
        ) AS stok_cocok_mentah,
        EXISTS (
            SELECT 1 FROM public.sr_stok AS s
            WHERE UPPER(BTRIM(CAST(s.stok_id AS text)))
                = UPPER(BTRIM(CAST(um.stok_id AS text)))
        ) AS stok_cocok_dinormalisasi,
        EXISTS (
            SELECT 1 FROM public.sr_stok AS s
            WHERE s.stok_id = um.stok_id
              AND UPPER(BTRIM(CAST(s.kd_perusahaan AS text))) = 'DTSA'
        ) AS perusahaan_cocok,
        EXISTS (
            SELECT 1 FROM public.sr_bayar_uang_muka AS b
            WHERE b.uang_muka_id = um.uang_muka_id
        ) AS ada_bayar
    FROM sumber AS um
)
SELECT
    COUNT(*) AS t0_semua_surat_pesanan,
    COUNT(*) FILTER (WHERE induk_kosong)
        AS t1_parent_id_kosong,
    COUNT(*) FILTER (WHERE induk_kosong AND aktif)
        AS t2_flag_aktif_a,
    COUNT(*) FILTER (WHERE induk_kosong AND aktif AND stok_cocok_mentah)
        AS t3_join_stok_mentah,
    COUNT(*) FILTER (WHERE induk_kosong AND aktif AND stok_cocok_dinormalisasi)
        AS t3b_join_stok_dinormalisasi,
    COUNT(*) FILTER (WHERE induk_kosong AND aktif AND perusahaan_cocok)
        AS t4_perusahaan_dtsa,
    COUNT(*) FILTER (WHERE induk_kosong AND aktif AND perusahaan_cocok AND ada_bayar)
        AS t5_punya_baris_bayar
FROM diperiksa;


/* =====================================================================
 * QUERY 2 — Rincian per surat pesanan pada tanggal tersebut
 * Menampilkan status tiap pemeriksaan sehingga terlihat surat pesanan
 * mana yang gugur dan karena apa.
 * ===================================================================== */
SELECT
    BTRIM(CAST(um.no_uang_muka AS text))                AS no_surat_pesanan,
    um.tgl_uang_muka,
    um.tgl_entry,
    CAST(um.flag_aktif AS text)                         AS flag_aktif,
    CAST(um.flag_batal AS text)                         AS flag_batal,
    CAST(um.parent_id AS text)                          AS parent_id,
    CAST(um.stok_id AS text)                            AS stok_id,
    (SELECT BTRIM(CAST(s.blok AS text)) || '/' || BTRIM(CAST(s.nomor AS text))
       FROM public.sr_stok AS s WHERE s.stok_id = um.stok_id
      LIMIT 1)                                          AS blok_nomor,
    (SELECT BTRIM(CAST(s.kd_perusahaan AS text))
       FROM public.sr_stok AS s WHERE s.stok_id = um.stok_id
      LIMIT 1)                                          AS kd_perusahaan,
    EXISTS (SELECT 1 FROM public.sr_stok AS s
             WHERE s.stok_id = um.stok_id)              AS stok_ketemu,
    (SELECT COUNT(*) FROM public.sr_bayar_uang_muka AS b
      WHERE b.uang_muka_id = um.uang_muka_id)           AS jumlah_baris_bayar
FROM public.sr_uang_muka AS um
WHERE um.tgl_uang_muka >= DATE '2026-06-04'
  AND um.tgl_uang_muka <  DATE '2026-06-04' + INTERVAL '1 day'
ORDER BY blok_nomor;


/* =====================================================================
 * QUERY 3 — Apakah 20 surat pesanan versi desktop memang ada di database?
 * Daftar di bawah disalin apa adanya dari laporan desktop tanggal
 * 04-06-2026. Baris yang tidak muncul di hasil query berarti tidak ada
 * sama sekali di sr_uang_muka.
 * ===================================================================== */
WITH desktop(no_surat_pesanan) AS (
    VALUES
        ('DTSA/2026-F/0107'), ('DTSA/2026-F/0108'), ('DTSA/2026-F/0109'),
        ('DTSA/2026-F/0110'), ('DTSA/2026-F/0111'), ('DTSA/2026-F/0112'),
        ('DTSA/2026-F/0113'), ('DTSA/2026-F/0114'), ('DTSA/2026-F/0115'),
        ('DTSA/2026-F/0117'), ('DTSA/2026-F/0118'), ('DTSA/2026-F/0119'),
        ('DTSA/2026-F/0120'), ('DTSA/2026-F/0121'), ('DTSA/2026-F/0122'),
        ('DTSA/2026-F/0124'), ('DTSA/2026-F/0125'), ('DTSA/2026-F/0129'),
        ('DTSA/2026-F/0143'), ('DTSA/2026-F/0144')
)
SELECT
    d.no_surat_pesanan,
    um.uang_muka_id IS NOT NULL          AS ada_di_sr_uang_muka,
    um.tgl_uang_muka,
    um.tgl_entry,
    CAST(um.flag_aktif AS text)          AS flag_aktif,
    CAST(um.flag_batal AS text)          AS flag_batal,
    CAST(um.parent_id AS text)           AS parent_id
FROM desktop AS d
LEFT JOIN public.sr_uang_muka AS um
       ON UPPER(BTRIM(CAST(um.no_uang_muka AS text))) = d.no_surat_pesanan
ORDER BY d.no_surat_pesanan;


/* =====================================================================
 * CARA MEMBACA HASILNYA
 *
 * QUERY 3 dijawab lebih dulu, karena paling menentukan.
 *
 * A. Banyak baris ada_di_sr_uang_muka = false
 *    -> Datanya memang tidak ada di PostgreSQL. Bukan query yang salah,
 *       melainkan migrasi/sinkronisasi sr_uang_muka yang belum lengkap.
 *
 * B. Semua ada, tetapi tgl_uang_muka-nya bukan 2026-06-04
 *    -> Kolom tanggal tergeser saat migrasi (mis. tertukar dengan
 *       tgl_entry, atau zona waktu menggeser tanggal). Filter tanggal
 *       yang perlu disesuaikan.
 *
 * C. Semua ada dan tanggalnya benar
 *    -> Lihat QUERY 1. Kolom pertama yang angkanya turun menunjukkan
 *       tahap yang membuang data:
 *
 *       t1  turun -> parent_id terisi padahal seharusnya kosong.
 *       t2  turun -> flag_aktif bukan 'A' (cek juga flag_batal di QUERY 2).
 *       t3  turun tetapi t3b tetap penuh
 *           -> stok_id di sr_uang_muka dan sr_stok berbeda spasi/huruf
 *              besar-kecil. Join mentah di model harus dinormalisasi.
 *       t3 dan t3b sama-sama turun -> baris stok-nya belum ada di sr_stok.
 *       t4  turun -> kd_perusahaan pada sr_stok bukan 'DTSA'.
 *       t5  turun -> tidak ada baris di sr_bayar_uang_muka. Ini sudah
 *              tidak lagi membuang unit sejak join diubah menjadi
 *              LEFT JOIN, jadi cukup sebagai informasi.
 * ===================================================================== */
