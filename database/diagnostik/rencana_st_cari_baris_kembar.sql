/* ============================================================
 * MENCARI BARIS KEMBAR DI DAFTAR RENCANA SERAH TERIMA
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * ------------------------------------------------------------
 * HASIL PEMERIKSAAN SEBELUMNYA
 *
 *   setelah perbaikan        1.519
 *   nasabah tidak ada        505
 *   flag_laporan NULL        0
 *   parent_id kosong         0
 *   unit lewat cadangan      0
 *
 * Tiga kemungkinan terakhir MATI. Yang pertama ternyata bukan
 * penyebab selisih, melainkan temuan lain yang jauh lebih besar:
 * 505 dari 1.519 baris menunjuk nasabah yang tidak ada di
 * master. Nomornya semua di atas 45.255, yaitu batas migrasi
 * sr_nasabah yang sudah kita ketahui, dan semuanya Apartemen.
 * Di desktop nasabah itu ADA, jadi desktop tetap menampilkannya.
 * Kalau 505 baris itu benar-benar terbuang, desktop hanya akan
 * memuat 1.014 baris, bukan 1.518.
 *
 * ------------------------------------------------------------
 * DUGAAN BARU: BARIS KEMBAR, BUKAN BARIS BERLEBIH
 *
 * Selisihnya tinggal satu. Satu baris terlalu rapi untuk sebuah
 * perbedaan syarat, tetapi pas sekali untuk sebuah penggandaan.
 *
 * Model menyambung sr_ppjb ke sr_stok lewat stok_id. Kalau di
 * PostgreSQL ada DUA baris sr_stok dengan stok_id yang sama,
 * satu PPJB akan tergandakan menjadi dua baris laporan. Di
 * desktop tidak, sebab di sana stok_id memang tunggal.
 *
 * Kembar semacam ini persis akibat yang sudah berulang kali kita
 * temui: kunci yang dulu berawalan DBPSA dan DBPSS dimigrasikan
 * menjadi angka, awalannya hilang, lalu dua kunci dari dua
 * keluarga bertabrakan menjadi satu.
 *
 * Penggandaan yang sama bisa datang dari sr_tipe dan dari
 * sr_jenis_bangunan, sebab keduanya disambung dengan LEFT JOIN.
 *
 * Yang TIDAK dihitung sebagai kelainan adalah PPJB dengan lebih
 * dari satu pembeli aktif, sebab desktop menggandakannya juga.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * MEMERIKSA PENGGANDAAN DI ATAS KUMPULAN 1.519 BARIS.
 *
 * Yang dicari: kolom mana yang berisi TEPAT SATU.
 *
 *   baris_dari_stok_kembar    stok_id yang dipakai dua kali
 *   baris_dari_tipe_kembar    pasangan jenis dan tipe dua kali
 *   baris_dari_jenis_kembar   kd_jenis dua kali di master
 *   ppjb_berpembeli_banyak    wajar, desktop begitu juga
 * ------------------------------------------------------------ */
