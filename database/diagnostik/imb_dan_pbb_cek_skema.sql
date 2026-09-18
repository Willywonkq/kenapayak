/* ============================================================
 * CEK SKEMA UNTUK MIGRASI DAFTAR IMB DAN DAFTAR PBB
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Dijalankan pada PostgreSQL hasil migrasi.
 *
 * Tujuannya memastikan tiga hal sebelum modelnya dipakai:
 *   1. tabel sr_imb dan sr_pbb memang ada, beserta nama kolomnya
 *   2. kolom kunci sertipikat_id bertipe teks atau angka, karena
 *      itu menentukan apakah awalan DBPSA- dan DBPSS- masih utuh
 *      atau harus disusun ulang
 *   3. nama kolom kode pada tabel pendukung, yang ternyata
 *      berbeda-beda antar tabel
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Apakah tabelnya ada, dan berapa isinya.
 * ------------------------------------------------------------ */
SELECT
    daftar.nama AS tabel,
    CASE WHEN c.oid IS NULL THEN 'TIDAK ADA' ELSE 'ada' END AS keberadaan,
    COALESCE(isi.jumlah, 0) AS perkiraan_baris
FROM (VALUES ('sr_imb'), ('sr_pbb'), ('sr_sertipikat'), ('sr_stok'),
             ('sr_ppjb'), ('sr_pembeli_ppjb'), ('sr_nasabah'),
             ('sr_lokasi'), ('sr_sektor')) AS daftar(nama)
LEFT JOIN pg_class AS c
    ON c.relname = daftar.nama
   AND c.relnamespace = 'public'::regnamespace
   AND c.relkind = 'r'
LEFT JOIN LATERAL (
    SELECT (xpath('/row/n/text()',
        query_to_xml(format('SELECT COUNT(*) AS n FROM public.%I', daftar.nama),
                     false, true, '')))[1]::text::bigint AS jumlah
) AS isi ON c.oid IS NOT NULL
ORDER BY daftar.nama;


/* ------------------------------------------------------------
 * QUERY 2
 * Kolom sr_imb dan sr_pbb beserta tipenya.
 *
 * YANG PALING PENTING: baris sertipikat_id. Kalau tipenya numeric,
 * awalan DBPSA- atau DBPSS- terbuang saat migrasi dan harus
 * disusun ulang, persis seperti pada sr_jaminan.
 * ------------------------------------------------------------ */
SELECT
    table_name AS tabel,
    ordinal_position AS urut,
    column_name AS kolom,
    data_type AS tipe,
    COALESCE(character_maximum_length::text, '') AS panjang
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('sr_imb', 'sr_pbb')
ORDER BY table_name, ordinal_position;


/* ------------------------------------------------------------
 * QUERY 3
 * Nama kolom kode pada tabel pendukung.
 *
 * Meniru cara model memilih nama kolom, yaitu mengambil yang
 * pertama ditemukan dari daftar calon. Sudah diketahui sr_lokasi
 * memakai kd_lv2 dan sr_sektor memakai kd_proyek, berbeda dari
 * sr_stok; query ini memastikannya sekali lagi sekaligus
 * memeriksa kolom pendukung lain yang dipakai kedua laporan.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT table_name, LOWER(column_name) AS column_name
    FROM information_schema.columns
    WHERE table_schema = 'public'
)
SELECT 'sr_stok / perusahaan' AS keperluan,
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_perusahaan','kd_unit','kd_pt']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1), 'TIDAK ADA') AS kolom_terpakai
UNION ALL
SELECT 'sr_stok / sektor',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1), 'TIDAK ADA')
UNION ALL
SELECT 'sr_stok / lokasi',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1), 'TIDAK ADA')
UNION ALL
SELECT 'sr_lokasi / kode',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster','kd_sektor']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_lokasi')
                  LIMIT 1), 'TIDAK ADA')
UNION ALL
SELECT 'sr_sektor / kode',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_sektor')
                  LIMIT 1), 'TIDAK ADA')
UNION ALL
SELECT 'sr_sektor punya kd_perusahaan?',
       CASE WHEN EXISTS (SELECT 1 FROM kolom WHERE table_name='sr_sektor' AND column_name='kd_perusahaan')
            THEN 'ada' ELSE 'TIDAK ADA' END
UNION ALL
SELECT 'sr_sertipikat punya blok dan nomor?',
       CASE WHEN EXISTS (SELECT 1 FROM kolom WHERE table_name='sr_sertipikat' AND column_name='blok')
             AND EXISTS (SELECT 1 FROM kolom WHERE table_name='sr_sertipikat' AND column_name='nomor')
            THEN 'ada, join blok/nomor bisa dipertahankan'
            ELSE 'TIDAK ADA, join blok/nomor harus dilepas' END
UNION ALL
SELECT 'sr_imb punya kd_perusahaan?',
       CASE WHEN EXISTS (SELECT 1 FROM kolom WHERE table_name='sr_imb' AND column_name='kd_perusahaan')
            THEN 'ada' ELSE 'TIDAK ADA' END
UNION ALL
SELECT 'sr_pbb punya kd_perusahaan?',
       CASE WHEN EXISTS (SELECT 1 FROM kolom WHERE table_name='sr_pbb' AND column_name='kd_perusahaan')
            THEN 'ada' ELSE 'TIDAK ADA' END;


/* ------------------------------------------------------------
 * QUERY 4
 * Bentuk nilai sertipikat_id pada sr_imb dan sr_pbb.
 *
 * Kalau seluruhnya "angka polos", awalannya memang terbuang dan
 * model harus menyusunnya ulang dari unit yang diminta.
 * Kalau "berawalan", nilainya masih utuh dan dipakai apa adanya.
 * ------------------------------------------------------------ */
