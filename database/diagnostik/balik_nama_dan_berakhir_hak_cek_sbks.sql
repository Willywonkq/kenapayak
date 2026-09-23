/* ============================================================
 * DUA LAPORAN SEKALIGUS, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN
 *
 *   Daftar Sertipikat Balik Nama      web 317   desktop 353
 *   Daftar Sertipikat Berakhir Hak    web   2   desktop   4
 *
 * Saringan keduanya: blok A sampai Z, sektor semua,
 * tanggal 01-07-2023 sampai 23-09-2026.
 *
 * Perbaikan saringan blok sudah terpasang dan terbukti jalan:
 * blok ZD sudah muncul di web, padahal dengan kode lama seluruh
 * blok berawalan Z pasti terbuang.
 *
 * ------------------------------------------------------------
 * DUGAAN, DAN KEDUANYA BERBEDA
 *
 * LAPORAN BALIK NAMA: nama pembeli WAJIB ada.
 *
 * Modelnya menyambung begini:
 *
 *     INNER JOIN pembeli_nama ON ...
 *
 * dan pembeli_nama sendiri disusun dengan
 *
 *     FROM sr_pembeli_ppjb INNER JOIN sr_nasabah
 *
 * Jadi sertipikat yang pembelinya tidak punya baris di
 * sr_nasabah TERBUANG seluruhnya, bukan sekadar kosong namanya.
 *
 * Ini setia pada desktop, yang juga menyambung NASABAH dengan
 * koma alias INNER JOIN. Bedanya, di SQL Server NASABAH lengkap
 * 46.818 baris, sedangkan sr_nasabah hasil migrasi kehilangan
 * 45 persen barisnya. Selisih 36 dari 353 baris, atau 10
 * persen, cocok dengan besaran itu.
 *
 * Sejalan dengan itu, ekor kedua laporan SAMA PERSIS. Baris
 * terakhir web nomor 317 dan desktop nomor 353 sama-sama
 * ZD/109 atas nama yang sama, dan tujuh baris sebelumnya juga
 * berpasangan satu per satu. Jadi yang hilang tersebar di
 * bagian awal, bukan terpotong di ujung.
 *
 * LAPORAN BERAKHIR HAK: sambungan wajib ke tabel induk.
 *
 * Modelnya bermula dari sr_sertipikat_idk dengan INNER JOIN,
 * sama seperti laporan pecahan. Sambungan ke ppjb dan nasabah
 * di sini justru LEFT JOIN, jadi nasabah TIDAK membuang baris.
 *
 * Yang hilang dari web ada dua dan keduanya bisa disebut:
 * RCH/009 atas nama DRS.ABDELAH HENDRA G, dan ZCB/070 atas nama
 * LENNY MARLINA DOLOKSARIBU. Induk ZCB/070 bertanggal 2023 dan
 * induk RCH/009 bertanggal 2020, dua-duanya di rentang ketika
 * sr_sertipikat_idk sudah mulai menipis.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG LAPORAN BALIK NAMA.
 *
 * Perhatikan dua angka terakhir. Selisihnya adalah baris yang
 * hilang semata-mata karena nasabahnya tidak ada.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit
),
ser AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_sertipikat AS s
    WHERE UPPER(BTRIM(COALESCE(CAST(s.status_blk_nm AS TEXT), ''))) = 'Y'
      AND SUBSTRING(CAST(s.tgl_input_blk_nm AS TEXT) FROM 1 FOR 10)
              BETWEEN '2023-07-01' AND '2026-09-23'
),
stok AS MATERIALIZED (
    SELECT
        BTRIM(CAST(st.stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), ''))) AS nomor
    FROM public.sr_stok AS st
    CROSS JOIN kolom AS k
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
),
ppjb AS MATERIALIZED (
    SELECT
        BTRIM(CAST(p.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(p.ppjb_id AS TEXT)) AS ppjb_id
    FROM public.sr_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'A'
      AND p.parent_id IS NULL
),
pembeli AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(pp.ppjb_id AS TEXT)) AS ppjb_id
    FROM public.sr_pembeli_ppjb AS pp
    WHERE UPPER(BTRIM(COALESCE(CAST(pp.flag_aktif AS TEXT), ''))) = 'Y'
),
pembeli_bernama AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(pp.ppjb_id AS TEXT)) AS ppjb_id
    FROM public.sr_pembeli_ppjb AS pp
    INNER JOIN public.sr_nasabah AS n
        ON BTRIM(CAST(n.nasabah_id AS TEXT)) = BTRIM(CAST(pp.nasabah_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(pp.flag_aktif AS TEXT), ''))) = 'Y'
      AND NULLIF(BTRIM(CAST(n.nama AS TEXT)), '') IS NOT NULL
)
SELECT
    COUNT(*)                                              AS t1_sertipikat_dalam_tanggal,
    COUNT(*) FILTER (WHERE stok.kunci_stok IS NOT NULL)   AS t2_stok_sbks,
    COUNT(*) FILTER (WHERE stok.kunci_stok IS NOT NULL
                       AND ((stok.blok || '/' || stok.nomor BETWEEN 'A' AND 'ZZ')
                            OR (stok.blok BETWEEN 'A' AND 'ZZ')))
                                                          AS t3_dalam_blok,
    COUNT(*) FILTER (WHERE stok.kunci_stok IS NOT NULL
                       AND ((stok.blok || '/' || stok.nomor BETWEEN 'A' AND 'ZZ')
                            OR (stok.blok BETWEEN 'A' AND 'ZZ'))
                       AND ppjb.ppjb_id IS NOT NULL)      AS t4_ada_ppjb_aktif,
    COUNT(*) FILTER (WHERE stok.kunci_stok IS NOT NULL
                       AND ((stok.blok || '/' || stok.nomor BETWEEN 'A' AND 'ZZ')
                            OR (stok.blok BETWEEN 'A' AND 'ZZ'))
                       AND ppjb.ppjb_id IS NOT NULL
                       AND pembeli.ppjb_id IS NOT NULL)   AS t5_ada_pembeli_aktif,
    COUNT(*) FILTER (WHERE stok.kunci_stok IS NOT NULL
                       AND ((stok.blok || '/' || stok.nomor BETWEEN 'A' AND 'ZZ')
                            OR (stok.blok BETWEEN 'A' AND 'ZZ'))
                       AND ppjb.ppjb_id IS NOT NULL
                       AND pembeli_bernama.ppjb_id IS NOT NULL)
                                                          AS t6_nasabahnya_ketemu
FROM ser
LEFT JOIN stok            ON stok.kunci_stok = ser.kunci_stok
LEFT JOIN ppjb            ON ppjb.kunci_stok = stok.kunci_stok
LEFT JOIN pembeli         ON pembeli.ppjb_id = ppjb.ppjb_id
LEFT JOIN pembeli_bernama ON pembeli_bernama.ppjb_id = ppjb.ppjb_id;


/* ------------------------------------------------------------
 * QUERY 2
 * DUA BARIS YANG HILANG DARI LAPORAN BERAKHIR HAK.
 *
 * Ditelusuri satu per satu. Kolom punya_induk itu kuncinya:
 * kalau bernilai f, berarti barisnya gugur pada sambungan wajib
 * ke sr_sertipikat_idk, dan sebabnya tabel induk yang belum
 * termigrasi, bukan saringan.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn','kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn','kd_jenis'], column_name)
          LIMIT 1) AS stok_jenis
),
induk AS MATERIALIZED (
    SELECT DISTINCT 'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat_idk AS i
)
SELECT
    UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) || '/'
        || UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), ''))) AS blok_nomor,
    BTRIM(CAST(s.sertipikat_id AS TEXT))                      AS sertipikat_id,
    BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), ''))        AS no_sertipikat,
    SUBSTRING(CAST(s.tgl_berlaku AS TEXT) FROM 1 FOR 10)      AS tgl_berlaku,
    (SUBSTRING(CAST(s.tgl_berlaku AS TEXT) FROM 1 FOR 10)
        BETWEEN '2023-07-01' AND '2026-09-23')                AS dalam_rentang,
    UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) AS kd_jenis,
    (induk.kunci IS NOT NULL)                                 AS punya_induk
FROM public.sr_stok AS st
CROSS JOIN kolom AS k
INNER JOIN public.sr_sertipikat AS s
    ON BTRIM(CAST(s.stok_id AS TEXT)) = BTRIM(CAST(st.stok_id AS TEXT))
LEFT JOIN induk
    ON induk.kunci = BTRIM(CAST(s.sertipikat_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
  AND (
        (UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) = 'RCH'
         AND BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')) IN ('009', '9'))
     OR (UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) = 'ZCB'
         AND BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')) IN ('070', '70'))
     OR (UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) = 'RDE'
         AND BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')) IN ('007', '7'))
     OR (UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) = 'TD'
         AND BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')) IN ('021', '21'))
      )
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * CORONG LAPORAN BERAKHIR HAK, supaya besaran keseluruhannya
 * juga terukur, bukan hanya dua baris itu.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn','kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn','kd_jenis'], column_name)
          LIMIT 1) AS stok_jenis
),
induk AS MATERIALIZED (
    SELECT DISTINCT 'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat_idk AS i
),
dasar AS (
    SELECT
        BTRIM(CAST(s.sertipikat_id AS TEXT))                      AS kunci,
        UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), '')))         AS blok,
        UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')))        AS nomor,
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) AS jenis,
        (induk.kunci IS NOT NULL)                                 AS punya_induk
    FROM public.sr_sertipikat AS s
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    LEFT JOIN induk
        ON induk.kunci = BTRIM(CAST(s.sertipikat_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
      AND SUBSTRING(CAST(s.tgl_berlaku AS TEXT) FROM 1 FOR 10)
              BETWEEN '2023-07-01' AND '2026-09-23'
)
SELECT
    COUNT(*)                                         AS t1_dalam_tanggal_dan_sbks,
    COUNT(*) FILTER (WHERE jenis NOT IN ('APT','KTR')) AS t2_bukan_apt_ktr,
    COUNT(*) FILTER (WHERE jenis NOT IN ('APT','KTR')
                       AND ((blok || '/' || nomor BETWEEN 'A' AND 'ZZ')
                            OR (blok BETWEEN 'A' AND 'ZZ')))
                                                     AS t3_dalam_blok,
    COUNT(*) FILTER (WHERE jenis NOT IN ('APT','KTR')
                       AND ((blok || '/' || nomor BETWEEN 'A' AND 'ZZ')
                            OR (blok BETWEEN 'A' AND 'ZZ'))
                       AND punya_induk)              AS t4_punya_induk
FROM dasar;
