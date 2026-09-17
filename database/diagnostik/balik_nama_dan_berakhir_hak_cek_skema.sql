-- =====================================================================
-- DIAGNOSTIK SKEMA - DAFTAR SERTIPIKAT BALIK NAMA DAN BERAKHIR HAKNYA
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- Kedua model menulis nama tabel dan kolom apa adanya, mengikuti bentuk
-- berkas lamanya. Tabel atau kolom yang tidak ada akan membuat querynya
-- gagal, dan kunci yang bentuknya berbeda akan membuat laporannya kosong.
--
-- Yang paling mungkin bermasalah:
--   * sr_pegawai belum pernah dipakai fitur lain yang sudah dimigrasi;
--   * kolom TGL_INPUT_BLK_NM, TGL_BERLAKU, dan nomor telepon nasabah
--     belum pernah diperiksa;
--   * sr_sertipikat_idk.sertipikat_id bertipe numeric sehingga awalannya
--     terbuang, sudah ditangani model tetapi tetap perlu dipastikan.
--
-- Jalankan QUERY 1 sampai QUERY 5 sebelum memakai kedua fiturnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
-- sr_pegawai boleh false; model sudah menanganinya dengan mengembalikan
-- daftar TTD kosong, dan layarnya tetap bisa dibuka.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_stok'), ('sr_sertipikat'), ('sr_sertipikat_idk'), ('sr_ppjb'),
        ('sr_pembeli_ppjb'), ('sr_nasabah'), ('sr_sektor'), ('sr_akta'),
        ('sr_pegawai')
)
SELECT
    d.nama_tabel,
    EXISTS (
        SELECT 1 FROM information_schema.tables AS t
        WHERE t.table_schema = 'public' AND t.table_name = d.nama_tabel
    ) AS ada
FROM dipakai AS d
ORDER BY d.nama_tabel;


-- ---------------------------------------------------------------------
-- QUERY 2 : kolom yang dipakai tetapi tidak ada
-- ---------------------------------------------------------------------
-- Yang diharapkan: TIDAK ADA BARIS SAMA SEKALI. Setiap baris yang muncul
-- adalah kolom yang dipakai model tetapi tidak ada di database.
--
-- Catatan: kd_jenis_bgn dan kd_jenis dicari dua-duanya karena model
-- memilih sendiri yang ada; begitu pula telp_rmh, no_hp, dan telp_ktr
-- yang boleh tidak ada dan akan ditampilkan kosong.
WITH dipakai (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'luas_tanah'), ('sr_stok', 'luas_bangunan'),
        ('sr_stok', 'jalan'), ('sr_stok', 'flag_aktif'),

        ('sr_sertipikat', 'sertipikat_id'), ('sr_sertipikat', 'stok_id'),
        ('sr_sertipikat', 'no_sertipikat'), ('sr_sertipikat', 'tgl_sertipikat'),
        ('sr_sertipikat', 'su_pisah'), ('sr_sertipikat', 'tgl_su_pisah'),
        ('sr_sertipikat', 'luas_sup'), ('sr_sertipikat', 'status_blk_nm'),
        ('sr_sertipikat', 'tgl_input_blk_nm'), ('sr_sertipikat', 'tgl_berlaku'),
        ('sr_sertipikat', 'no_sarusun'), ('sr_sertipikat', 'tgl_sarusun'),
        ('sr_sertipikat', 'no_denah'), ('sr_sertipikat', 'tgl_denah'),
        ('sr_sertipikat', 'luas_denah'),

        ('sr_sertipikat_idk', 'sertipikat_id'),
        ('sr_sertipikat_idk', 'sertipikat_idk'),
        ('sr_sertipikat_idk', 'nama_pt'), ('sr_sertipikat_idk', 'tgl_ser_idk'),
        ('sr_sertipikat_idk', 'su_induk'), ('sr_sertipikat_idk', 'tgl_su_induk'),
        ('sr_sertipikat_idk', 'luas_su_induk'),
        ('sr_sertipikat_idk', 'luas_su_pisah'),
        ('sr_sertipikat_idk', 'mohon_pisah'),
        ('sr_sertipikat_idk', 'tgl_mohon_pisah'),
        ('sr_sertipikat_idk', 'ser_pisah'), ('sr_sertipikat_idk', 'tgl_ser_pisah'),
        ('sr_sertipikat_idk', 'su_pisah'), ('sr_sertipikat_idk', 'tgl_su_pisah'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'stok_id'),
        ('sr_ppjb', 'flag_aktif'), ('sr_ppjb', 'parent_id'),
        ('sr_ppjb', 'tgl_ppjb'), ('sr_ppjb', 'user_entry'),

        ('sr_pembeli_ppjb', 'ppjb_id'), ('sr_pembeli_ppjb', 'nasabah_id'),
        ('sr_pembeli_ppjb', 'flag_aktif'),

        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),

        ('sr_sektor', 'deskripsi'), ('sr_sektor', 'flag_aktif'),

        ('sr_akta', 'sertipikat_id'), ('sr_akta', 'ppjb_id'),
        ('sr_akta', 'no_akta')
)
SELECT d.nama_tabel, d.nama_kolom
FROM dipakai AS d
WHERE NOT EXISTS (
    SELECT 1 FROM information_schema.columns AS c
    WHERE c.table_schema = 'public'
      AND c.table_name = d.nama_tabel
      AND c.column_name = d.nama_kolom
)
ORDER BY d.nama_tabel, d.nama_kolom;


