-- =====================================================================
-- DIAGNOSTIK TAHAP 2 - MENENTUKAN AWALAN sr_sertipikat_idk PER BARIS
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- HASIL TAHAP 1
-- Tiga cara yang dulu berhasil untuk tabel lain ternyata tidak bisa
-- dipakai di sini:
--   * sr_sertipikat_idk tidak punya kd_perusahaan, jadi tidak bisa
--     seperti sr_biaya_ajb;
--   * kolom sertipikat_idk ternyata nomor sertipikat induk, misalnya
--     "B.1142/K.G.TMR.", bukan kunci berawalan, jadi tidak bisa seperti
--     sr_akta;
--   * uji tanggal tidak memisahkan apa pun: DBPSA- wajar 99,0% dan
--     DBPSS- wajar 99,3%, sama saja.
--
-- Lebih jauh lagi, dari 23.308 baris idk ada 19.465 baris yang angkanya
-- ada di kedua keluarga. Jadi awalannya memang tidak bisa ditentukan
-- untuk seluruh tabel sekaligus, harus ditentukan baris per baris.
--
-- GAGASAN TAHAP 2
-- Kedua tabel menyimpan data pemisahan yang sama, hanya dari dua sisi:
--     idk.su_pisah        <-> sertipikat.su_pisah
--     idk.tgl_su_pisah    <-> sertipikat.tgl_su_pisah
--     idk.ser_pisah       <-> sertipikat.no_sertipikat
--     idk.tgl_ser_pisah   <-> sertipikat.tgl_sertipikat
--     idk.luas_su_pisah   <-> sertipikat.luas_sup
--     idk.tgl_input       <-> sertipikat.tgl_input_ser
-- Pasangan yang benar isinya akan sama, pasangan yang salah tidak. Isi
-- datanya sendiri yang menunjukkan awalan mana yang betul.
--
-- Mohon jalankan QUERY 1 sampai QUERY 5 lalu hasilnya dikirimkan.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : seberapa sering isinya sama, per awalan
-- ---------------------------------------------------------------------
-- Untuk tiap awalan dihitung berapa pasangan yang isinya sama dan
-- berapa yang berbeda. Awalan yang benar seharusnya jauh lebih banyak
-- yang sama daripada yang berbeda.
--
-- Kolom "..._sama" hanya dihitung bila kedua sisi terisi, jadi baris
-- kosong tidak ikut menggelembungkan angkanya.
WITH idk AS (
    SELECT
        BTRIM(CAST(i.sertipikat_id AS TEXT))                   AS angka,
        UPPER(BTRIM(COALESCE(CAST(i.su_pisah  AS TEXT), '')))  AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')))  AS ser_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_su_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_su_pisah AS TEXT)) AS TIMESTAMP) END  AS tgl_su_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_ser_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_ser_pisah AS TEXT)) AS TIMESTAMP) END AS tgl_ser_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_input AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_input AS TEXT)) AS TIMESTAMP) END     AS tgl_input,
        i.luas_su_pisah                                        AS luas_su_pisah
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
ser AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')  AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        UPPER(BTRIM(COALESCE(CAST(s.su_pisah      AS TEXT), '')))            AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), '')))            AS no_sertipikat,
        CASE WHEN BTRIM(CAST(s.tgl_su_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_su_pisah AS TEXT)) AS TIMESTAMP) END   AS tgl_su_pisah,
        CASE WHEN BTRIM(CAST(s.tgl_sertipikat AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_sertipikat AS TEXT)) AS TIMESTAMP) END AS tgl_sertipikat,
        CASE WHEN BTRIM(CAST(s.tgl_input_ser AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_input_ser AS TEXT)) AS TIMESTAMP) END  AS tgl_input_ser,
        s.luas_sup                                                             AS luas_sup
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
)
SELECT
    ser.awalan                                     AS awalan,
    COUNT(*)                                       AS pasangan,

    COUNT(*) FILTER (WHERE idk.su_pisah <> '' AND ser.su_pisah <> ''
                       AND idk.su_pisah  = ser.su_pisah)        AS su_pisah_sama,
    COUNT(*) FILTER (WHERE idk.su_pisah <> '' AND ser.su_pisah <> ''
                       AND idk.su_pisah <> ser.su_pisah)        AS su_pisah_beda,

    COUNT(*) FILTER (WHERE idk.ser_pisah <> '' AND ser.no_sertipikat <> ''
                       AND idk.ser_pisah  = ser.no_sertipikat)  AS no_ser_sama,
    COUNT(*) FILTER (WHERE idk.ser_pisah <> '' AND ser.no_sertipikat <> ''
                       AND idk.ser_pisah <> ser.no_sertipikat)  AS no_ser_beda,

    COUNT(*) FILTER (WHERE idk.tgl_su_pisah  = ser.tgl_su_pisah)  AS tgl_su_sama,
    COUNT(*) FILTER (WHERE idk.tgl_su_pisah <> ser.tgl_su_pisah)  AS tgl_su_beda,

    COUNT(*) FILTER (WHERE idk.tgl_ser_pisah  = ser.tgl_sertipikat) AS tgl_ser_sama,
    COUNT(*) FILTER (WHERE idk.tgl_ser_pisah <> ser.tgl_sertipikat) AS tgl_ser_beda,

    COUNT(*) FILTER (WHERE idk.luas_su_pisah  = ser.luas_sup
                       AND idk.luas_su_pisah > 0)               AS luas_sama,
    COUNT(*) FILTER (WHERE idk.luas_su_pisah <> ser.luas_sup
                       AND idk.luas_su_pisah > 0)               AS luas_beda,

    COUNT(*) FILTER (WHERE idk.tgl_input = ser.tgl_input_ser)    AS tgl_input_sama_persis,
    COUNT(*) FILTER (WHERE CAST(idk.tgl_input AS DATE)
                         = CAST(ser.tgl_input_ser AS DATE))      AS tgl_input_sehari
