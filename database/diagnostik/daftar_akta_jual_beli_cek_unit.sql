-- =====================================================================
-- DIAGNOSTIK LANJUTAN DAFTAR AKTA JUAL BELI - PENYARING UNIT
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- ---------------------------------------------------------------------
-- DUDUK PERKARA
-- ---------------------------------------------------------------------
-- Corong pada diagnostik sebelumnya memberi:
--
--   t0 seluruh akta      17.407
--   t1 rentang tanggal      757
--   t2 ppjb aktif           756
--   t3 punya pembeli        756
--   t4 punya sertipikat   1.336   <- NAIK, berarti ada yang berganda
--   t5 stok aktif         1.328
--   t6 unit DTSA              0   <- MATI DI SINI
--   t7 rentang blok           0
--
-- Dua hal terbaca dari situ.
--
-- Pertama, t4 naik dari 756 menjadi 1.336. Membuang awalan membuat satu
-- akta menemukan dua sertipikat, karena ternyata ada DUA awalan yang
-- dipakai bersamaan, DBPSA- dan DBPSS-, dan setiap angka muncul pada
-- keduanya. Ini sudah diperbaiki di model: awalan yang benar diambil
-- dari PPJB_ID pada baris akta itu sendiri, bukan ditebak.
--
-- Kedua, dan ini penghalang yang sebenarnya, t6 jatuh menjadi NOL.
-- Dari 1.328 baris yang lolos, tidak satu pun membawa kode perusahaan
-- DTSA. Query di bawah mencari tahu kode apa yang sebenarnya dibawa.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : kolom kode apa saja yang ada pada sr_stok
-- ---------------------------------------------------------------------
-- Model memilih kolom perusahaan dari kd_perusahaan, lalu kd_unit, lalu
-- kd_pt, mana yang lebih dulu ada. Kalau yang terpilih ternyata bukan
-- kolom yang menyimpan unit, penyaringnya akan menghapus semua baris.
SELECT column_name AS nama_kolom, udt_name AS tipe
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'sr_stok'
  AND column_name LIKE 'kd_%'
ORDER BY column_name;


-- ---------------------------------------------------------------------
-- QUERY 2 : kode perusahaan apa saja yang benar-benar ada di sr_stok
-- ---------------------------------------------------------------------
-- Cari apakah 'DTSA' memang ada di situ. Bila yang muncul misalnya
-- 'PDSA' atau nilai kosong, di situlah penyebabnya.
SELECT
    UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '(null)'))) AS kode,
    COUNT(*) AS jumlah
FROM public.sr_stok
GROUP BY 1
ORDER BY 2 DESC
LIMIT 30;


-- ---------------------------------------------------------------------
-- QUERY 3 : awalan apa saja yang dipakai tiap tabel
-- ---------------------------------------------------------------------
-- Memperlihatkan berapa banyak "sumber" yang digabung menjadi satu tabel.
SELECT 'sr_stok' AS tabel,
       REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan,
       COUNT(*) AS jumlah
FROM public.sr_stok GROUP BY 1, 2
UNION ALL
SELECT 'sr_ppjb',
       REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', ''), COUNT(*)
FROM public.sr_ppjb GROUP BY 1, 2
UNION ALL
SELECT 'sr_sertipikat',
       REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''),
       COUNT(*)
FROM public.sr_sertipikat GROUP BY 1, 2
UNION ALL
SELECT 'sr_akta (dari ppjb_id)',
       REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', ''), COUNT(*)
FROM public.sr_akta GROUP BY 1, 2
ORDER BY 1, 3 DESC;


-- ---------------------------------------------------------------------
-- QUERY 4 : awalan mana yang dipakai unit DTSA
-- ---------------------------------------------------------------------
-- Kalau DTSA ternyata memakai DBPSS- sedangkan aktanya DBPSA-, berarti
-- keduanya memang dua sumber yang berbeda.
SELECT
    UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '(null)'))) AS kode,
    REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan,
    COUNT(*) AS jumlah
FROM public.sr_stok
GROUP BY 1, 2
ORDER BY 3 DESC
LIMIT 30;


