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


/* =====================================================================
 * QUERY 4 — Kode perusahaan tiap surat pesanan pada 04-06-2026
 *
 * Ditambahkan setelah hasil QUERY 1 keluar: 21 surat pesanan lolos filter
 * KD_PERUSAHAAN = 'DTSA', tetapi web hanya menampilkan 5. Selisih 26 - 21
 * juga tepat 5. Query ini memastikan apakah 5 yang tampil di web justru
 * yang kd_perusahaan-nya BUKAN DTSA.
 * ===================================================================== */
SELECT
    UPPER(BTRIM(CAST(s.kd_perusahaan AS text)))         AS kd_perusahaan,
    BTRIM(CAST(um.no_uang_muka AS text))                AS no_surat_pesanan,
    BTRIM(CAST(s.blok AS text)) || '/' || BTRIM(CAST(s.nomor AS text))
                                                        AS blok_nomor,
    CAST(um.stok_id AS text)                            AS stok_id
FROM public.sr_uang_muka AS um
INNER JOIN public.sr_stok AS s
        ON s.stok_id = um.stok_id
WHERE um.tgl_uang_muka >= DATE '2026-06-04'
  AND um.tgl_uang_muka <  DATE '2026-06-04' + INTERVAL '1 day'
  AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
ORDER BY kd_perusahaan, blok_nomor;


/* =====================================================================
 * QUERY 5 — Sebaran surat pesanan per kode perusahaan, seluruh periode
 * laporan (01-07-2023 s.d 07-09-2026).
 *
 * Ini pembanding langsung untuk angka TOTAL. Bila muncul satu kode dengan
 * jumlah sekitar 805 dan kode lain sekitar 261, berarti laporan web
 * memakai kode perusahaan yang salah.
 * ===================================================================== */
SELECT
    UPPER(BTRIM(CAST(s.kd_perusahaan AS text)))     AS kd_perusahaan,
    COUNT(DISTINCT um.uang_muka_id)                 AS jumlah_surat_pesanan
FROM public.sr_uang_muka AS um
INNER JOIN public.sr_stok AS s
        ON s.stok_id = um.stok_id
WHERE um.tgl_uang_muka >= DATE '2023-07-01'
  AND um.tgl_uang_muka <  DATE '2026-09-07' + INTERVAL '1 day'
  AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'
  AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
GROUP BY 1
ORDER BY 2 DESC;


/* =====================================================================
 * HASIL PEMERIKSAAN — apa yang sudah terbukti
 *
 * QUERY 3 : ke-20 surat pesanan versi desktop ADA di sr_uang_muka,
 *           tanggalnya benar, flag_aktif 'A', flag_batal dan parent_id
 *           kosong. Data tidak kurang.
 *
 * QUERY 1 : 29 -> 28 -> 26 -> 26 -> 26 -> 21 -> 21.
 *           t3 = t3b, jadi stok_id tidak bermasalah.
 *           t5 = t4, jadi semua sudah punya baris bayar.
 *
 * QUERY 4 : 21 baris ber-kd_perusahaan DTSA pada 04-06-2026, dan 5 sisanya
 *           milik MKPP/SPCC/SPCH/SPCK dengan nomor surat pesanan berawalan
 *           lain. Jadi 5 unit yang tampil di web BUKAN kelompok non-DTSA.
 *
 * QUERY 5 : DTSA 796 surat pesanan untuk seluruh periode, mendekati angka
 *           805 pada laporan desktop.
 *
 * KESIMPULAN
 * Database lengkap dan tahap kandidat pada model menghasilkan 21 baris
 * untuk 04-06-2026. Seluruh join sesudah candidate_um adalah LEFT JOIN dan
 * tidak ada WHERE tambahan, sehingga query tidak mungkin memangkas 21
 * menjadi 5. Sisa kemungkinan hanya satu: nilai filter yang benar-benar
 * dikirim ke model berbeda dari yang diduga.
 *
 * LANGKAH BERIKUTNYA
 * 1. Jalankan laporan sekali lagi. Header laporan kini menyebutkan Unit
 *    dan setiap penyaring yang sedang aktif (Status, Tipe Bayar, BGB,
 *    Agen, Sales). Bila salah satunya muncul, itulah penyebabnya.
 * 2. Bila header hanya menampilkan Unit, nyalakan APP_DEBUG lalu lihat
 *    storage/logs/laravel.log pada entri
 *    "Daftar Surat Pesanan performance". Entri itu memuat candidate_count,
 *    row_count, dan seluruh nilai filter yang diterima model.
 *    candidate_count = 5 berarti penyaringan terjadi di tahap kandidat dan
 *    nilai filter pada entri yang sama menunjukkan penyebabnya.
 *    candidate_count = 21 dengan row_count = 5 berarti model yang berjalan
 *    di server bukan versi ini.
 * ===================================================================== */


/* =====================================================================
 * PENYEBAB SEBENARNYA — SUDAH DITEMUKAN DAN DIPERBAIKI
 *
 * Bukan query, bukan data, bukan filter. Penyebabnya ada di tahap hydrate
 * pada PHP, di method hydrateNasabahNames().
 *
 * Setelah query utama selesai, method itu mengisi kolom NASABAH_NAMA lalu
 * MEMBUANG setiap baris yang nama pembelinya tidak dapat ditemukan:
 *
 *     if ($row->NASABAH_NAMA !== '-') {
 *         $filtered[] = $row;
 *     }
 *     ...
 *     $rows = $filtered;
 *
 * Query desktop memanggil dbo.F_GET_PEMBELI_DP(UANG_MUKA_ID) hanya pada
 * daftar SELECT dan tidak pernah menyaring berdasarkan hasilnya, sehingga
 * surat pesanan tanpa data pembeli tetap muncul dengan kolom Nama Pembeli
 * berisi '-'.
 *
 * Karena itu tahap kandidat menghasilkan 21 baris untuk 04-06-2026 tetapi
 * laporan hanya menampilkan 5: enam belas sisanya belum memiliki baris di
 * sr_pembeli_dp / sr_pembeli_ppjb sehingga dibuang di PHP.
 *
 * Penyaringan tersebut sudah dihapus. Baris tanpa nama pembeli kini tetap
 * tampil dengan tanda '-', sama seperti desktop.
 * ===================================================================== */