FROM idk
INNER JOIN ser ON ser.angka = idk.angka
GROUP BY 1
ORDER BY 1;


-- ---------------------------------------------------------------------
-- QUERY 2 : baris per baris, awalan mana yang isinya cocok
-- ---------------------------------------------------------------------
-- Ini query penentunya. Satu baris idk disebut cocok dengan satu awalan
-- bila ada minimal satu kolom isinya yang sama persis.
--   hanya_satu_awalan   : bisa ditentukan dengan pasti, aman dipakai
--   dua_awalan_sekaligus: masih rancu, perlu dilihat lagi
--   tidak_ada_yang_cocok: isinya memang tidak bisa dibandingkan
-- Kalau "hanya_satu_awalan" sudah mencakup sebagian besar baris, cara
-- inilah yang akan saya pakai untuk memulihkan awalannya.
WITH idk AS (
    SELECT
        i.ctid                                                 AS baris,
        BTRIM(CAST(i.sertipikat_id AS TEXT))                   AS angka,
        UPPER(BTRIM(COALESCE(CAST(i.su_pisah  AS TEXT), '')))  AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')))  AS ser_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_su_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_su_pisah AS TEXT)) AS TIMESTAMP) END  AS tgl_su_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_ser_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_ser_pisah AS TEXT)) AS TIMESTAMP) END AS tgl_ser_pisah,
        CASE WHEN BTRIM(CAST(i.tgl_input AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(i.tgl_input AS TEXT)) AS TIMESTAMP) END     AS tgl_input,
        i.luas_su_pisah                                        AS luas_su_pisah
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
ser AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')  AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        UPPER(BTRIM(COALESCE(CAST(s.su_pisah      AS TEXT), '')))            AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), '')))            AS no_sertipikat,
        CASE WHEN BTRIM(CAST(s.tgl_su_pisah AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_su_pisah AS TEXT)) AS TIMESTAMP) END   AS tgl_su_pisah,
        CASE WHEN BTRIM(CAST(s.tgl_sertipikat AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_sertipikat AS TEXT)) AS TIMESTAMP) END AS tgl_sertipikat,
        CASE WHEN BTRIM(CAST(s.tgl_input_ser AS TEXT)) ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(BTRIM(CAST(s.tgl_input_ser AS TEXT)) AS TIMESTAMP) END  AS tgl_input_ser,
        s.luas_sup                                                             AS luas_sup
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
),
nilai AS (
    SELECT
        idk.baris    AS baris,
        ser.awalan   AS awalan,
        CASE WHEN (idk.su_pisah  <> '' AND idk.su_pisah  = ser.su_pisah)
                OR (idk.ser_pisah <> '' AND idk.ser_pisah = ser.no_sertipikat)
                OR (idk.tgl_su_pisah  = ser.tgl_su_pisah)
                OR (idk.tgl_ser_pisah = ser.tgl_sertipikat)
                OR (idk.tgl_input     = ser.tgl_input_ser)
                OR (idk.luas_su_pisah = ser.luas_sup AND idk.luas_su_pisah > 0)
             THEN 1 ELSE 0 END AS setuju
    FROM idk
    INNER JOIN ser ON ser.angka = idk.angka
),
ringkas AS (
    SELECT
        baris,
        MAX(CASE WHEN awalan = 'DBPSA-' AND setuju = 1 THEN 1 ELSE 0 END) AS cocok_dbpsa,
        MAX(CASE WHEN awalan = 'DBPSS-' AND setuju = 1 THEN 1 ELSE 0 END) AS cocok_dbpss
    FROM nilai
    GROUP BY baris
)
SELECT
    COUNT(*)                                                       AS baris_idk_bercalon,
    COUNT(*) FILTER (WHERE cocok_dbpsa = 1 AND cocok_dbpss = 0)     AS hanya_dbpsa,
    COUNT(*) FILTER (WHERE cocok_dbpsa = 0 AND cocok_dbpss = 1)     AS hanya_dbpss,
    COUNT(*) FILTER (WHERE cocok_dbpsa + cocok_dbpss = 1)           AS hanya_satu_awalan,
    COUNT(*) FILTER (WHERE cocok_dbpsa = 1 AND cocok_dbpss = 1)     AS dua_awalan_sekaligus,
    COUNT(*) FILTER (WHERE cocok_dbpsa = 0 AND cocok_dbpss = 0)     AS tidak_ada_yang_cocok
