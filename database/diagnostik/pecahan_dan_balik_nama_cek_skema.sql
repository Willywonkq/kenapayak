-- =====================================================================
-- DIAGNOSTIK SKEMA - DAFTAR SERTIPIKAT PECAHAN & PENGAJUAN BALIK NAMA
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- Kedua model menulis nama tabel dan kolom apa adanya, mengikuti bentuk
-- berkas lama. Tabel atau kolom yang tidak ada akan membuat query gagal,
-- dan kunci yang bentuknya berbeda akan membuat laporannya kosong.
--
-- sr_sertipikat_idk belum pernah dipakai fitur lain, dan banyak kolom
-- pada sr_sertipikat serta sr_stok juga baru dipakai di sini. Jalankan
-- keempat query di bawah sebelum memakai fiturnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_sertipikat_idk'), ('sr_sertipikat'), ('sr_akta'), ('sr_stok'),
        ('sr_ppjb'), ('sr_pembeli_ppjb'), ('sr_nasabah'), ('sr_sektor')
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
-- Catatan: kolom jenis bangunan pada sr_stok sengaja tidak diperiksa di
-- sini, karena model mencarinya saat dijalankan antara kd_jenis_bgn dan
-- kd_jenis, seperti pada fitur Rekap Estimasi Biaya AJB. Kolom kode
-- perusahaan dan sektor juga dicari begitu.
WITH diperlukan (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_sertipikat_idk', 'sertipikat_id'),
        ('sr_sertipikat_idk', 'sertipikat_idk'),
        ('sr_sertipikat_idk', 'nama_pt'),
        ('sr_sertipikat_idk', 'tgl_ser_idk'),
        ('sr_sertipikat_idk', 'su_induk'),
        ('sr_sertipikat_idk', 'tgl_su_induk'),
        ('sr_sertipikat_idk', 'luas_su_induk'),
        ('sr_sertipikat_idk', 'luas_su_pisah'),
        ('sr_sertipikat_idk', 'mohon_pisah'),
        ('sr_sertipikat_idk', 'tgl_mohon_pisah'),
        ('sr_sertipikat_idk', 'ser_pisah'),
        ('sr_sertipikat_idk', 'tgl_ser_pisah'),
        ('sr_sertipikat_idk', 'su_pisah'),
        ('sr_sertipikat_idk', 'tgl_su_pisah'),

        ('sr_sertipikat', 'sertipikat_id'), ('sr_sertipikat', 'stok_id'),
        ('sr_sertipikat', 'no_sertipikat'), ('sr_sertipikat', 'tgl_sertipikat'),
        ('sr_sertipikat', 'su_pisah'), ('sr_sertipikat', 'tgl_su_pisah'),
        ('sr_sertipikat', 'luas_sup'), ('sr_sertipikat', 'tgl_berlaku'),
        ('sr_sertipikat', 'no_sarusun'), ('sr_sertipikat', 'tgl_sarusun'),
        ('sr_sertipikat', 'no_denah'), ('sr_sertipikat', 'tgl_denah'),
        ('sr_sertipikat', 'luas_denah'), ('sr_sertipikat', 'mohon_blk_nm'),
        ('sr_sertipikat', 'tgl_mohon_blk_nm'),
        ('sr_sertipikat', 'status_blk_nm'),
        ('sr_sertipikat', 'tgl_input_gabung'),
        ('sr_sertipikat', 'tgl_input_ser'),
        ('sr_sertipikat', 'atas_nama_pt'),
        ('sr_sertipikat', 'blok'), ('sr_sertipikat', 'nomor'),

        ('sr_akta', 'ppjb_id'), ('sr_akta', 'sertipikat_id'),
        ('sr_akta', 'no_akta'), ('sr_akta', 'tgl_akta'),
        ('sr_akta', 'no_notaris'), ('sr_akta', 'notaris'),
        ('sr_akta', 'harga'), ('sr_akta', 'tgl_input'),

        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'luas_tanah'), ('sr_stok', 'luas_bangunan'),
        ('sr_stok', 'jalan'), ('sr_stok', 'flag_aktif'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'stok_id'),
        ('sr_ppjb', 'flag_aktif'), ('sr_ppjb', 'parent_id'),
        ('sr_ppjb', 'tgl_ppjb'), ('sr_ppjb', 'user_entry'),
        ('sr_ppjb', 'addendum'), ('sr_ppjb', 'jenis_perubahan'),
        ('sr_ppjb', 'luas_tanah'),

        ('sr_pembeli_ppjb', 'ppjb_id'), ('sr_pembeli_ppjb', 'nasabah_id'),
        ('sr_pembeli_ppjb', 'flag_aktif'),
        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),
        ('sr_sektor', 'deskripsi'), ('sr_sektor', 'flag_aktif')
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
-- QUERY 3 : bentuk kunci penghubung
-- ---------------------------------------------------------------------
-- Pelajaran dari empat fitur sebelumnya: kunci pada database ini ditulis
-- berawalan, misalnya DBPSA-18784, tetapi sebagian tabel terlanjur dibuat
-- bertipe angka sehingga awalannya terbuang.
--
-- Yang sudah diketahui: sr_akta.sertipikat_id numeric sedangkan
-- sr_sertipikat.sertipikat_id varchar. Model sudah menanganinya dengan
-- menyusun ulang awalan dari sr_akta.ppjb_id, cara yang sama seperti
-- pada Daftar Akta Jual Beli.
--
-- Yang perlu diperhatikan: sr_sertipikat_idk.sertipikat_id. Bila tipenya
-- numeric sedangkan sr_sertipikat.sertipikat_id varchar berawalan, kedua
-- laporan ini pasti kosong.
SELECT
    table_name AS nama_tabel,
    column_name AS nama_kolom,
    udt_name AS tipe,
    numeric_precision AS presisi,
    numeric_scale AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND column_name IN ('sertipikat_id', 'stok_id', 'ppjb_id', 'nasabah_id')
  AND table_name IN ('sr_sertipikat_idk', 'sr_sertipikat', 'sr_akta',
                     'sr_stok', 'sr_ppjb', 'sr_pembeli_ppjb', 'sr_nasabah')
