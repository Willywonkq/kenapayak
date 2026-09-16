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
--
-- CATATAN PERBAIKAN
-- Versi sebelumnya membandingkan kunci dengan CAST(... AS NUMERIC).
-- Itu keliru: kunci pada database ini ternyata berupa teks berawalan,
-- misalnya DBPSA-18784, sehingga PostgreSQL menolak dengan
-- "invalid input syntax for type numeric". Seluruh perbandingan di
-- bawah kini memakai TEKS sehingga tidak mungkin gagal lagi, apa pun
-- isi kolomnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tipe kolom penghubung          [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil dari Anda sudah memperlihatkan hal yang menentukan:
--
--   sr_akta.sertipikat_id        numeric(18,0)
--   sr_sertipikat.sertipikat_id  varchar
--
-- Kedua sisi join bertipe berbeda, dan kolom varchar pada database ini
-- berisi teks berawalan seperti DBPSA-123. Angka tidak akan pernah
-- sama dengan teks semacam itu.
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
-- QUERY 2 : contoh nilai kunci apa adanya           [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Memperlihatkan bagaimana kedua sisi join sertipikat benar-benar
-- tertulis. Perhatikan apakah sisi akta berupa angka polos sedangkan
-- sisi sertipikat berawalan huruf.
SELECT 'sr_akta.sertipikat_id' AS sisi,
       CAST(a.sertipikat_id AS TEXT) AS contoh_nilai
FROM public.sr_akta AS a
WHERE a.sertipikat_id IS NOT NULL
ORDER BY a.sertipikat_id
LIMIT 10;

SELECT 'sr_sertipikat.sertipikat_id' AS sisi,
       CAST(s.sertipikat_id AS TEXT) AS contoh_nilai
FROM public.sr_sertipikat AS s
WHERE s.sertipikat_id IS NOT NULL
ORDER BY s.sertipikat_id
LIMIT 10;


-- ---------------------------------------------------------------------
-- QUERY 3 : apakah kunci penghubung benar-benar ketemu  [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Semua perbandingan memakai TEKS, jadi query ini tidak akan error.
-- Bila angka "cocok" nol sedangkan "terisi" besar, berarti nilainya
-- ada tetapi tidak pernah menemukan pasangan.
SELECT
    (SELECT COUNT(*) FROM public.sr_akta) AS baris_akta,

    (SELECT COUNT(*) FROM public.sr_akta a WHERE a.ppjb_id IS NOT NULL)
        AS ppjb_id_terisi,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_ppjb p
                     WHERE BTRIM(CAST(p.ppjb_id AS TEXT))
                         = BTRIM(CAST(a.ppjb_id AS TEXT))))
        AS ppjb_id_cocok,

    (SELECT COUNT(*) FROM public.sr_akta a WHERE a.sertipikat_id IS NOT NULL)
        AS sertipikat_id_terisi,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE BTRIM(CAST(s.sertipikat_id AS TEXT))
                         = BTRIM(CAST(a.sertipikat_id AS TEXT))))
        AS sertipikat_id_cocok;


