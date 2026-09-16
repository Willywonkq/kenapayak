-- =====================================================================
-- DIAGNOSTIK SKEMA - DAFTAR/REKAP PERALIHAN HAK
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- Model menulis nama tabel dan kolom apa adanya, mengikuti bentuk berkas
-- lama. Tabel atau kolom yang tidak ada akan membuat query gagal, dan
-- kunci yang bentuknya berbeda akan membuat laporannya kosong.
--
-- Empat tabel di sini belum pernah dipakai fitur lain, yaitu sr_peralihan,
-- sr_pembeli_lama, sr_pembeli_baru, dan sr_approval. Itu yang paling
-- mungkin bermasalah.
--
-- Jalankan keempat query di bawah sebelum memakai fiturnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_peralihan'), ('sr_pembeli_lama'), ('sr_pembeli_baru'),
        ('sr_approval'), ('sr_ppjb'), ('sr_stok'), ('sr_nasabah'),
        ('sr_tipe'), ('sr_sektor'), ('sr_angsuran')
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
-- Yang diharapkan: NOL BARIS.
--
-- Catatan: kolom jenis dan tipe bangunan pada sr_stok sengaja tidak
-- diperiksa di sini, karena model mencarinya saat dijalankan antara
-- kd_jenis_bgn dan kd_jenis, seperti pada Rekap Estimasi Biaya AJB.
WITH diperlukan (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_peralihan', 'peralihan_id'), ('sr_peralihan', 'ppjb_id'),
        ('sr_peralihan', 'tgl_peralihan'), ('sr_peralihan', 'notaris'),
        ('sr_peralihan', 'tgl_notaris'), ('sr_peralihan', 'no_kuitansi'),
        ('sr_peralihan', 'tgl_kuitansi'), ('sr_peralihan', 'jml_kuitansi'),
        ('sr_peralihan', 'harga_pasar'), ('sr_peralihan', 'nm_agen'),
        ('sr_peralihan', 'nm_sales'), ('sr_peralihan', 'no_telp'),
        ('sr_peralihan', 'flag_entry'),

        ('sr_pembeli_lama', 'peralihan_id'), ('sr_pembeli_lama', 'nasabah_id'),
        ('sr_pembeli_baru', 'peralihan_id'), ('sr_pembeli_baru', 'nasabah_id'),

        ('sr_approval', 'parent_id'), ('sr_approval', 'jenis'),
        ('sr_approval', 'approve1'), ('sr_approval', 'approve2'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'stok_id'),
        ('sr_ppjb', 'luas_bangunan'), ('sr_ppjb', 'dpp'),
        ('sr_ppjb', 'harga_jual'),

        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'luas_tanah'), ('sr_stok', 'luas_semi_gross'),

        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),
        ('sr_tipe', 'kd_jenis'), ('sr_tipe', 'kd_tipe'),
        ('sr_tipe', 'deskripsi'),
        ('sr_sektor', 'deskripsi'), ('sr_sektor', 'flag_aktif'),
        ('sr_angsuran', 'ppjb_id'), ('sr_angsuran', 'kd_transaksi'),
        ('sr_angsuran', 'tgl_kuitansi')
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
-- Pelajaran dari dua fitur sebelumnya: kunci pada database ini ditulis
-- berawalan, misalnya DBPSA-18784, tetapi sebagian tabel terlanjur dibuat
-- bertipe angka sehingga awalannya terbuang. Bila itu terjadi, join tidak
-- pernah menemukan pasangan dan laporannya kosong.
--
-- Yang perlu diperhatikan: sr_peralihan.ppjb_id. Bila tipenya numeric
-- sedangkan sr_ppjb.ppjb_id varchar berawalan, laporan Peralihan Hak
-- pasti kosong dan saya harus menyesuaikan join-nya.
SELECT
    table_name AS nama_tabel,
    column_name AS nama_kolom,
    udt_name AS tipe,
    numeric_precision AS presisi,
    numeric_scale AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND column_name IN ('peralihan_id', 'parent_id', 'ppjb_id', 'stok_id',
                      'nasabah_id')
  AND table_name IN ('sr_peralihan', 'sr_pembeli_lama', 'sr_pembeli_baru',
                     'sr_approval', 'sr_ppjb', 'sr_stok', 'sr_nasabah',
                     'sr_angsuran')
