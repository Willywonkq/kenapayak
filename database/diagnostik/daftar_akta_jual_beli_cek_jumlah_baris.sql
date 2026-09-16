-- =====================================================================
-- DIAGNOSTIK DAFTAR AKTA JUAL BELI  (PostgreSQL)
-- =====================================================================
-- Dipakai ketika desktop menampilkan banyak baris sedangkan web
-- menampilkan "Data tidak ditemukan" untuk rentang tanggal yang sama.
--
-- Seluruh query di berkas ini HANYA MEMBACA. Tidak ada CREATE, INSERT,
-- UPDATE, DELETE, DROP, maupun ALTER. Aman dijalankan pada database
-- produksi.
--
-- Penyaring yang dipakai di sini disamakan dengan layar Anda:
--   Blok        : A s/d ZZ
--   Tanggal AJB : 01-07-2023 s/d 16-09-2026
--   Unit        : DTSA
--   Lokasi      : semua
--   Sektor      : semua
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tipe kolom penghubung
-- ---------------------------------------------------------------------
-- Ini pemeriksaan terpenting. Model menyambung AKTA ke PPJB lewat
-- PPJB_ID, dan AKTA ke SERTIPIKAT lewat SERTIPIKAT_ID. Bila kedua sisi
-- bertipe angka tetapi skalanya berbeda, misalnya numeric(18,2) pada
-- satu tabel dan integer pada tabel lain, maka 11.00 tidak sama dengan
-- 11 bila dibandingkan sebagai teks, dan join tidak menghasilkan satu
-- baris pun.
SELECT
    table_name  AS nama_tabel,
    column_name AS nama_kolom,
    udt_name    AS tipe,
    numeric_precision AS presisi,
    numeric_scale     AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND column_name IN ('ppjb_id', 'sertipikat_id', 'stok_id', 'nasabah_id')
  AND table_name IN (
      'sr_akta', 'sr_ppjb', 'sr_sertipikat', 'sr_stok',
      'sr_pembeli_ppjb', 'sr_nasabah', 'sr_pengambilan', 'sr_angsuran'
  )
ORDER BY column_name, table_name;


-- ---------------------------------------------------------------------
-- QUERY 2 : apakah kunci penghubung benar-benar ketemu
-- ---------------------------------------------------------------------
-- Bila angka "cocok" jauh lebih kecil daripada "terisi", berarti
-- nilainya ada tetapi tidak menemukan pasangan. Itu menunjuk ke
-- perbedaan tipe atau ke data yang belum ikut tersalin.
SELECT
    (SELECT COUNT(*) FROM public.sr_akta) AS baris_akta,

    (SELECT COUNT(*) FROM public.sr_akta a WHERE a.ppjb_id IS NOT NULL)
        AS ppjb_id_terisi,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_ppjb p
                     WHERE CAST(p.ppjb_id AS NUMERIC) = CAST(a.ppjb_id AS NUMERIC)))
        AS ppjb_id_cocok_angka,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_ppjb p
                     WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = BTRIM(CAST(a.ppjb_id AS TEXT))))
        AS ppjb_id_cocok_teks,

    (SELECT COUNT(*) FROM public.sr_akta a WHERE a.sertipikat_id IS NOT NULL)
        AS sertipikat_id_terisi,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE CAST(s.sertipikat_id AS NUMERIC) = CAST(a.sertipikat_id AS NUMERIC)))
        AS sertipikat_id_cocok_angka,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE BTRIM(CAST(s.sertipikat_id AS TEXT)) = BTRIM(CAST(a.sertipikat_id AS TEXT))))
        AS sertipikat_id_cocok_teks;


-- ---------------------------------------------------------------------
-- QUERY 3 : contoh nilai kunci apa adanya
-- ---------------------------------------------------------------------
-- Memperlihatkan bagaimana angkanya benar-benar tertulis. Bila kolom
-- akta berisi 11.00 sedangkan kolom ppjb berisi 11, penyebabnya sudah
-- pasti perbedaan skala.
SELECT
    CAST(a.ppjb_id AS TEXT)        AS akta_ppjb_id,
    CAST(a.sertipikat_id AS TEXT)  AS akta_sertipikat_id,
    CAST(a.tgl_akta AS TEXT)       AS akta_tgl_akta,
    CAST(a.no_akta AS TEXT)        AS akta_no_akta
FROM public.sr_akta AS a
ORDER BY a.tgl_akta DESC NULLS LAST
LIMIT 10;

SELECT CAST(p.ppjb_id AS TEXT) AS ppjb_id
FROM public.sr_ppjb AS p
ORDER BY p.ppjb_id
LIMIT 10;