-- ---------------------------------------------------------------------
-- QUERY 4 : bentuk nilai pada kolom sertipikat_id       [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Menghitung berapa nilai yang berupa angka polos dan berapa yang
-- berawalan huruf. Kalau sisi sertipikat seluruhnya berawalan huruf
-- sedangkan sisi akta seluruhnya angka, join memang mustahil terjadi.
SELECT 'sr_sertipikat' AS tabel,
       COUNT(*) AS jumlah,
       COUNT(*) FILTER (WHERE sertipikat_id IS NULL) AS kosong,
       COUNT(*) FILTER (
           WHERE BTRIM(CAST(sertipikat_id AS TEXT)) ~ '^[0-9]+$'
       ) AS angka_polos,
       COUNT(*) FILTER (
           WHERE BTRIM(CAST(sertipikat_id AS TEXT)) !~ '^[0-9]+$'
             AND sertipikat_id IS NOT NULL
       ) AS berawalan_huruf
FROM public.sr_sertipikat
UNION ALL
SELECT 'sr_akta',
       COUNT(*),
       COUNT(*) FILTER (WHERE sertipikat_id IS NULL),
       COUNT(*) FILTER (
           WHERE BTRIM(CAST(sertipikat_id AS TEXT)) ~ '^[0-9]+$'
       ),
       COUNT(*) FILTER (
           WHERE BTRIM(CAST(sertipikat_id AS TEXT)) !~ '^[0-9]+$'
             AND sertipikat_id IS NOT NULL
       )
FROM public.sr_akta;


-- ---------------------------------------------------------------------
-- QUERY 5 : uji dugaan awalan yang hilang               [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Dugaan: saat migrasi, sr_akta.sertipikat_id dibuat bertipe angka
-- sehingga awalan DBPSA- terbuang, sedangkan sr_sertipikat.sertipikat_id
-- tetap menyimpan teks lengkapnya. Bila dugaan itu benar, membuang
-- awalan dari sisi sertipikat akan membuat keduanya bertemu.
--
-- Bandingkan "cocok_apa_adanya" dengan "cocok_tanpa_awalan".
SELECT
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE a.sertipikat_id IS NOT NULL
        AND EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE BTRIM(CAST(s.sertipikat_id AS TEXT))
                         = BTRIM(CAST(a.sertipikat_id AS TEXT))))
        AS cocok_apa_adanya,
    (SELECT COUNT(*) FROM public.sr_akta a
      WHERE a.sertipikat_id IS NOT NULL
        AND EXISTS (SELECT 1 FROM public.sr_sertipikat s
                     WHERE REGEXP_REPLACE(
                               BTRIM(CAST(s.sertipikat_id AS TEXT)),
                               '^[^0-9]+', ''
                           )
                         = BTRIM(CAST(a.sertipikat_id AS TEXT))))
        AS cocok_tanpa_awalan;


-- ---------------------------------------------------------------------
-- QUERY 6 : corong jumlah baris, tahap demi tahap       [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Urutannya persis mengikuti syarat pada model, tetapi seluruh
-- perbandingan kunci memakai TEKS sehingga tidak akan error lagi.
-- Perhatikan tahap mana yang pertama kali jatuh menjadi nol; di situlah
-- penyebabnya.
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
    SELECT t2_ppjb.*
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
         = BTRIM(CAST(t3_pembeli.sertipikat_id AS TEXT))
    WHERE s.stok_id IS NOT NULL
),
t5_stok AS (
    SELECT t4_sertipikat.*, st.blok, st.nomor
    FROM t4_sertipikat
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT))
         = BTRIM(CAST(t4_sertipikat.s_stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
),
t6_unit AS (
    SELECT t5_stok.*
    FROM t5_stok
    INNER JOIN public.sr_stok AS st2
        ON BTRIM(CAST(st2.stok_id AS TEXT))
         = BTRIM(CAST(t5_stok.s_stok_id AS TEXT))
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
-- QUERY 7 : tanggal akta yang tidak terbaca     [SUDAH DIJAWAB, AMAN]
-- ---------------------------------------------------------------------
-- Hasil Anda: 17.407 akta, 17.357 tanggal terbaca, 50 kosong, 0 rusak.
-- Tanggal bukan penyebabnya.
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
-- QUERY 8 : nilai penanda aktif                 [SUDAH DIJAWAB, AMAN]
-- ---------------------------------------------------------------------
-- Hasil Anda: sr_ppjb memakai A dan T, sr_pembeli_ppjb memakai Y dan T,
-- sr_stok memakai A dan T. Semuanya sesuai dengan yang disaring model.
-- Penanda aktif bukan penyebabnya.
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
-- QUERY 9 : sebaran akta per tahun              [SUDAH DIJAWAB, AMAN]
-- ---------------------------------------------------------------------
-- Hasil Anda berhenti di 2024 dengan 54 akta, ditambah 1.052 akta pada
-- 2023 dan satu baris tahun 2203 yang jelas salah ketik. Jadi rentang
-- 01-07-2023 s/d 16-09-2026 memang masih berisi data.
SELECT
    EXTRACT(YEAR FROM CAST(a.tgl_akta AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah_akta
FROM public.sr_akta AS a
WHERE COALESCE(CAST(a.tgl_akta AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;
