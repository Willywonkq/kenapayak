/* ============================================================
 * CORONG DAFTAR UNDANGAN SURAT RUMAH, JENIS "UNDANGAN SERAH
 * TERIMA", UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti di layar:
 *   UNIT         : SBKS
 *   BLOK         : kosong, tidak menyaring
 *   CLUSTER      : kosong, semua sektor
 *   PERIODE      : 01-07-2023 s/d 21-09-2026
 *   JENIS REPORT : Undangan Serah Terima
 *   tanpa centang "Belum Diundang"
 *
 * Yang sudah diketahui dari layar:
 *   desktop  1.293 baris
 *   web        396 baris
 *
 * Bedanya dengan jenis Undangan PPJB kemarin:
 *
 *   tabel sumbernya lain, sr_undangan_st, bukan
 *   sr_undangan_ppjb, dan jenis ini TIDAK memakai penyaring
 *   jenis_surat sama sekali. Jadi tahapnya satu lebih sedikit.
 *
 * Kelihatannya sebabnya sama dengan kemarin, yaitu sr_nasabah
 * yang tidak lengkap. Baris terakhir yang tampil di web ternyata
 * baris yang sama dengan nomor 1287 di desktop, jadi daftarnya
 * bukan terpotong di ujung melainkan menipis di sepanjang jalan,
 * dan itu memang ciri kehilangan per baris.
 *
 * Tetapi tabel sumbernya berbeda, jadi itu masih dugaan. Corong
 * ini yang memastikan, bukan saya.
 *
 * Pasangannya: sqlserver_undangan_st_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG UTAMA.
 *
 * Kalau tahap 4 dan 5 keluar 1.293 sama seperti desktop, lalu
 * jatuh ke 396 di tahap 6, berarti sebabnya persis sama dengan
 * kemarin dan tidak ada yang perlu diubah pada model.
 *
 * Kalau sudah berbeda sebelum tahap 6, berarti ada persoalan
 * lain yang khusus jenis ini, dan itu urusan saya.
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
        surat.tgl_surat,
        CASE
            WHEN BTRIM(CAST(surat.ppjb_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(surat.ppjb_id AS TEXT))
            ELSE (SELECT awalan FROM awalan_unit)
                 || BTRIM(CAST(surat.ppjb_id AS TEXT))
        END AS kunci_ppjb
    FROM public.sr_undangan_st AS surat
),
t2 AS (
    SELECT * FROM t1
    WHERE tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
),
t3 AS (
    SELECT t2.*, ppjb_aktif.kunci_stok
    FROM t2
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = t2.kunci_ppjb
),
t4 AS (
    SELECT t3.*, stok.blok, stok.nomor
    FROM t3
    INNER JOIN stok_terpilih AS stok ON stok.kunci_stok = t3.kunci_stok
),
t5 AS (
    SELECT t4.* FROM t4
    WHERE EXISTS (
        SELECT 1
        FROM public.sr_pembeli_ppjb AS pembeli
        WHERE BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t4.kunci_ppjb
          AND UPPER(BTRIM(COALESCE(
                  CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    )
),
t6 AS (
    SELECT t5.* FROM t5
    WHERE EXISTS (
        SELECT 1
        FROM public.sr_pembeli_ppjb AS pembeli
        INNER JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
        WHERE BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t5.kunci_ppjb
          AND UPPER(BTRIM(COALESCE(
                  CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    )
),
t7 AS (
    SELECT * FROM t6
    WHERE blok IS NOT NULL AND nomor IS NOT NULL
),
t8 AS (
    SELECT t7.*, nasabah.nama
    FROM t7
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t7.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(
               CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
)
            SELECT 1 AS urut, 'Tahap 1  seluruh sr_undangan_st'          AS tahap, COUNT(*) AS baris FROM t1
UNION ALL   SELECT 2,         'Tahap 2  + rentang tgl_surat',                      COUNT(*) FROM t2
UNION ALL   SELECT 3,         'Tahap 3  + PPJB aktif dan bukan turunan',           COUNT(*) FROM t3
UNION ALL   SELECT 4,         'Tahap 4  + stok SBKS yang aktif',                   COUNT(*) FROM t4
UNION ALL   SELECT 5,         'Tahap 5  + ada pembeli aktif',                      COUNT(*) FROM t5
UNION ALL   SELECT 6,         'Tahap 6  + pembelinya ketemu di sr_nasabah',        COUNT(*) FROM t6
UNION ALL   SELECT 7,         'Tahap 7  + blok dan nomor terisi',                  COUNT(*) FROM t7
UNION ALL   SELECT 8,         'Tahap 8  digandakan pembeli = YANG TAMPIL',         COUNT(*) FROM t8
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN PER TAHUN, dengan sebab gugurnya dipisah.
 *
 * Kalau pola menanjaknya sama seperti jenis Undangan PPJB
 * kemarin, yaitu 2023 hampir utuh lalu makin ke belakang makin
 * habis, itu tanda tangan langit-langit nomor nasabah yang
 * sudah ditemukan, bukan persoalan baru.
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
    FROM public.sr_undangan_st AS s
    WHERE s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
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
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = surat.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_pembeli,
        EXISTS (
            SELECT 1
            FROM public.sr_pembeli_ppjb AS p
            INNER JOIN public.sr_nasabah AS n
                ON BTRIM(CAST(n.nasabah_id AS TEXT))
                 = BTRIM(CAST(p.nasabah_id AS TEXT))
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = surat.kunci_ppjb
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
        ) AS ada_nasabah
    FROM surat
    INNER JOIN ppjb_aktif ON ppjb_aktif.kunci_ppjb = surat.kunci_ppjb
    INNER JOIN stok_sbks AS stok ON stok.kunci_stok = ppjb_aktif.kunci_stok
)
SELECT
    EXTRACT(YEAR FROM tgl_surat)::INT AS tahun,
    COUNT(*)                                    AS surat_sbks,
    COUNT(*) FILTER (WHERE ada_pembeli)         AS ada_pembeli,
    COUNT(*) FILTER (WHERE ada_nasabah)         AS ada_nasabah,
    COUNT(*) FILTER (WHERE ada_pembeli
                       AND NOT ada_nasabah)     AS pembeli_tanpa_nasabah,
    COUNT(*) FILTER (WHERE flag_aktif <> 'A')   AS stok_tidak_aktif,
    COUNT(*) FILTER (WHERE ada_nasabah
                       AND flag_aktif = 'A'
                       AND blok IS NOT NULL
                       AND nomor IS NOT NULL)   AS lolos_semua
FROM punya_sbks
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * KEMUTAKHIRAN sr_undangan_st, untuk memastikan tabel suratnya
 * sendiri memang termigrasi sampai sekarang.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)       AS baris,
    MIN(tgl_surat) AS tgl_paling_awal,
    MAX(tgl_surat) AS tgl_paling_akhir
FROM public.sr_undangan_st;
