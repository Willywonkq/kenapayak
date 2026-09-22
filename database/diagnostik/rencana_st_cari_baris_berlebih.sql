/* ============================================================
 * KENAPA DAFTAR RENCANA SERAH TERIMA LEBIH BANYAK DI WEB
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT             : SBKS
 *   TGL RENCANA ST   : 01-07-2023 s/d 22-09-2026
 *   BLOK             : A s/d ZZ
 *   SEKTOR           : Semua
 *   JENIS            : Non Kavling
 *   Versi Management : tidak dicentang
 *
 *   desktop  1.518 baris
 *   web      1.521 baris
 *
 * ------------------------------------------------------------
 * DUGAAN YANG DIUJI
 *
 * Query desktop menyambung TIPE dan JENIS_BANGUNAN dengan join
 * lama, yaitu koma di FROM, yang artinya INNER JOIN:
 *
 *     TIPE.KD_JENIS = STOK.KD_JENIS
 *     AND TIPE.KD_TIPE = STOK.KD_TIPE
 *     AND TIPE.KD_JENIS = JENIS_BANGUNAN.KD_JENIS
 *     AND JENIS_BANGUNAN.FLAG_LAPORAN <> 2
 *
 * Model web memakai LEFT JOIN untuk keduanya. Itu keputusan yang
 * disengaja, sebab pasangan KD_JENIS dan KD_TIPE di PostgreSQL
 * belum lengkap, dan INNER JOIN akan membuang unit yang di
 * desktop tetap tampil.
 *
 * Tetapi LEFT JOIN punya akibat yang belum tertangani. Ketika
 * pasangannya tidak ketemu, flag_laporan menjadi NULL, lalu
 * diubah menjadi string kosong, lalu diuji dengan:
 *
 *     BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan ...), '')) <> '2'
 *
 * String kosong tidak sama dengan '2', jadi syaratnya LOLOS dan
 * barisnya ikut tampil di laporan Non Kavling. Padahal desktop
 * membuangnya, dan kita sendiri tidak tahu jenis sebenarnya:
 * bisa saja Kavling.
 *
 * Kalau dugaan ini benar, jumlah baris yang jenis bangunannya
 * tidak ketemu akan tepat 3.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * RINCIAN HASIL AKHIR menurut ketemu atau tidaknya master.
 *
 * Kolom yang menentukan: jenis_tidak_ketemu.
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
        BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
            || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')) AS blok_nomor,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        (tipe.kd_jenis IS NULL)           AS tipe_tidak_ketemu,
        (jenis_bangunan.kd_jenis IS NULL) AS jenis_tidak_ketemu,
        BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan AS TEXT), '')) AS flag_laporan,
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
    LEFT JOIN public.sr_jenis_bangunan AS jenis_bangunan
        ON UPPER(BTRIM(COALESCE(CAST(jenis_bangunan.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
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
tersaring AS (
    SELECT * FROM dasar
    WHERE tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
      AND tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
      AND flag_laporan <> '2'
),
akhir AS (
    SELECT tersaring.*
    FROM tersaring
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = tersaring.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
)
SELECT
    COUNT(*)                                             AS baris_tampil,
    COUNT(*) FILTER (WHERE tipe_tidak_ketemu)            AS tipe_tidak_ketemu,
    COUNT(*) FILTER (WHERE jenis_tidak_ketemu)           AS jenis_tidak_ketemu,
    COUNT(*) FILTER (WHERE NOT jenis_tidak_ketemu)       AS jenis_ketemu
FROM akhir;


/* ------------------------------------------------------------
 * QUERY 2
 * BARIS YANG JENIS BANGUNANNYA TIDAK KETEMU, satu per satu.
 *
 * Daftar ini yang dibandingkan dengan layar desktop. Kalau baris
 * ini TIDAK ada di desktop, berarti memang inilah kelebihannya.
 * Kode jenis dan tipenya juga ditampilkan supaya bisa dicari
 * FLAG_LAPORAN aslinya di SQL Server.
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
        BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
            || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')) AS blok_nomor,
        BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')) AS no_ppjb,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_tipe, '')))  AS kd_tipe,
        (tipe.kd_jenis IS NULL)           AS tipe_tidak_ketemu,
        (jenis_bangunan.kd_jenis IS NULL) AS jenis_tidak_ketemu,
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
    LEFT JOIN public.sr_jenis_bangunan AS jenis_bangunan
        ON UPPER(BTRIM(COALESCE(CAST(jenis_bangunan.kd_jenis AS TEXT), '')))
         = UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND NULLIF(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')), '') IS NOT NULL
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
)
SELECT DISTINCT
    dasar.blok_nomor,
    dasar.no_ppjb,
    dasar.kd_jenis,
    dasar.kd_tipe,
    dasar.tipe_tidak_ketemu,
    dasar.jenis_tidak_ketemu,
    dasar.tgl_rencana_filter
FROM dasar
INNER JOIN public.sr_pembeli_ppjb AS pembeli
    ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = dasar.kunci_ppjb
   AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
WHERE dasar.jenis_tidak_ketemu
  AND dasar.tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
  AND dasar.tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
ORDER BY dasar.blok_nomor;


/* ------------------------------------------------------------
 * QUERY 3
 * KEMUNGKINAN LAIN: master yang berisi baris kembar.
 *
 * Kalau satu pasangan kd_jenis dan kd_tipe punya lebih dari satu
 * baris di sr_tipe, atau satu kd_jenis punya lebih dari satu
 * baris di sr_jenis_bangunan, sambungannya akan menggandakan
 * baris laporan. Itu sebab yang berbeda dan penanganannya juga
 * berbeda, jadi perlu dicoret lebih dulu.
 * ------------------------------------------------------------ */