-- ---------------------------------------------------------------------
-- QUERY 4 : corong jumlah baris, tahap demi tahap
-- ---------------------------------------------------------------------
-- Urutannya persis mengikuti syarat pada model. Perhatikan tahap mana
-- yang pertama kali jatuh menjadi nol; di situlah penyebabnya.
WITH
t1_rentang AS (
    SELECT a.*
    FROM public.sr_akta AS a
    WHERE CASE
              WHEN COALESCE(CAST(a.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(a.tgl_akta AS TIMESTAMP)
          END >= DATE '2023-07-01'
      AND CASE
              WHEN COALESCE(CAST(a.tgl_akta AS TEXT), '')
                   ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
              THEN CAST(a.tgl_akta AS TIMESTAMP)
          END < DATE '2026-09-16' + INTERVAL '1 day'
),
t2_ppjb AS (
    SELECT t1_rentang.*, p.ppjb_id AS p_ppjb_id
    FROM t1_rentang
    INNER JOIN public.sr_ppjb AS p
        ON CAST(p.ppjb_id AS NUMERIC) = CAST(t1_rentang.ppjb_id AS NUMERIC)
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'A'
      AND p.parent_id IS NULL
),
t3_pembeli AS (
    SELECT t2_ppjb.*
    FROM t2_ppjb
    INNER JOIN public.sr_pembeli_ppjb AS pb
        ON CAST(pb.ppjb_id AS NUMERIC) = CAST(t2_ppjb.p_ppjb_id AS NUMERIC)
    WHERE UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
),
t4_sertipikat AS (
    SELECT t3_pembeli.*, s.stok_id AS s_stok_id
    FROM t3_pembeli
    INNER JOIN public.sr_sertipikat AS s
        ON CAST(s.sertipikat_id AS NUMERIC)
         = CAST(t3_pembeli.sertipikat_id AS NUMERIC)
    WHERE s.stok_id IS NOT NULL
),
t5_stok AS (
    SELECT t4_sertipikat.*, st.blok, st.nomor
    FROM t4_sertipikat
    INNER JOIN public.sr_stok AS st
        ON CAST(st.stok_id AS NUMERIC)
         = CAST(t4_sertipikat.s_stok_id AS NUMERIC)
    WHERE UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
),
t6_unit AS (
    SELECT t5_stok.*
    FROM t5_stok
    INNER JOIN public.sr_stok AS st2
        ON CAST(st2.stok_id AS NUMERIC) = CAST(t5_stok.s_stok_id AS NUMERIC)
    WHERE UPPER(BTRIM(COALESCE(CAST(st2.kd_perusahaan AS TEXT), ''))) = 'DTSA'
),
t7_blok AS (
    SELECT t6_unit.*
    FROM t6_unit
    WHERE (
            UPPER(BTRIM(COALESCE(CAST(t6_unit.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(t6_unit.nomor AS TEXT), '')))
            BETWEEN 'A' AND 'ZZ'
          )
       OR (
            UPPER(BTRIM(COALESCE(CAST(t6_unit.blok AS TEXT), '')))
            BETWEEN 'A' AND 'ZZ'
          )
)
SELECT
    (SELECT COUNT(*) FROM public.sr_akta) AS t0_seluruh_akta,
    (SELECT COUNT(*) FROM t1_rentang)     AS t1_rentang_tanggal,
    (SELECT COUNT(*) FROM t2_ppjb)        AS t2_ppjb_aktif,
    (SELECT COUNT(*) FROM t3_pembeli)     AS t3_punya_pembeli,
    (SELECT COUNT(*) FROM t4_sertipikat)  AS t4_punya_sertipikat,
    (SELECT COUNT(*) FROM t5_stok)        AS t5_stok_aktif,
    (SELECT COUNT(*) FROM t6_unit)        AS t6_unit_dtsa,
    (SELECT COUNT(*) FROM t7_blok)        AS t7_rentang_blok;
-- t7 adalah jumlah baris yang seharusnya tampil di web.


-- ---------------------------------------------------------------------
-- QUERY 5 : tanggal akta yang tidak terbaca sebagai tanggal
-- ---------------------------------------------------------------------
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
-- QUERY 6 : nilai penanda aktif yang benar-benar dipakai
-- ---------------------------------------------------------------------
-- Model menyaring PPJB dengan 'A', PEMBELI_PPJB dengan 'Y', dan STOK
-- dengan 'A'. Bila salah satu tabel ternyata memakai nilai lain,
-- penyaringnya akan menghapus seluruh baris.
SELECT 'sr_ppjb' AS tabel,
       UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), '(null)'))) AS nilai,
       COUNT(*) AS jumlah
FROM public.sr_ppjb GROUP BY 2
UNION ALL
SELECT 'sr_pembeli_ppjb',
       UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), '(null)'))), COUNT(*)
FROM public.sr_pembeli_ppjb GROUP BY 2
UNION ALL
SELECT 'sr_stok',
       UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), '(null)'))), COUNT(*)
FROM public.sr_stok GROUP BY 2
ORDER BY 1, 3 DESC;


-- ---------------------------------------------------------------------
-- QUERY 7 : sebaran akta per tahun
-- ---------------------------------------------------------------------
SELECT
    EXTRACT(YEAR FROM CAST(a.tgl_akta AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah_akta
FROM public.sr_akta AS a
WHERE COALESCE(CAST(a.tgl_akta AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;
