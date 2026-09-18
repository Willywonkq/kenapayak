/* ============================================================
 * MENENTUKAN ASAL BARIS sr_imb DAN sr_pbb
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Latar belakang. Cek skema menunjukkan sertipikat_id pada kedua
 * tabel bertipe angka sehingga awalan DBPSA- atau DBPSS- terbuang,
 * dan 92,5 persen baris sr_imb serta 87,4 persen baris sr_pbb
 * angkanya dipakai kedua keluarga awalan sekaligus.
 *
 * Model menyusun ulang awalan dari unit yang diminta di layar.
 * Cara itu TIDAK PERNAH kehilangan baris, tetapi bisa menarik
 * baris milik keluarga lain kalau angkanya kebetulan sama.
 *
 * Besar kecilnya risiko itu ditentukan satu hal: apakah kedua
 * tabel memuat baris dari dua database sumber, atau hanya satu.
 *
 *   Kalau hanya SRIS_PUSAT  -> laporan unit berawalan DBPSA-
 *                              aman sepenuhnya, karena tidak ada
 *                              baris DBPSS- yang bisa nyasar;
 *                              sebaliknya unit berawalan DBPSS-
 *                              tidak boleh dipercaya sama sekali.
 *   Kalau keduanya          -> risikonya merata untuk semua unit
 *                              dan perlu diukur per unit.
 *
 * Jalankan berkas ini, lalu sqlserver_imb_dan_pbb_cek_asal.sql.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Kolom yang dipakai model tetapi belum sempat diperiksa.
 * Semua barisnya harus berbunyi "ada".
 * ------------------------------------------------------------ */
WITH perlu(tabel, kolom) AS (
    VALUES
        ('sr_stok','jalan'), ('sr_stok','luas_tanah'), ('sr_stok','luas_bangunan'),
        ('sr_ppjb','no_ppjb'), ('sr_ppjb','tgl_ppjb'), ('sr_ppjb','user_entry'),
        ('sr_ppjb','parent_id'), ('sr_ppjb','flag_aktif'),
        ('sr_nasabah','telp_rmh'), ('sr_nasabah','no_hp'), ('sr_nasabah','telp_ktr'),
        ('sr_nasabah','alamat_ktp'), ('sr_nasabah','kota_ktp'),
        ('sr_nasabah','kode_pos_ktp'), ('sr_nasabah','no_identitas'),
        ('sr_nasabah','npwp'), ('sr_nasabah','nama'),
        ('sr_pembeli_ppjb','flag_aktif'), ('sr_sektor','flag_aktif'),
        ('sr_sektor','deskripsi'), ('sr_lokasi','deskripsi')
)
SELECT
    perlu.tabel, perlu.kolom,
    CASE WHEN k.column_name IS NULL THEN 'TIDAK ADA, beri tahu saya' ELSE 'ada' END
        AS keadaan
FROM perlu
LEFT JOIN information_schema.columns AS k
    ON k.table_schema = 'public'
   AND k.table_name = perlu.tabel
   AND LOWER(k.column_name) = perlu.kolom
ORDER BY keadaan DESC, perlu.tabel, perlu.kolom;


/* ------------------------------------------------------------
 * QUERY 2
 * INI YANG MENENTUKAN.
 *
 * Kalau kedua database sumber digabung, kunci utamanya bertabrakan
 * karena keduanya menomori dari satu. Jadi imb_id dan pbb_id akan
 * punya nilai kembar, kecuali migrasinya menomori ulang.
 *
 * kembar = 0  ->  isinya hanya dari SATU database sumber
 * kembar > 0  ->  isinya dari DUA database sumber
 * ------------------------------------------------------------ */
SELECT 'sr_imb' AS tabel, 'imb_id' AS kunci,
       COUNT(*) AS baris,
       COUNT(DISTINCT imb_id) AS nilai_berbeda,
       COUNT(*) - COUNT(DISTINCT imb_id) AS kembar
FROM public.sr_imb
WHERE imb_id IS NOT NULL
UNION ALL
SELECT 'sr_pbb', 'pbb_id',
       COUNT(*), COUNT(DISTINCT pbb_id),
       COUNT(*) - COUNT(DISTINCT pbb_id)
FROM public.sr_pbb
WHERE pbb_id IS NOT NULL
UNION ALL
/* Pembanding: tabel yang kuncinya masih berupa teks berawalan. */
SELECT 'sr_sertipikat', 'sertipikat_id',
       COUNT(*), COUNT(DISTINCT sertipikat_id),
       COUNT(*) - COUNT(DISTINCT sertipikat_id)
FROM public.sr_sertipikat
WHERE sertipikat_id IS NOT NULL
UNION ALL
SELECT 'sr_stok', 'stok_id',
       COUNT(*), COUNT(DISTINCT stok_id),
       COUNT(*) - COUNT(DISTINCT stok_id)
FROM public.sr_stok
WHERE stok_id IS NOT NULL;


/* ------------------------------------------------------------
 * QUERY 3
 * Berapa baris yang akan tampil untuk tiap unit, dan berapa
 * di antaranya yang angkanya rancu.
 *
 * Inilah gambaran ketelitian laporan per unit, sebelum dibuka
 * satu per satu. Kolom persen_rancu adalah bagian yang belum
 * tentu benar; sisanya pasti benar.
 *
 * Perhatikan dua keluarga awalan dibandingkan berdampingan,
 * supaya kelihatan apakah baris sr_imb memang tersebar di
 * keduanya atau menumpuk di salah satu.
 * ------------------------------------------------------------ */
WITH peta_unit AS (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) AS kode_unit,
        REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
    GROUP BY 1, 2
),
angka_sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        COUNT(DISTINCT REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''))
            AS banyak_keluarga
    FROM public.sr_sertipikat
    WHERE sertipikat_id IS NOT NULL
    GROUP BY 1
),
stok_unit AS (
    SELECT
        BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) AS kode_unit
    FROM public.sr_stok
),
tersambung AS (
    SELECT
        peta_unit.kode_unit,
        peta_unit.awalan,
        CASE WHEN COALESCE(angka_sertipikat.banyak_keluarga, 1) = 1
             THEN 'pasti' ELSE 'rancu' END AS golongan
    FROM public.sr_imb AS imb
    CROSS JOIN peta_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
         = peta_unit.awalan || BTRIM(CAST(imb.sertipikat_id AS TEXT))
    INNER JOIN stok_unit
        ON stok_unit.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
       AND stok_unit.kode_unit = peta_unit.kode_unit
    LEFT JOIN angka_sertipikat
        ON angka_sertipikat.angka
         = REGEXP_REPLACE(BTRIM(CAST(imb.sertipikat_id AS TEXT)), '^[^0-9]+', '')
    WHERE BTRIM(CAST(imb.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
)
SELECT
    kode_unit, awalan,
    COUNT(*) AS baris_tampil,
    SUM(CASE WHEN golongan = 'pasti' THEN 1 ELSE 0 END) AS pasti,
    SUM(CASE WHEN golongan = 'rancu' THEN 1 ELSE 0 END) AS rancu,
    ROUND(100.0 * SUM(CASE WHEN golongan = 'rancu' THEN 1 ELSE 0 END)
          / NULLIF(COUNT(*), 0), 1) AS persen_rancu
FROM tersambung
GROUP BY kode_unit, awalan
ORDER BY baris_tampil DESC;
