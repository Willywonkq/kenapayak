/* ============================================================
 * MENUNJUKKAN BARIS YANG LEBIH ITU
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * Pasangannya: sqlserver_rencana_st_tunjukkan_baris_itu.sql
 *
 * ------------------------------------------------------------
 * PENYANDINGAN SEBARAN SUDAH MENGURUNGNYA
 *
 *   awal_blok RD      web 273   desktop 272
 *   jenis RMH         web 869   desktop 868
 *   tahun_ppjb 2024   web 417   desktop 416
 *
 * Kelompok lain sama persis. Ketiga sudut menunjuk baris yang
 * sama, jadi barisnya pasti berblok RD, berjenis RMH, dan
 * PPJB-nya tahun 2024.
 *
 * Berkas ini mendaftar seluruh baris di persimpangan itu pada
 * kedua basis data. Tinggal disandingkan, dan yang ada di sini
 * tetapi tidak ada di sana itulah jawabannya.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * HITUNGAN PER BLOK PENUH di persimpangan itu. Kalau daftar
 * QUERY 2 terlalu panjang untuk disalin, cukup kirim yang ini
 * dari kedua sisi, sebab bloknya pasti tinggal satu yang beda.
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
dasar AS (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                          AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))        AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                                                                   AS blok_nomor,
        BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))            AS no_ppjb,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        ppjb.tgl_ppjb                                              AS tgl_ppjb,
        ppjb.tgl_rencana_sb                                        AS tgl_rencana_sb,
        ppjb.waktu                                                 AS waktu,
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
      AND dasar.blok LIKE 'RD%'
      AND dasar.kd_jenis = 'RMH'
      AND EXTRACT(YEAR FROM dasar.tgl_ppjb) = 2024
)
SELECT blok, COUNT(*) AS jumlah
FROM baris
GROUP BY blok
ORDER BY blok;


/* ------------------------------------------------------------
 * QUERY 2
 * DAFTAR LENGKAP di persimpangan itu.
 *
 * Nama pembeli sengaja tidak ditampilkan. Kolom tgl_rencana_sb
 * dan waktu ikut dibawa sebab kalau ternyata barisnya ADA di
 * kedua sisi, perbedaannya pasti di salah satu kolom itu.
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
dasar AS (
    SELECT
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                          AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))        AS blok,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                                                                   AS blok_nomor,
        BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))            AS no_ppjb,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        ppjb.tgl_ppjb                                              AS tgl_ppjb,
        ppjb.tgl_rencana_sb                                        AS tgl_rencana_sb,
        ppjb.waktu                                                 AS waktu,
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
      AND dasar.blok LIKE 'RD%'
      AND dasar.kd_jenis = 'RMH'
      AND EXTRACT(YEAR FROM dasar.tgl_ppjb) = 2024
)
SELECT
    blok_nomor,
    no_ppjb,
    kd_tipe,
    TO_CHAR(tgl_ppjb, 'YYYY-MM-DD')             AS tgl_ppjb,
    COALESCE(TO_CHAR(tgl_rencana_sb, 'YYYY-MM-DD'), '(kosong)')
                                                AS tgl_rencana_sb,
    COALESCE(CAST(waktu AS TEXT), '(kosong)')   AS waktu,
    TO_CHAR(tgl_rencana_filter, 'YYYY-MM-DD')   AS tgl_rencana_dipakai
FROM baris
ORDER BY blok_nomor, no_ppjb;
