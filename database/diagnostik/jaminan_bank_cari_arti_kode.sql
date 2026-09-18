-- =====================================================================
-- DIAGNOSTIK - MENCARI ARTI KODE JENIS_JAMINAN
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- QUERY 1 memakai query_to_xml, yang menjalankan query lain di dalamnya.
-- Query yang dijalankannya HANYA SELECT COUNT(*), teksnya disusun di
-- dalam berkas ini dan tidak berasal dari masukan siapa pun.
--
-- LATAR BELAKANG
-- Kolom JENIS_JAMINAN berisi kode satu huruf: 4, 2, kosong, A, H, P, 3,
-- 5, dan T. Dropdown pada layar mengirim tulisan IMB, Akta Jual Beli,
-- Sertipikat, PPJB, dan Peralihan Hak, sehingga tidak pernah cocok.
-- Pencarian nama tabel sudah dilakukan dan tidak menemukan tabel acuan.
--
-- Berkas ini menempuh dua jalan lain:
--   QUERY 1 mencari ISI tabelnya, bukan namanya, kalau-kalau ada tabel
--           acuan yang namanya tidak terduga.
--   QUERY 2 dan 3 menyimpulkan artinya dari perilaku datanya sendiri.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : cari tulisan IMB, PPJB, dan sejenisnya di seluruh tabel kecil
-- ---------------------------------------------------------------------
-- Tabel acuan biasanya kecil. Query ini memeriksa isi setiap kolom teks
-- pada tabel yang barisnya sedikit, mencari tulisan yang sama dengan
-- pilihan pada dropdown.
--
-- Bila ada barisnya, tabel acuan itu ketemu dan artinya bisa dibaca
-- langsung. Bila kosong, artinya memang tidak tersimpan di database ini.
WITH tabel_kecil AS (
    SELECT c.relname AS nama_tabel
    FROM pg_class AS c
    INNER JOIN pg_namespace AS n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public'
      AND c.relkind = 'r'
      AND c.reltuples >= 0
      AND c.reltuples <= 5000
),
kolom_teks AS (
    SELECT col.table_name AS nama_tabel, col.column_name AS nama_kolom
    FROM information_schema.columns AS col
    INNER JOIN tabel_kecil ON tabel_kecil.nama_tabel = col.table_name
    WHERE col.table_schema = 'public'
      AND col.data_type IN ('character varying', 'text', 'character')
),
hitung AS (
    SELECT
        kolom_teks.nama_tabel,
        kolom_teks.nama_kolom,
        (xpath(
            '/row/c/text()',
            query_to_xml(
                format(
                    'SELECT COUNT(*) AS c FROM public.%I WHERE %I::text ~* %L',
                    kolom_teks.nama_tabel,
                    kolom_teks.nama_kolom,
                    '(IMB|AKTA JUAL BELI|PERALIHAN HAK|PPJB|SERTIPIKAT)'
                ),
                false, true, ''
            )
        ))[1]::text::bigint AS jumlah_cocok
    FROM kolom_teks
)
SELECT nama_tabel, nama_kolom, jumlah_cocok
FROM hitung
WHERE jumlah_cocok > 0
ORDER BY jumlah_cocok DESC, nama_tabel, nama_kolom;


