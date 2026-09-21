/* ============================================================
 * CORONG DAFTAR UNDANGAN SURAT RUMAH, JENIS "UNDANGAN AJB"
 * UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT         : SBKS
 *   BLOK         : *  s/d  *
 *   CLUSTER      : kosong, semua sektor
 *   PERIODE      : 01-07-2023 s/d 21-09-2026
 *   JENIS REPORT : Undangan AJB
 *   tanpa centang "Belum Diundang"
 *
 * Yang sudah diketahui dari layar:
 *   desktop  4.621 baris
 *   web      1.843 baris
 *
 * ------------------------------------------------------------
 * KENAPA JENIS INI TIDAK BOLEH DISAMAKAN BEGITU SAJA
 *
 * Ada satu risiko di sini yang TIDAK ada pada jenis 1 dan 3.
 *
 * Pada sr_undangan_ppjb dan sr_undangan_st, kolom ppjb_id
 * termigrasi utuh sebagai teks, lengkap dengan awalan DBPSA-
 * atau DBPSS-. Pada sr_undangan_ajb kolom itu termigrasi
 * sebagai angka, jadi awalannya TERBUANG. Model menyusunnya
 * kembali dengan menebak awalan dari unitnya.
 *
 * Kalau penyusunan ulang itu meleset, barisnya akan gugur saat
 * disambungkan ke sr_ppjb, dan gejalanya akan terlihat persis
 * sama seperti data yang belum termigrasi. Karena itu tahap
 * penyambungan ke PPJB di sini sengaja DIPECAH DUA:
 *
 *   tahap 3  kuncinya ketemu di sr_ppjb, apa pun keadaannya
 *   tahap 4  ditambah syarat aktif dan bukan turunan
 *
 * Kalau barisnya mati di tahap 3, itu kesalahan penyusunan
 * kunci, yaitu kesalahan model, dan itu bagian saya.
 *
 * ------------------------------------------------------------
 * YANG SUDAH TERBACA DARI GAMBAR
 *
 * Baris terakhir di kedua sisi sama-sama DK/036 NOVI DIYAH
 * ASTUTI, jadi daftarnya bukan terpotong di ujung melainkan
 * menipis di sepanjang jalan.
 *
 * Yang hilang di web pada halaman terakhir adalah seluruh surat
 * bernomor 04/2026, sementara yang 03/2024 tetap ada. Satu
 * rumah hilang seluruhnya, DJ/016 atas nama ADRIANA DE ROSARIO,
 * termasuk suratnya yang 2024.
 *
 * Itu mengesankan dua sebab bekerja bersamaan, yang baru dan
 * yang lama. Tapi mengesankan bukan berarti terbukti, dan itu
 * gunanya corong ini.
 *
 * Pasangannya: sqlserver_undangan_ajb_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * BENTUK KUNCI pada sr_undangan_ajb.
 *
 * Dijalankan lebih dulu supaya jelas berapa banyak baris yang
 * awalannya memang terbuang dan harus disusun ulang.
 * ------------------------------------------------------------ */
SELECT
    CASE
        WHEN ppjb_id IS NULL THEN '(kosong)'
        WHEN BTRIM(CAST(ppjb_id AS TEXT)) ~ '^[0-9]+$'
            THEN 'angka telanjang, awalan disusun ulang'
        ELSE 'berawalan: '
             || REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', '')
    END          AS bentuk,
    COUNT(*)     AS baris
FROM public.sr_undangan_ajb
GROUP BY 1
ORDER BY 2 DESC;


