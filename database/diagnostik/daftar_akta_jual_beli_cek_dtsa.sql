-- =====================================================================
-- DIAGNOSTIK DAFTAR AKTA JUAL BELI - ADAKAH AKTA MILIK DTSA
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, maupun ALTER. Aman dijalankan pada database produksi.
--
-- ---------------------------------------------------------------------
-- DUDUK PERKARA
-- ---------------------------------------------------------------------
-- Corong dengan join yang sudah diperbaiki memberi:
--
--   t0 seluruh akta      17.407
--   t1 rentang tanggal      757
--   t2 ppjb aktif           756
--   t3 punya pembeli        756
--   t4 punya sertipikat     756   <- tidak berganda lagi, join sudah benar
--   t5 stok aktif           756
--   t6 unit DTSA              0
--
-- Dan 756 baris itu ternyata milik unit lain seluruhnya:
--
--   SBKS 546, MKPP 99, SKRW 81, SKLG 18, GDOR 8, WGP 2, SMSF 1, SKPN 1
--
-- Jadi pada rentang 01-07-2023 s/d 16-09-2026 memang TIDAK ADA satu pun
-- akta milik DTSA di PostgreSQL. Penyaring unitnya benar, modelnya benar,
-- datanya yang tidak ada.
--
-- Query di bawah memastikan apakah akta DTSA memang belum ikut termigrasi
-- sama sekali, atau ada tetapi di rentang tanggal yang berbeda.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : akta per unit, TANPA batas tanggal
-- ---------------------------------------------------------------------
-- Kalau DTSA tidak muncul sama sekali di daftar ini, berarti akta milik
-- DTSA memang belum ikut tersalin ke PostgreSQL.
WITH akta_unit AS (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), '(kosong)')))
            AS kode_unit,
        a.tgl_akta
    FROM public.sr_akta AS a
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT))
         = CASE
               WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
               WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
               THEN REGEXP_REPLACE(
                        BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                    ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
               ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
           END
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT))
         = BTRIM(CAST(s.stok_id AS TEXT))
)
SELECT kode_unit, COUNT(*) AS jumlah_akta
FROM akta_unit
GROUP BY 1
ORDER BY 2 DESC;


-- ---------------------------------------------------------------------
-- QUERY 2 : kalau DTSA muncul di atas, tahun berapa saja aktanya
-- ---------------------------------------------------------------------
-- Kalau hasilnya berhenti sebelum Juli 2023, berarti aktanya ada tetapi
-- lebih tua daripada rentang yang Anda pilih.
WITH akta_dtsa AS (
    SELECT a.tgl_akta
    FROM public.sr_akta AS a
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT))
         = CASE
               WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
               WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
               THEN REGEXP_REPLACE(
                        BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                    ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
               ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
           END
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT))
         = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA'
)
SELECT
    EXTRACT(YEAR FROM CAST(tgl_akta AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah_akta
FROM akta_dtsa
WHERE COALESCE(CAST(tgl_akta AS TEXT), '') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;


-- ---------------------------------------------------------------------
-- QUERY 3 : rantai data DTSA, putus di mana
-- ---------------------------------------------------------------------
-- Memperlihatkan sampai tahap mana data DTSA ikut tersalin.
SELECT
    (SELECT COUNT(*) FROM public.sr_stok st
      WHERE UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA')
        AS stok_dtsa,
    (SELECT COUNT(*) FROM public.sr_stok st
      WHERE UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA'
        AND EXISTS (SELECT 1 FROM public.sr_ppjb p
                     WHERE BTRIM(CAST(p.stok_id AS TEXT))
                         = BTRIM(CAST(st.stok_id AS TEXT))))
        AS punya_ppjb,
    (SELECT COUNT(*) FROM public.sr_stok st
      WHERE UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA'
        AND EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE BTRIM(CAST(s.stok_id AS TEXT))
                         = BTRIM(CAST(st.stok_id AS TEXT))))
        AS punya_sertipikat,
    (SELECT COUNT(*) FROM public.sr_stok st
      WHERE UPPER(BTRIM(COALESCE(CAST(st.kd_perusahaan AS TEXT), ''))) = 'DTSA'
        AND EXISTS (
            SELECT 1
            FROM public.sr_sertipikat s
            INNER JOIN public.sr_akta a
                ON BTRIM(CAST(s.sertipikat_id AS TEXT))
                 = CASE
                       WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                       THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
                       WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                       THEN REGEXP_REPLACE(
                                BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                            ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
                       ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
                   END
            WHERE BTRIM(CAST(s.stok_id AS TEXT))
                = BTRIM(CAST(st.stok_id AS TEXT))))
        AS punya_akta;
