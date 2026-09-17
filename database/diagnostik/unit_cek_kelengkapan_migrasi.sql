-- =====================================================================
-- DIAGNOSTIK - KELENGKAPAN DATA PER UNIT
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- PERTANYAANNYA
-- Unit DTSA punya 1.869 baris pada sr_stok, tetapi tidak satu pun punya
-- akta, dan DTSA juga tidak muncul pada sebaran unit sr_sertipikat_idk.
-- Apakah memang belum ada dokumennya, atau datanya belum ikut termigrasi?
--
-- CARA MEMBEDAKANNYA
-- Kalau DTSA unit yang masih muda, pola isinya akan seperti unit muda
-- yang lain: ada stok, mungkin ada PPJB, tetapi belum ada sertipikat dan
-- akta, dan tanggal PPJB-nya baru semua.
--
-- Kalau migrasinya yang belum lengkap, polanya akan ganjil: DTSA punya
-- PPJB lama, sebayanya unit-unit yang sudah punya ribuan sertipikat,
-- tetapi sertipikatnya nol.
--
-- Jalankan QUERY 1 sampai QUERY 3 lalu kirimkan hasilnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : isi tiap tabel per unit
-- ---------------------------------------------------------------------
-- Baris DTSA dibandingkan dengan seluruh unit lain. Yang dicari: adakah
-- unit lain yang polanya sama, yaitu punya stok dan PPJB tetapi nol
-- sertipikat.
WITH stok AS (
    SELECT
        BTRIM(CAST(s.stok_id AS TEXT))                             AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), '')))  AS unit,
        UPPER(BTRIM(COALESCE(CAST(s.flag_aktif AS TEXT), '')))     AS aktif
    FROM public.sr_stok AS s
    WHERE s.stok_id IS NOT NULL
),
ppjb AS (
    SELECT
        stok.unit                          AS unit,
        COUNT(*)                           AS jumlah,
        BTRIM(CAST(p.ppjb_id AS TEXT))     AS kunci_ppjb
    FROM public.sr_ppjb AS p
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(p.stok_id AS TEXT))
    GROUP BY 1, 3
),
ppjb_unit AS (
    SELECT unit, COUNT(*) AS jumlah FROM ppjb GROUP BY 1
),
ser_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_sertipikat AS x
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
    GROUP BY 1
),
akta_unit AS (
    SELECT ppjb.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_akta AS a
    INNER JOIN ppjb ON ppjb.kunci_ppjb = BTRIM(CAST(a.ppjb_id AS TEXT))
    GROUP BY 1
),
stok_unit AS (
    SELECT
        unit,
        COUNT(*)                              AS jumlah,
        COUNT(*) FILTER (WHERE aktif = 'A')   AS jumlah_aktif
    FROM stok
    GROUP BY 1
)
SELECT
    stok_unit.unit                          AS unit,
    stok_unit.jumlah                        AS stok,
    stok_unit.jumlah_aktif                  AS stok_aktif,
    COALESCE(ppjb_unit.jumlah, 0)           AS ppjb,
    COALESCE(ser_unit.jumlah, 0)            AS sertipikat,
    COALESCE(akta_unit.jumlah, 0)           AS akta
FROM stok_unit
LEFT JOIN ppjb_unit ON ppjb_unit.unit = stok_unit.unit
LEFT JOIN ser_unit  ON ser_unit.unit  = stok_unit.unit
LEFT JOIN akta_unit ON akta_unit.unit = stok_unit.unit
ORDER BY stok_unit.jumlah DESC;


-- ---------------------------------------------------------------------
-- QUERY 2 : seberapa tua data tiap unit
-- ---------------------------------------------------------------------
-- Unit yang baru dibuka wajar belum punya sertipikat. Unit yang PPJB-nya
-- sudah bertahun-tahun tetapi sertipikatnya nol itu yang mencurigakan.
WITH stok AS (
    SELECT
        BTRIM(CAST(s.stok_id AS TEXT))                            AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), ''))) AS unit
    FROM public.sr_stok AS s
    WHERE s.stok_id IS NOT NULL
)
SELECT
    stok.unit                                          AS unit,
    COUNT(*)                                           AS jumlah_ppjb,
    MIN(CASE
            WHEN COALESCE(CAST(p.tgl_ppjb AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(p.tgl_ppjb AS TIMESTAMP)
        END)                                           AS ppjb_terlama,
    MAX(CASE
            WHEN COALESCE(CAST(p.tgl_ppjb AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(p.tgl_ppjb AS TIMESTAMP)
        END)                                           AS ppjb_terbaru
FROM public.sr_ppjb AS p
INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(p.stok_id AS TEXT))
GROUP BY 1
ORDER BY 3 NULLS LAST;


-- ---------------------------------------------------------------------
-- QUERY 3 : contoh isi DTSA apa adanya
-- ---------------------------------------------------------------------
-- Dua puluh stok DTSA beserta ada tidaknya PPJB dan sertipikatnya.
-- Kalau kolom "ada_ppjb" berisi Y sementara "ada_sertipikat" seluruhnya
-- T, berarti rantai datanya memang putus di sertipikat.
SELECT
    BTRIM(CAST(stok.stok_id AS TEXT))                          AS stok_id,
    UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))        AS blok,
    UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))       AS nomor,
    UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), '')))  AS aktif,
    CASE WHEN EXISTS (
             SELECT 1 FROM public.sr_ppjb AS p
             WHERE BTRIM(CAST(p.stok_id AS TEXT))
                 = BTRIM(CAST(stok.stok_id AS TEXT))
         ) THEN 'Y' ELSE 'T' END                               AS ada_ppjb,
    CASE WHEN EXISTS (
             SELECT 1 FROM public.sr_sertipikat AS x
             WHERE BTRIM(CAST(x.stok_id AS TEXT))
                 = BTRIM(CAST(stok.stok_id AS TEXT))
         ) THEN 'Y' ELSE 'T' END                               AS ada_sertipikat
