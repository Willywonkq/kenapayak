/* ============================================================
 * UNIT MANA SAJA YANG BERAWALAN DBPSS-
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * DDL, maupun CREATE INDEX. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * KENAPA PERLU DIPERIKSA
 *
 * Awalan DBPSA- dan DBPSS- bukan bagian dari data aslinya.
 * Keduanya ditambahkan waktu SRIS_PUSAT dan SRIS_SERPONG
 * digabung menjadi satu basis data PostgreSQL: DBPSA- untuk
 * PUSAT, DBPSS- untuk SERPONG.
 *
 * Beberapa tabel kehilangan awalan itu saat dipindahkan karena
 * kolom kuncinya dijadikan angka. Untuk tabel tabel itu, unit
 * berawalan DBPSS- adalah yang paling berisiko laporannya
 * kosong atau ditolak.
 *
 * Catatan terdahulu menyebut ENAM unit berawalan DBPSS-:
 *
 *     SSPG   SPCK   KSLV   KSVT   KSLL   SPCH
 *
 * Angka itu diperoleh dari pemeriksaan sr_imb dan sr_pbb
 * beberapa waktu lalu. QUERY 1 memastikannya lagi dari data
 * sekarang, langsung dari sr_stok yang awalannya masih utuh.
 *
 * ------------------------------------------------------------
 * CARA MEMBACA
 *
 * QUERY 1 menjawab "unit apa saja yang DBPSS-".
 * QUERY 2 menjawab "apakah datanya sudah keluar atau belum",
 * per unit, untuk tabel tabel yang selama ini bermasalah.
 *
 * Pada QUERY 2, unit yang kolom AKTA, IDK, IMB, atau PBB-nya
 * NOL sementara STOK dan PPJB-nya ribuan, itulah unit yang
 * laporannya akan kosong. Bandingkan dengan unit berawalan
 * DBPSA- yang seumuran: kalau yang itu terisi dan yang ini
 * nol, sebabnya migrasi, bukan memang belum ada dokumennya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1. AWALAN KUNCI TIAP UNIT
 *
 * Dibaca dari sr_stok karena stok_id di tabel itu masih
 * berbentuk teks, jadi awalannya belum hilang.
 * ------------------------------------------------------------ */
SELECT
    UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) AS "UNIT",
    COUNT(*)                                                     AS "BARIS_STOK",
    COUNT(*) FILTER (
        WHERE BTRIM(CAST(stok.stok_id AS TEXT)) LIKE 'DBPSA-%'
    )                                                            AS "AWALAN_DBPSA",
    COUNT(*) FILTER (
        WHERE BTRIM(CAST(stok.stok_id AS TEXT)) LIKE 'DBPSS-%'
    )                                                            AS "AWALAN_DBPSS",
    COUNT(*) FILTER (
        WHERE BTRIM(CAST(stok.stok_id AS TEXT)) NOT LIKE 'DBPSA-%'
          AND BTRIM(CAST(stok.stok_id AS TEXT)) NOT LIKE 'DBPSS-%'
    )                                                            AS "TANPA_AWALAN",
    CASE
        WHEN COUNT(*) FILTER (
                 WHERE BTRIM(CAST(stok.stok_id AS TEXT)) LIKE 'DBPSS-%'
             ) = 0 THEN 'PUSAT'
        WHEN COUNT(*) FILTER (
                 WHERE BTRIM(CAST(stok.stok_id AS TEXT)) LIKE 'DBPSA-%'
             ) = 0 THEN 'SERPONG  <- berisiko'
        ELSE 'CAMPURAN  <- perlu dilihat'
    END                                                          AS "ASAL"
FROM public.sr_stok AS stok
WHERE stok.stok_id IS NOT NULL
GROUP BY 1
ORDER BY 6, 1;


/* ------------------------------------------------------------
 * QUERY 2. ISI TIAP TABEL PER UNIT
 *
 * Semua dihitung lebih dulu lalu disambungkan, tanpa anak
 * kueri berkorelasi, supaya tidak menyapu tabel berulang kali.
 * ------------------------------------------------------------ */
