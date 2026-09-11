-- =====================================================================
-- DIAGNOSTIK DAFTAR AKTA JUAL BELI  (PostgreSQL)
-- =====================================================================
-- Seluruh query di berkas ini HANYA MEMBACA. Tidak ada CREATE, INSERT,
-- UPDATE, DELETE, DROP, maupun ALTER. Aman dijalankan pada database
-- produksi.
--
-- Cara pakai: jalankan QUERY 1 dan QUERY 2 lebih dulu untuk memastikan
-- nama tabel dan kolomnya. Bila namanya berbeda dari dugaan di sini,
-- ganti nama tabel pada QUERY 3 ke bawah sesuai hasil QUERY 1.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tabel mana yang benar-benar ada
-- ---------------------------------------------------------------------
-- Model mencari nama tabelnya sendiri lewat katalog. Query ini
-- memperlihatkan hasil pencarian yang sama.
SELECT
    kandidat.nama          AS nama_tabel,
    kandidat.dipakai_untuk AS dipakai_untuk,
    CASE WHEN t.table_name IS NULL THEN 'TIDAK ADA' ELSE 'ADA' END
        AS keterangan
FROM (
    VALUES
        ('sr_akta',            'akta'),
        ('sr_akta_jual_beli',  'akta'),
        ('akta',               'akta'),
        ('sr_sertipikat',      'sertipikat'),
        ('sertipikat',         'sertipikat'),
        ('sr_pengambilan',     'pengambilan'),
        ('sr_pengambilan_sertipikat', 'pengambilan'),
        ('pengambilan',        'pengambilan'),
        ('sr_stok',            'stok'),
        ('sr_ppjb',            'ppjb'),
        ('sr_pembeli_ppjb',    'pembeli_ppjb'),
        ('sr_nasabah',         'nasabah'),
        ('sr_lokasi',          'lokasi'),
        ('sr_sektor',          'sektor'),
        ('sr_angsuran',        'angsuran')
) AS kandidat (nama, dipakai_untuk)
LEFT JOIN information_schema.tables AS t
    ON t.table_schema = 'public'
   AND t.table_name = kandidat.nama
ORDER BY kandidat.dipakai_untuk, kandidat.nama;


-- ---------------------------------------------------------------------
-- QUERY 2 : kolom pada tiga tabel yang belum pernah dipakai fitur lain
-- ---------------------------------------------------------------------
-- Kolom yang dicari model: pada akta -> ppjb_id, sertipikat_id, no_akta,
-- tgl_akta, no_notaris, tgl_notaris, notaris, tgl_input, ttd_akta,
-- tgl_entry, user_entry. Pada sertipikat -> sertipikat_id, stok_id.
-- Pada pengambilan -> sertipikat_id, tgl_ambil_akta, tgl_cetak_akta.
SELECT
    table_name,
    column_name,
    udt_name AS tipe
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN (
      'sr_akta',
      'sr_akta_jual_beli',
      'akta',
      'sr_sertipikat',
      'sertipikat',
      'sr_pengambilan',
      'sr_pengambilan_sertipikat',
      'pengambilan'
  )
ORDER BY table_name, ordinal_position;


