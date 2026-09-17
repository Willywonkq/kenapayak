-- =====================================================================
-- DIAGNOSTIK SKEMA - REKAP JAMINAN BANK
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- sr_jaminan belum pernah dipakai fitur lain yang sudah dimigrasi, jadi
-- bentuknya sama sekali belum diketahui. Yang paling menentukan adalah
-- tipe kolom SERTIPIKAT_ID dan ada tidaknya KD_PERUSAHAAN, karena kedua
-- hal itu menentukan cara model menyambungkannya ke sr_sertipikat.
--
-- Jalankan QUERY 1 sampai QUERY 6 lalu kirimkan hasilnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel yang dipakai, adakah yang hilang
-- ---------------------------------------------------------------------
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_jaminan'), ('sr_sertipikat'), ('sr_stok'), ('sr_ppjb'),
        ('sr_pembeli_ppjb'), ('sr_nasabah'), ('sr_sektor'), ('sr_lokasi'),
        ('sr_jadwal_angsuran'), ('sr_akta')
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
-- QUERY 2 : seluruh kolom sr_jaminan
-- ---------------------------------------------------------------------
-- INI YANG PALING MENENTUKAN. Dua hal yang dicari:
--
--   a. tipe SERTIPIKAT_ID.
--      character varying -> kuncinya utuh, model menyambung langsung;
--      numeric           -> awalannya terbuang seperti pada sr_akta dan
--                           sr_sertipikat_idk, dan model perlu menyusun
--                           ulang awalannya.
--
--   b. adakah kolom KD_PERUSAHAAN.
--      Kalau ada, awalannya bisa dipulihkan dengan pasti lewat peta unit
--      ke awalan. Kalau tidak ada dan tipenya numeric, model hanya akan
--      memakai angka yang menunjuk ke tepat satu sertipikat, sehingga
--      sebagian barisnya bisa tidak tampil.
SELECT
    ordinal_position         AS urutan,
    column_name              AS nama_kolom,
    data_type                AS tipe,
    character_maximum_length AS panjang,
    numeric_precision        AS presisi,
    numeric_scale            AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name   = 'sr_jaminan'
ORDER BY ordinal_position;


-- ---------------------------------------------------------------------
-- QUERY 3 : kolom yang dipakai tetapi tidak ada
-- ---------------------------------------------------------------------
-- Yang diharapkan: TIDAK ADA BARIS SAMA SEKALI.
WITH dipakai (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_jaminan', 'sertipikat_id'), ('sr_jaminan', 'pengajuan'),
        ('sr_jaminan', 'tgl_pengajuan'), ('sr_jaminan', 'no_jaminan'),
        ('sr_jaminan', 'tgl_jaminan'), ('sr_jaminan', 'nama_bank'),
        ('sr_jaminan', 'alamat_bank'), ('sr_jaminan', 'jenis_jaminan'),
        ('sr_jaminan', 'nama_ambil'), ('sr_jaminan', 'tgl_ambil'),
        ('sr_jaminan', 'no_lunas'), ('sr_jaminan', 'tgl_lunas'),
        ('sr_jaminan', 'no_batal'), ('sr_jaminan', 'tgl_batal'),

        ('sr_sertipikat', 'sertipikat_id'), ('sr_sertipikat', 'stok_id'),
        ('sr_sertipikat', 'tgl_sertipikat'),

        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'flag_aktif'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'stok_id'),
        ('sr_ppjb', 'flag_aktif'), ('sr_ppjb', 'parent_id'),

        ('sr_pembeli_ppjb', 'ppjb_id'), ('sr_pembeli_ppjb', 'nasabah_id'),
        ('sr_pembeli_ppjb', 'flag_aktif'),

        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),
        ('sr_sektor', 'deskripsi'), ('sr_lokasi', 'deskripsi'),

        ('sr_jadwal_angsuran', 'ppjb_id'), ('sr_jadwal_angsuran', 'jumlah'),
        ('sr_jadwal_angsuran', 'flag_kpr'),

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
-- QUERY 4 : berapa baris jaminan yang memang akan tampil
-- ---------------------------------------------------------------------
-- Laporannya hanya menampilkan jaminan yang NO_JAMINAN terisi, belum
-- lunas, dan belum batal. Kalau "siap_tampil" bernilai 0, laporannya
-- memang kosong dan itu soal data, bukan soal query.
SELECT
    COUNT(*)                                        AS baris_jaminan,
    COUNT(no_jaminan)                               AS no_jaminan_terisi,
    COUNT(*) FILTER (WHERE no_lunas IS NOT NULL)    AS sudah_lunas,
    COUNT(*) FILTER (WHERE no_batal IS NOT NULL)    AS sudah_batal,
    COUNT(*) FILTER (
        WHERE no_jaminan IS NOT NULL
          AND no_lunas IS NULL
          AND no_batal IS NULL
    )                                               AS siap_tampil
