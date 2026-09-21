/* ============================================================
 * CORONG DAFTAR UNDANGAN SURAT RUMAH, JENIS "UNDANGAN PPJB"
 * UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Tidak ada CREATE INDEX.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT         : SBKS
 *   BLOK         : *  s/d  *
 *   PERIODE      : 01-07-2023 s/d 21-09-2026
 *   JENIS REPORT : Undangan PPJB (PPSRS)
 *   SEKTOR       : Semua
 *   tanpa centang "Belum Diundang"
 *
 * Yang sudah diketahui dari layar:
 *   desktop  294 baris, berhenti di SRIMAYA COMMERCIAL
 *   web     ~165 baris, berhenti di SCARLET COMMERCIAL
 *   dua sektor hilang seluruhnya: SOULTAN ISLAND dan SRIMAYA
 *   COMMERCIAL, dan sisanya kurang tersebar di sektor-sektor
 *   yang lebih awal.
 *
 * Yang sudah dipastikan lebih dulu, supaya tidak ikut ditebak:
 *
 *   Saringan blok TIDAK mungkin jadi sebabnya. Kedua batasnya
 *   bintang, dan pada keadaan itu syaratnya lolos seluruhnya.
 *
 *   Urutan keluaran juga TIDAK mungkin jadi sebabnya. Urutan
 *   hanya menyusun, tidak membuang. Kalau sektor hilang, yang
 *   membuangnya pasti salah satu sambungan, bukan ORDER BY.
 *
 * Jadi yang tersisa hanya rantai sambungannya, dan corong ini
 * memisahkannya satu per satu supaya kelihatan baris mati di
 * tahap mana.
 *
 * Pasangannya: sqlserver_undangan_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG UTAMA.
 *
 * Tahap 6 dan tahap 7 sengaja dipisah. Tahap 6 hanya menuntut
 * PPJB-nya punya pembeli aktif, tahap 7 menuntut pembeli itu
 * ketemu orangnya di sr_nasabah. Kalau angkanya jatuh di antara
 * keduanya, sebabnya bukan model melainkan sr_nasabah yang belum
 * termigrasi lengkap, persis seperti yang sudah ditemukan pada
 * daftar IMB.
 *
 * Tahap 9 memakai sambungan sungguhan, bukan EXISTS, sehingga
 * satu rumah dengan dua pembeli terhitung dua baris. Itu memang
 * yang tampil di layar. Selisih tahap 8 ke tahap 9 adalah
 * penggandaan karena pembeli, bukan kehilangan.
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
ppjb_aktif AS (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
t1 AS (
    SELECT
        surat.ppjb_id,
        surat.urut,
        surat.no_surat,
        surat.tgl_surat,
        surat.jenis_surat,
        CASE
            WHEN BTRIM(CAST(surat.ppjb_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(surat.ppjb_id AS TEXT))
            ELSE (SELECT awalan FROM awalan_unit)
                 || BTRIM(CAST(surat.ppjb_id AS TEXT))
        END AS kunci_ppjb
    FROM public.sr_undangan_ppjb AS surat
),
t2 AS (
    SELECT * FROM t1
    WHERE BTRIM(COALESCE(CAST(jenis_surat AS TEXT), '')) = '1'
),
t3 AS (
    SELECT * FROM t2
    WHERE tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
t4 AS (
    SELECT t3.*, ppjb_aktif.kunci_stok
    FROM t3
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = t3.kunci_ppjb
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
            SELECT 1 AS urut, 'Tahap 1  seluruh sr_undangan_ppjb'        AS tahap, COUNT(*) AS baris FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + jenis_surat = 1',                        COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + rentang tgl_surat',                      COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + PPJB aktif dan bukan turunan',           COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + stok SBKS yang aktif',                   COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + ada pembeli aktif',                      COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + pembelinya ketemu di sr_nasabah',        COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  + blok dan nomor terisi',                  COUNT(*) FROM t8
UNION ALL   SELECT 9,         'Tahap 9  digandakan pembeli = YANG TAMPIL',         COUNT(*) FROM t9
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * RINCIAN PER SEKTOR pada hasil akhir.
 *
 * Inilah yang disandingkan langsung dengan daftar sektor di
 * desktop. Sektor yang ada di desktop tetapi tidak muncul di
 * sini, itulah yang hilang seluruhnya.
 * ------------------------------------------------------------ */