-- ---------------------------------------------------------------------
-- QUERY 5 : corong ulang memakai cara penyambungan yang sudah diperbaiki
-- ---------------------------------------------------------------------
-- Awalan sertipikat diambil dari PPJB_ID, persis seperti model sekarang.
-- Tahap unit sengaja DIPECAH supaya terlihat kode apa yang dibawa baris
-- yang lolos sampai t5.
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
        ON BTRIM(CAST(p.ppjb_id AS TEXT))
         = BTRIM(CAST(t1_rentang.ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'A'
      AND p.parent_id IS NULL
),
t3_pembeli AS (
    SELECT DISTINCT t2_ppjb.*
    FROM t2_ppjb
    INNER JOIN public.sr_pembeli_ppjb AS pb
        ON BTRIM(CAST(pb.ppjb_id AS TEXT))
         = BTRIM(CAST(t2_ppjb.p_ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
),
t4_sertipikat AS (
    SELECT t3_pembeli.*, s.stok_id AS s_stok_id
    FROM t3_pembeli
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT))
         = CASE
               WHEN BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
                    !~ '^[0-9]+$'
               THEN BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
               WHEN BTRIM(CAST(t3_pembeli.ppjb_id AS TEXT))
                    ~ '^[^0-9]+[0-9]+$'
               THEN REGEXP_REPLACE(
                        BTRIM(CAST(t3_pembeli.ppjb_id AS TEXT)),
                        '[0-9]+$', ''
                    ) || BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
               ELSE BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
           END
    WHERE s.stok_id IS NOT NULL
),
t5_stok AS (
    SELECT t4_sertipikat.*, st.blok, st.nomor, st.kd_perusahaan
    FROM t4_sertipikat
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT))
         = BTRIM(CAST(t4_sertipikat.s_stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
)
SELECT
    (SELECT COUNT(*) FROM public.sr_akta) AS t0_seluruh_akta,
    (SELECT COUNT(*) FROM t1_rentang)     AS t1_rentang_tanggal,
    (SELECT COUNT(*) FROM t2_ppjb)        AS t2_ppjb_aktif,
    (SELECT COUNT(*) FROM t3_pembeli)     AS t3_punya_pembeli,
    (SELECT COUNT(*) FROM t4_sertipikat)  AS t4_punya_sertipikat,
    (SELECT COUNT(*) FROM t5_stok)        AS t5_stok_aktif,
    (SELECT COUNT(*) FROM t5_stok
      WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'DTSA')
        AS t6_unit_dtsa;

-- Yang paling menentukan: kode perusahaan apa yang dibawa baris yang
-- lolos sampai t5. Kalau di sini muncul kode selain DTSA, penyaring unit
-- pada model memang harus disesuaikan.
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
        ON BTRIM(CAST(p.ppjb_id AS TEXT))
         = BTRIM(CAST(t1_rentang.ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'A'
      AND p.parent_id IS NULL
),
t3_pembeli AS (
    SELECT DISTINCT t2_ppjb.*
    FROM t2_ppjb
    INNER JOIN public.sr_pembeli_ppjb AS pb
        ON BTRIM(CAST(pb.ppjb_id AS TEXT))
         = BTRIM(CAST(t2_ppjb.p_ppjb_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(pb.flag_aktif AS TEXT), ''))) = 'Y'
),
t4_sertipikat AS (
    SELECT t3_pembeli.*, s.stok_id AS s_stok_id
    FROM t3_pembeli
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT))
         = CASE
               WHEN BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
                    !~ '^[0-9]+$'
               THEN BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
               WHEN BTRIM(CAST(t3_pembeli.ppjb_id AS TEXT))
                    ~ '^[^0-9]+[0-9]+$'
               THEN REGEXP_REPLACE(
                        BTRIM(CAST(t3_pembeli.ppjb_id AS TEXT)),
                        '[0-9]+$', ''
                    ) || BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
               ELSE BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
           END
    WHERE s.stok_id IS NOT NULL
)
SELECT
    UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), '(null)')))
        AS kode_perusahaan,
    COUNT(*) AS jumlah
FROM t4_sertipikat
INNER JOIN public.sr_stok AS st
    ON BTRIM(CAST(st.stok_id AS TEXT))
     = BTRIM(CAST(t4_sertipikat.s_stok_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
  AND st.blok IS NOT NULL
  AND st.nomor IS NOT NULL
GROUP BY 1
ORDER BY 2 DESC;