FROM public.sr_jaminan;


-- ---------------------------------------------------------------------
-- QUERY 5 : sambungan jaminan ke sertipikat
-- ---------------------------------------------------------------------
-- Memeriksa apakah kuncinya nyambung apa adanya, dan seberapa rancu bila
-- awalannya harus disusun ulang.
--
--   cocok_langsung   : nyambung tanpa perlu disusun ulang. Kalau angkanya
--                      besar, tidak ada masalah sama sekali.
--   angka_polos      : nilainya hanya angka, jadi awalannya terbuang.
--   tepat_satu       : dari angka polos itu, yang menunjuk ke tepat satu
--                      sertipikat. Ini yang bisa dipulihkan dengan aman
--                      tanpa penanda unit.
--   lebih_dari_satu  : rancu, tidak boleh ditebak.
WITH jm AS (
    SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS nilai
    FROM public.sr_jaminan AS j
    WHERE j.sertipikat_id IS NOT NULL
),
ser_penuh AS (
    SELECT BTRIM(CAST(s.sertipikat_id AS TEXT)) AS nilai
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
),
ser_angka AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        COUNT(*) AS jumlah
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
    GROUP BY 1
)
SELECT
    (SELECT COUNT(*) FROM jm)                                  AS baris_jaminan,
    (SELECT COUNT(*) FROM jm
      WHERE EXISTS (SELECT 1 FROM ser_penuh WHERE ser_penuh.nilai = jm.nilai))
                                                               AS cocok_langsung,
    (SELECT COUNT(*) FROM jm WHERE jm.nilai ~ '^[0-9]+$')      AS angka_polos,
    (SELECT COUNT(*) FROM jm
       INNER JOIN ser_angka ON ser_angka.angka = jm.nilai
      WHERE jm.nilai ~ '^[0-9]+$' AND ser_angka.jumlah = 1)    AS tepat_satu,
    (SELECT COUNT(*) FROM jm
       INNER JOIN ser_angka ON ser_angka.angka = jm.nilai
      WHERE jm.nilai ~ '^[0-9]+$' AND ser_angka.jumlah > 1)    AS lebih_dari_satu;


-- ---------------------------------------------------------------------
-- QUERY 6 : sebaran jenis jaminan dan tahunnya
-- ---------------------------------------------------------------------
-- Dropdown Jenis Jaminan pada desktop berisi IMB, Akta Jual Beli,
-- Sertipikat, PPJB, dan Peralihan Hak. Query ini memastikan isi datanya
-- memang memakai tulisan yang sama, dan memperlihatkan rentang tanggal
-- yang ada supaya pilihan tanggal di layar tidak meleset.
SELECT
    UPPER(BTRIM(COALESCE(CAST(jenis_jaminan AS TEXT), '(kosong)'))) AS jenis,
    COUNT(*)                                                       AS jumlah,
    MIN(tgl_jaminan)                                               AS tgl_terlama,
    MAX(tgl_jaminan)                                               AS tgl_terbaru
FROM public.sr_jaminan
WHERE no_jaminan IS NOT NULL
  AND no_lunas IS NULL
  AND no_batal IS NULL
GROUP BY 1
ORDER BY 2 DESC;
