/* ============================================================
 * BATAS ATAS sr_nasabah, DAN SIAPA SAJA YANG TERKENA
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Pemeriksaan sebelumnya menemukan satu angka yang menjelaskan
 * banyak hal sekaligus:
 *
 *   nomor tertinggi yang ADA di sr_nasabah   DBPSA-45255
 *   nomor tertinggi yang DIRUJUK pembeli     DBPSA-49385
 *
 * Ada langit-langit. Nasabah bernomor di atas 45255 tidak ada
 * satu pun, padahal dirujuk sampai 49385. Itu menjelaskan
 * kenapa kehilangannya menanjak dari tahun ke tahun, sebab
 * pembeli baru bernomor lebih besar.
 *
 * Yang belum jelas: apakah langit-langit itu satu-satunya
 * sebab, atau masih ada lubang di bawahnya juga. Bedanya
 * penting. Kalau semata langit-langit, migrasinya tinggal
 * dilanjutkan dari titik itu. Kalau berlubang di mana-mana,
 * salinannya harus diulang.
 *
 * Berkas ini menjawab itu, lalu mendaftar unit mana saja yang
 * terkena dan seberapa parah, supaya laporannya bisa langsung
 * dibawa.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * LANGIT-LANGIT ATAU BERLUBANG.
 *
 * Pembeli aktif keluarga DBPSA yang orangnya tidak ketemu,
 * dipisah antara yang nomornya DI ATAS nomor tertinggi yang ada
 * di sr_nasabah, dan yang nomornya masih DI DALAM jangkauan
 * tetapi tetap tidak ketemu.
 *
 * Kalau di_dalam_batas nol atau kecil sekali, sebabnya semata
 * langit-langit.
 * ------------------------------------------------------------ */
WITH nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
),
batas AS (
    SELECT MAX((REGEXP_REPLACE(kunci, '^DBPSA-', ''))::NUMERIC) AS tertinggi
    FROM nasabah_ada
    WHERE kunci ~ '^DBPSA-[0-9]+$'
),
pembeli AS MATERIALIZED (
    SELECT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_pembeli_ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
      AND BTRIM(CAST(nasabah_id AS TEXT)) ~ '^DBPSA-[0-9]+$'
),
gugur AS (
    SELECT (REGEXP_REPLACE(pembeli.kunci, '^DBPSA-', ''))::NUMERIC AS angka
    FROM pembeli
    LEFT JOIN nasabah_ada ON nasabah_ada.kunci = pembeli.kunci
    WHERE nasabah_ada.kunci IS NULL
)
SELECT
    (SELECT tertinggi FROM batas)                    AS batas_atas,
    COUNT(*)                                         AS pembeli_gugur,
    COUNT(*) FILTER (
        WHERE angka > (SELECT tertinggi FROM batas))  AS di_atas_batas,
    COUNT(*) FILTER (
        WHERE angka <= (SELECT tertinggi FROM batas)) AS di_dalam_batas,
    COUNT(DISTINCT angka)                            AS orang_berbeda
FROM gugur;


/* ------------------------------------------------------------
 * QUERY 2
 * KALAU BERLUBANG, LUBANGNYA DI MANA.
 *
 * Nomor yang gugur dikelompokkan per lima ribu, supaya kelihatan
 * apakah menumpuk di ujung atas saja atau tersebar.
 * ------------------------------------------------------------ */
WITH nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
),
pembeli AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_pembeli_ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
      AND BTRIM(CAST(nasabah_id AS TEXT)) ~ '^DBPSA-[0-9]+$'
),
dinilai AS (
    SELECT
        (REGEXP_REPLACE(pembeli.kunci, '^DBPSA-', ''))::NUMERIC AS angka,
        (nasabah_ada.kunci IS NOT NULL) AS ketemu
    FROM pembeli
    LEFT JOIN nasabah_ada ON nasabah_ada.kunci = pembeli.kunci
)
SELECT
    (FLOOR(angka / 5000) * 5000)::BIGINT AS mulai_nomor,
    COUNT(*)                             AS orang_dirujuk,
    COUNT(*) FILTER (WHERE ketemu)       AS ketemu,
    COUNT(*) FILTER (WHERE NOT ketemu)   AS tidak_ketemu
FROM dinilai
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * NOMOR BERTANDA MINUS, apakah orangnya sebenarnya ada.
 *
 * Nilai seperti DBPSA--32543 itu awalan DBPSA- disambung dengan
 * nomor -32543. Kalau DBPSA-32543 ternyata ADA di sr_nasabah,
 * berarti tandanya saja yang terbalik dan 180 baris itu bisa
 * diselamatkan. Kalau tidak ada juga, ya memang data cacat.
 *
 * Saya TIDAK menyarankan model membalik tanda sendiri. Ini
 * hanya untuk dilaporkan, karena menebak tanda berarti menebak
 * orang.
 * ------------------------------------------------------------ */
WITH nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
),
minus AS (
    SELECT
        BTRIM(CAST(nasabah_id AS TEXT)) AS kunci_asli,
        SUBSTRING(BTRIM(CAST(nasabah_id AS TEXT)) FROM '^(DBPS[AS]-)')
            || REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)),
                              '^DBPS[AS]--', '') AS kunci_dibalik
    FROM public.sr_pembeli_ppjb
    WHERE BTRIM(CAST(nasabah_id AS TEXT)) ~ '^DBPS[AS]--[0-9]+$'
)
SELECT
    SUBSTRING(minus.kunci_asli FROM '^(DBPS[AS]--)') AS bentuk,
    COUNT(*)                                          AS baris,
    COUNT(nasabah_ada.kunci)                          AS ketemu_kalau_tanda_dibalik,
    COUNT(*) - COUNT(nasabah_ada.kunci)               AS tetap_tidak_ketemu
FROM minus
LEFT JOIN nasabah_ada ON nasabah_ada.kunci = minus.kunci_dibalik
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 4
 * UNIT MANA SAJA YANG TERKENA, dan seberapa parah.
 *
 * Inilah daftar yang dibawa kalau melapor. Unit dengan persen
 * 100 berarti seluruh laporan yang menampilkan nama pembeli di
 * unit itu akan kosong.
 * ------------------------------------------------------------ */
WITH nasabah_ada AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
),
pembeli AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb_id AS TEXT))    AS kunci_ppjb,
        BTRIM(CAST(nasabah_id AS TEXT)) AS kunci_nasabah
    FROM public.sr_pembeli_ppjb
    WHERE nasabah_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
),
ppjb AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb_id AS TEXT)) AS kunci_ppjb,
        BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_ppjb
),
stok AS MATERIALIZED (
    SELECT
        BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '(kosong)'))) AS unit
    FROM public.sr_stok
)
SELECT
    stok.unit,
    COUNT(*)                                   AS pembeli_aktif,
    COUNT(nasabah_ada.kunci)                   AS orangnya_ketemu,
    COUNT(*) - COUNT(nasabah_ada.kunci)        AS orangnya_tidak_ketemu,
    ROUND(100.0 * (COUNT(*) - COUNT(nasabah_ada.kunci))
          / NULLIF(COUNT(*), 0), 1)            AS persen_hilang
FROM pembeli
INNER JOIN ppjb ON ppjb.kunci_ppjb = pembeli.kunci_ppjb
INNER JOIN stok ON stok.kunci_stok = ppjb.kunci_stok
LEFT JOIN nasabah_ada ON nasabah_ada.kunci = pembeli.kunci_nasabah
GROUP BY stok.unit
ORDER BY 5 DESC, 1;