SELECT 'sr_imb' AS tabel,
       CASE WHEN BTRIM(CAST(sertipikat_id AS TEXT)) ~ '^[0-9]+$'
            THEN 'angka polos, awalan terbuang'
            ELSE 'berawalan, masih utuh' END AS bentuk,
       COUNT(*) AS baris,
       MIN(BTRIM(CAST(sertipikat_id AS TEXT))) AS contoh_terkecil,
       MAX(BTRIM(CAST(sertipikat_id AS TEXT))) AS contoh_terbesar
FROM public.sr_imb
WHERE sertipikat_id IS NOT NULL
GROUP BY 2
UNION ALL
SELECT 'sr_pbb',
       CASE WHEN BTRIM(CAST(sertipikat_id AS TEXT)) ~ '^[0-9]+$'
            THEN 'angka polos, awalan terbuang'
            ELSE 'berawalan, masih utuh' END,
       COUNT(*),
       MIN(BTRIM(CAST(sertipikat_id AS TEXT))),
       MAX(BTRIM(CAST(sertipikat_id AS TEXT)))
FROM public.sr_pbb
WHERE sertipikat_id IS NOT NULL
GROUP BY 2
ORDER BY 1, 2;


/* ------------------------------------------------------------
 * QUERY 5
 * Peta unit ke awalan, dibaca dari sr_stok.
 *
 * Model akan memakai peta ini untuk menyusun ulang awalan
 * berdasarkan UNIT YANG DIMINTA di layar, bukan berdasarkan
 * suara terbanyak seluruh tabel. Caranya lebih tepat, karena
 * laporan ini memang selalu untuk satu unit saja.
 *
 * Kolom jumlah_awalan harus 1 untuk setiap unit. Kalau ada yang
 * lebih dari 1, beri tahu saya sebelum modelnya dipakai.
 * ------------------------------------------------------------ */
SELECT
    UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) AS kode_unit,
    COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', ''))
        AS jumlah_awalan,
    STRING_AGG(DISTINCT
        REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', ''),
        ', ') AS awalan,
    COUNT(*) AS baris_stok
FROM public.sr_stok
WHERE stok_id IS NOT NULL
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 6
 * Seberapa besar risiko angka yang rancu.
 *
 * Angka yang dipakai dua keluarga awalan sekaligus tidak bisa
 * dipastikan milik unit mana. Query ini mengukur berapa banyak
 * baris sr_imb dan sr_pbb yang angkanya seperti itu, supaya
 * ketelitian laporannya diketahui sejak awal, bukan setelah
 * dibandingkan dengan desktop.
 * ------------------------------------------------------------ */
WITH angka_sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''))
            AS banyak_keluarga
    FROM public.sr_sertipikat
    WHERE sertipikat_id IS NOT NULL
    GROUP BY 1
)
SELECT 'sr_imb' AS tabel,
       CASE WHEN COALESCE(a.banyak_keluarga, 1) = 1 THEN 'PASTI' ELSE 'RANCU' END AS golongan,
       COUNT(*) AS baris
FROM public.sr_imb AS t
LEFT JOIN angka_sertipikat AS a
    ON a.angka = REGEXP_REPLACE(BTRIM(CAST(t.sertipikat_id AS TEXT)), '^[^0-9]+', '')
WHERE t.sertipikat_id IS NOT NULL
GROUP BY 2
UNION ALL
SELECT 'sr_pbb',
       CASE WHEN COALESCE(a.banyak_keluarga, 1) = 1 THEN 'PASTI' ELSE 'RANCU' END,
       COUNT(*)
FROM public.sr_pbb AS t
LEFT JOIN angka_sertipikat AS a
    ON a.angka = REGEXP_REPLACE(BTRIM(CAST(t.sertipikat_id AS TEXT)), '^[^0-9]+', '')
WHERE t.sertipikat_id IS NOT NULL
GROUP BY 2
ORDER BY 1, 2;