WITH kolom_sektor_stok AS (
    SELECT (
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'sr_stok'
          AND column_name = ANY (ARRAY[
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'])
        ORDER BY array_position(
            ARRAY['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            column_name)
        LIMIT 1
    ) AS nama
),
kolom_sektor_master AS (
    SELECT (
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'sr_sektor'
          AND column_name = ANY (ARRAY[
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'])
        ORDER BY array_position(
            ARRAY['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            column_name)
        LIMIT 1
    ) AS nama
),
sektor_ref AS (
    SELECT kode, MIN(deskripsi) AS deskripsi
    FROM (
        SELECT
            UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> k.nama, ''))) AS kode,
            BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), ''))     AS deskripsi
        FROM public.sr_sektor AS sektor
        CROSS JOIN kolom_sektor_master AS k
    ) AS daftar
    GROUP BY kode
),
awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1
    LIMIT 1
),
stok_terpilih AS (
    SELECT
        BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok,
        stok.blok,
        stok.nomor,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.nama, ''))) AS kd_sektor
    FROM public.sr_stok AS stok
    CROSS JOIN kolom_sektor_stok AS k
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
)
SELECT
    stok.kd_sektor                               AS kd_sektor,
    COALESCE(sektor_ref.deskripsi, '(TIDAK ADA DI MASTER)') AS nama_sektor,
    COUNT(*)                                     AS baris
FROM surat
INNER JOIN ppjb_aktif   ON ppjb_aktif.kunci_ppjb = surat.kunci_ppjb
INNER JOIN stok_terpilih AS stok
    ON stok.kunci_stok = ppjb_aktif.kunci_stok
INNER JOIN public.sr_pembeli_ppjb AS pembeli
    ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = surat.kunci_ppjb
   AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
INNER JOIN public.sr_nasabah AS nasabah
    ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
     = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
LEFT JOIN sektor_ref ON sektor_ref.kode = stok.kd_sektor
WHERE stok.blok IS NOT NULL AND stok.nomor IS NOT NULL
GROUP BY 1, 2
ORDER BY 2, 1;


/* ------------------------------------------------------------
 * QUERY 3
 * SEKTOR YANG HILANG, ditelusuri tahap demi tahap.
 *
 * Bedanya dengan QUERY 1: di sini penyaringnya dilonggarkan satu
 * per satu tetapi hasilnya tetap dipilah menurut sektor, jadi
 * kelihatan SOULTAN ISLAND dan SRIMAYA COMMERCIAL sebenarnya
 * punya surat atau tidak, dan kalau punya, matinya di mana.
 *
 * Kalau kolom "punya_surat" ada isinya tetapi "lolos_semua" nol,
 * berarti suratnya termigrasi dan yang putus adalah salah satu
 * sambungan. Kalau "punya_surat" sendiri nol, berarti suratnya
 * yang memang belum termigrasi.
 * ------------------------------------------------------------ */
