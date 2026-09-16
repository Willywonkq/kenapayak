-- =====================================================================
-- DIAGNOSTIK DAFTAR AKTA JUAL BELI  (PostgreSQL)
-- =====================================================================
-- Seluruh query di berkas ini HANYA MEMBACA. Tidak ada CREATE, INSERT,
-- UPDATE, DELETE, DROP, maupun ALTER. Aman dijalankan pada database
-- produksi.
--
-- Penyaring disamakan dengan layar: Blok A s/d ZZ, tanggal AJB
-- 01-07-2023 s/d 16-09-2026, unit DTSA, lokasi dan sektor semua.
--
-- ---------------------------------------------------------------------
-- KESIMPULAN  (sudah terbukti dari hasil QUERY 1 s/d 5 pada web_sris)
-- ---------------------------------------------------------------------
-- Migrasi tidak utuh. Kunci pada database ini ditulis berawalan, misalnya
-- DBPSA-18784. Sebagian tabel ikut memakai bentuk itu, tetapi SR_AKTA dan
-- SR_PENGAMBILAN terlanjur dibuat bertipe angka sehingga awalannya
-- terbuang dan hanya menyisakan 18784.
--
--     sr_sertipikat.sertipikat_id  varchar        50.479 baris
--                                                 seluruhnya berawalan
--     sr_akta.sertipikat_id        numeric(18,0)  17.407 baris
--                                                 seluruhnya angka telanjang
--
--     ppjb_id terisi 16.794, cocok 16.794   -> join PPJB sehat
--     sertipikat_id terisi 17.407, cocok 0  -> join SERTIPIKAT mati total
--     cocok setelah awalan dibuang: 17.407  -> seluruh datanya utuh
--
-- Model menyambung AKTA ke SERTIPIKAT dengan INNER JOIN, jadi nol
-- pasangan berarti nol baris. Itulah sebab web menampilkan
-- "Data tidak ditemukan".
--
-- Model sudah diperbaiki: pada join antara kolom angka dan kolom teks,
-- awalan pada sisi teks dibuang lebih dulu sebelum keduanya dibandingkan
-- sebagai angka. Tidak ada perubahan apa pun pada database.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : tipe kolom penghubung                       [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
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
-- QUERY 2 : contoh nilai kunci apa adanya               [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: sisi akta berupa angka telanjang, sisi sertipikat berawalan
-- DBPSA-. Dua bentuk yang tidak mungkin bertemu.
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
-- QUERY 3 : apakah kunci penghubung ketemu              [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: 17407  16794  16794  17407  0
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
-- QUERY 4 : bentuk nilai pada sertipikat_id             [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: sr_sertipikat 50479 baris seluruhnya berawalan huruf,
--        sr_akta       17407 baris seluruhnya angka polos.
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
-- QUERY 5 : uji dugaan awalan yang hilang               [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: cocok_apa_adanya 0, cocok_tanpa_awalan 17407.
-- Seluruh akta menemukan sertipikatnya begitu awalan dibuang.
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
-- QUERY 6 : apakah membuang awalan aman                  [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Membuang awalan hanya aman bila hasilnya tetap unik. Kalau di dalam
-- sr_sertipikat ada dua ID berawalan berbeda yang menyisakan angka sama,
-- misalnya DBPSA-123 dan ABC-123, satu akta akan menemukan dua pasangan
-- dan barisnya tampil berganda.
--
-- "awalan_berbeda" harus 1 dan "angka_kembar" harus 0.
SELECT
    COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)),
                                  '[0-9]+$', '')) AS awalan_berbeda,
    COUNT(*) FILTER (WHERE TRUE) AS jumlah_baris,
    COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)),
                                  '^[^0-9]+', '')) AS angka_unik
FROM public.sr_sertipikat
WHERE sertipikat_id IS NOT NULL;

-- Daftar angka yang kembar, kalau memang ada. Kosong berarti aman.
SELECT REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '')
           AS angka,
       COUNT(*) AS jumlah,
       STRING_AGG(BTRIM(CAST(sertipikat_id AS TEXT)), ', ') AS nilai_asli
FROM public.sr_sertipikat
WHERE sertipikat_id IS NOT NULL
GROUP BY 1
HAVING COUNT(*) > 1
LIMIT 20;


-- ---------------------------------------------------------------------
-- QUERY 7 : corong jumlah baris, tahap demi tahap        [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Memakai cara penyambungan yang sama dengan model setelah diperbaiki,
-- yaitu awalan pada sisi teks dibuang lebih dulu. Hasil t7 adalah jumlah
-- baris yang akan muncul di web sekarang.
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
        ON REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)),
                          '^[^0-9]+', '')
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


