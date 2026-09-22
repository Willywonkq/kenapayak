/* ============================================================
 * MENCARI SATU BARIS YANG MASIH TERSISA
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN SEKARANG
 *
 *   sebelum perbaikan   1.521
 *   sesudah perbaikan   1.519
 *   desktop             1.518
 *
 * Perbaikan sambungan jenis bangunan sudah terbukti benar. Dari
 * 90 baris yang tipenya tidak ketemu, 88 ternyata Rumah dan 2
 * ternyata Kavling Commercial, dan yang 2 itulah yang terbuang.
 * Tidak ada satu baris pun yang jenisnya tetap tidak terbaca.
 *
 * Sisa satu baris berasal dari sebab yang lain. Berkas ini
 * menguji empat kemungkinan sekaligus, semuanya di atas
 * kumpulan 1.519 baris yang sudah diperbaiki, supaya angkanya
 * langsung bisa dibandingkan.
 *
 * Keempatnya berangkat dari pola yang sama: desktop lebih tegas
 * membuang, web lebih longgar menerima.
 *
 * Catatan kecepatan. Pemadanan ke sr_nasabah TIDAK memakai
 * EXISTS yang berkorelasi, sebab EXISTS semacam itu dijalankan
 * sekali untuk setiap baris dan pernah membuat diagnosis
 * sebelumnya menggantung bermenit-menit. Di sini kuncinya
 * dikumpulkan sekali lewat CTE, lalu disambung biasa.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * MENGHITUNG KEEMPAT KEMUNGKINAN SEKALIGUS.
 *
 * KEMUNGKINAN A. Nasabah wajib ada.
 *   Desktop menyambung dengan koma, yaitu
 *       ( PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID )
 *   dan koma di SQL lama berarti INNER JOIN. Jadi pembeli yang
 *   nasabahnya tidak ada di master akan MEMBUANG barisnya.
 *   Model memakai LEFT JOIN, sehingga barisnya tetap tampil
 *   hanya dengan nama kosong. Mengingat sr_nasabah memang
 *   diketahui belum lengkap, ini dugaan yang paling kuat.
 *
 * KEMUNGKINAN B. FLAG_LAPORAN kosong.
 *   Desktop menyaring dengan JENIS_BANGUNAN.FLAG_LAPORAN <> 2,
 *   perbandingan angka. Kalau nilainya NULL maka hasilnya
 *   bukan benar dan bukan salah, melainkan tidak diketahui,
 *   dan SQL Server MEMBUANG barisnya.
 *   Model menyaring dengan teks,
 *       BTRIM(COALESCE(CAST(flag_laporan AS TEXT), '')) <> '2'
 *   sehingga NULL berubah menjadi string kosong, dan string
 *   kosong memang tidak sama dengan '2', jadi barisnya LOLOS.
 *
 * KEMUNGKINAN C. parent_id kosong, bukan NULL.
 *   Desktop memakai PPJB.PARENT_ID IS NULL. String kosong bukan
 *   NULL, jadi terbuang di desktop. Model memakai
 *       NULLIF(BTRIM(COALESCE(CAST(parent_id AS TEXT), '')), '') IS NULL
 *   yang menyamakan string kosong dengan NULL, jadi lolos.
 *
 * KEMUNGKINAN D. Unit diambil dari kolom cadangan.
 *   Desktop hanya memakai STOK.KD_PERUSAHAAN. Model memakai
 *   rantai kd_perusahaan, lalu kd_unit, lalu kd_pt. Stok yang
 *   kd_perusahaan-nya kosong tetapi kd_unit-nya SBKS akan ikut
 *   terbawa di web dan tidak di desktop.
 *
 * YANG DICARI: kolom mana yang berisi TEPAT SATU.
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
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                       AS kunci_ppjb,
        jenis.flag_laporan                                      AS flag_laporan_asli,
        ppjb.parent_id                                          AS parent_id_asli,
        NULLIF(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')), '')
                                                                AS kd_perusahaan_asli,
        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        )                                                       AS tgl_rencana_filter
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
    SELECT
        dasar.flag_laporan_asli,
        dasar.parent_id_asli,
        dasar.kd_perusahaan_asli,
        BTRIM(CAST(pembeli.nasabah_id AS TEXT))                 AS nasabah_teks,
        NULLIF(REGEXP_REPLACE(
            COALESCE(CAST(pembeli.nasabah_id AS TEXT), ''),
            '[^0-9]', '', 'g'), '')                             AS nasabah_angka
    FROM dasar
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
),
nasabah_kunci AS MATERIALIZED (
    SELECT DISTINCT
        BTRIM(CAST(n.nasabah_id AS TEXT))                       AS teks,
        NULLIF(REGEXP_REPLACE(
            COALESCE(CAST(n.nasabah_id AS TEXT), ''),
            '[^0-9]', '', 'g'), '')                             AS angka
    FROM public.sr_nasabah AS n
),
kunci_teks AS (
    SELECT DISTINCT teks FROM nasabah_kunci WHERE teks IS NOT NULL
),
kunci_angka AS (
    SELECT DISTINCT angka FROM nasabah_kunci WHERE angka IS NOT NULL
),
dipadankan AS (
    SELECT
        baris.*,
        (kunci_teks.teks IS NOT NULL)   AS nasabah_cocok_persis,
        (kunci_angka.angka IS NOT NULL) AS nasabah_cocok_angka
    FROM baris
    LEFT JOIN kunci_teks  ON kunci_teks.teks   = baris.nasabah_teks
    LEFT JOIN kunci_angka ON kunci_angka.angka = baris.nasabah_angka
)
SELECT
    COUNT(*)                                                    AS setelah_perbaikan_1519,
    COUNT(*) FILTER (WHERE NOT nasabah_cocok_persis
                       AND NOT nasabah_cocok_angka)             AS a_nasabah_tidak_ada,
    COUNT(*) FILTER (WHERE flag_laporan_asli IS NULL)           AS b_flag_laporan_null,
    COUNT(*) FILTER (WHERE parent_id_asli IS NOT NULL
                       AND BTRIM(CAST(parent_id_asli AS TEXT)) = '')
                                                                AS c_parent_id_kosong,
    COUNT(*) FILTER (WHERE kd_perusahaan_asli IS NULL)          AS d_lewat_kolom_cadangan
FROM dipadankan;


/* ------------------------------------------------------------
 * QUERY 2
 * MENAMPILKAN BARIS YANG DICURIGAI, paling banyak 20, lengkap
 * dengan alasannya. Kalau QUERY 1 menunjukkan satu, di sinilah
 * baris itu kelihatan batang hidungnya.
 *
 * Nama pembeli sengaja TIDAK ditampilkan. Blok, nomor PPJB dan
 * kode jenis sudah cukup untuk menelusurinya di desktop.
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
        BTRIM(CAST(ppjb.ppjb_id AS TEXT))                       AS kunci_ppjb,
        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                                                                AS blok_nomor,
        BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))         AS no_ppjb,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        BTRIM(COALESCE(CAST(jenis.deskripsi AS TEXT), ''))      AS nama_jenis,
        jenis.flag_laporan                                      AS flag_laporan_asli,
        ppjb.parent_id                                          AS parent_id_asli,
        NULLIF(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')), '')
                                                                AS kd_perusahaan_asli,
        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        )                                                       AS tgl_rencana_filter
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
    SELECT
        dasar.*,
        BTRIM(CAST(pembeli.nasabah_id AS TEXT))                 AS nasabah_teks,
        NULLIF(REGEXP_REPLACE(
            COALESCE(CAST(pembeli.nasabah_id AS TEXT), ''),
            '[^0-9]', '', 'g'), '')                             AS nasabah_angka
    FROM dasar
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
),
nasabah_kunci AS MATERIALIZED (
    SELECT DISTINCT
        BTRIM(CAST(n.nasabah_id AS TEXT))                       AS teks,
        NULLIF(REGEXP_REPLACE(
            COALESCE(CAST(n.nasabah_id AS TEXT), ''),
            '[^0-9]', '', 'g'), '')                             AS angka
    FROM public.sr_nasabah AS n
),
kunci_teks AS (
    SELECT DISTINCT teks FROM nasabah_kunci WHERE teks IS NOT NULL
),
kunci_angka AS (
    SELECT DISTINCT angka FROM nasabah_kunci WHERE angka IS NOT NULL
),
dipadankan AS (
    SELECT
        baris.*,
        (kunci_teks.teks IS NOT NULL)   AS nasabah_cocok_persis,
        (kunci_angka.angka IS NOT NULL) AS nasabah_cocok_angka
    FROM baris
    LEFT JOIN kunci_teks  ON kunci_teks.teks   = baris.nasabah_teks
    LEFT JOIN kunci_angka ON kunci_angka.angka = baris.nasabah_angka
)
SELECT
    blok_nomor,
    no_ppjb,
    kd_jenis,
    kd_tipe,
    nama_jenis,
    COALESCE(CAST(flag_laporan_asli AS TEXT), '(NULL)')         AS flag_laporan,
    nasabah_teks                                                AS nasabah_id,
    CASE
        WHEN NOT nasabah_cocok_persis AND NOT nasabah_cocok_angka
            THEN 'A. nasabah tidak ada di master'
        WHEN flag_laporan_asli IS NULL
            THEN 'B. flag_laporan NULL'
        WHEN parent_id_asli IS NOT NULL
             AND BTRIM(CAST(parent_id_asli AS TEXT)) = ''
            THEN 'C. parent_id kosong bukan NULL'
        ELSE 'D. unit lewat kolom cadangan'
    END                                                         AS alasan
FROM dipadankan
WHERE (NOT nasabah_cocok_persis AND NOT nasabah_cocok_angka)
   OR flag_laporan_asli IS NULL
   OR (parent_id_asli IS NOT NULL
       AND BTRIM(CAST(parent_id_asli AS TEXT)) = '')
   OR kd_perusahaan_asli IS NULL
ORDER BY alasan, blok_nomor
LIMIT 20;


/* ------------------------------------------------------------
 * QUERY 3
 * ISI MASTER JENIS BANGUNAN, untuk disandingkan dengan SQL
 * Server. Daftar ini pendek. Kalau ada satu kd_jenis yang di
 * PostgreSQL flag_laporan-nya bukan 2 sedangkan di SQL Server 2,
 * seluruh baris berjenis itu akan ikut tampil di laporan Non
 * Kavling padahal seharusnya tidak. Kolom dipakai_berapa_baris
 * menunjukkan bobotnya.
 * ------------------------------------------------------------ */
WITH kolom_stok AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema = 'public' AND table_name = 'sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn', 'kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn', 'kd_jenis'], column_name)
          LIMIT 1) AS kol_jenis
),
dipakai AS (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        COUNT(*) AS baris
    FROM public.sr_ppjb AS ppjb
    CROSS JOIN kolom_stok AS k
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND NULLIF(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
      AND COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
          ) >= CAST('2023-07-01' AS DATE)
      AND COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
          ) < CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
    GROUP BY 1
)
SELECT
    UPPER(BTRIM(COALESCE(CAST(jb.kd_jenis AS TEXT), ''))) AS kd_jenis,
    BTRIM(COALESCE(CAST(jb.deskripsi AS TEXT), ''))       AS deskripsi,
    COALESCE(CAST(jb.flag_laporan AS TEXT), '(NULL)')     AS flag_laporan,
    COALESCE(dipakai.baris, 0)                            AS dipakai_berapa_baris
FROM public.sr_jenis_bangunan AS jb
LEFT JOIN dipakai
    ON dipakai.kd_jenis = UPPER(BTRIM(COALESCE(CAST(jb.kd_jenis AS TEXT), '')))
ORDER BY COALESCE(dipakai.baris, 0) DESC, 1;