ORDER BY column_name, table_name;


-- ---------------------------------------------------------------------
-- QUERY 4 : apakah kuncinya benar-benar ketemu
-- ---------------------------------------------------------------------
-- Perbandingan memakai teks sehingga tidak mungkin error, apa pun isi
-- kolomnya. Kalau "cocok" jauh lebih kecil daripada "terisi", berarti
-- bentuk penulisannya berbeda seperti pada kasus SERTIPIKAT_ID dulu.
SELECT
    (SELECT COUNT(*) FROM public.sr_peralihan) AS baris_peralihan,

    (SELECT COUNT(*) FROM public.sr_peralihan p
      WHERE EXISTS (SELECT 1 FROM public.sr_ppjb x
                     WHERE BTRIM(CAST(x.ppjb_id AS TEXT))
                         = BTRIM(CAST(p.ppjb_id AS TEXT))))
        AS ppjb_cocok,
    (SELECT COUNT(*) FROM public.sr_peralihan p
      WHERE EXISTS (SELECT 1 FROM public.sr_pembeli_lama x
                     WHERE BTRIM(CAST(x.peralihan_id AS TEXT))
                         = BTRIM(CAST(p.peralihan_id AS TEXT))))
        AS pembeli_lama_cocok,
    (SELECT COUNT(*) FROM public.sr_peralihan p
      WHERE EXISTS (SELECT 1 FROM public.sr_pembeli_baru x
                     WHERE BTRIM(CAST(x.peralihan_id AS TEXT))
                         = BTRIM(CAST(p.peralihan_id AS TEXT))))
        AS pembeli_baru_cocok,
    (SELECT COUNT(*) FROM public.sr_peralihan p
      WHERE EXISTS (SELECT 1 FROM public.sr_approval x
                     WHERE BTRIM(CAST(x.parent_id AS TEXT))
                         = BTRIM(CAST(p.peralihan_id AS TEXT))
                       AND BTRIM(CAST(x.jenis AS TEXT)) = '6'))
        AS approval_jenis6_cocok;


-- ---------------------------------------------------------------------
-- QUERY 5 : seluruh kolom sr_peralihan
-- ---------------------------------------------------------------------
-- Dipakai bila QUERY 3 menunjukkan ppjb_id bertipe angka. Pada fitur
-- Rekap Estimasi Biaya AJB, awalan yang hilang bisa dipulihkan karena
-- tabelnya membawa KD_PERUSAHAAN sendiri. Query ini mencari apakah
-- sr_peralihan juga punya kolom semacam itu.
SELECT
    ordinal_position AS urutan,
    column_name AS nama_kolom,
    udt_name AS tipe,
    character_maximum_length AS panjang
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'sr_peralihan'
ORDER BY ordinal_position;

-- Lima baris apa adanya, supaya terlihat kolom mana yang isinya
-- berbentuk DBPSA-xxxx.
SELECT * FROM public.sr_peralihan LIMIT 5;


-- ---------------------------------------------------------------------
-- QUERY 6 : sebaran tanggal dan status peralihan
-- ---------------------------------------------------------------------
-- Supaya rentang tanggal dan status yang dipilih pada layar tidak
-- meleset dari data yang memang ada, seperti yang terjadi pada Rekap
-- Estimasi Biaya AJB.
SELECT
    EXTRACT(YEAR FROM CAST(tgl_peralihan AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah
FROM public.sr_peralihan
WHERE COALESCE(CAST(tgl_peralihan AS TEXT), '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;

SELECT
    UPPER(BTRIM(COALESCE(CAST(flag_entry AS TEXT), '(null)'))) AS flag_entry,
    COUNT(*) AS jumlah
FROM public.sr_peralihan
GROUP BY 1
ORDER BY 2 DESC;