WITH kolom_sektor_stok AS (
    SELECT (
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'sr_stok'
          AND column_name = ANY (ARRAY[
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'])
        ORDER BY array_position(
            ARRAY['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            column_name)
        LIMIT 1
    ) AS nama
),
kolom_sektor_master AS (
    SELECT (
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'sr_sektor'
          AND column_name = ANY (ARRAY[
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'])
        ORDER BY array_position(
            ARRAY['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            column_name)
        LIMIT 1
    ) AS nama
),
sektor_ref AS (
    SELECT kode, MIN(deskripsi) AS deskripsi
    FROM (
        SELECT
            UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> k.nama, ''))) AS kode,
            BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), ''))     AS deskripsi
        FROM public.sr_sektor AS sektor
        CROSS JOIN kolom_sektor_master AS k
    ) AS daftar
    GROUP BY kode
),
awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1
    LIMIT 1
),
stok_sbks AS (
    SELECT
        BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok,
        stok.blok,
        stok.nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), 'T'))) AS flag_aktif,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.nama, ''))) AS kd_sektor
    FROM public.sr_stok AS stok
    CROSS JOIN kolom_sektor_stok AS k
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
),
ppjb_semua AS (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) AS flag_aktif,
        ppjb.parent_id
    FROM public.sr_ppjb AS ppjb
),
surat AS (
    SELECT
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
gabung AS (
    SELECT
        stok.kd_sektor,
        stok.flag_aktif        AS stok_aktif,
        ppjb_semua.flag_aktif  AS ppjb_aktif,
        ppjb_semua.parent_id,
        stok.blok,
        stok.nomor,
        EXISTS (
            SELECT 1 FROM public.sr_pembeli_ppjb AS p
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = ppjb_semua.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_pembeli,
        EXISTS (
            SELECT 1
            FROM public.sr_pembeli_ppjb AS p
            INNER JOIN public.sr_nasabah AS n
                ON BTRIM(CAST(n.nasabah_id AS TEXT))
                 = BTRIM(CAST(p.nasabah_id AS TEXT))
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = ppjb_semua.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_nasabah
    FROM surat
    INNER JOIN ppjb_semua ON ppjb_semua.kunci_ppjb = surat.kunci_ppjb
    INNER JOIN stok_sbks AS stok ON stok.kunci_stok = ppjb_semua.kunci_stok
)
SELECT
    kd_sektor,
    COALESCE(sektor_ref.deskripsi, '(TIDAK ADA DI MASTER)') AS nama_sektor,
    COUNT(*)                                                       AS punya_surat,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A')                       AS ppjb_aktif,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A' AND parent_id IS NULL)  AS bukan_turunan,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A' AND parent_id IS NULL
                       AND stok_aktif = 'A')                       AS stok_aktif,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A' AND parent_id IS NULL
                       AND stok_aktif = 'A' AND ada_pembeli)        AS ada_pembeli,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A' AND parent_id IS NULL
                       AND stok_aktif = 'A' AND ada_nasabah)        AS ada_nasabah,
    COUNT(*) FILTER (WHERE ppjb_aktif = 'A' AND parent_id IS NULL
                       AND stok_aktif = 'A' AND ada_nasabah
                       AND blok IS NOT NULL AND nomor IS NOT NULL)  AS lolos_semua
FROM gabung
LEFT JOIN sektor_ref ON sektor_ref.kode = gabung.kd_sektor
GROUP BY 1, 2
ORDER BY 2, 1;


/* ------------------------------------------------------------
 * QUERY 4
 * KEMUTAKHIRAN sr_undangan_ppjb.
 *
 * Kalau tanggal surat terakhir jauh di belakang hari ini,
 * sebabnya migrasi yang berhenti, bukan model. Ini yang terjadi
 * pada sr_imb dan sr_pengambilan.
 * ------------------------------------------------------------ */
SELECT
    BTRIM(COALESCE(CAST(jenis_surat AS TEXT), '(kosong)')) AS jenis_surat,
    COUNT(*)            AS baris,
    MIN(tgl_surat)      AS tgl_paling_awal,
    MAX(tgl_surat)      AS tgl_paling_akhir
FROM public.sr_undangan_ppjb
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 5
 * SEBARAN PER TAHUN untuk jenis 1, dipilah menurut apakah
 * barisnya lolos sampai akhir.
 *
 * Ini menjawab dugaan bahwa surat tahun 2025 dan 2026 lebih
 * banyak yang gugur karena pembelinya belum ada di sr_nasabah.
 * Kalau kolom "gugur" menumpuk di tahun-tahun terakhir, dugaan
 * itu benar. Kalau merata, dugaan itu salah dan sebabnya lain.
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
stok_sbks AS (
    SELECT
        BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok,
        stok.blok,
        stok.nomor,
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), 'T'))) AS flag_aktif
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
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
punya_sbks AS (
    SELECT
        surat.tgl_surat,
        stok.blok,
        stok.nomor,
        stok.flag_aktif,
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
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = surat.kunci_ppjb
    INNER JOIN stok_sbks AS stok ON stok.kunci_stok = ppjb_aktif.kunci_stok
)
SELECT
    EXTRACT(YEAR FROM tgl_surat)::INT AS tahun,
    COUNT(*)                                        AS surat_sbks,
    COUNT(*) FILTER (WHERE ada_pembeli)             AS ada_pembeli,
    COUNT(*) FILTER (WHERE ada_nasabah)             AS ada_nasabah,
    COUNT(*) FILTER (WHERE ada_pembeli
                       AND NOT ada_nasabah)         AS pembeli_tanpa_nasabah,
    COUNT(*) FILTER (WHERE flag_aktif <> 'A')       AS stok_tidak_aktif,
    COUNT(*) FILTER (WHERE ada_nasabah
                       AND flag_aktif = 'A'
                       AND blok IS NOT NULL
                       AND nomor IS NOT NULL)       AS lolos_semua
FROM punya_sbks
GROUP BY 1
ORDER BY 1;
