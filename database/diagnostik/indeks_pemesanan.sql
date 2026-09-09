/*
 * INDEX untuk laporan Pemesanan pada PostgreSQL
 *
 * Database hasil migrasi biasanya belum memiliki index selain primary key,
 * sehingga setiap laporan memindai seluruh isi tabel. Pada sr_angsuran dan
 * sr_jadwal_angsuran yang berisi ratusan ribu baris, itulah penyebab utama
 * laporan terasa lama.
 *
 * Seluruh perintah memakai IF NOT EXISTS sehingga aman dijalankan berulang.
 * Setiap CREATE INDEX mengunci tabelnya sebentar terhadap penulisan, jadi
 * sebaiknya dijalankan di luar jam sibuk. Bila ingin tanpa mengunci, ganti
 * CREATE INDEX menjadi CREATE INDEX CONCURRENTLY dan jalankan satu per satu
 * di luar transaksi.
 *
 * Jalankan QUERY PEMERIKSAAN di bagian bawah lebih dulu untuk melihat index
 * apa saja yang sudah ada.
 */


/* ---------- Kunci utama yang dipakai menyambung antar tabel ---------- */

CREATE INDEX IF NOT EXISTS ix_sr_uang_muka_stok        ON public.sr_uang_muka (stok_id);
CREATE INDEX IF NOT EXISTS ix_sr_uang_muka_tgl_um      ON public.sr_uang_muka (tgl_uang_muka);
CREATE INDEX IF NOT EXISTS ix_sr_uang_muka_tgl_entry   ON public.sr_uang_muka (tgl_entry);

CREATE INDEX IF NOT EXISTS ix_sr_bayar_um_um           ON public.sr_bayar_uang_muka (uang_muka_id);

CREATE INDEX IF NOT EXISTS ix_sr_ppjb_um               ON public.sr_ppjb (uang_muka_id);
CREATE INDEX IF NOT EXISTS ix_sr_ppjb_stok             ON public.sr_ppjb (stok_id);
CREATE INDEX IF NOT EXISTS ix_sr_ppjb_tgl              ON public.sr_ppjb (tgl_ppjb);

CREATE INDEX IF NOT EXISTS ix_sr_biaya_dp_um           ON public.sr_biaya_dp (uang_muka_id);

CREATE INDEX IF NOT EXISTS ix_sr_jadwal_angsuran_ppjb  ON public.sr_jadwal_angsuran (ppjb_id);
CREATE INDEX IF NOT EXISTS ix_sr_angsuran_ppjb         ON public.sr_angsuran (ppjb_id);

CREATE INDEX IF NOT EXISTS ix_sr_npv_ppjb              ON public.sr_npv (ppjb_id);

CREATE INDEX IF NOT EXISTS ix_sr_pembeli_dp_um         ON public.sr_pembeli_dp (uang_muka_id);
CREATE INDEX IF NOT EXISTS ix_sr_pembeli_ppjb_ppjb     ON public.sr_pembeli_ppjb (ppjb_id);
CREATE INDEX IF NOT EXISTS ix_sr_nasabah_id            ON public.sr_nasabah (nasabah_id);

CREATE INDEX IF NOT EXISTS ix_sr_stok_id               ON public.sr_stok (stok_id);


/* ---------- Perbarui statistik agar perencana memilih index ---------- */

ANALYZE public.sr_uang_muka;
ANALYZE public.sr_bayar_uang_muka;
ANALYZE public.sr_ppjb;
ANALYZE public.sr_biaya_dp;
ANALYZE public.sr_jadwal_angsuran;
ANALYZE public.sr_angsuran;
ANALYZE public.sr_npv;
ANALYZE public.sr_pembeli_dp;
ANALYZE public.sr_pembeli_ppjb;
ANALYZE public.sr_nasabah;
ANALYZE public.sr_stok;


/* =====================================================================
 * QUERY PEMERIKSAAN — index apa saja yang sudah ada
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
 * QUERY PEMERIKSAAN — ukuran tabel, untuk memperkirakan beban pemindaian
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