WITH kolom_stok AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema = 'public' AND table_name = 'sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn', 'kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn', 'kd_jenis'], column_name)
          LIMIT 1) AS kol_jenis,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema = 'public' AND table_name = 'sr_stok'
            AND column_name = ANY (ARRAY['kd_tipe_bgn', 'kd_tipe'])
          ORDER BY array_position(ARRAY['kd_tipe_bgn', 'kd_tipe'], column_name)
          LIMIT 1) AS kol_tipe
),
stok_kembar AS MATERIALIZED (
    SELECT BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci
    FROM public.sr_stok AS stok
    GROUP BY 1
    HAVING COUNT(*) > 1
),
ppjb_kembar AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci
    FROM public.sr_ppjb AS ppjb
    GROUP BY 1
    HAVING COUNT(*) > 1
),
tipe_kembar AS MATERIALIZED (
    SELECT
        UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))  AS kd_tipe
    FROM public.sr_tipe AS tipe
    GROUP BY 1, 2
    HAVING COUNT(*) > 1
),
jenis_kembar AS MATERIALIZED (
    SELECT UPPER(BTRIM(COALESCE(CAST(jb.kd_jenis AS TEXT), ''))) AS kd_jenis
    FROM public.sr_jenis_bangunan AS jb
    GROUP BY 1
    HAVING COUNT(*) > 1
),
pembeli_banyak AS MATERIALIZED (
    SELECT BTRIM(CAST(p.ppjb_id AS TEXT)) AS kunci
    FROM public.sr_pembeli_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
    GROUP BY 1
    HAVING COUNT(*) > 1
),
dasar AS (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                          AS kunci_ppjb,
        BTRIM(CAST(ppjb.stok_id AS TEXT))                          AS kunci_stok,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        )                                                          AS tgl_rencana_filter
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN kolom_stok AS k
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
    LEFT JOIN public.sr_tipe AS tipe
        ON UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, '')))
       AND UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))
    LEFT JOIN public.sr_jenis_bangunan AS jenis
        ON UPPER(BTRIM(COALESCE(CAST(jenis.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(
               CAST(tipe.kd_jenis AS TEXT),
               to_jsonb(stok) ->> k.kol_jenis,
               '')))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND NULLIF(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
      AND BTRIM(COALESCE(CAST(jenis.flag_laporan AS TEXT), '')) <> '2'
      AND (
            (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
             || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
            OR (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
          )
),
baris AS (
    SELECT dasar.*
    FROM dasar
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
)
SELECT
    COUNT(*)                                        AS jumlah_baris_1519,
    COUNT(DISTINCT baris.kunci_ppjb)                AS ppjb_unik,
    COUNT(*) FILTER (WHERE sk.kunci IS NOT NULL)    AS baris_dari_stok_kembar,
    COUNT(*) FILTER (WHERE pk.kunci IS NOT NULL)    AS baris_dari_ppjb_kembar,
    COUNT(*) FILTER (WHERE tk.kd_jenis IS NOT NULL) AS baris_dari_tipe_kembar,
    COUNT(*) FILTER (WHERE jk.kd_jenis IS NOT NULL) AS baris_dari_jenis_kembar,
    COUNT(*) FILTER (WHERE pb.kunci IS NOT NULL)    AS baris_ppjb_berpembeli_banyak
FROM baris
LEFT JOIN stok_kembar    AS sk ON sk.kunci    = baris.kunci_stok
LEFT JOIN ppjb_kembar    AS pk ON pk.kunci    = baris.kunci_ppjb
LEFT JOIN tipe_kembar    AS tk ON tk.kd_jenis = baris.kd_jenis
                              AND tk.kd_tipe  = baris.kd_tipe
LEFT JOIN jenis_kembar   AS jk ON jk.kd_jenis = baris.kd_jenis
LEFT JOIN pembeli_banyak AS pb ON pb.kunci    = baris.kunci_ppjb;


/* ------------------------------------------------------------
 * QUERY 2
 * MENAMPILKAN BLOK YANG MUNCUL LEBIH DARI SEKALI di laporan,
 * beserta berapa pembeli aktifnya. Kalau sebuah blok muncul dua
 * kali padahal pembeli aktifnya hanya satu, berarti yang kembar
 * adalah datanya, bukan pembelinya, dan itulah baris yang lebih.
 * ------------------------------------------------------------ */
WITH kolom_stok AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema = 'public' AND table_name = 'sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn', 'kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn', 'kd_jenis'], column_name)
          LIMIT 1) AS kol_jenis,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema = 'public' AND table_name = 'sr_stok'
            AND column_name = ANY (ARRAY['kd_tipe_bgn', 'kd_tipe'])
          ORDER BY array_position(ARRAY['kd_tipe_bgn', 'kd_tipe'], column_name)
          LIMIT 1) AS kol_tipe
),
jumlah_pembeli AS MATERIALIZED (
    SELECT
        BTRIM(CAST(p.ppjb_id AS TEXT)) AS kunci,
        COUNT(*)                       AS pembeli_aktif
    FROM public.sr_pembeli_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
    GROUP BY 1
),
dasar AS (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                          AS kunci_ppjb,
        BTRIM(CAST(ppjb.stok_id AS TEXT))                          AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                                                                   AS blok_nomor,
        BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))            AS no_ppjb,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        )                                                          AS tgl_rencana_filter
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN kolom_stok AS k
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
    LEFT JOIN public.sr_tipe AS tipe
        ON UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, '')))
       AND UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))
    LEFT JOIN public.sr_jenis_bangunan AS jenis
        ON UPPER(BTRIM(COALESCE(CAST(jenis.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(
               CAST(tipe.kd_jenis AS TEXT),
               to_jsonb(stok) ->> k.kol_jenis,
               '')))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND NULLIF(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
      AND BTRIM(COALESCE(CAST(jenis.flag_laporan AS TEXT), '')) <> '2'
      AND (
            (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
             || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
            OR (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
          )
),
baris AS (
    SELECT dasar.*
    FROM dasar
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
),
ringkas AS (
    SELECT
        baris.blok_nomor,
        baris.no_ppjb,
        baris.kunci_ppjb,
        baris.kunci_stok,
        baris.kd_jenis,
        COUNT(*) AS muncul_berapa_kali
    FROM baris
    GROUP BY 1, 2, 3, 4, 5
),
jumlah_stok AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci, COUNT(*) AS jumlah
    FROM public.sr_stok
    GROUP BY 1
),
jumlah_ppjb AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb_id AS TEXT)) AS kunci, COUNT(*) AS jumlah
    FROM public.sr_ppjb
    GROUP BY 1
)
SELECT
    ringkas.blok_nomor,
    ringkas.no_ppjb,
    ringkas.kd_jenis,
    ringkas.muncul_berapa_kali,
    COALESCE(jumlah_pembeli.pembeli_aktif, 0) AS pembeli_aktif,
    COALESCE(jumlah_stok.jumlah, 0)           AS baris_stok_dengan_id_itu,
    COALESCE(jumlah_ppjb.jumlah, 0)           AS baris_ppjb_dengan_id_itu