-- ---------------------------------------------------------------------
-- QUERY 3 : kolom yang boleh tidak ada
-- ---------------------------------------------------------------------
-- Model memilih sendiri kolom yang tersedia. Query ini hanya memberi
-- tahu mana yang dipakai, bukan menandai kesalahan.
SELECT
    c.table_name  AS nama_tabel,
    c.column_name AS nama_kolom,
    c.data_type   AS tipe
FROM information_schema.columns AS c
WHERE c.table_schema = 'public'
  AND (
        (c.table_name = 'sr_stok'
         AND c.column_name IN ('kd_perusahaan', 'kd_unit', 'kd_pt',
                               'kd_sektor', 'kd_proyek', 'kd_cluster',
                               'kd_jenis_bgn', 'kd_jenis'))
     OR (c.table_name = 'sr_sektor'
         AND c.column_name IN ('kd_sektor', 'kd_proyek', 'kd_cluster',
                               'kd_perusahaan', 'kd_unit', 'kd_pt'))
     OR (c.table_name = 'sr_nasabah'
         AND c.column_name IN ('telp_rmh', 'no_hp', 'telp_ktr'))
     OR (c.table_name = 'sr_pegawai'
         AND c.column_name IN ('pegawai_id', 'kd_pegawai', 'nip', 'nama'))
      )
ORDER BY c.table_name, c.column_name;


-- ---------------------------------------------------------------------
-- QUERY 4 : keterisian kolom penyaring
-- ---------------------------------------------------------------------
-- Daftar Sertipikat Balik Nama menyaring STATUS_BLK_NM = 'Y' dan rentang
-- TGL_INPUT_BLK_NM. Daftar Sertipikat Berakhir Haknya menyaring rentang
-- TGL_BERLAKU. Bila kolomnya kosong seluruhnya, laporannya pasti kosong
-- berapa pun tanggal yang dipilih, dan itu soal data bukan soal query.
SELECT
    COUNT(*)                                AS baris_sertipikat,
    COUNT(*) FILTER (
        WHERE UPPER(BTRIM(COALESCE(CAST(status_blk_nm AS TEXT), ''))) = 'Y'
    )                                       AS status_blk_nm_y,
    COUNT(tgl_input_blk_nm)                 AS tgl_input_blk_nm_terisi,
    COUNT(tgl_berlaku)                      AS tgl_berlaku_terisi
FROM public.sr_sertipikat;


-- ---------------------------------------------------------------------
-- QUERY 5 : sebaran tahun kedua tanggal penyaring
-- ---------------------------------------------------------------------
-- Supaya rentang tanggal yang dipilih pada layar tidak meleset dari data
-- yang memang ada, seperti yang sempat terjadi pada Rekap Estimasi Biaya.
SELECT
    'tgl_input_blk_nm' AS sumber,
    EXTRACT(YEAR FROM CAST(tgl_input_blk_nm AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah
FROM public.sr_sertipikat
WHERE COALESCE(CAST(tgl_input_blk_nm AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1, 2

UNION ALL

SELECT
    'tgl_berlaku' AS sumber,
    EXTRACT(YEAR FROM CAST(tgl_berlaku AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah
FROM public.sr_sertipikat
WHERE COALESCE(CAST(tgl_berlaku AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1, 2

ORDER BY 1, 2;
