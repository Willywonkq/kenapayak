/*
 * PEMERIKSAAN INDEX untuk laporan Pemesanan pada PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada perintah yang mengubah database.
 *
 * Bagian USULAN INDEX di bawah sengaja ditulis sebagai komentar sehingga
 * tidak akan berjalan walaupun seluruh berkas dieksekusi. Pembuatan index
 * adalah perubahan struktur database dan hanya boleh dilakukan oleh yang
 * berwenang, misalnya DBA atau pemilik aplikasi. Serahkan usulan di bawah
 * kepada mereka, jangan dijalankan sendiri.
 * ==========================================================================
 */


/* =====================================================================
 * QUERY 1 — Index apa saja yang sudah ada
 * Bandingkan dengan daftar usulan di bagian bawah untuk melihat mana yang
 * belum tersedia.
 * ===================================================================== */
SELECT
    tablename  AS tabel,
    indexname  AS nama_index,
    indexdef   AS definisi
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN (
        'sr_uang_muka', 'sr_bayar_uang_muka', 'sr_ppjb', 'sr_biaya_dp',
        'sr_jadwal_angsuran', 'sr_angsuran', 'sr_npv', 'sr_pembeli_dp',
        'sr_pembeli_ppjb', 'sr_nasabah', 'sr_stok'
      )
ORDER BY tablename, indexname;


/* =====================================================================
 * QUERY 2 — Ukuran tabel dan perkiraan jumlah baris
 * Tabel besar tanpa index itulah yang membuat laporan lama.
 * ===================================================================== */
SELECT
    relname                                        AS tabel,
    to_char(n_live_tup, 'FM999G999G999')           AS perkiraan_jumlah_baris,
    pg_size_pretty(pg_total_relation_size(relid))  AS ukuran
FROM pg_stat_user_tables
WHERE schemaname = 'public'
  AND relname LIKE 'sr_%'
ORDER BY n_live_tup DESC
LIMIT 25;


/* =====================================================================
 * QUERY 3 — Ringkasan: kolom kunci mana yang belum ada index-nya
 * Kolom yang muncul di sini adalah yang paling berpengaruh pada kecepatan.
 * ===================================================================== */
WITH diperlukan(tabel, kolom) AS (
    VALUES
        ('sr_uang_muka',       'stok_id'),
        ('sr_uang_muka',       'tgl_uang_muka'),
        ('sr_uang_muka',       'tgl_entry'),
        ('sr_bayar_uang_muka', 'uang_muka_id'),
        ('sr_ppjb',            'uang_muka_id'),
        ('sr_ppjb',            'stok_id'),
        ('sr_ppjb',            'tgl_ppjb'),
        ('sr_biaya_dp',        'uang_muka_id'),
        ('sr_jadwal_angsuran', 'ppjb_id'),
        ('sr_angsuran',        'ppjb_id'),
        ('sr_npv',             'ppjb_id'),
        ('sr_pembeli_dp',      'uang_muka_id'),
        ('sr_pembeli_ppjb',    'ppjb_id'),
        ('sr_nasabah',         'nasabah_id'),
        ('sr_stok',            'stok_id')
)
SELECT
    d.tabel,
    d.kolom,
    CASE
        WHEN EXISTS (
            SELECT 1
            FROM pg_indexes AS i
            WHERE i.schemaname = 'public'
              AND i.tablename = d.tabel
              AND i.indexdef LIKE '%(' || d.kolom || '%'
        )
        THEN 'sudah ada'
        ELSE 'BELUM ADA'
    END AS status_index,
    COALESCE(
        (SELECT to_char(t.n_live_tup, 'FM999G999G999')
         FROM pg_stat_user_tables AS t
         WHERE t.schemaname = 'public' AND t.relname = d.tabel),
        '?'
    ) AS perkiraan_jumlah_baris
FROM diperlukan AS d
ORDER BY status_index DESC, d.tabel, d.kolom;


/* =====================================================================
 * USULAN INDEX — JANGAN DIJALANKAN SENDIRI
 *
 * Seluruh baris di bawah berupa komentar. Salin dan serahkan kepada yang
 * berwenang bila QUERY 3 menunjukkan masih ada yang BELUM ADA.
 *
 * Setiap pembuatan index mengunci tabelnya sebentar terhadap penulisan.
 * Varian CONCURRENTLY tidak mengunci, tetapi harus dijalankan satu per satu
 * di luar transaksi.
 *
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_uang_muka_stok       ON public.sr_uang_muka (stok_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_uang_muka_tgl_um     ON public.sr_uang_muka (tgl_uang_muka);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_uang_muka_tgl_entry  ON public.sr_uang_muka (tgl_entry);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_bayar_um_um          ON public.sr_bayar_uang_muka (uang_muka_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_ppjb_um              ON public.sr_ppjb (uang_muka_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_ppjb_stok            ON public.sr_ppjb (stok_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_ppjb_tgl             ON public.sr_ppjb (tgl_ppjb);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_biaya_dp_um          ON public.sr_biaya_dp (uang_muka_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_jadwal_angsuran_ppjb ON public.sr_jadwal_angsuran (ppjb_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_angsuran_ppjb        ON public.sr_angsuran (ppjb_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_npv_ppjb             ON public.sr_npv (ppjb_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_pembeli_dp_um        ON public.sr_pembeli_dp (uang_muka_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_pembeli_ppjb_ppjb    ON public.sr_pembeli_ppjb (ppjb_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_nasabah_id           ON public.sr_nasabah (nasabah_id);
 * CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_sr_stok_id              ON public.sr_stok (stok_id);
 *
 * ANALYZE public.sr_angsuran;
 * ANALYZE public.sr_jadwal_angsuran;
 * ANALYZE public.sr_uang_muka;
 * ANALYZE public.sr_ppjb;
 * ===================================================================== */
