/* ============================================================
 * MENGUJI PERBAIKAN SEBELUM MODELNYA DIUBAH
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * ------------------------------------------------------------
 * APA YANG SUDAH DIKETAHUI
 *
 *   web          1.521 baris
 *   desktop      1.518 baris
 *   masternya tidak ketemu untuk 90 baris
 *   sisanya      1.431 baris
 *
 * Dugaan pertama saya SALAH. Saya kira jalan keluarnya membuang
 * baris yang masternya tidak ketemu, meniru INNER JOIN desktop.
 * Kalau itu dilakukan hasilnya 1.431, yaitu 87 baris LEBIH
 * SEDIKIT daripada desktop. Jadi membuang seluruhnya justru
 * merusak, bukan membetulkan.
 *
 * Artinya dari 90 baris itu desktop menampilkan 87 dan membuang
 * 3. Yang dibuang tentu yang FLAG_LAPORAN-nya 2, yaitu Kavling.
 *
 * ------------------------------------------------------------
 * PERBAIKAN YANG DIUJI
 *
 * Yang hilang di PostgreSQL ternyata pasangan pada sr_tipe,
 * bukan sr_jenis_bangunan. Model menyambung jenis bangunan lewat
 * tipe:
 *
 *     tipe   ON  tipe.kd_jenis = stok.kd_jenis
 *                AND tipe.kd_tipe = stok.kd_tipe
 *     jenis  ON  jenis.kd_jenis = tipe.kd_jenis
 *
 * Begitu tipe tidak ketemu, tipe.kd_jenis menjadi NULL, sehingga
 * sambungan ke jenis bangunan ikut gagal walaupun barisnya
 * sebenarnya ADA. Kegagalan beruntun, bukan data yang hilang.
 *
 * Padahal tipe.kd_jenis selalu sama dengan stok.kd_jenis, sebab
 * itu memang syarat sambungannya. Jadi jenis bangunan bisa
 * disambung langsung dari stok:
 *
 *     jenis  ON  jenis.kd_jenis = COALESCE(tipe.kd_jenis, stok.kd_jenis)
 *
 * Kalau benar, KVC akan terbaca Kavling dan terbuang dari
 * laporan Non Kavling, sedangkan RMH tetap tampil.
 *
 * QUERY 1 menghitung hasilnya. Angkanya HARUS 1.518.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * HASIL DENGAN PERBAIKAN. Harus 1.518.
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
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        (tipe.kd_jenis IS NULL) AS tipe_tidak_ketemu,

        BTRIM(COALESCE(CAST(jenis_lama.flag_laporan AS TEXT), ''))  AS flag_cara_lama,
        BTRIM(COALESCE(CAST(jenis_baru.flag_laporan AS TEXT), ''))  AS flag_cara_baru,
        (jenis_baru.kd_jenis IS NULL) AS jenis_tetap_tidak_ketemu,

        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        ) AS tgl_rencana_filter
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN kolom_stok AS k
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
    LEFT JOIN public.sr_tipe AS tipe
        ON UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, '')))
       AND UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))

    LEFT JOIN public.sr_jenis_bangunan AS jenis_lama
        ON UPPER(BTRIM(COALESCE(CAST(jenis_lama.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))

    LEFT JOIN public.sr_jenis_bangunan AS jenis_baru
        ON UPPER(BTRIM(COALESCE(CAST(jenis_baru.kd_jenis AS TEXT), '')))
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
      AND (
            (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
             || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
            OR (UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                BETWEEN 'A' AND 'ZZ')
          )
),
dalam_rentang AS (
    SELECT dasar.*
    FROM dasar
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
)
SELECT
    COUNT(*) FILTER (WHERE flag_cara_lama <> '2')   AS cara_sekarang_harus_1521,
    COUNT(*) FILTER (WHERE flag_cara_baru <> '2')   AS dengan_perbaikan_harus_1518,
    COUNT(*) FILTER (WHERE tipe_tidak_ketemu)       AS tipe_tidak_ketemu,
    COUNT(*) FILTER (WHERE tipe_tidak_ketemu
                       AND flag_cara_baru = '2')    AS ketahuan_kavling,
    COUNT(*) FILTER (WHERE tipe_tidak_ketemu
                       AND flag_cara_baru <> '2'
                       AND NOT jenis_tetap_tidak_ketemu) AS ketahuan_bukan_kavling,
    COUNT(*) FILTER (WHERE jenis_tetap_tidak_ketemu) AS tetap_tidak_terbaca
FROM dalam_rentang;


/* ------------------------------------------------------------
 * QUERY 2
 * RINCIAN 90 BARIS ITU menurut kode jenisnya, supaya kelihatan
 * mana yang terselamatkan oleh perbaikan dan mana yang tidak.
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
)
SELECT
    UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis_stok,
    UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe_stok,
    BTRIM(COALESCE(CAST(jenis_baru.flag_laporan AS TEXT), '(tidak ada di master)'))
                                                               AS flag_laporan_sebenarnya,
    BTRIM(COALESCE(CAST(jenis_baru.deskripsi AS TEXT), ''))     AS nama_jenis,
    COUNT(*)                                                   AS baris
FROM public.sr_ppjb AS ppjb
CROSS JOIN kolom_stok AS k
INNER JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
INNER JOIN public.sr_pembeli_ppjb AS pembeli
    ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
   AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
LEFT JOIN public.sr_tipe AS tipe
    ON UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
     = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, '')))
   AND UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
     = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))
LEFT JOIN public.sr_jenis_bangunan AS jenis_baru
    ON UPPER(BTRIM(COALESCE(CAST(jenis_baru.kd_jenis AS TEXT), '')))
     = UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, '')))
WHERE tipe.kd_jenis IS NULL
  AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
  AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
  AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
  AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
  AND COALESCE(
        ppjb.tgl_rencana_sb,
        ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
      ) >= CAST('2023-07-01' AS DATE)
  AND COALESCE(
        ppjb.tgl_rencana_sb,
        ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
      ) < CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
GROUP BY 1, 2, 3, 4
ORDER BY 5 DESC, 1, 2;


/* ------------------------------------------------------------
 * QUERY 3
 * PASANGAN YANG HILANG DI sr_tipe, untuk dilaporkan ke tim
 * migrasi. Ini penyebab sesungguhnya, dan selama belum
 * diperbaiki laporan lain yang memakai tipe juga akan goyah.
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
dipakai AS (
    SELECT DISTINCT
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe
    FROM public.sr_stok AS stok
    CROSS JOIN kolom_stok AS k
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
)
SELECT
    COUNT(*)                                        AS pasangan_dipakai_sbks,
    COUNT(*) FILTER (WHERE tipe.kd_jenis IS NULL)   AS pasangan_hilang,
    COUNT(*) FILTER (WHERE tipe.kd_jenis IS NOT NULL) AS pasangan_ada
FROM dipakai
LEFT JOIN public.sr_tipe AS tipe
    ON UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), ''))) = dipakai.kd_jenis
   AND UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))  = dipakai.kd_tipe;
