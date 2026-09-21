/* ============================================================
 * KENAPA PEMBELI TIDAK KETEMU DI sr_nasabah
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Corong daftar undangan sudah menunjukkan seluruh kehilangan
 * terjadi di satu tempat saja: pembeli ada, orangnya tidak
 * ketemu di sr_nasabah. 294 menjadi 165.
 *
 * Tetapi "tidak ketemu" masih punya DUA sebab yang berbeda, dan
 * keduanya kelihatan persis sama dari luar:
 *
 *   SEBAB A  barisnya memang belum termigrasi
 *            -> yang harus dibereskan migrasinya
 *
 *   SEBAB B  barisnya ada, tetapi kuncinya beda bentuk, misalnya
 *            sr_pembeli_ppjb menyimpan angka telanjang sedangkan
 *            sr_nasabah menyimpan angka berawalan DBPSA-
 *            -> yang harus dibereskan MODELNYA, seperti yang
 *               sudah terjadi pada ppjb_id dan sertipikat_id
 *
 * Berkas ini memisahkan keduanya. Saya tidak mau menyimpulkan
 * sebab A tanpa mencoret sebab B lebih dulu.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * BENTUK KUNCINYA di kedua tabel.
 *
 * Kalau salah satu numeric dan satunya varchar, itu pertanda
 * kuat awalan yang hilang, yaitu sebab B.
 * ------------------------------------------------------------ */
SELECT
    table_name  AS tabel,
    column_name AS kolom,
    data_type   AS tipe,
    character_maximum_length AS panjang
FROM information_schema.columns
WHERE table_schema = 'public'
  AND column_name = 'nasabah_id'
  AND table_name IN ('sr_nasabah', 'sr_pembeli_ppjb')
ORDER BY table_name;


/* ------------------------------------------------------------
 * QUERY 2
 * ISI KUNCINYA, berapa banyak yang berawalan dan berapa yang
 * angka telanjang, di kedua tabel.
 *
 * Kalau sr_nasabah semuanya berawalan sedangkan sr_pembeli_ppjb
 * semuanya angka telanjang, sebabnya B dan sudah pasti.
 * ------------------------------------------------------------ */
SELECT
    'sr_nasabah' AS tabel,
    CASE
        WHEN BTRIM(CAST(nasabah_id AS TEXT)) ~ '^[0-9]+$'
        THEN 'angka telanjang'
        ELSE 'berawalan: '
             || REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)), '[0-9]+$', '')
    END AS bentuk,
    COUNT(*) AS baris
FROM public.sr_nasabah
WHERE nasabah_id IS NOT NULL
GROUP BY 1, 2

UNION ALL

SELECT
    'sr_pembeli_ppjb',
    CASE
        WHEN BTRIM(CAST(nasabah_id AS TEXT)) ~ '^[0-9]+$'
        THEN 'angka telanjang'
        ELSE 'berawalan: '
             || REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)), '[0-9]+$', '')
    END,
    COUNT(*)
FROM public.sr_pembeli_ppjb
WHERE nasabah_id IS NOT NULL
GROUP BY 1, 2

ORDER BY 1, 2;


