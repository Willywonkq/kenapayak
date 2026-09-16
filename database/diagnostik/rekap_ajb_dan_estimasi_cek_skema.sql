-- =====================================================================
-- DIAGNOSTIK SKEMA - REKAP PPAT/AKTA JUAL BELI & REKAP ESTIMASI BIAYA AJB
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- Kedua model menulis nama tabel dan kolom apa adanya, mengikuti bentuk
-- berkas lama. Konsekuensinya, tabel atau kolom yang tidak ada akan
-- membuat query gagal. Jalankan ketiga query di bawah lebih dulu.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
-- Enam tabel terakhir belum pernah dipakai fitur lain, jadi itu yang
-- paling mungkin bermasalah.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_akta'), ('sr_ppjb'), ('sr_pembeli_ppjb'), ('sr_nasabah'),
        ('sr_sertipikat'), ('sr_stok'), ('sr_lokasi'), ('sr_sektor'),
        ('sr_biaya_ajb'), ('sr_tbl_notaris'), ('sr_bank'),
        ('sr_perjanjian_bank'), ('sr_tipe_bayar'), ('sr_agen'),
        ('sr_sales'), ('sr_tipe'), ('sr_jenis_bangunan')
)
SELECT
    d.nama_tabel,
    EXISTS (
        SELECT 1 FROM information_schema.tables AS t
        WHERE t.table_schema = 'public' AND t.table_name = d.nama_tabel
    ) AS ada
FROM dipakai AS d
ORDER BY 2, 1;


-- ---------------------------------------------------------------------
-- QUERY 2 : kolom yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: NOL BARIS. Setiap baris yang muncul berarti kolom itu
-- tidak ada pada hasil migrasi dan perlu saya tangani.
WITH diperlukan (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_akta', 'ppjb_id'), ('sr_akta', 'no_akta'),
        ('sr_akta', 'tgl_akta'), ('sr_akta', 'no_notaris'),
        ('sr_akta', 'tgl_notaris'), ('sr_akta', 'notaris'),
        ('sr_akta', 'ttd_akta'), ('sr_akta', 'harga'),
        ('sr_akta', 'harga_njop'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'stok_id'),
        ('sr_ppjb', 'no_ppjb'), ('sr_ppjb', 'tgl_ppjb'),
        ('sr_ppjb', 'harga_jual'), ('sr_ppjb', 'flag_aktif'),
        ('sr_ppjb', 'parent_id'), ('sr_ppjb', 'user_entry'),
        ('sr_ppjb', 'tipe_bayar'), ('sr_ppjb', 'perjanjian_bank_id'),
        ('sr_ppjb', 'kd_agen'), ('sr_ppjb', 'kd_sales'),
        ('sr_ppjb', 'dpp'), ('sr_ppjb', 'ppn'),

        ('sr_pembeli_ppjb', 'ppjb_id'), ('sr_pembeli_ppjb', 'nasabah_id'),
        ('sr_pembeli_ppjb', 'flag_aktif'),

        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),
        ('sr_nasabah', 'telp_rmh'), ('sr_nasabah', 'fax_rmh'),
        ('sr_nasabah', 'telp_ktr'), ('sr_nasabah', 'fax_ktr'),
        ('sr_nasabah', 'no_hp'), ('sr_nasabah', 'alamat_rmh'),
        ('sr_nasabah', 'kota_rmh'), ('sr_nasabah', 'kode_pos_rmh'),

        ('sr_sertipikat', 'stok_id'), ('sr_sertipikat', 'no_sertipikat'),
        ('sr_sertipikat', 'tgl_sertipikat'), ('sr_sertipikat', 'luas_sup'),

        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'luas_tanah'), ('sr_stok', 'luas_bangunan'),
        ('sr_stok', 'flag_aktif'), ('sr_stok', 'parent_id'),
        ('sr_stok', 'kd_jenis'), ('sr_stok', 'kd_tipe'),
        ('sr_stok', 'no_virtual_acc'), ('sr_stok', 'atas_nama_va'),

        ('sr_lokasi', 'deskripsi'),
        ('sr_sektor', 'deskripsi'), ('sr_sektor', 'flag_aktif'),

        ('sr_biaya_ajb', 'ppjb_id'), ('sr_biaya_ajb', 'no_dokumen'),
        ('sr_biaya_ajb', 'tgl_dokumen'), ('sr_biaya_ajb', 'lb'),
        ('sr_biaya_ajb', 'lbb'), ('sr_biaya_ajb', 'lt'),
        ('sr_biaya_ajb', 'njop_lb'), ('sr_biaya_ajb', 'njop_lbb'),
        ('sr_biaya_ajb', 'njop_lt'), ('sr_biaya_ajb', 'bea_surat'),
        ('sr_biaya_ajb', 'bea_hgb'), ('sr_biaya_ajb', 'selisih_njop'),
        ('sr_biaya_ajb', 'bea_pnbp'), ('sr_biaya_ajb', 'bea_bphtb'),
        ('sr_biaya_ajb', 'bea_cadangan'), ('sr_biaya_ajb', 'fee_pajak'),
        ('sr_biaya_ajb', 'total_dev'), ('sr_biaya_ajb', 'total_notaris'),
        ('sr_biaya_ajb', 'keterangan'), ('sr_biaya_ajb', 'flag_aktif'),
        ('sr_biaya_ajb', 'tgl_entry'), ('sr_biaya_ajb', 'user_entry'),
        ('sr_biaya_ajb', 'tgl_update'), ('sr_biaya_ajb', 'user_update'),
        ('sr_biaya_ajb', 'kd_notaris'),

        ('sr_tbl_notaris', 'kd_notaris'), ('sr_tbl_notaris', 'nm_notaris'),
        ('sr_tbl_notaris', 'no_rekening'), ('sr_tbl_notaris', 'cabang_bank'),
        ('sr_tbl_notaris', 'kd_bank'),

        ('sr_bank', 'kd_bank'), ('sr_bank', 'nama'),
        ('sr_perjanjian_bank', 'perjanjian_bank_id'),
        ('sr_perjanjian_bank', 'kd_bank'),
        ('sr_tipe_bayar', 'tipe_bayar'), ('sr_tipe_bayar', 'nama'),
        ('sr_agen', 'kd_agen'), ('sr_agen', 'nama_agen'),
        ('sr_sales', 'kd_sales'), ('sr_sales', 'deskripsi'),
        ('sr_tipe', 'kd_jenis'), ('sr_tipe', 'kd_tipe'),
        ('sr_tipe', 'deskripsi'),
        ('sr_jenis_bangunan', 'kd_jenis'),
        ('sr_jenis_bangunan', 'flag_laporan')
)
SELECT d.nama_tabel, d.nama_kolom AS kolom_yang_hilang
FROM diperlukan AS d
WHERE EXISTS (
        SELECT 1 FROM information_schema.tables AS t
        WHERE t.table_schema = 'public' AND t.table_name = d.nama_tabel
      )
  AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns AS c
        WHERE c.table_schema = 'public'
          AND c.table_name = d.nama_tabel
          AND LOWER(c.column_name) = d.nama_kolom
      )