-- ---------------------------------------------------------------------
-- QUERY 2 : perilaku tiap kode jenis jaminan
-- ---------------------------------------------------------------------
-- Ini jalan yang paling menentukan bila tabel acuannya memang tidak ada.
--
-- Jaminan bank berarti dokumen itu yang dipegang bank, sehingga
-- dokumennya harus benar-benar ada. Jadi:
--
--   kode yang artinya Akta Jual Beli -> hampir semuanya punya AKTA;
--   kode yang artinya Sertipikat     -> hampir semuanya NO_SERTIPIKAT
--                                       terisi;
--   kode yang artinya PPJB           -> kebanyakan BELUM punya AKTA,
--                                       karena masih tahap PPJB;
--   kode yang artinya Peralihan Hak  -> punya baris pada sr_peralihan;
--   kode yang artinya IMB            -> tidak menonjol pada ketiganya.
--
-- Awalannya dihitung dari data, sama seperti yang dilakukan model.
WITH awalan AS (
    SELECT ser.awalan AS awalan
    FROM (
        SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
        FROM public.sr_jaminan AS j
        WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
    ) AS jm
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
            COUNT(*) AS banyak
        FROM public.sr_sertipikat
        WHERE sertipikat_id IS NOT NULL
        GROUP BY 1
    ) AS ser ON ser.angka = jm.angka
    WHERE ser.banyak = 1
    GROUP BY 1
    ORDER BY COUNT(*) DESC
    LIMIT 1
),
jaminan AS (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(j.jenis_jaminan AS TEXT), '(kosong)'))) AS kode,
        (SELECT awalan FROM awalan) || BTRIM(CAST(j.sertipikat_id AS TEXT))
            AS kunci_sertipikat
    FROM public.sr_jaminan AS j
    WHERE j.no_jaminan IS NOT NULL
      AND j.no_lunas IS NULL
      AND j.no_batal IS NULL
      AND BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
),
gabung AS (
    SELECT
        jaminan.kode AS kode,
        BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci_sertipikat,
        NULLIF(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), '')), '')
            IS NOT NULL AS punya_no_sertipikat,
        EXISTS (
            SELECT 1 FROM public.sr_akta AS a
            WHERE a.no_akta IS NOT NULL
              AND CASE
                      WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                      THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
                      WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                      THEN REGEXP_REPLACE(
                               BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                           ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
                      ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
                  END = BTRIM(CAST(s.sertipikat_id AS TEXT))
        ) AS punya_akta,
        EXISTS (
            SELECT 1
            FROM public.sr_ppjb AS p
            INNER JOIN public.sr_peralihan AS pr
                ON BTRIM(CAST(pr.ppjb_id AS TEXT))
                 = REGEXP_REPLACE(BTRIM(CAST(p.ppjb_id AS TEXT)), '^[^0-9]+', '')
            WHERE BTRIM(CAST(p.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
        ) AS punya_peralihan
    FROM jaminan
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT)) = jaminan.kunci_sertipikat
)
SELECT
    kode,
    COUNT(*)                                                       AS baris,
    ROUND(100.0 * COUNT(*) FILTER (WHERE punya_akta) / COUNT(*), 1) AS persen_punya_akta,
    ROUND(100.0 * COUNT(*) FILTER (WHERE punya_no_sertipikat) / COUNT(*), 1)
                                                                   AS persen_no_sertipikat,
    ROUND(100.0 * COUNT(*) FILTER (WHERE punya_peralihan) / COUNT(*), 1)
                                                                   AS persen_peralihan
FROM gabung
GROUP BY kode
ORDER BY baris DESC;


-- ---------------------------------------------------------------------
-- QUERY 3 : kapan tiap kode dipakai
-- ---------------------------------------------------------------------
-- Kode lama dan kode baru terlihat dari rentang tahunnya. Bila sebuah
-- kode berhenti dipakai tepat ketika kode lain mulai dipakai, keduanya
-- kemungkinan besar berarti sama dan hanya berganti penulisan.
SELECT
    UPPER(BTRIM(COALESCE(CAST(jenis_jaminan AS TEXT), '(kosong)'))) AS kode,
    COUNT(*)                                                       AS baris,
    MIN(EXTRACT(YEAR FROM tgl_jaminan))::int                       AS tahun_awal,
    MAX(EXTRACT(YEAR FROM tgl_jaminan))::int                       AS tahun_akhir
FROM public.sr_jaminan
WHERE no_jaminan IS NOT NULL
  AND no_lunas IS NULL
  AND no_batal IS NULL
  AND tgl_jaminan IS NOT NULL
GROUP BY 1
ORDER BY 3, 1;