FROM public.sr_stok AS stok
WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'DTSA'
ORDER BY BTRIM(CAST(stok.stok_id AS TEXT))
LIMIT 20;


-- ---------------------------------------------------------------------
-- QUERY 4 : rantai data DTSA, dilacak langkah demi langkah
-- ---------------------------------------------------------------------
-- Pada aplikasi desktop, DTSA jelas punya data Daftar Sertipikat
-- Pemisahan. Query ini mencari di langkah mana rantainya putus pada
-- PostgreSQL.
--
-- Cara membacanya, dari atas ke bawah:
--   stok jadi 0            -> unitnya sendiri belum termigrasi
--   stok_aktif jadi 0      -> datanya ADA, hanya tidak bertanda aktif;
--                             ini bisa diperbaiki di kode
--   sertipikat jadi 0      -> tabel sertipikatnya belum termigrasi
--   idk_dbpsa jadi 0       -> sertipikat induknya belum termigrasi
--
-- Kolom yang berakhiran _aktif memakai syarat flag_aktif = 'A' seperti
-- yang dipakai laporannya; yang tanpa akhiran itu tanpa syarat apa pun.
WITH stok_dtsa AS (
    SELECT
        BTRIM(CAST(s.stok_id AS TEXT))                          AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(s.flag_aktif AS TEXT), '')))  AS aktif
    FROM public.sr_stok AS s
    WHERE UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), ''))) = 'DTSA'
      AND s.stok_id IS NOT NULL
),
ser_dtsa AS (
    SELECT
        BTRIM(CAST(x.sertipikat_id AS TEXT)) AS kunci_ser,
        stok_dtsa.aktif                      AS aktif
    FROM public.sr_sertipikat AS x
    INNER JOIN stok_dtsa
        ON stok_dtsa.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
),
idk AS (
    SELECT
        'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci_dbpsa,
        'DBPSS-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci_dbpss
    FROM public.sr_sertipikat_idk AS i
    WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
)
SELECT
    (SELECT COUNT(*) FROM stok_dtsa)                              AS stok,
    (SELECT COUNT(*) FROM stok_dtsa WHERE aktif = 'A')            AS stok_aktif,
    (SELECT COUNT(*) FROM ser_dtsa)                               AS sertipikat,
    (SELECT COUNT(*) FROM ser_dtsa WHERE aktif = 'A')             AS sertipikat_aktif,
    (SELECT COUNT(*) FROM idk
      INNER JOIN ser_dtsa ON ser_dtsa.kunci_ser = idk.kunci_dbpsa) AS idk_dbpsa,
    (SELECT COUNT(*) FROM idk
      INNER JOIN ser_dtsa ON ser_dtsa.kunci_ser = idk.kunci_dbpss) AS idk_dbpss;


-- ---------------------------------------------------------------------
-- QUERY 5 : sebaran tanda aktif pada stok, per unit
-- ---------------------------------------------------------------------
-- Laporannya hanya menampilkan stok bertanda 'A'. Kalau DTSA memakai
-- tanda lain, atau kosong, itulah sebabnya tidak muncul apa-apa.
SELECT
    UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '(kosong)'))) AS unit,
    UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), '(kosong)')))    AS tanda_aktif,
    COUNT(*)                                                        AS jumlah
FROM public.sr_stok
GROUP BY 1, 2
ORDER BY 1, 3 DESC;


-- ---------------------------------------------------------------------
-- QUERY 6 : awalan sertipikat milik DTSA
-- ---------------------------------------------------------------------
-- Memastikan sertipikat DTSA memang memakai salah satu dari dua awalan
-- yang sudah dikenal. Kalau muncul awalan ketiga, penyusunan kuncinya
-- harus disesuaikan.
SELECT
    REGEXP_REPLACE(BTRIM(CAST(x.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
    COUNT(*)                                                           AS jumlah,
    MIN(BTRIM(CAST(x.sertipikat_id AS TEXT)))                          AS contoh
FROM public.sr_sertipikat AS x
INNER JOIN public.sr_stok AS s
    ON BTRIM(CAST(s.stok_id AS TEXT)) = BTRIM(CAST(x.stok_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), ''))) = 'DTSA'
GROUP BY 1
ORDER BY 2 DESC;