/* ------------------------------------------------------------
 * QUERY 2
 * CORONG UTAMA.
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
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_semua AS (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) AS flag_aktif,
        ppjb.parent_id
    FROM public.sr_ppjb AS ppjb
),
t1 AS (
    SELECT
        surat.tgl_surat,
        CASE
            WHEN BTRIM(CAST(surat.ppjb_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(surat.ppjb_id AS TEXT))
            ELSE (SELECT awalan FROM awalan_unit)
                 || BTRIM(CAST(surat.ppjb_id AS TEXT))
        END AS kunci_ppjb
    FROM public.sr_undangan_ajb AS surat
),
t2 AS (
    SELECT * FROM t1
    WHERE tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
t3 AS (
    SELECT t2.*, ppjb_semua.kunci_stok, ppjb_semua.flag_aktif,
           ppjb_semua.parent_id
    FROM t2
    INNER JOIN ppjb_semua ON ppjb_semua.kunci_ppjb = t2.kunci_ppjb
),
t4 AS (
    SELECT * FROM t3
    WHERE flag_aktif = 'A' AND parent_id IS NULL
),
t5 AS (
    SELECT t4.*, stok.blok, stok.nomor
    FROM t4
    INNER JOIN stok_terpilih AS stok ON stok.kunci_stok = t4.kunci_stok
),
t6 AS (
    SELECT t5.* FROM t5
    WHERE EXISTS (
        SELECT 1
        FROM public.sr_pembeli_ppjb AS pembeli
        WHERE BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t5.kunci_ppjb
          AND UPPER(BTRIM(COALESCE(
                  CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    )
),
t7 AS (
    SELECT t6.* FROM t6
    WHERE EXISTS (
        SELECT 1
        FROM public.sr_pembeli_ppjb AS pembeli
        INNER JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
        WHERE BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t6.kunci_ppjb
          AND UPPER(BTRIM(COALESCE(
                  CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    )
),
t8 AS (
    SELECT * FROM t7
    WHERE blok IS NOT NULL AND nomor IS NOT NULL
),
t9 AS (
    SELECT t8.*, nasabah.nama
    FROM t8
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t8.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(
               CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
)
            SELECT 1 AS urut, 'Tahap 1  seluruh sr_undangan_ajb'           AS tahap, COUNT(*) AS baris FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + rentang tgl_surat',                        COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + kuncinya ketemu di sr_ppjb',               COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + PPJB aktif dan bukan turunan',             COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + stok SBKS yang aktif',                     COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + ada pembeli aktif',                        COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + pembelinya ketemu di sr_nasabah',          COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  + blok dan nomor terisi',                    COUNT(*) FROM t8
UNION ALL   SELECT 9,         'Tahap 9  digandakan pembeli = YANG TAMPIL',           COUNT(*) FROM t9
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 3
 * SEBARAN PER TAHUN, dengan sebab gugurnya dipisah.
 *
 * Kolom kunci_tidak_ketemu itu yang membedakan jenis ini dari
 * yang sudah diperiksa sebelumnya. Kalau kolom itu ada isinya,
 * penyusunan ulang awalan yang bermasalah.
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
ppjb_semua AS (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) AS flag_aktif,
        ppjb.parent_id
    FROM public.sr_ppjb AS ppjb
),
stok_sbks AS (
    SELECT
        BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok,
        stok.blok,
        stok.nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), 'T'))) AS flag_aktif
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
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
    FROM public.sr_undangan_ajb AS s
    WHERE s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
dinilai AS (
    SELECT
        surat.tgl_surat,
        ppjb_semua.kunci_ppjb IS NULL           AS kunci_tidak_ketemu,
        ppjb_semua.flag_aktif                   AS ppjb_aktif,
        ppjb_semua.parent_id                    AS ppjb_parent,
        stok.kunci_stok IS NOT NULL             AS stok_sbks,
        stok.flag_aktif                         AS stok_aktif,
        stok.blok,
        stok.nomor,
        EXISTS (
            SELECT 1
            FROM public.sr_pembeli_ppjb AS p
            INNER JOIN public.sr_nasabah AS n
                ON BTRIM(CAST(n.nasabah_id AS TEXT))
                 = BTRIM(CAST(p.nasabah_id AS TEXT))
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = surat.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_nasabah,
        EXISTS (
            SELECT 1
            FROM public.sr_pembeli_ppjb AS p
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = surat.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_pembeli
    FROM surat
    LEFT JOIN ppjb_semua ON ppjb_semua.kunci_ppjb = surat.kunci_ppjb
    LEFT JOIN stok_sbks AS stok ON stok.kunci_stok = ppjb_semua.kunci_stok
)
SELECT
    EXTRACT(YEAR FROM tgl_surat)::INT                  AS tahun,
    COUNT(*)                                           AS surat,
    COUNT(*) FILTER (WHERE kunci_tidak_ketemu)         AS kunci_tidak_ketemu,
    COUNT(*) FILTER (WHERE stok_sbks)                  AS milik_sbks,
    COUNT(*) FILTER (WHERE stok_sbks
                       AND ppjb_aktif = 'A'
                       AND ppjb_parent IS NULL)        AS ppjb_aktif,
    COUNT(*) FILTER (WHERE stok_sbks
                       AND ppjb_aktif = 'A'
                       AND ppjb_parent IS NULL
                       AND ada_pembeli
                       AND NOT ada_nasabah)            AS pembeli_tanpa_nasabah,
    COUNT(*) FILTER (WHERE stok_sbks
                       AND ppjb_aktif = 'A'
                       AND ppjb_parent IS NULL
                       AND stok_aktif = 'A'
                       AND ada_nasabah
                       AND blok IS NOT NULL
                       AND nomor IS NOT NULL)          AS lolos_semua
FROM dinilai
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 4
 * KEMUTAKHIRAN sr_undangan_ajb.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)       AS baris,
    MIN(tgl_surat) AS tgl_paling_awal,
    MAX(tgl_surat) AS tgl_paling_akhir
FROM public.sr_undangan_ajb;
