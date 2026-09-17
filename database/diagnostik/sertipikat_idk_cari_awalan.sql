-- =====================================================================
-- DIAGNOSTIK - MENCARI AWALAN sr_sertipikat_idk.sertipikat_id
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- LATAR BELAKANG
-- Hasil diagnosis sebelumnya menunjukkan:
--     sr_sertipikat.sertipikat_id      varchar   -> "DBPSA-12345"
--     sr_sertipikat_idk.sertipikat_id  numeric   -> 12345
-- sehingga sambungan antara keduanya menghasilkan 0 baris, dan kedua
-- laporan (Daftar Sertipikat Pecahan dan Daftar Pengajuan Balik Nama)
-- pasti kosong.
--
-- Awalannya tidak boleh ditebak. "DBPSA-1" dan "DBPSS-1" dua-duanya ada,
-- jadi kalau awalan dibuang begitu saja barisnya bisa berlipat dan
-- datanya bisa nyasar ke unit yang salah.
--
-- Lima query di bawah dipakai untuk menemukan dari mana awalan itu bisa
-- dikembalikan, persis seperti yang dulu dilakukan untuk sr_akta,
-- sr_biaya_ajb, dan sr_peralihan.
--
-- Mohon jalankan QUERY 1 sampai QUERY 6 lalu kirimkan hasilnya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : seluruh kolom sr_sertipikat_idk
-- ---------------------------------------------------------------------
-- Yang dicari: kolom bertipe teks yang mungkin masih menyimpan awalan
-- (misalnya sertipikat_idk sendiri) atau kolom penanda unit (misalnya
-- kd_perusahaan). Salah satunya nanti dipakai untuk mengembalikan awalan.
SELECT
    ordinal_position  AS urutan,
    column_name       AS nama_kolom,
    data_type         AS tipe,
    character_maximum_length AS panjang,
    numeric_precision AS presisi,
    numeric_scale     AS skala
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name   = 'sr_sertipikat_idk'
ORDER BY ordinal_position;


-- ---------------------------------------------------------------------
-- QUERY 2 : contoh isi barisnya
-- ---------------------------------------------------------------------
-- Yang dilihat: apakah sertipikat_idk (kunci tabel ini sendiri) masih
-- memakai awalan seperti "DBPSA-...". Kalau iya, awalannya bisa diambil
-- dari sana, persis cara sr_akta mengambil awalan dari ppjb_id.
SELECT
    BTRIM(CAST(idk.sertipikat_idk AS TEXT)) AS sertipikat_idk,
    BTRIM(CAST(idk.sertipikat_id  AS TEXT)) AS sertipikat_id,
    BTRIM(CAST(idk.nama_pt        AS TEXT)) AS nama_pt,
    BTRIM(CAST(idk.su_induk       AS TEXT)) AS su_induk,
    BTRIM(CAST(idk.tgl_ser_idk    AS TEXT)) AS tgl_ser_idk
FROM public.sr_sertipikat_idk AS idk
ORDER BY idk.sertipikat_id
LIMIT 20;


-- ---------------------------------------------------------------------
-- QUERY 3 : daftar awalan yang dipakai sr_sertipikat
-- ---------------------------------------------------------------------
-- Ini daftar calon awalannya. Kolom "jumlah_baris" menunjukkan seberapa
-- besar tiap kelompok.
SELECT
    REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
    COUNT(*)                                                           AS jumlah_baris,
    MIN(BTRIM(CAST(s.sertipikat_id AS TEXT)))                          AS contoh_terkecil,
    MAX(BTRIM(CAST(s.sertipikat_id AS TEXT)))                          AS contoh_terbesar
FROM public.sr_sertipikat AS s
WHERE s.sertipikat_id IS NOT NULL
GROUP BY 1
ORDER BY 2 DESC;


