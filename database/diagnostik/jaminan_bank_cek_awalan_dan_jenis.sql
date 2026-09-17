-- =====================================================================
-- DIAGNOSTIK LANJUTAN - REKAP JAMINAN BANK
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- HASIL PEMERIKSAAN PERTAMA
--   * sr_jaminan.sertipikat_id bertipe numeric, jadi awalannya terbuang;
--   * sr_jaminan TIDAK punya kolom kd_perusahaan, jadi awalannya tidak
--     bisa dipulihkan lewat unit;
--   * dari 5.190 baris, hanya 501 yang angkanya menunjuk ke tepat satu
--     sertipikat, sedangkan 4.689 rancu;
--   * jenis_jaminan ternyata varchar(1) berisi kode seperti 4, 2, A, H,
--     dan P, bukan tulisan IMB atau Akta Jual Beli seperti pada dropdown.
--
-- Dua query pertama menentukan apakah awalannya bisa dipulihkan penuh.
-- Dua terakhir mencari arti kode jenis jaminan.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : suara dari baris yang sudah pasti
-- ---------------------------------------------------------------------
-- Dari 501 baris yang tidak rancu, keluarga mana yang mereka tunjuk?
--
--   satu awalan hampir 100 persen -> seluruh sr_jaminan memang satu
--       keluarga, dan awalan itu aman dipakai untuk semua baris. Model
--       sudah menerapkannya sendiri dengan ambang 95 persen.
--   terbagi dua                   -> tidak bisa disimpulkan, model akan
--       memakai yang tidak rancu saja dan sebagian baris tidak tampil.
WITH jm AS (
    SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
    FROM public.sr_jaminan AS j
    WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
),
ser AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
        COUNT(*) AS banyak
    FROM public.sr_sertipikat
    WHERE sertipikat_id IS NOT NULL
    GROUP BY 1
)
SELECT
    ser.awalan                                       AS awalan,
    COUNT(*)                                         AS baris_pasti,
    ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 1) AS persen
FROM jm
INNER JOIN ser ON ser.angka = jm.angka
WHERE ser.banyak = 1
GROUP BY 1
ORDER BY 2 DESC;


-- ---------------------------------------------------------------------
-- QUERY 2 : bila satu awalan dipakai untuk semua, berapa yang tampil
-- ---------------------------------------------------------------------
-- Memperkirakan hasilnya sebelum dicoba di layar. Hanya baris yang siap
-- tampil yang dihitung, yaitu NO_JAMINAN terisi, belum lunas, belum batal.
WITH jm AS (
    SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
    FROM public.sr_jaminan AS j
    WHERE j.no_jaminan IS NOT NULL
      AND j.no_lunas IS NULL
      AND j.no_batal IS NULL
      AND BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
),
calon (awalan) AS (
    SELECT DISTINCT
        REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')
    FROM public.sr_sertipikat
    WHERE sertipikat_id IS NOT NULL
)
SELECT
    calon.awalan                                             AS awalan,
    COUNT(*)                                                 AS jaminan_ketemu,
    COUNT(DISTINCT UPPER(BTRIM(COALESCE(
        CAST(stok.kd_perusahaan AS TEXT), ''))))             AS unit_terjangkau
FROM jm
CROSS JOIN calon
INNER JOIN public.sr_sertipikat AS s
    ON BTRIM(CAST(s.sertipikat_id AS TEXT)) = calon.awalan || jm.angka
LEFT JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
GROUP BY 1
ORDER BY 2 DESC;


-- ---------------------------------------------------------------------
-- QUERY 3 : adakah tabel acuan arti kode jenis jaminan
-- ---------------------------------------------------------------------
-- Dropdown pada layar mengirim tulisan IMB, Akta Jual Beli, Sertipikat,
-- PPJB, dan Peralihan Hak, sedangkan datanya menyimpan kode satu huruf.
-- Query ini mencari tabel mana pun yang mungkin memuat artinya.
SELECT
    t.table_name  AS nama_tabel,
    c.column_name AS nama_kolom,
    c.data_type   AS tipe
FROM information_schema.tables AS t
INNER JOIN information_schema.columns AS c
    ON c.table_schema = t.table_schema
   AND c.table_name = t.table_name
WHERE t.table_schema = 'public'
  AND (
        t.table_name ILIKE '%jaminan%'
     OR t.table_name ILIKE '%jenis%'
     OR c.column_name ILIKE '%jenis_jaminan%'
      )
ORDER BY t.table_name, c.ordinal_position;


-- ---------------------------------------------------------------------
-- QUERY 4 : contoh baris untuk tiap kode jenis jaminan
-- ---------------------------------------------------------------------
-- Melihat isi kolom lain pada tiap kode, kalau-kalau ada petunjuk
-- artinya, misalnya PENGAJUAN atau NO_BERKAS yang bertuliskan sesuatu.
SELECT DISTINCT ON (kode)
    kode, no_jaminan, pengajuan, no_berkas, notaris, nama_bank
FROM (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(jenis_jaminan AS TEXT), '(kosong)'))) AS kode,
        BTRIM(COALESCE(CAST(no_jaminan AS TEXT), '')) AS no_jaminan,
        BTRIM(COALESCE(CAST(pengajuan AS TEXT), '')) AS pengajuan,
        BTRIM(COALESCE(CAST(no_berkas AS TEXT), '')) AS no_berkas,
        BTRIM(COALESCE(CAST(notaris AS TEXT), '')) AS notaris,
        BTRIM(COALESCE(CAST(nama_bank AS TEXT), '')) AS nama_bank,
        ctid AS urutan_fisik
    FROM public.sr_jaminan
    WHERE no_jaminan IS NOT NULL
) AS daftar
ORDER BY kode, urutan_fisik;
