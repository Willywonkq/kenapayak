/* ============================================================
 * SEBERAPA LUAS sr_nasabah YANG HILANG, DAN KELUARGA MANA
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Berkas ini menggantikan QUERY 6 pada
 * nasabah_cek_kenapa_tidak_ketemu.sql, yang saya tulis dengan
 * ceroboh sehingga berjalan sangat lama. QUERY 6 itu memakai
 * EXISTS yang dijalankan sekali untuk SETIAP baris pembeli,
 * dan kedua sisi perbandingannya dibungkus BTRIM dan CAST
 * sehingga index tidak bisa dipakai. Untuk enam puluh ribu
 * baris pembeli, itu berarti puluhan ribu penyisiran tabel
 * nasabah. Pantas tidak selesai.
 *
 * Di sini perbandingannya dibalik menjadi satu penggabungan
 * saja, jadi kedua tabel cukup dibaca sekali. Hasilnya sama,
 * waktunya hitungan detik.
 *
 * QUERY 6 yang lama aman dibatalkan kapan saja. Dia hanya
 * membaca, tidak ada yang tertinggal setengah jadi.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * LUASNYA, dipilah menurut keluarga datanya.
 *
 * Pengganti QUERY 6 yang lama. Ditambah pemilahan DBPSA dan
 * DBPSS, karena pemeriksaan sebelumnya menunjukkan sr_nasabah
 * hanya berisi satu keluarga saja.
 * ------------------------------------------------------------ */
WITH nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
),
pembeli AS MATERIALIZED (
    SELECT
        BTRIM(CAST(nasabah_id AS TEXT)) AS kunci,
        COALESCE(SUBSTRING(BTRIM(CAST(nasabah_id AS TEXT))
                 FROM '^(DBPS[AS]-)'), '(bentuk lain)') AS keluarga
    FROM public.sr_pembeli_ppjb
    WHERE nasabah_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
)
SELECT
    pembeli.keluarga,
    COUNT(*)                                            AS pembeli_aktif,
    COUNT(nasabah_ada.kunci)                            AS orangnya_ketemu,
    COUNT(*) - COUNT(nasabah_ada.kunci)                 AS orangnya_tidak_ketemu,
    ROUND(100.0 * (COUNT(*) - COUNT(nasabah_ada.kunci))
          / NULLIF(COUNT(*), 0), 1)                     AS persen_hilang
FROM pembeli
LEFT JOIN nasabah_ada ON nasabah_ada.kunci = pembeli.kunci
GROUP BY ROLLUP (pembeli.keluarga)
ORDER BY pembeli.keluarga NULLS LAST;


/* ------------------------------------------------------------
 * QUERY 2
 * KELUARGA DARI PEMBELI SBKS YANG GUGUR ITU SENDIRI.
 *
 * Ini yang belum terjawab. Kalau yang gugur ternyata berawalan
 * DBPSS, berarti pembeli SBKS tersimpan di keluarga SERPONG dan
 * seluruh keluarga itu memang belum termigrasi. Kalau yang
 * gugur berawalan DBPSA, berarti salinan DBPSA-nya sendiri yang
 * masih bolong.
 *
 * Dua kemungkinan itu berbeda laporannya ke yang mengurus
 * migrasi, jadi perlu dipastikan.
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
nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
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
pembeli_sbks AS (
    SELECT
        BTRIM(CAST(pembeli.nasabah_id AS TEXT)) AS kunci,
        EXTRACT(YEAR FROM surat.tgl_surat)::INT AS tahun
    FROM surat
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = surat.kunci_ppjb
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = ppjb_aktif.kunci_stok
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = surat.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(
               CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
)
SELECT
    COALESCE(SUBSTRING(pembeli_sbks.kunci FROM '^(DBPS[AS]-)'),
             '(bentuk lain)')                   AS keluarga,
    pembeli_sbks.tahun,
    COUNT(*)                                    AS baris,
    COUNT(nasabah_ada.kunci)                    AS orangnya_ketemu,
    COUNT(*) - COUNT(nasabah_ada.kunci)         AS orangnya_tidak_ketemu
FROM pembeli_sbks
LEFT JOIN nasabah_ada ON nasabah_ada.kunci = pembeli_sbks.kunci
GROUP BY 1, 2
ORDER BY 1, 2;


/* ------------------------------------------------------------
 * QUERY 3
 * APAKAH TERPOTONG DI NOMOR TERTENTU.
 *
 * Kalau angka terbesar yang ADA di sr_nasabah jauh lebih kecil
 * daripada angka terbesar yang DIRUJUK pembeli, berarti
 * migrasinya berhenti di suatu nomor, bukan hilang acak. Itu
 * kabar baik, karena berarti tinggal dilanjutkan.
 *
 * Nilai yang bentuknya aneh, seperti awalan berganda DBPSA--
 * yang sudah terlihat pada pemeriksaan sebelumnya, sengaja
 * tidak diikutkan dalam hitungan angka karena memang bukan
 * nomor yang sah.
 * ------------------------------------------------------------ */
WITH nasabah_angka AS (
    SELECT
        SUBSTRING(BTRIM(CAST(nasabah_id AS TEXT)) FROM '^(DBPS[AS]-)') AS keluarga,
        (REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)),
                        '^DBPS[AS]-', ''))::NUMERIC AS angka
    FROM public.sr_nasabah
    WHERE BTRIM(CAST(nasabah_id AS TEXT)) ~ '^DBPS[AS]-[0-9]+$'
),
pembeli_angka AS (
    SELECT
        SUBSTRING(BTRIM(CAST(nasabah_id AS TEXT)) FROM '^(DBPS[AS]-)') AS keluarga,
        (REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)),
                        '^DBPS[AS]-', ''))::NUMERIC AS angka
    FROM public.sr_pembeli_ppjb
    WHERE BTRIM(CAST(nasabah_id AS TEXT)) ~ '^DBPS[AS]-[0-9]+$'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
)
            SELECT 'ADA di sr_nasabah'       AS sisi, keluarga,
                   COUNT(*) AS baris, MIN(angka) AS angka_terkecil,
                   MAX(angka) AS angka_terbesar
            FROM nasabah_angka GROUP BY 1, 2
UNION ALL   SELECT 'DIRUJUK sr_pembeli_ppjb', keluarga,
                   COUNT(*), MIN(angka), MAX(angka)
            FROM pembeli_angka GROUP BY 1, 2
ORDER BY keluarga, sisi;


/* ------------------------------------------------------------
 * QUERY 4
 * NILAI YANG BENTUKNYA ANEH.
 *
 * Awalan berganda DBPSA-- dan DBPSS-- tidak mungkin cocok
 * dengan apa pun. Perlu dilihat wujud aslinya, karena besar
 * kemungkinan itu berasal dari nomor bertanda minus di sumber
 * yang ikut disambung dengan awalannya.
 * ------------------------------------------------------------ */
SELECT
    BTRIM(CAST(nasabah_id AS TEXT)) AS nilai,
    COUNT(*)                        AS baris
FROM public.sr_pembeli_ppjb
WHERE BTRIM(CAST(nasabah_id AS TEXT)) !~ '^DBPS[AS]-[0-9]+$'
  AND nasabah_id IS NOT NULL
GROUP BY 1
ORDER BY 2 DESC, 1
LIMIT 20;
