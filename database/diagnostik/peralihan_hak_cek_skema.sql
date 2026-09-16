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


-- =====================================================================
-- HASIL PADA DATABASE DTSA  (QUERY 1 s/d 6 sudah dijawab)
-- =====================================================================
-- QUERY 1 : kesepuluh tabel ada semua.
--
-- QUERY 2 : tiga kolom tidak ada, yaitu sr_peralihan.nm_agen, nm_sales,
--           dan no_telp. Model sudah disesuaikan: bila kolomnya memang
--           tidak ada, nilainya diisi NULL dan susunan kolom tetap utuh.
--
-- QUERY 3 : PERALIHAN_ID konsisten numeric(5,0) di ketiga tabelnya, dan
--           APPROVAL.PARENT_ID numeric(18,0) masih cocok sebagai teks.
--           Yang tidak cocok ada dua:
--             sr_peralihan.ppjb_id        numeric(18,0)
--             sr_ppjb.ppjb_id             varchar berawalan
--             sr_pembeli_lama.nasabah_id  numeric(18,0)
--             sr_pembeli_baru.nasabah_id  numeric(18,0)
--             sr_nasabah.nasabah_id       varchar berawalan
--
-- QUERY 4 : 3069 peralihan, ppjb_cocok NOL, pembeli_lama 2990,
--           pembeli_baru 2989, approval jenis 6 sebanyak 1526.
--           Tanpa perbaikan laporannya pasti kosong.
--
-- QUERY 5 : sr_peralihan hanya punya 16 kolom dan TIDAK membawa
--           KD_PERUSAHAAN. Jadi awalan yang hilang tidak bisa dipulihkan
--           dari baris itu sendiri, berbeda dengan sr_biaya_ajb.
--
-- QUERY 6 : flag_entry berisi Y sebanyak 2.992 dan T sebanyak 77.
--
-- Karena awalannya tidak bisa dipulihkan, model hanya memakai angka yang
-- menunjuk TEPAT SATU pasangan. Angka yang dipakai dua awalan sekaligus
-- sengaja tidak ditampilkan, sebab menempelkannya berarti menampilkan
-- peralihan hak milik unit lain.
--
-- QUERY 7 di bawah mengukur berapa banyak yang terpengaruh.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 7 : berapa baris yang terpakai dan berapa yang tersingkir
-- ---------------------------------------------------------------------
-- Bagian pertama: dari 3.069 peralihan, berapa angka PPJB-nya menunjuk
-- tepat satu PPJB, berapa menunjuk dua, dan berapa tidak ketemu.
SELECT
    COUNT(*) AS jumlah_peralihan,
    COUNT(*) FILTER (WHERE k.jumlah = 1) AS menunjuk_satu_ppjb,
    COUNT(*) FILTER (WHERE k.jumlah > 1) AS menunjuk_lebih_dari_satu,
    COUNT(*) FILTER (WHERE k.angka IS NULL) AS tidak_ketemu
FROM public.sr_peralihan AS p
LEFT JOIN (
    SELECT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
           COUNT(*) AS jumlah
    FROM public.sr_ppjb WHERE ppjb_id IS NOT NULL GROUP BY 1
) AS k
    ON k.angka = REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '');

-- Bagian kedua: hal yang sama untuk NASABAH_ID pada pembeli lama.
SELECT
    COUNT(*) AS jumlah_pembeli_lama,
    COUNT(*) FILTER (WHERE k.jumlah = 1) AS menunjuk_satu_nasabah,
    COUNT(*) FILTER (WHERE k.jumlah > 1) AS menunjuk_lebih_dari_satu,
    COUNT(*) FILTER (WHERE k.angka IS NULL) AS tidak_ketemu
FROM public.sr_pembeli_lama AS pl
LEFT JOIN (
    SELECT REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)), '^[^0-9]+', '') AS angka,
           COUNT(*) AS jumlah
    FROM public.sr_nasabah WHERE nasabah_id IS NOT NULL GROUP BY 1
) AS k
    ON k.angka = REGEXP_REPLACE(BTRIM(CAST(pl.nasabah_id AS TEXT)), '^[^0-9]+', '');

