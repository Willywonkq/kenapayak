/* ============================================================
 * MENYANDINGKAN SEBARAN WEB DAN DESKTOP
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * Pasangannya: sqlserver_rencana_st_sandingkan_sebaran.sql
 *
 * ------------------------------------------------------------
 * APA YANG SUDAH TERBUKTI DAN APA YANG SUDAH MATI
 *
 *   web       1.519 baris, 1.519 PPJB berbeda
 *   desktop   1.518 baris
 *
 * Dugaan penggandaan MATI. Ternyata 1.519 baris itu berasal
 * dari 1.519 PPJB yang berbeda, masing-masing berpembeli aktif
 * tepat satu. Tidak ada kunci kembar sama sekali, baik di
 * sr_stok, sr_ppjb, sr_tipe, sr_jenis_bangunan, maupun
 * sr_nasabah. Jadi tidak ada baris yang tergandakan.
 *
 * Dugaan nasabah wajib ada juga MATI. Di SQL Server hasilnya
 * 1.518 dan 1.518, artinya di sana tidak ada satu pun pembeli
 * yang nasabahnya hilang.
 *
 * Kesimpulannya tinggal satu: ADA SATU PPJB yang dimuat web dan
 * tidak dimuat desktop. Bukan soal syarat, bukan soal kembar,
 * melainkan satu baris yang memang berbeda isinya di antara dua
 * basis data itu.
 *
 * ------------------------------------------------------------
 * CARA MENCARINYA
 *
 * Menyandingkan 1.519 baris satu per satu jelas tidak masuk
 * akal. Jadi keduanya dihitung per kelompok, lalu kelompok yang
 * selisihnya satu itulah yang ditelusuri lebih lanjut.
 *
 * Tiga sudut sekaligus, dan barisnya duduk di persimpangan
 * ketiganya:
 *   blok         dua huruf pertama
 *   jenis        kode jenis bangunan
 *   tahun_ppjb   tahun tanggal PPJB
 *
 * Jalankan berkas ini dan pasangannya, lalu bandingkan.
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
        UPPER(BTRIM(COALESCE(to_jsonb(stok) ->> k.kol_jenis, ''))) AS kd_jenis,
        ppjb.tgl_ppjb                                              AS tgl_ppjb,
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
SELECT 'awal_blok' AS dimensi, SUBSTRING(blok FROM 1 FOR 2) AS nilai, COUNT(*) AS jumlah
FROM baris GROUP BY 1, 2
UNION ALL
SELECT 'jenis', kd_jenis, COUNT(*)
FROM baris GROUP BY 1, 2
UNION ALL
SELECT 'tahun_ppjb', TO_CHAR(tgl_ppjb, 'YYYY'), COUNT(*)
FROM baris GROUP BY 1, 2
UNION ALL
SELECT 'JUMLAH', '(semua)', COUNT(*)
FROM baris
ORDER BY 1, 2;