FROM ringkas
LEFT JOIN jumlah_pembeli ON jumlah_pembeli.kunci = ringkas.kunci_ppjb
LEFT JOIN jumlah_stok    ON jumlah_stok.kunci    = ringkas.kunci_stok
LEFT JOIN jumlah_ppjb    ON jumlah_ppjb.kunci    = ringkas.kunci_ppjb
WHERE ringkas.muncul_berapa_kali > COALESCE(jumlah_pembeli.pembeli_aktif, 0)
   OR COALESCE(jumlah_stok.jumlah, 0) > 1
   OR COALESCE(jumlah_ppjb.jumlah, 0) > 1
ORDER BY ringkas.muncul_berapa_kali DESC, ringkas.blok_nomor
LIMIT 20;


/* ------------------------------------------------------------
 * QUERY 3
 * SENSUS KEMBAR PADA TABEL INDUK, untuk laporan ke tim migrasi.
 * Angka ini berlaku untuk seluruh basis data, bukan hanya SBKS,
 * jadi berguna juga bagi laporan lain yang memakai tabel sama.
 * ------------------------------------------------------------ */
SELECT 'sr_stok.stok_id'          AS tabel_dan_kunci,
       COUNT(*)                   AS kunci_kembar,
       COALESCE(SUM(jumlah - 1), 0) AS baris_berlebih
FROM (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci, COUNT(*) AS jumlah
    FROM public.sr_stok GROUP BY 1 HAVING COUNT(*) > 1
) AS t

UNION ALL
SELECT 'sr_ppjb.ppjb_id',
       COUNT(*),
       COALESCE(SUM(jumlah - 1), 0)
FROM (
    SELECT BTRIM(CAST(ppjb_id AS TEXT)) AS kunci, COUNT(*) AS jumlah
    FROM public.sr_ppjb GROUP BY 1 HAVING COUNT(*) > 1
) AS t

UNION ALL
SELECT 'sr_tipe.kd_jenis+kd_tipe',
       COUNT(*),
       COALESCE(SUM(jumlah - 1), 0)
FROM (
    SELECT UPPER(BTRIM(COALESCE(CAST(kd_jenis AS TEXT), ''))) AS a,
           UPPER(BTRIM(COALESCE(CAST(kd_tipe AS TEXT), '')))  AS b,
           COUNT(*) AS jumlah
    FROM public.sr_tipe GROUP BY 1, 2 HAVING COUNT(*) > 1
) AS t

UNION ALL
SELECT 'sr_jenis_bangunan.kd_jenis',
       COUNT(*),
       COALESCE(SUM(jumlah - 1), 0)
FROM (
    SELECT UPPER(BTRIM(COALESCE(CAST(kd_jenis AS TEXT), ''))) AS kunci,
           COUNT(*) AS jumlah
    FROM public.sr_jenis_bangunan GROUP BY 1 HAVING COUNT(*) > 1
) AS t

UNION ALL
SELECT 'sr_nasabah.nasabah_id',
       COUNT(*),
       COALESCE(SUM(jumlah - 1), 0)
FROM (
    SELECT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci, COUNT(*) AS jumlah
    FROM public.sr_nasabah GROUP BY 1 HAVING COUNT(*) > 1
) AS t;