-- ---------------------------------------------------------------------
-- QUERY 3 : corong jumlah baris, tahap demi tahap
-- ---------------------------------------------------------------------
-- Ganti kedua tanggal di bawah ini dengan tanggal yang dipakai di
-- desktop, lalu bandingkan angka t7 dengan jumlah data di web dan
-- jumlah data di desktop.
WITH
t1_akta AS (
    SELECT a.*
    FROM public.sr_akta AS a
    WHERE CASE
              WHEN COALESCE(CAST(a.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(a.tgl_akta AS TIMESTAMP)
          END >= DATE '2026-01-01'
      AND CASE
              WHEN COALESCE(CAST(a.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(a.tgl_akta AS TIMESTAMP)
          END < DATE '2026-12-31' + INTERVAL '1 day'
),
t2_ppjb AS (
    SELECT t1_akta.*
    FROM t1_akta
    INNER JOIN public.sr_ppjb AS p
        ON BTRIM(CAST(p.ppjb_id AS TEXT))
         = BTRIM(CAST(t1_akta.ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'A'
      AND p.parent_id IS NULL
),
t3_sertipikat AS (
    SELECT t2_ppjb.*, s.stok_id
    FROM t2_ppjb
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT))
         = BTRIM(CAST(t2_ppjb.sertipikat_id AS TEXT))
    WHERE s.stok_id IS NOT NULL
),
t4_stok AS (
    SELECT t3_sertipikat.*
    FROM t3_sertipikat
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT))
         = BTRIM(CAST(t3_sertipikat.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
),
t5_pembeli AS (
    SELECT t4_stok.*
    FROM t4_stok
    INNER JOIN public.sr_pembeli_ppjb AS pb
        ON BTRIM(CAST(pb.ppjb_id AS TEXT))
         = BTRIM(CAST(t4_stok.ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
),
t6_nasabah_inner AS (
    SELECT t5_pembeli.*
    FROM t5_pembeli
    INNER JOIN public.sr_pembeli_ppjb AS pb
        ON BTRIM(CAST(pb.ppjb_id AS TEXT))
         = BTRIM(CAST(t5_pembeli.ppjb_id AS TEXT))
       AND UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
    INNER JOIN public.sr_nasabah AS n
        ON BTRIM(CAST(pb.nasabah_id AS TEXT))
         = BTRIM(CAST(n.nasabah_id AS TEXT))
)
SELECT
    (SELECT COUNT(*) FROM public.sr_akta)  AS t0_seluruh_akta,
    (SELECT COUNT(*) FROM t1_akta)         AS t1_rentang_tgl_akta,
    (SELECT COUNT(*) FROM t2_ppjb)         AS t2_ppjb_aktif,
    (SELECT COUNT(*) FROM t3_sertipikat)   AS t3_punya_sertipikat,
    (SELECT COUNT(*) FROM t4_stok)         AS t4_stok_aktif_unit,
    (SELECT COUNT(*) FROM t5_pembeli)      AS t5_punya_pembeli,
    (SELECT COUNT(*) FROM t6_nasabah_inner) AS t6_kalau_nasabah_inner_join;
-- t5 = jumlah baris yang dikembalikan model PostgreSQL (nasabah LEFT JOIN).
-- t6 = jumlah baris kalau nasabah dipakai INNER JOIN seperti desktop.
-- Selisih t5 - t6 adalah unit yang akan hilang bila NASABAH tetap
-- INNER JOIN, dan itulah alasan model memakai LEFT JOIN.


-- ---------------------------------------------------------------------
-- QUERY 4 : akta yang tanggalnya tidak terbaca sebagai tanggal
-- ---------------------------------------------------------------------
-- Desktop menyaring dengan ISDATE(). Query ini memperlihatkan berapa
-- baris yang dibuang oleh penjagaan yang setara di PostgreSQL.
SELECT
    COUNT(*) AS jumlah_akta,
    COUNT(*) FILTER (
        WHERE COALESCE(CAST(tgl_akta AS TEXT), '')
              ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
    ) AS tanggal_terbaca,
    COUNT(*) FILTER (
        WHERE COALESCE(CAST(tgl_akta AS TEXT), '') = ''
    ) AS tanggal_kosong,
    COUNT(*) FILTER (
        WHERE COALESCE(CAST(tgl_akta AS TEXT), '') <> ''
          AND COALESCE(CAST(tgl_akta AS TEXT), '')
              !~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
    ) AS tanggal_tidak_terbaca
FROM public.sr_akta;


-- ---------------------------------------------------------------------
-- QUERY 5 : apakah laporan berpotensi ganda
-- ---------------------------------------------------------------------
-- Satu PPJB yang punya lebih dari satu pembeli aktif akan menggandakan
-- baris laporan. Hasil yang diharapkan: tidak ada baris sama sekali.
SELECT
    pb.ppjb_id,
    COUNT(*) AS jumlah_pembeli_aktif
FROM public.sr_pembeli_ppjb AS pb
WHERE UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
GROUP BY pb.ppjb_id
HAVING COUNT(*) > 1
ORDER BY COUNT(*) DESC
LIMIT 50;


-- ---------------------------------------------------------------------
-- QUERY 6 : satu sertipikat dengan lebih dari satu baris pengambilan
-- ---------------------------------------------------------------------
-- Sama seperti QUERY 5, ini juga sumber baris ganda. Hasil yang
-- diharapkan: tidak ada baris sama sekali.
SELECT
    pg.sertipikat_id,
    COUNT(*) AS jumlah_baris_pengambilan
FROM public.sr_pengambilan AS pg
GROUP BY pg.sertipikat_id
HAVING COUNT(*) > 1
ORDER BY COUNT(*) DESC
LIMIT 50;


-- ---------------------------------------------------------------------
-- QUERY 7 : sebaran per tahun tanggal akta
-- ---------------------------------------------------------------------
-- Dipakai untuk membandingkan langsung dengan hasil query yang sama di
-- SQL Server, supaya ketahuan tahun mana yang datanya belum tersalin.
SELECT
    EXTRACT(YEAR FROM CAST(a.tgl_akta AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah_akta
FROM public.sr_akta AS a
WHERE COALESCE(CAST(a.tgl_akta AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;