/* ------------------------------------------------------------
 * QUERY 3
 * PUTUSAN UNTUK 129 BARIS YANG HILANG ITU SENDIRI.
 *
 * Diambil persis dari corong tadi: surat jenis 1, rentang
 * tanggal yang sama, stok SBKS aktif, punya pembeli aktif,
 * tetapi pembelinya tidak ketemu dengan cara yang dipakai model.
 *
 * Lalu tiap pembeli itu dicoba dengan tiga cara pencocokan:
 *
 *   sama_persis   cara yang dipakai model sekarang
 *   angka_saja    kedua sisi dikupas awalannya lebih dulu
 *   pakai_awalan  nasabah_id pembeli diberi awalan unit
 *
 * Kalau angka_saja atau pakai_awalan ada isinya, berarti orangnya
 * ADA dan yang salah modelnya. Kalau ketiganya nol, orangnya
 * memang belum termigrasi.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1
    LIMIT 1
),
stok_terpilih AS (
    SELECT BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_aktif AS (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        s.tgl_surat,
        CASE
            WHEN BTRIM(CAST(s.ppjb_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(s.ppjb_id AS TEXT))
            ELSE (SELECT awalan FROM awalan_unit)
                 || BTRIM(CAST(s.ppjb_id AS TEXT))
        END AS kunci_ppjb
    FROM public.sr_undangan_ppjb AS s
    WHERE BTRIM(COALESCE(CAST(s.jenis_surat AS TEXT), '')) = '1'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
pembeli_gugur AS (
    SELECT DISTINCT
        BTRIM(CAST(pembeli.nasabah_id AS TEXT)) AS nasabah_id_pembeli,
        EXTRACT(YEAR FROM surat.tgl_surat)::INT AS tahun
    FROM surat
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = surat.kunci_ppjb
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = ppjb_aktif.kunci_stok
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = surat.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(
               CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE NOT EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
        WHERE BTRIM(CAST(n.nasabah_id AS TEXT))
            = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
    )
),
putusan AS (
    SELECT
        pembeli_gugur.tahun,
        EXISTS (
            SELECT 1 FROM public.sr_nasabah AS n
            WHERE BTRIM(CAST(n.nasabah_id AS TEXT))
                = pembeli_gugur.nasabah_id_pembeli
        ) AS sama_persis,
        EXISTS (
            SELECT 1 FROM public.sr_nasabah AS n
            WHERE REGEXP_REPLACE(
                      BTRIM(CAST(n.nasabah_id AS TEXT)), '^[^0-9]+', '')
                = REGEXP_REPLACE(
                      pembeli_gugur.nasabah_id_pembeli, '^[^0-9]+', '')
        ) AS angka_saja,
        EXISTS (
            SELECT 1 FROM public.sr_nasabah AS n
            CROSS JOIN awalan_unit
            WHERE BTRIM(CAST(n.nasabah_id AS TEXT))
                = awalan_unit.awalan || pembeli_gugur.nasabah_id_pembeli
        ) AS pakai_awalan
    FROM pembeli_gugur
)
SELECT
    tahun,
    COUNT(*)                                      AS pembeli_gugur,
    COUNT(*) FILTER (WHERE sama_persis)           AS sama_persis,
    COUNT(*) FILTER (WHERE angka_saja)            AS angka_saja,
    COUNT(*) FILTER (WHERE pakai_awalan)          AS pakai_awalan,
    COUNT(*) FILTER (WHERE NOT angka_saja
                       AND NOT pakai_awalan)      AS benar_benar_tidak_ada
FROM putusan
GROUP BY tahun
ORDER BY tahun;


/* ------------------------------------------------------------
 * QUERY 4
 * CONTOH NILAINYA, sepuluh dari masing-masing tabel.
 *
 * Ini untuk dilihat dengan mata, supaya tidak hanya percaya
 * pada hitungan.
 * ------------------------------------------------------------ */
SELECT 'sr_nasabah' AS tabel, BTRIM(CAST(nasabah_id AS TEXT)) AS contoh_nilai
FROM public.sr_nasabah
WHERE nasabah_id IS NOT NULL
ORDER BY 2 DESC
LIMIT 10;

SELECT 'sr_pembeli_ppjb' AS tabel, BTRIM(CAST(nasabah_id AS TEXT)) AS contoh_nilai
FROM public.sr_pembeli_ppjb
WHERE nasabah_id IS NOT NULL
ORDER BY 2 DESC
LIMIT 10;


/* ------------------------------------------------------------
 * QUERY 5
 * SAMPAI KAPAN sr_nasabah termigrasi.
 *
 * Nama kolom tanggalnya belum tentu ada, jadi dicoba beberapa
 * nama sekaligus lewat to_jsonb. Nama yang tidak ada akan
 * keluar kosong, bukan error.
 *
 * Kalau ada kolom tanggal yang berhenti di akhir 2023, itu
 * menjelaskan kenapa kehilangannya menumpuk di 2024 sampai 2026.
 * ------------------------------------------------------------ */
SELECT
    c.nama                                                  AS kolom_dicoba,
    COUNT(*) FILTER (WHERE to_jsonb(n) ->> c.nama IS NOT NULL) AS terisi,
    MIN(to_jsonb(n) ->> c.nama)                             AS paling_awal,
    MAX(to_jsonb(n) ->> c.nama)                             AS paling_akhir
FROM public.sr_nasabah AS n
CROSS JOIN (VALUES
    ('tgl_input'), ('tgl_update'), ('tgl_daftar'), ('tgl_entry'),
    ('created_at'), ('updated_at')
) AS c(nama)
GROUP BY c.nama
ORDER BY c.nama;


/* ------------------------------------------------------------
 * QUERY 6
 * SEBERAPA LUAS akibatnya, bukan hanya untuk laporan ini.
 *
 * Menghitung seluruh pembeli aktif di seluruh unit, berapa yang
 * orangnya ketemu dan berapa yang tidak. Angka ini yang dibawa
 * kalau mau melapor ke yang mengurus migrasi, karena laporan
 * lain yang menyambung ke nasabah ikut terkena.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                   AS pembeli_aktif,
    COUNT(*) FILTER (WHERE ketemu)             AS orangnya_ketemu,
    COUNT(*) FILTER (WHERE NOT ketemu)         AS orangnya_tidak_ketemu,
    ROUND(100.0 * COUNT(*) FILTER (WHERE NOT ketemu)
          / NULLIF(COUNT(*), 0), 1)            AS persen_hilang
FROM (
    SELECT EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
        WHERE BTRIM(CAST(n.nasabah_id AS TEXT))
            = BTRIM(CAST(p.nasabah_id AS TEXT))
    ) AS ketemu
    FROM public.sr_pembeli_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
) AS daftar;