WITH stok AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.stok_id AS TEXT))                            AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), '')))  AS unit,
        CASE
            WHEN BTRIM(CAST(s.stok_id AS TEXT)) LIKE 'DBPSS-%'
            THEN 'DBPSS-' ELSE 'DBPSA-'
        END                                                        AS awalan
    FROM public.sr_stok AS s
    WHERE s.stok_id IS NOT NULL
),
unit_awalan AS (
    SELECT
        unit,
        COUNT(*)                                       AS baris_stok,
        MAX(awalan) FILTER (WHERE awalan = 'DBPSS-')   AS ada_dbpss,
        MAX(awalan) FILTER (WHERE awalan = 'DBPSA-')   AS ada_dbpsa
    FROM stok GROUP BY 1
),
ppjb AS MATERIALIZED (
    SELECT
        BTRIM(CAST(p.ppjb_id AS TEXT)) AS kunci_ppjb,
        BTRIM(CAST(p.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_ppjb AS p
),
ppjb_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM ppjb INNER JOIN stok ON stok.kunci_stok = ppjb.kunci_stok
    GROUP BY 1
),
sertipikat_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_sertipikat AS x
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
    GROUP BY 1
),
akta_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_akta AS a
    INNER JOIN ppjb ON ppjb.kunci_ppjb = BTRIM(CAST(a.ppjb_id AS TEXT))
    INNER JOIN stok ON stok.kunci_stok = ppjb.kunci_stok
    GROUP BY 1
),
idk_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_sertipikat_idk AS i
    INNER JOIN public.sr_sertipikat AS x
        ON BTRIM(CAST(x.sertipikat_id AS TEXT))
         = BTRIM(CAST(i.sertipikat_id AS TEXT))
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
    GROUP BY 1
),
imb_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_imb AS m
    INNER JOIN public.sr_sertipikat AS x
        ON BTRIM(CAST(x.sertipikat_id AS TEXT))
         = BTRIM(CAST(m.sertipikat_id AS TEXT))
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
    GROUP BY 1
),
pbb_unit AS (
    SELECT stok.unit AS unit, COUNT(*) AS jumlah
    FROM public.sr_pbb AS b
    INNER JOIN public.sr_sertipikat AS x
        ON BTRIM(CAST(x.sertipikat_id AS TEXT))
         = BTRIM(CAST(b.sertipikat_id AS TEXT))
    INNER JOIN stok ON stok.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
    GROUP BY 1
)
SELECT
    u.unit                                  AS "UNIT",
    CASE
        WHEN u.ada_dbpss IS NOT NULL AND u.ada_dbpsa IS NOT NULL
             THEN 'CAMPURAN'
        WHEN u.ada_dbpss IS NOT NULL THEN 'DBPSS-'
        ELSE 'DBPSA-'
    END                                     AS "AWALAN",
    u.baris_stok                            AS "STOK",
    COALESCE(pu.jumlah, 0)                  AS "PPJB",
    COALESCE(su.jumlah, 0)                  AS "SERTIPIKAT",
    COALESCE(au.jumlah, 0)                  AS "AKTA",
    COALESCE(iu.jumlah, 0)                  AS "IDK",
    COALESCE(mu.jumlah, 0)                  AS "IMB",
    COALESCE(bu.jumlah, 0)                  AS "PBB"
FROM unit_awalan AS u
LEFT JOIN ppjb_unit       AS pu ON pu.unit = u.unit
LEFT JOIN sertipikat_unit AS su ON su.unit = u.unit
LEFT JOIN akta_unit       AS au ON au.unit = u.unit
LEFT JOIN idk_unit        AS iu ON iu.unit = u.unit
LEFT JOIN imb_unit        AS mu ON mu.unit = u.unit
LEFT JOIN pbb_unit        AS bu ON bu.unit = u.unit
ORDER BY 2 DESC, 3 DESC;
