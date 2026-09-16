-- =====================================================================
-- DIAGNOSTIK SKEMA - REKAP PPAT/AKTA JUAL BELI & REKAP ESTIMASI BIAYA AJB
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- Kedua model menulis nama tabel dan kolom apa adanya, mengikuti bentuk
-- berkas lama. Konsekuensinya, tabel atau kolom yang tidak ada akan
-- membuat query gagal.
--
-- ---------------------------------------------------------------------
-- HASIL PADA DATABASE DTSA  (QUERY 1 s/d 4 sudah dijawab)
-- ---------------------------------------------------------------------
-- QUERY 1 : ketujuh belas tabel ada semua.
--
-- QUERY 2 : hanya dua kolom yang tidak ketemu, yaitu sr_stok.kd_jenis dan
--           sr_stok.kd_tipe. Nama sebenarnya kd_jenis_bgn dan kd_tipe_bgn,
--           sedangkan sr_tipe dan sr_jenis_bangunan tetap memakai kd_jenis
--           dan kd_tipe. Model sudah disesuaikan.
--
-- QUERY 3 : sr_biaya_ajb.ppjb_id bertipe numeric(18,0), sedangkan
--           sr_ppjb.ppjb_id bertipe varchar berisi teks berawalan seperti
--           DBPSA-18784. Sama persis dengan cacat migrasi pada
--           SERTIPIKAT_ID di fitur Daftar Akta Jual Beli.
--
-- QUERY 4 : 1.664 baris sr_biaya_ajb, seluruhnya terisi, dan NOL yang
--           cocok bila dibandingkan apa adanya. Tanpa perbaikan, Rekap
--           Estimasi Biaya AJB pasti kosong.
--
-- QUERY 5 : ternyata awalan TIDAK BISA ditebak dari angkanya. Dari
--           62.328 baris sr_ppjb hanya 38.895 angka yang berbeda, karena
--           DBPSA-1 dan DBPSS-1 sama-sama ada. Akibatnya 1.348 dari 1.664
--           baris biaya menempel ke dua PPJB sekaligus.
--
-- QUERY 6 : jawabannya ada di kolom pertama sr_biaya_ajb, yaitu
--           KD_PERUSAHAAN varchar(5), berisi SKLG, MKPP, dan seterusnya.
--           Karena setiap unit hanya memakai satu awalan, awalan yang
--           benar bisa diambil dari unit pada baris biaya itu lalu
--           disambung dengan angkanya menjadi PPJB_ID yang utuh.
--
-- Model sudah memakai cara itu, jadi seluruh baris biaya terpakai dan
-- tidak ada yang menempel ke unit yang salah. Diagnostik ini tinggal
-- menjadi catatan; tidak ada lagi yang perlu dijalankan.
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


-- ---------------------------------------------------------------------
-- QUERY 5 : apakah membuang awalan PPJB_ID aman        [JALANKAN INI]
-- ---------------------------------------------------------------------
-- sr_biaya_ajb.ppjb_id kehilangan awalannya saat migrasi, dan berbeda
-- dengan kasus SERTIPIKAT_ID dulu, di sini tidak ada kolom teks lain
-- pada baris biaya yang bisa dipakai untuk menyusun ulang awalan itu.
-- Jadi join terpaksa membandingkan angkanya saja.
--
-- Itu hanya aman bila tidak ada angka PPJB yang dipakai oleh dua awalan
-- sekaligus. Kalau DBPSA-500 dan DBPSS-500 sama-sama ada, satu baris
-- biaya bisa menempel ke unit yang salah.
--
-- Yang diharapkan: query pertama menunjukkan angka_unik sama dengan
-- jumlah_baris, dan query kedua NOL BARIS.
SELECT
    COUNT(*) AS jumlah_baris,
    COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', ''))
        AS angka_unik,
    COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', ''))
        AS awalan_berbeda
FROM public.sr_ppjb
WHERE ppjb_id IS NOT NULL;

-- Daftar angka PPJB yang dipakai lebih dari satu awalan. Kosong berarti
-- aman, dan Rekap Estimasi Biaya AJB bisa dipakai apa adanya.
SELECT
    REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
    COUNT(*) AS jumlah,
    STRING_AGG(BTRIM(CAST(ppjb_id AS TEXT)), ', ') AS nilai_asli
FROM public.sr_ppjb
WHERE ppjb_id IS NOT NULL
GROUP BY 1
HAVING COUNT(*) > 1
LIMIT 20;

-- Berapa baris sr_biaya_ajb yang menemukan pasangan setelah awalan
-- dibuang. Bandingkan dengan 1.664 baris yang terisi.
SELECT
    (SELECT COUNT(*) FROM public.sr_biaya_ajb b
      WHERE EXISTS (
            SELECT 1 FROM public.sr_ppjb p
            WHERE REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
                = REGEXP_REPLACE(BTRIM(CAST(b.ppjb_id AS TEXT)), '^[^0-9]+', '')
      )) AS cocok_tanpa_awalan,
    (SELECT COUNT(*) FROM public.sr_biaya_ajb b
      WHERE (
            SELECT COUNT(*) FROM public.sr_ppjb p
            WHERE REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
                = REGEXP_REPLACE(BTRIM(CAST(b.ppjb_id AS TEXT)), '^[^0-9]+', '')
      ) > 1) AS menempel_ke_lebih_dari_satu;


-- ---------------------------------------------------------------------
-- QUERY 6 : mencari kolom sr_biaya_ajb yang membawa awalan [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Setiap tabel pada database ini punya kunci sendiri bertipe teks
-- berawalan: sr_ppjb punya ppjb_id DBPSA-18784, sr_stok punya stok_id,
-- sr_sertipikat punya sertipikat_id. Besar kemungkinan sr_biaya_ajb juga
-- punya kunci semacam itu, misalnya biaya_ajb_id, dan awalannya bisa
-- diambil dari situ persis seperti cara model Daftar Akta Jual Beli
-- mengambil awalan SERTIPIKAT_ID dari PPJB_ID.
--
-- Query pertama menampilkan seluruh kolom sr_biaya_ajb. Cari kolom
-- bertipe varchar yang isinya berawalan huruf.
SELECT
    ordinal_position AS urutan,
    column_name AS nama_kolom,
    udt_name AS tipe,
    character_maximum_length AS panjang,
    numeric_precision AS presisi,
    numeric_scale AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'sr_biaya_ajb'
ORDER BY ordinal_position;

-- Query kedua menampilkan lima baris apa adanya, supaya terlihat kolom
-- mana yang isinya berbentuk DBPSA-xxxx atau DBPSS-xxxx.
SELECT *
FROM public.sr_biaya_ajb
LIMIT 5;

-- Query ketiga: untuk setiap kolom teks pada sr_biaya_ajb, berapa banyak
-- nilainya yang berbentuk awalan-huruf diikuti angka. Kolom dengan
-- jumlah besar itulah kandidat pembawa awalan.
SELECT
    c.column_name AS nama_kolom,
    (SELECT COUNT(*) FROM public.sr_biaya_ajb b
      WHERE to_jsonb(b) ->> c.column_name ~ '^[A-Za-z]+[^0-9]*[0-9]+$')
        AS berbentuk_berawalan,
    (SELECT COUNT(*) FROM public.sr_biaya_ajb) AS jumlah_baris
FROM information_schema.columns AS c
WHERE c.table_schema = 'public'
  AND c.table_name = 'sr_biaya_ajb'
  AND c.udt_name IN ('varchar', 'bpchar', 'text')
ORDER BY 2 DESC, 1;