FROM ringkas;


-- ---------------------------------------------------------------------
-- QUERY 3 : contoh perbandingan berdampingan
-- ---------------------------------------------------------------------
-- Dua puluh contoh angka yang ada di kedua keluarga, ditampilkan
-- berdampingan supaya kelihatan dengan mata sendiri sisi mana yang
-- isinya benar-benar sama.
WITH idk AS (
    SELECT
        BTRIM(CAST(i.sertipikat_id AS TEXT))                  AS angka,
        UPPER(BTRIM(COALESCE(CAST(i.su_pisah  AS TEXT), ''))) AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) AS ser_pisah,
        BTRIM(CAST(i.tgl_input AS TEXT))                      AS tgl_input
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) <> ''
),
ser AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')  AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        UPPER(BTRIM(COALESCE(CAST(s.su_pisah      AS TEXT), '')))            AS su_pisah,
        UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), '')))            AS no_sertipikat,
        BTRIM(CAST(s.tgl_input_ser AS TEXT))                                 AS tgl_input_ser
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
),
bercabang AS (
    SELECT angka FROM ser GROUP BY angka
    HAVING COUNT(DISTINCT awalan) > 1
)
SELECT
    idk.angka           AS angka,
    idk.ser_pisah       AS idk_ser_pisah,
    idk.su_pisah        AS idk_su_pisah,
    idk.tgl_input       AS idk_tgl_input,
    ser.awalan          AS awalan,
    ser.no_sertipikat   AS ser_no_sertipikat,
    ser.su_pisah        AS ser_su_pisah,
    ser.tgl_input_ser   AS ser_tgl_input
FROM idk
INNER JOIN bercabang ON bercabang.angka = idk.angka
INNER JOIN ser       ON ser.angka       = idk.angka
ORDER BY CAST(idk.angka AS NUMERIC) DESC, ser.awalan
LIMIT 20;


-- ---------------------------------------------------------------------
-- QUERY 4 : isi kolom nama perusahaan di kedua tabel
-- ---------------------------------------------------------------------
-- Memastikan apakah sertipikat.atas_nama_pt memuat nama perusahaan
-- seperti idk.nama_pt, atau hanya penanda Y/T. Kalau isinya nama, kolom
-- ini bisa jadi bukti tambahan.
SELECT
    'sr_sertipikat.atas_nama_pt' AS asal,
    UPPER(BTRIM(COALESCE(CAST(atas_nama_pt AS TEXT), '(kosong)'))) AS isi,
    COUNT(*) AS jumlah
FROM public.sr_sertipikat
GROUP BY 2
ORDER BY 3 DESC
LIMIT 15;

SELECT
    'sr_sertipikat_idk.nama_pt' AS asal,
    UPPER(BTRIM(COALESCE(CAST(nama_pt AS TEXT), '(kosong)'))) AS isi,
    COUNT(*) AS jumlah
FROM public.sr_sertipikat_idk
GROUP BY 2
ORDER BY 3 DESC
LIMIT 15;


-- ---------------------------------------------------------------------
-- QUERY 5 : berapa banyak baris idk yang benar-benar terpakai laporan
-- ---------------------------------------------------------------------
-- Laporan hanya menampilkan sertipikat yang stoknya aktif. Query ini
-- menghitung, seandainya awalan sudah dipulihkan, berapa banyak baris
-- yang akan tampil, dipisah per unit. Dipakai untuk membandingkan
-- dengan hasil di layar nanti.
WITH idk AS (
    SELECT BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka
    FROM public.sr_sertipikat_idk AS i
    WHERE i.sertipikat_id IS NOT NULL
),
ser AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')  AS awalan,
        REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BTRIM(CAST(s.stok_id AS TEXT))                                       AS kunci_stok
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
)
SELECT
    ser.awalan                                                          AS awalan,
    UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '(kosong)'))) AS unit,
    COUNT(*)                                                            AS baris
FROM idk
INNER JOIN ser  ON ser.angka = idk.angka
INNER JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT)) = ser.kunci_stok
   AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
GROUP BY 1, 2
ORDER BY 1, 3 DESC;
