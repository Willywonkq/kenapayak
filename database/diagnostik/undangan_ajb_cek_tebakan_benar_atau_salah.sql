/* ============================================================
 * APAKAH TEBAKAN AWALAN ITU BENAR ATAU SALAH
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * ------------------------------------------------------------
 * KEADAANNYA
 *
 * Pemeriksaan kerancuan menemukan hal yang tidak enak:
 *
 *   3.277 surat AJB masuk rentang tanggal laporan
 *     691 angkanya pasti milik keluarga DBPSA
 *   2.586 angkanya ADA DI KEDUA KELUARGA, jadi ditebak
 *
 *   2.001 baris akhirnya tampil di laporan SBKS
 *   1.801 di antaranya kuncinya hasil tebakan
 *
 * Sembilan dari sepuluh baris laporan bertumpu pada tebakan.
 * Itu tidak bisa dibiarkan tanpa dipastikan.
 *
 * Perlu ditegaskan: RANCU BUKAN BERARTI SALAH. Rancu berarti
 * model tidak punya bukti bahwa tebakannya benar. Tebakan itu
 * bisa saja benar semua. Tetapi "bisa saja benar" bukan dasar
 * yang cukup untuk laporan yang dipakai orang bekerja.
 *
 * ------------------------------------------------------------
 * CARA MEMASTIKANNYA
 *
 * Ternyata ada bukti yang selama ini terlewat, dan letaknya di
 * depan mata: KODE UNITNYA TERTULIS DI NOMOR SURATNYA SENDIRI.
 *
 *   0022/SBKS-LK/03/2024
 *   0380/SBKS-LK/04/2026
 *
 * Nomor surat itu milik barisnya sendiri. Dia tidak ikut
 * disusun ulang, tidak ikut ditebak, dan tidak bergantung pada
 * awalan apa pun. Jadi dia bisa dipakai menguji tebakan tanpa
 * perlu membandingkan ke SQL Server sama sekali.
 *
 * Kalau ada baris yang nomor suratnya menyebut unit lain tetapi
 * tetap tampil di laporan SBKS, itu baris karangan, dan
 * terbukti, bukan dicurigai.
 *
 * QUERY 3 adalah pembandingnya, dan harus dibaca lebih dulu.
 * Dia menjalankan uji yang sama pada jenis yang kuncinya
 * termigrasi utuh sehingga tidak pernah ditebak. Kalau di sana
 * pun banyak nomor surat menyebut unit lain, berarti nomor
 * surat memang bukan penanda unit yang bisa dipercaya, dan
 * seluruh berkas ini tidak berlaku.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * BARIS YANG TAMPIL DI LAPORAN SBKS, diuji dengan nomor
 * suratnya sendiri.
 *
 * Kolom yang menentukan: menyebut_unit_lain.
 *
 *   nol           tebakannya benar semua, laporan boleh dipakai
 *   lebih dari nol  ada baris karangan sebanyak itu
 * ------------------------------------------------------------ */