-- ---------------------------------------------------------------------
-- QUERY 8 : tanggal akta yang tidak terbaca             [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: 17.407 akta, 17.357 tanggal terbaca, 50 kosong, 0 rusak.
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
-- QUERY 9 : nilai penanda aktif                         [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil: sr_ppjb memakai A dan T, sr_pembeli_ppjb memakai Y dan T,
-- sr_stok memakai A dan T. Sesuai dengan yang disaring model.
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
-- QUERY 10 : sebaran akta per tahun                     [SUDAH DIJAWAB]
-- ---------------------------------------------------------------------
-- Hasil berhenti di 2024 dengan 54 akta. Tidak ada satu pun akta
-- bertahun 2025 atau 2026. Ini persoalan terpisah: migrasi sr_akta
-- tampaknya tertinggal, jadi meskipun join sudah diperbaiki, akta
-- 2025 dan 2026 tetap tidak akan muncul karena memang belum ada.
SELECT
    EXTRACT(YEAR FROM CAST(a.tgl_akta AS TIMESTAMP))::int AS tahun,
    COUNT(*) AS jumlah_akta
FROM public.sr_akta AS a
WHERE COALESCE(CAST(a.tgl_akta AS TEXT), '')
      ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
GROUP BY 1
ORDER BY 1;


-- ---------------------------------------------------------------------
-- QUERY 11 : kolom yang dipakai model, adakah yang hilang [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Model kini menulis nama kolom apa adanya, mengikuti bentuk berkas lama,
-- dan tidak lagi menebak-nebak sendiri. Konsekuensinya, kolom yang tidak
-- ada akan membuat query gagal. Query ini memeriksanya sekaligus.
--
-- Yang diharapkan: NOL BARIS. Setiap baris yang muncul berarti kolom itu
-- tidak ada pada hasil migrasi dan perlu saya tangani.
WITH diperlukan (nama_tabel, nama_kolom) AS (
    VALUES
        ('sr_akta', 'ppjb_id'), ('sr_akta', 'sertipikat_id'),
        ('sr_akta', 'no_notaris'), ('sr_akta', 'tgl_notaris'),
        ('sr_akta', 'notaris'), ('sr_akta', 'no_akta'),
        ('sr_akta', 'tgl_akta'), ('sr_akta', 'tgl_input'),
        ('sr_akta', 'ttd_akta'), ('sr_akta', 'tgl_entry'),
        ('sr_akta', 'user_entry'),

        ('sr_ppjb', 'ppjb_id'), ('sr_ppjb', 'no_ppjb'),
        ('sr_ppjb', 'tgl_ppjb'), ('sr_ppjb', 'harga_jual'),
        ('sr_ppjb', 'flag_aktif'), ('sr_ppjb', 'parent_id'),

        ('sr_pembeli_ppjb', 'ppjb_id'), ('sr_pembeli_ppjb', 'nasabah_id'),
        ('sr_pembeli_ppjb', 'flag_aktif'),

        ('sr_nasabah', 'nasabah_id'), ('sr_nasabah', 'nama'),
        ('sr_nasabah', 'telp_rmh'), ('sr_nasabah', 'fax_rmh'),
        ('sr_nasabah', 'telp_ktr'), ('sr_nasabah', 'fax_ktr'),
        ('sr_nasabah', 'no_hp'), ('sr_nasabah', 'alamat_rmh'),
        ('sr_nasabah', 'kota_rmh'), ('sr_nasabah', 'kode_pos_rmh'),

        ('sr_sertipikat', 'sertipikat_id'), ('sr_sertipikat', 'stok_id'),

        ('sr_pengambilan', 'sertipikat_id'),
        ('sr_pengambilan', 'tgl_ambil_akta'),
        ('sr_pengambilan', 'tgl_cetak_akta'),

        ('sr_stok', 'stok_id'), ('sr_stok', 'blok'), ('sr_stok', 'nomor'),
        ('sr_stok', 'luas_tanah'), ('sr_stok', 'luas_bangunan'),
        ('sr_stok', 'flag_aktif'),

        ('sr_lokasi', 'deskripsi'),
        ('sr_sektor', 'deskripsi'), ('sr_sektor', 'flag_aktif'),

        ('sr_angsuran', 'ppjb_id'), ('sr_angsuran', 'kd_transaksi'),
        ('sr_angsuran', 'tgl_kuitansi')
)
SELECT d.nama_tabel, d.nama_kolom AS kolom_yang_hilang
FROM diperlukan AS d
WHERE NOT EXISTS (
    SELECT 1
    FROM information_schema.columns AS c
    WHERE c.table_schema = 'public'
      AND c.table_name = d.nama_tabel
      AND LOWER(c.column_name) = d.nama_kolom
)
ORDER BY 1, 2;


-- ---------------------------------------------------------------------
-- QUERY 12 : tabel yang dipakai model, adakah yang hilang [JALANKAN INI]
-- ---------------------------------------------------------------------
-- Model menulis nama tabel apa adanya dengan awalan sr_ pada schema
-- public, sama seperti model lain yang sudah dimigrasi. Query ini
-- memastikan kesepuluh tabelnya memang ada dengan nama itu.
--
-- Yang diharapkan: kolom "ada" bernilai true untuk seluruh baris.
WITH dipakai (nama_tabel) AS (
    VALUES
        ('sr_akta'), ('sr_ppjb'), ('sr_pembeli_ppjb'), ('sr_nasabah'),
        ('sr_sertipikat'), ('sr_pengambilan'), ('sr_stok'),
        ('sr_lokasi'), ('sr_sektor'), ('sr_angsuran')
)
SELECT
    d.nama_tabel,
    EXISTS (
        SELECT 1
        FROM information_schema.tables AS t
        WHERE t.table_schema = 'public'
          AND t.table_name = d.nama_tabel
    ) AS ada
FROM dipakai AS d
ORDER BY 2, 1;