ORDER BY 1, 2;


-- ---------------------------------------------------------------------
-- QUERY 3 : bentuk kunci pada tabel-tabel baru
-- ---------------------------------------------------------------------
-- Pelajaran dari Daftar Akta Jual Beli: kunci pada database ini ditulis
-- berawalan, misalnya DBPSA-18784, tetapi sebagian tabel terlanjur dibuat
-- bertipe angka sehingga awalannya terbuang dan join tidak pernah
-- menemukan pasangan.
--
-- Yang perlu diperhatikan pada sr_biaya_ajb.ppjb_id. Kalau tipenya
-- numeric sedangkan sr_ppjb.ppjb_id varchar berawalan, laporan Rekap
-- Estimasi Biaya akan kosong dan saya harus menyesuaikan join-nya.
SELECT
    table_name AS nama_tabel,
    column_name AS nama_kolom,
    udt_name AS tipe,
    numeric_precision AS presisi,
    numeric_scale AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND column_name IN ('ppjb_id', 'stok_id', 'nasabah_id', 'sertipikat_id',
                      'kd_notaris', 'kd_bank', 'perjanjian_bank_id',
                      'kd_agen', 'kd_sales', 'kd_jenis', 'kd_tipe',
                      'tipe_bayar')
  AND table_name IN ('sr_akta', 'sr_ppjb', 'sr_pembeli_ppjb', 'sr_nasabah',
                     'sr_sertipikat', 'sr_stok', 'sr_biaya_ajb',
                     'sr_tbl_notaris', 'sr_bank', 'sr_perjanjian_bank',
                     'sr_tipe_bayar', 'sr_agen', 'sr_sales', 'sr_tipe',
                     'sr_jenis_bangunan')
ORDER BY column_name, table_name;


-- ---------------------------------------------------------------------
-- QUERY 4 : apakah kunci sr_biaya_ajb benar-benar ketemu
-- ---------------------------------------------------------------------
-- Perbandingan memakai teks sehingga tidak mungkin error, apa pun isi
-- kolomnya. Kalau "cocok" jauh lebih kecil daripada "terisi", berarti
-- bentuk penulisannya berbeda seperti pada kasus SERTIPIKAT_ID dulu.
SELECT
    (SELECT COUNT(*) FROM public.sr_biaya_ajb) AS baris_biaya_ajb,
    (SELECT COUNT(*) FROM public.sr_biaya_ajb b
      WHERE b.ppjb_id IS NOT NULL) AS ppjb_id_terisi,
    (SELECT COUNT(*) FROM public.sr_biaya_ajb b
      WHERE EXISTS (SELECT 1 FROM public.sr_ppjb p
                     WHERE BTRIM(CAST(p.ppjb_id AS TEXT))
                         = BTRIM(CAST(b.ppjb_id AS TEXT)))) AS ppjb_id_cocok;