-- Bagian ketiga: seandainya SELURUH sr_peralihan berasal dari satu sumber
-- saja, awalan mana yang paling cocok. Kalau salah satu awalan mencakup
-- hampir seluruh 3.069 baris sedangkan yang lain jauh lebih sedikit,
-- berarti tabelnya memang satu sumber dan pembatasan di atas bisa
-- dicabut sehingga seluruh baris terpakai.
SELECT
    awalan,
    COUNT(*) AS peralihan_yang_cocok
FROM public.sr_peralihan AS p
CROSS JOIN (SELECT DISTINCT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)),
                                           '[0-9]+$', '') AS awalan
            FROM public.sr_ppjb WHERE ppjb_id IS NOT NULL) AS a
WHERE EXISTS (
    SELECT 1 FROM public.sr_ppjb AS x
    WHERE BTRIM(CAST(x.ppjb_id AS TEXT))
        = a.awalan || REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
)
GROUP BY 1
ORDER BY 2 DESC;

-- Bagian keempat: isi kolom no_peralihan, kalau-kalau kolom itu membawa
-- kode unit seperti AJB-SKLG... pada sr_biaya_ajb.
SELECT
    COUNT(*) AS jumlah_baris,
    COUNT(*) FILTER (
        WHERE NULLIF(BTRIM(COALESCE(CAST(no_peralihan AS TEXT), '')), '') IS NOT NULL
    ) AS no_peralihan_terisi
FROM public.sr_peralihan;

SELECT DISTINCT BTRIM(CAST(no_peralihan AS TEXT)) AS contoh_no_peralihan
FROM public.sr_peralihan
WHERE NULLIF(BTRIM(COALESCE(CAST(no_peralihan AS TEXT), '')), '') IS NOT NULL
LIMIT 10;


-- =====================================================================
-- HASIL QUERY 7 PADA DATABASE DTSA
-- =====================================================================
-- Bagian 1 : 3069 peralihan, hanya 77 yang angka PPJB-nya menunjuk tepat
--            satu PPJB. Sebanyak 2.991 menunjuk dua, dan 1 tidak ketemu.
--            Jadi aturan "hanya yang tunggal" cuma menampilkan 2,5 persen
--            datanya. Terlalu sedikit untuk dipakai.
--
-- Bagian 2 : 3007 pembeli lama, SELURUHNYA menunjuk tepat satu nasabah.
--            Nama pembeli lama dan baru aman, tidak perlu pembatasan.
--
-- Bagian 3 : awalan DBPSA- mencakup 3.068 dari 3.069 baris, DBPSS-
--            mencakup 2.991. Keduanya tinggi karena rentang angkanya
--            memang bertumpang tindih, jadi ini belum membuktikan apa pun.
--
-- Bagian 4 : no_peralihan berisi nomor akta notaris seperti 09/2023 dan
--            43/2022, bukan kode unit. Tidak bisa dipakai.
--
-- QUERY 8 di bawah memakai penguji yang tidak bergantung pada awalan:
-- tanggal peralihan tidak mungkin mendahului tanggal PPJB-nya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 8 : menentukan awalan lewat kelayakan tanggal
-- ---------------------------------------------------------------------
-- Peralihan hak terjadi SETELAH PPJB. Jadi pasangan yang benar hampir
-- selalu memenuhi tgl_peralihan >= tgl_ppjb, sedangkan pasangan yang
-- salah akan sering melanggarnya.
--
-- Bandingkan kedua baris hasilnya. Awalan yang "layak"-nya jauh lebih
-- banyak dan "melanggar"-nya jauh lebih sedikit adalah sumber yang benar.
WITH kandidat AS (
    SELECT
        a.awalan,
        p.peralihan_id,
        CASE
            WHEN COALESCE(CAST(p.tgl_peralihan AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(p.tgl_peralihan AS TIMESTAMP)
        END AS tgl_peralihan,
        x.tgl_ppjb AS tgl_ppjb
    FROM public.sr_peralihan AS p
    CROSS JOIN (
        SELECT DISTINCT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', '')
            AS awalan
        FROM public.sr_ppjb WHERE ppjb_id IS NOT NULL
    ) AS a
    INNER JOIN public.sr_ppjb AS x
        ON BTRIM(CAST(x.ppjb_id AS TEXT))
         = a.awalan
           || REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
)
SELECT
    awalan,
    COUNT(*) AS jumlah_pasangan,
    COUNT(*) FILTER (WHERE tgl_peralihan >= tgl_ppjb) AS layak_tanggal,
    COUNT(*) FILTER (WHERE tgl_peralihan < tgl_ppjb) AS melanggar_tanggal,
    COUNT(*) FILTER (WHERE tgl_peralihan IS NULL OR tgl_ppjb IS NULL)
        AS tanggal_kosong
FROM kandidat
GROUP BY 1
ORDER BY 3 DESC;


-- ---------------------------------------------------------------------
-- QUERY 9 : sebaran unit di bawah masing-masing awalan
-- ---------------------------------------------------------------------
-- Penguji kedua. Peralihan hak biasanya terkumpul di beberapa unit saja.
-- Awalan yang benar akan memberi sebaran unit yang masuk akal, sedangkan
-- yang salah biasanya tersebar tidak karuan.
--
-- Perhatikan juga apakah unit yang muncul di bawah DBPSA- memang unit
-- berawalan DBPSA-, dan sebaliknya. Kalau iya untuk keduanya, berarti
-- memang tidak bisa dibedakan dari sini.
WITH kandidat AS (
    SELECT
        a.awalan,
        UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), '(kosong)')))
            AS kode_unit
    FROM public.sr_peralihan AS p
    CROSS JOIN (
        SELECT DISTINCT REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', '')
            AS awalan
        FROM public.sr_ppjb WHERE ppjb_id IS NOT NULL
    ) AS a
    INNER JOIN public.sr_ppjb AS x
        ON BTRIM(CAST(x.ppjb_id AS TEXT))
         = a.awalan
           || REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(x.stok_id AS TEXT))
)
SELECT awalan, kode_unit, COUNT(*) AS jumlah
FROM kandidat
GROUP BY 1, 2
ORDER BY 1, 3 DESC;