SELECT 'sr_tipe (kd_jenis + kd_tipe)' AS master,
       COUNT(*) AS pasangan_kembar
FROM (
    SELECT 1
    FROM public.sr_tipe
    GROUP BY UPPER(BTRIM(COALESCE(CAST(kd_jenis AS TEXT), ''))),
             UPPER(BTRIM(COALESCE(CAST(kd_tipe AS TEXT), '')))
    HAVING COUNT(*) > 1
) AS d

UNION ALL

SELECT 'sr_jenis_bangunan (kd_jenis)', COUNT(*)
FROM (
    SELECT 1
    FROM public.sr_jenis_bangunan
    GROUP BY UPPER(BTRIM(COALESCE(CAST(kd_jenis AS TEXT), '')))
    HAVING COUNT(*) > 1
) AS d

UNION ALL

SELECT 'sr_nasabah (nasabah_id)', COUNT(*)
FROM (
    SELECT 1
    FROM public.sr_nasabah
    GROUP BY BTRIM(CAST(nasabah_id AS TEXT))
    HAVING COUNT(*) > 1
) AS d;


/* ------------------------------------------------------------
 * QUERY 4
 * KEMUNGKINAN KEDUA: batas tanggal akhir yang berbeda artinya.
 *
 * Desktop menyaring dengan:
 *     Isnull(TGL_RENCANA_SB, ...) <= :TGL_AKHIR
 *
 * Kalau TGL_AKHIR dikirim sebagai tanggal tanpa jam, batas itu
 * berarti "sampai pukul 00:00", sehingga baris pada hari yang
 * sama tetapi berjam lebih dari nol justru DIBUANG.
 *
 * Model web menyaring dengan:
 *     tgl_rencana_filter < :tgl_akhir + 1 hari
 *
 * yang berarti "sampai akhir hari", jadi lebih longgar satu hari
 * penuh. Selisihnya baru terasa kalau kolom tanggalnya menyimpan
 * jam, bukan tengah malam.
 *
 * Kalau kolom berjam pada hari terakhir ada isinya, itu sebab
 * tambahan yang berdiri sendiri dan harus dibereskan terpisah.
 * ------------------------------------------------------------ */
WITH dasar AS (
    SELECT
        COALESCE(
            ppjb.tgl_rencana_sb,
            ppjb.tgl_ppjb + (COALESCE(ppjb.waktu, 0) * INTERVAL '1 month')
        ) AS tgl_rencana_filter
    FROM public.sr_ppjb AS ppjb
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(ppjb.stok_id AS TEXT))
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL
)
SELECT
    COUNT(*) FILTER (
        WHERE tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
          AND tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
    ) AS cara_web_sampai_akhir_hari,
    COUNT(*) FILTER (
        WHERE tgl_rencana_filter >= CAST('2023-07-01' AS DATE)
          AND tgl_rencana_filter <= CAST('2026-09-22' AS TIMESTAMP)
    ) AS cara_desktop_sampai_tengah_malam,
    COUNT(*) FILTER (
        WHERE tgl_rencana_filter >  CAST('2026-09-22' AS TIMESTAMP)
          AND tgl_rencana_filter <  CAST('2026-09-22' AS DATE) + INTERVAL '1 day'
    ) AS hanya_ikut_di_cara_web,
    COUNT(*) FILTER (WHERE tgl_rencana_filter::TIME <> TIME '00:00:00')
                                                   AS baris_yang_tanggalnya_berjam
FROM dasar;