-- ---------------------------------------------------------------------
-- QUERY 4 : tiap awalan mengenai berapa baris sr_sertipikat_idk
-- ---------------------------------------------------------------------
-- Tabel idk adalah tabel rincian, jadi awalan yang benar seharusnya
-- mengenai hampir seluruh 23.308 barisnya. Awalan yang salah hanya
-- mengenai sebagian kecil.
--
-- Kolom "unit_terjangkau" menghitung ada berapa unit (kd_perusahaan)
-- yang terjangkau lewat awalan itu. Awalan yang benar biasanya jatuh ke
-- satu unit saja.
WITH idk AS (
    SELECT BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BTRIM(CAST(s.stok_id AS TEXT))                                      AS kunci_stok
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
)
SELECT
    sertipikat.awalan                                       AS awalan,
    COUNT(*)                                                AS baris_idk_ketemu,
    COUNT(DISTINCT idk.angka)                               AS angka_unik_ketemu,
    COUNT(DISTINCT UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')))) AS unit_terjangkau
FROM idk
INNER JOIN sertipikat ON sertipikat.angka = idk.angka
LEFT JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT)) = sertipikat.kunci_stok
GROUP BY 1
ORDER BY 2 DESC;


-- ---------------------------------------------------------------------
-- QUERY 5 : seberapa rancu kalau awalannya diabaikan
-- ---------------------------------------------------------------------
-- Untuk tiap baris sr_sertipikat_idk dihitung ada berapa baris
-- sr_sertipikat yang angkanya sama.
--   tidak_ketemu  : angkanya tidak ada sama sekali di sr_sertipikat
--   tepat_satu    : aman, hanya satu kemungkinan
--   lebih_dari_satu : rancu, tidak boleh ditebak
-- Kalau "tepat_satu" sudah mencakup hampir seluruh baris, cara paling
-- aman adalah memakai yang tidak rancu saja.
WITH idk AS (
    SELECT BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        COUNT(*) AS jumlah
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
    GROUP BY 1
)
SELECT
    COUNT(*)                                                       AS baris_idk,
    COUNT(*) FILTER (WHERE sertipikat.angka IS NULL)               AS tidak_ketemu,
    COUNT(*) FILTER (WHERE sertipikat.jumlah = 1)                  AS tepat_satu,
    COUNT(*) FILTER (WHERE sertipikat.jumlah > 1)                  AS lebih_dari_satu
FROM idk
LEFT JOIN sertipikat ON sertipikat.angka = idk.angka;


-- ---------------------------------------------------------------------
-- QUERY 6 : uji kewajaran tanggal per awalan
-- ---------------------------------------------------------------------
-- Cara ini yang dulu berhasil menentukan awalan sr_peralihan.
-- sertipikat_idk menyimpan data sertipikat induk, jadi tanggalnya
-- semestinya tidak jauh melompati tanggal input sertipikat pecahannya.
--   lebih_awal : tgl_ser_idk <= tgl_input_ser sertipikat (wajar)
--   lebih_akhir: tgl_ser_idk >  tgl_input_ser sertipikat
--   tak_terbaca: salah satu tanggalnya kosong atau bukan tanggal
-- Awalan yang benar adalah yang pola tanggalnya paling masuk akal, bukan
-- yang acak setengah-setengah.
WITH idk AS (
    SELECT
        BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
        CASE
            WHEN BTRIM(CAST(i.tgl_ser_idk AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(BTRIM(CAST(i.tgl_ser_idk AS TEXT)) AS TIMESTAMP)
        END AS tgl_idk
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')  AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        CASE
            WHEN BTRIM(CAST(COALESCE(s.tgl_input_ser, s.tgl_input_gabung) AS TEXT))
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(BTRIM(CAST(
                     COALESCE(s.tgl_input_ser, s.tgl_input_gabung) AS TEXT
                 )) AS TIMESTAMP)
        END AS tgl_sertipikat
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
)
SELECT
    sertipikat.awalan AS awalan,
    COUNT(*)          AS baris_dipasangkan,
    COUNT(*) FILTER (
        WHERE idk.tgl_idk IS NOT NULL
          AND sertipikat.tgl_sertipikat IS NOT NULL
          AND idk.tgl_idk <= sertipikat.tgl_sertipikat
    ) AS lebih_awal,
    COUNT(*) FILTER (
        WHERE idk.tgl_idk IS NOT NULL
          AND sertipikat.tgl_sertipikat IS NOT NULL
          AND idk.tgl_idk > sertipikat.tgl_sertipikat
    ) AS lebih_akhir,
    COUNT(*) FILTER (
        WHERE idk.tgl_idk IS NULL OR sertipikat.tgl_sertipikat IS NULL
    ) AS tak_terbaca
FROM idk
INNER JOIN sertipikat ON sertipikat.angka = idk.angka
GROUP BY 1
ORDER BY 2 DESC;