WITH unit_dikenal AS MATERIALIZED (
    SELECT DISTINCT UPPER(BTRIM(CAST(kd_perusahaan AS TEXT))) AS kode
    FROM public.sr_stok
    WHERE kd_perusahaan IS NOT NULL
      AND BTRIM(CAST(kd_perusahaan AS TEXT)) <> ''
),
keluarga_ppjb AS MATERIALIZED (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSA-%') AS ada_a,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSS-%') AS ada_s
    FROM public.sr_ppjb
    WHERE ppjb_id IS NOT NULL
    GROUP BY 1
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        BTRIM(CAST(s.ppjb_id AS TEXT))             AS angka,
        'DBPSA-' || BTRIM(CAST(s.ppjb_id AS TEXT)) AS kunci_tebakan,
        EXTRACT(YEAR FROM s.tgl_surat)::INT        AS tahun,
        UPPER(COALESCE(CAST(s.no_surat AS TEXT), '')) AS no_surat
    FROM public.sr_undangan_ajb AS s
    WHERE s.ppjb_id IS NOT NULL
      AND BTRIM(CAST(s.ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
tampil AS (
    SELECT
        surat.tahun,
        surat.no_surat,
        (keluarga_ppjb.ada_a AND keluarga_ppjb.ada_s) AS rancu,
        (
            SELECT unit_dikenal.kode
            FROM unit_dikenal
            WHERE POSITION(unit_dikenal.kode IN surat.no_surat) > 0
            ORDER BY LENGTH(unit_dikenal.kode) DESC
            LIMIT 1
        ) AS kode_di_nomor
    FROM surat
    INNER JOIN ppjb_sbks ON ppjb_sbks.kunci_ppjb = surat.kunci_tebakan
    LEFT JOIN keluarga_ppjb ON keluarga_ppjb.angka = surat.angka
)
SELECT
    tahun,
    COUNT(*)                                           AS tampil_di_laporan,
    COUNT(*) FILTER (WHERE rancu)                      AS kuncinya_ditebak,
    COUNT(*) FILTER (WHERE kode_di_nomor = 'SBKS')     AS nomornya_menyebut_sbks,
    COUNT(*) FILTER (WHERE kode_di_nomor IS NOT NULL
                       AND kode_di_nomor <> 'SBKS')    AS menyebut_unit_lain,
    COUNT(*) FILTER (WHERE kode_di_nomor IS NULL)      AS tidak_ada_kode_unit
FROM tampil
GROUP BY tahun
ORDER BY tahun;


/* ------------------------------------------------------------
 * QUERY 2
 * ARAH SEBALIKNYA.
 *
 * Surat yang nomornya jelas-jelas menyebut SBKS tetapi TIDAK
 * tampil di laporan. Kalau banyak, berarti tebakannya
 * membuang milik sendiri, bukan cuma memungut milik orang.
 * ------------------------------------------------------------ */
WITH unit_dikenal AS MATERIALIZED (
    SELECT DISTINCT UPPER(BTRIM(CAST(kd_perusahaan AS TEXT))) AS kode
    FROM public.sr_stok
    WHERE kd_perusahaan IS NOT NULL
      AND BTRIM(CAST(kd_perusahaan AS TEXT)) <> ''
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        'DBPSA-' || BTRIM(CAST(s.ppjb_id AS TEXT)) AS kunci_tebakan,
        EXTRACT(YEAR FROM s.tgl_surat)::INT        AS tahun,
        UPPER(COALESCE(CAST(s.no_surat AS TEXT), '')) AS no_surat
    FROM public.sr_undangan_ajb AS s
    WHERE s.ppjb_id IS NOT NULL
      AND BTRIM(CAST(s.ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
dinilai AS (
    SELECT
        surat.tahun,
        (ppjb_sbks.kunci_ppjb IS NOT NULL) AS tampil,
        (
            SELECT unit_dikenal.kode
            FROM unit_dikenal
            WHERE POSITION(unit_dikenal.kode IN surat.no_surat) > 0
            ORDER BY LENGTH(unit_dikenal.kode) DESC
            LIMIT 1
        ) AS kode_di_nomor
    FROM surat
    LEFT JOIN ppjb_sbks ON ppjb_sbks.kunci_ppjb = surat.kunci_tebakan
)
SELECT
    tahun,
    COUNT(*) FILTER (WHERE kode_di_nomor = 'SBKS')  AS nomornya_sbks,
    COUNT(*) FILTER (WHERE kode_di_nomor = 'SBKS'
                       AND tampil)                  AS sbks_dan_tampil,
    COUNT(*) FILTER (WHERE kode_di_nomor = 'SBKS'
                       AND NOT tampil)              AS sbks_tapi_hilang
FROM dinilai
GROUP BY tahun
ORDER BY tahun;


/* ------------------------------------------------------------
 * QUERY 3
 * PEMBANDING. BACA INI LEBIH DULU.
 *
 * Uji yang sama pada sr_undangan_ppjb dan sr_undangan_st, yang
 * kuncinya termigrasi utuh sehingga TIDAK PERNAH ditebak.
 *
 * Di sini menyebut_unit_lain seharusnya nol atau nyaris nol.
 * Kalau ternyata besar juga, berarti nomor surat bukan penanda
 * unit yang bisa dipercaya, dan QUERY 1 tidak boleh dipakai
 * untuk menyimpulkan apa pun.
 * ------------------------------------------------------------ */
WITH unit_dikenal AS MATERIALIZED (
    SELECT DISTINCT UPPER(BTRIM(CAST(kd_perusahaan AS TEXT))) AS kode
    FROM public.sr_stok
    WHERE kd_perusahaan IS NOT NULL
      AND BTRIM(CAST(kd_perusahaan AS TEXT)) <> ''
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
            SELECT 'sr_undangan_ppjb jenis 1' AS asal,
                   BTRIM(CAST(s.ppjb_id AS TEXT)) AS kunci,
                   UPPER(COALESCE(CAST(s.no_surat AS TEXT), '')) AS no_surat
            FROM public.sr_undangan_ppjb AS s
            WHERE BTRIM(COALESCE(CAST(s.jenis_surat AS TEXT), '')) = '1'
              AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
              AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)

UNION ALL   SELECT 'sr_undangan_st jenis 3',
                   BTRIM(CAST(s.ppjb_id AS TEXT)),
                   UPPER(COALESCE(CAST(s.no_surat AS TEXT), ''))
            FROM public.sr_undangan_st AS s
            WHERE s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
              AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
tampil AS (
    SELECT
        surat.asal,
        (
            SELECT unit_dikenal.kode
            FROM unit_dikenal
            WHERE POSITION(unit_dikenal.kode IN surat.no_surat) > 0
            ORDER BY LENGTH(unit_dikenal.kode) DESC
            LIMIT 1
        ) AS kode_di_nomor
    FROM surat
    INNER JOIN ppjb_sbks ON ppjb_sbks.kunci_ppjb = surat.kunci
)
SELECT
    asal,
    COUNT(*)                                          AS tampil_di_laporan,
    COUNT(*) FILTER (WHERE kode_di_nomor = 'SBKS')    AS nomornya_menyebut_sbks,
    COUNT(*) FILTER (WHERE kode_di_nomor IS NOT NULL
                       AND kode_di_nomor <> 'SBKS')   AS menyebut_unit_lain,
    COUNT(*) FILTER (WHERE kode_di_nomor IS NULL)     AS tidak_ada_kode_unit
FROM tampil
GROUP BY asal
ORDER BY asal;


/* ------------------------------------------------------------
 * QUERY 4
 * CONTOHNYA, kalau QUERY 1 memang menemukan yang menyebut unit
 * lain. Dua puluh saja, untuk dilihat dengan mata.
 * ------------------------------------------------------------ */
WITH unit_dikenal AS MATERIALIZED (
    SELECT DISTINCT UPPER(BTRIM(CAST(kd_perusahaan AS TEXT))) AS kode
    FROM public.sr_stok
    WHERE kd_perusahaan IS NOT NULL
      AND BTRIM(CAST(kd_perusahaan AS TEXT)) <> ''
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok, blok, nomor
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        stok_sbks.blok,
        stok_sbks.nomor
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        BTRIM(CAST(s.ppjb_id AS TEXT))             AS angka,
        'DBPSA-' || BTRIM(CAST(s.ppjb_id AS TEXT)) AS kunci_tebakan,
        s.tgl_surat,
        UPPER(COALESCE(CAST(s.no_surat AS TEXT), '')) AS no_surat
    FROM public.sr_undangan_ajb AS s
    WHERE s.ppjb_id IS NOT NULL
      AND BTRIM(CAST(s.ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
)
SELECT
    surat.angka        AS ppjb_id_asli,
    surat.kunci_tebakan,
    ppjb_sbks.blok,
    ppjb_sbks.nomor,
    surat.no_surat,
    surat.tgl_surat,
    (
        SELECT unit_dikenal.kode
        FROM unit_dikenal
        WHERE POSITION(unit_dikenal.kode IN surat.no_surat) > 0
        ORDER BY LENGTH(unit_dikenal.kode) DESC
        LIMIT 1
    ) AS kode_di_nomor
FROM surat
INNER JOIN ppjb_sbks ON ppjb_sbks.kunci_ppjb = surat.kunci_tebakan
WHERE (
        SELECT unit_dikenal.kode
        FROM unit_dikenal
        WHERE POSITION(unit_dikenal.kode IN surat.no_surat) > 0
        ORDER BY LENGTH(unit_dikenal.kode) DESC
        LIMIT 1
      ) IS DISTINCT FROM 'SBKS'
ORDER BY surat.tgl_surat DESC
LIMIT 20;