-- =====================================================================
-- HASIL QUERY 8 DAN 9 PADA DATABASE DTSA  -  SUDAH TERJAWAB
-- =====================================================================
-- QUERY 8, kelayakan tanggal:
--
--     awalan   pasangan   layak   melanggar   tanggal kosong
--     DBPSA-      3.068   3.064           0                4
--     DBPSS-      2.991   1.795       1.192                4
--
-- DBPSA- tidak melanggar sama sekali, sedangkan DBPSS- melanggar pada
-- 40 persen pasangannya. Peralihan hak tidak mungkin terjadi sebelum
-- PPJB-nya, jadi kecocokan DBPSS- itu hanyalah tabrakan angka.
-- Kesimpulannya sr_peralihan berasal dari satu sumber saja, yaitu yang
-- berawalan DBPSA-.
--
-- QUERY 9, sebaran unit di bawah DBPSA-:
--
--     SBKS 1.552, SKLG 721, GDOR 324, WGP 206, MKPP 101, SKPN 46,
--     KCJA 40, BHMS 33, SKRW 25, MNST 10, SGMC 9, SMSF 1
--
-- Seluruhnya unit berawalan DBPSA-, dan jumlahnya tepat 3.068. Sejalan
-- dengan kesimpulan di atas.
--
-- Model sudah memakai cara ini. Awalannya TIDAK ditulis mati, melainkan
-- ditentukan dari data memakai penguji kelayakan tanggal yang sama,
-- sehingga tetap benar bila suatu saat sumbernya berubah. Hasilnya
-- diingat supaya query penentunya hanya jalan sekali per permintaan,
-- dan pada pengujian seukuran produksi query itu memakan 132 milidetik.
--
-- Dengan begitu pembatasan "hanya angka yang tunggal" tidak diperlukan
-- lagi, dan seluruh baris peralihan terpakai, bukan hanya 77.
--
-- Tidak ada lagi query yang perlu dijalankan pada berkas ini.
-- =====================================================================