ORDER BY column_name, table_name;


-- ---------------------------------------------------------------------
-- QUERY 4 : apakah kuncinya benar-benar ketemu
-- ---------------------------------------------------------------------
-- Perbandingan memakai teks sehingga tidak mungkin error, apa pun isi
-- kolomnya. Kalau "cocok" jauh lebih kecil daripada jumlah barisnya,
-- berarti bentuk penulisannya berbeda.
SELECT
    (SELECT COUNT(*) FROM public.sr_sertipikat_idk) AS baris_sertipikat_idk,
    (SELECT COUNT(*) FROM public.sr_sertipikat_idk AS idk
      WHERE EXISTS (SELECT 1 FROM public.sr_sertipikat AS s
                     WHERE BTRIM(CAST(s.sertipikat_id AS TEXT))
                         = BTRIM(CAST(idk.sertipikat_id AS TEXT))))
        AS idk_ketemu_sertipikat,
    (SELECT COUNT(*) FROM public.sr_sertipikat AS s
      WHERE EXISTS (SELECT 1 FROM public.sr_stok AS st
                     WHERE BTRIM(CAST(st.stok_id AS TEXT))
                         = BTRIM(CAST(s.stok_id AS TEXT))))
        AS sertipikat_ketemu_stok,
    (SELECT COUNT(*) FROM public.sr_akta AS a
      WHERE EXISTS (
            SELECT 1 FROM public.sr_sertipikat AS s
            WHERE BTRIM(CAST(s.sertipikat_id AS TEXT))
                = CASE
                      WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                      THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
                      WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                      THEN REGEXP_REPLACE(
                               BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                           ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
                      ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
                  END
      )) AS akta_ketemu_sertipikat;


-- ---------------------------------------------------------------------
-- QUERY 5 : sebaran tanggal sumber penyaring
-- ---------------------------------------------------------------------
-- Supaya rentang tanggal yang dipilih pada layar tidak meleset dari data
-- yang memang ada, seperti yang sempat terjadi pada Rekap Estimasi Biaya.
--
-- Daftar Sertipikat Pecahan menyaring memakai TGL_INPUT_GABUNG bila ada,
-- kalau tidak TGL_INPUT_SER. Kartu Surat Tanah hanya memakai
-- TGL_INPUT_SER. Daftar Pengajuan Balik Nama memakai AKTA.TGL_INPUT.
SELECT
    COUNT(*) AS baris_sertipikat,
    COUNT(tgl_input_gabung) AS tgl_input_gabung_terisi,
    COUNT(tgl_input_ser) AS tgl_input_ser_terisi,
    COUNT(*) FILTER (
        WHERE UPPER(BTRIM(COALESCE(CAST(status_blk_nm AS TEXT), ''))) = 'T'
           OR status_blk_nm IS NULL
    ) AS belum_balik_nama
FROM public.sr_sertipikat;

SELECT
    EXTRACT(YEAR FROM CAST(
        COALESCE(tgl_input_gabung, tgl_input_ser) AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah
FROM public.sr_sertipikat
WHERE COALESCE(CAST(COALESCE(tgl_input_gabung, tgl_input_ser) AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;

SELECT
    EXTRACT(YEAR FROM CAST(tgl_input AS TIMESTAMP))::int AS tahun_akta,
    COUNT(*) AS jumlah
FROM public.sr_akta
WHERE COALESCE(CAST(tgl_input AS TEXT), '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;
