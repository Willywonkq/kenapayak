/* ============================================================
 * BANDINGKAN TAHAP DEMI TAHAP, DAFTAR IMB UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Filter yang ditiru, sama seperti percobaan terakhir:
 *   UNIT      : SBKS
 *   BLOK      : A s/d ZZ
 *   TGL INPUT : 01-01-2020 s/d 31-12-2023
 *
 * Yang sudah diketahui:
 *   web menampilkan       5.596
 *   desktop menampilkan   6.144
 *   selisih                 548
 *
 * Dari 548 itu, 114 sudah terjelaskan sebagai baris sr_imb yang
 * belum termigrasi. Sisanya 434 BELUM terjelaskan, dan hilangnya
 * terjadi setelah tahap stok, yaitu pada penyambungan ke PPJB dan
 * pembeli. Berkas ini mencari di mana persisnya.
 *
 * Pasangannya: sqlserver_imb_bandingkan_tahap_sbks.sql
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG, memakai sambungan yang sama persis dengan model.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1
),
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
      AND stok.blok IS NOT NULL AND stok.nomor IS NOT NULL
),
t2 AS (
    SELECT * FROM public.sr_imb
    WHERE tgl_input >= CAST('2020-01-01' AS TIMESTAMP)
      AND tgl_input <  CAST('2024-01-01' AS TIMESTAMP)
),
t3 AS (
    SELECT t2.*, sertipikat.stok_id
    FROM t2
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE WHEN BTRIM(CAST(t2.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(t2.sertipikat_id AS TEXT))
                ELSE awalan_unit.awalan || BTRIM(CAST(t2.sertipikat_id AS TEXT)) END
    WHERE sertipikat.stok_id IS NOT NULL
),
t4 AS (
    SELECT t3.*, stok.kunci_stok, stok.blok, stok.nomor
    FROM t3
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(t3.stok_id AS TEXT))
),
t5 AS (
    SELECT t4.*, BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM t4
    INNER JOIN public.sr_ppjb AS ppjb
        ON BTRIM(CAST(ppjb.stok_id AS TEXT)) = t4.kunci_stok
       AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
       AND ppjb.parent_id IS NULL
),
t6 AS (
    SELECT t5.*, pembeli.nasabah_id
    FROM t5
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT)) = t5.kunci_ppjb
       AND UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
),
t7 AS (
    SELECT t6.*
    FROM t6
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(t6.nasabah_id AS TEXT))
),
t8 AS (
    SELECT * FROM t7
    WHERE (
            (BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || COALESCE(CAST(nomor AS TEXT), '') >= 'A'
             AND BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || COALESCE(CAST(nomor AS TEXT), '') <= 'ZZ')
            OR (blok >= 'A' AND blok <= 'ZZ')
          )
)
SELECT 2 AS urut, 'Tahap 2  sr_imb dalam rentang tanggal'      AS tahap, COUNT(*) AS baris FROM t2
UNION ALL SELECT 3, 'Tahap 3  + ketemu sertipikatnya',            COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + stok SBKS aktif',                 COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + PPJB aktif bukan turunan',        COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + pembeli aktif',                   COUNT(*) FROM t6
UNION ALL SELECT 7, 'Tahap 7  + nasabahnya ketemu',               COUNT(*) FROM t7
UNION ALL SELECT 8, 'Tahap 8  + saringan blok = YANG TAMPIL',     COUNT(*) FROM t8
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 2
 * Apakah sambungan antar tabel kehilangan baris karena BENTUK
 * nilainya, bukan karena datanya tidak ada.
 *
 * Model menyambung memakai BTRIM(CAST(... AS TEXT)). Kalau salah
 * satu sisi bertipe angka berdesimal, 123 bisa menjadi 123.00 dan
 * sambungannya gagal padahal nilainya sama. Kolom cocok_angka
 * membandingkan sebagai angka; kalau angkanya lebih besar daripada
 * cocok_teks, berarti modelnya yang perlu diperbaiki, bukan
 * datanya yang kurang.
 * ------------------------------------------------------------ */
SELECT
    'sr_pembeli_ppjb -> sr_nasabah' AS sambungan,
    COUNT(*) AS baris_pembeli_aktif,
    SUM(CASE WHEN ada_teks THEN 1 ELSE 0 END)  AS cocok_teks,
    SUM(CASE WHEN ada_angka THEN 1 ELSE 0 END) AS cocok_angka
FROM (
    SELECT
        EXISTS (SELECT 1 FROM public.sr_nasabah AS n
                 WHERE BTRIM(CAST(n.nasabah_id AS TEXT))
                     = BTRIM(CAST(p.nasabah_id AS TEXT))) AS ada_teks,
        EXISTS (SELECT 1 FROM public.sr_nasabah AS n
                 WHERE n.nasabah_id = p.nasabah_id)       AS ada_angka
    FROM public.sr_pembeli_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
) AS d
UNION ALL
SELECT
    'sr_pembeli_ppjb -> sr_ppjb',
    COUNT(*),
    SUM(CASE WHEN ada_teks THEN 1 ELSE 0 END),
    SUM(CASE WHEN ada_teks THEN 1 ELSE 0 END)
FROM (
    SELECT EXISTS (SELECT 1 FROM public.sr_ppjb AS q
                    WHERE BTRIM(CAST(q.ppjb_id AS TEXT))
                        = BTRIM(CAST(p.ppjb_id AS TEXT))) AS ada_teks
    FROM public.sr_pembeli_ppjb AS p
    WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
) AS d;


/* ------------------------------------------------------------
 * QUERY 3
 * Stok SBKS yang punya baris IMB pada rentang itu tetapi TIDAK
 * punya PPJB aktif bukan turunan, atau tidak punya pembeli aktif.
 *
 * Inilah daftar penyebab hilangnya baris di tahap 5 dan 6.
 * Kalau jumlahnya besar, berarti sr_ppjb atau sr_pembeli_ppjb yang
 * belum lengkap, bukan modelnya.
 * ------------------------------------------------------------ */
WITH awalan_unit AS (
    SELECT REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS awalan
    FROM public.sr_stok
    WHERE stok_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
    GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1
),
stok_ber_imb AS (
    SELECT DISTINCT BTRIM(CAST(sertipikat.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_imb AS imb
    CROSS JOIN awalan_unit
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE WHEN BTRIM(CAST(imb.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(imb.sertipikat_id AS TEXT))
                ELSE awalan_unit.awalan || BTRIM(CAST(imb.sertipikat_id AS TEXT)) END
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT))
         = BTRIM(CAST(sertipikat.stok_id AS TEXT))
    WHERE imb.tgl_input >= CAST('2020-01-01' AS TIMESTAMP)
      AND imb.tgl_input <  CAST('2024-01-01' AS TIMESTAMP)
      AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
)
SELECT
    CASE
        WHEN NOT EXISTS (
            SELECT 1 FROM public.sr_ppjb AS q
            WHERE BTRIM(CAST(q.stok_id AS TEXT)) = s.kunci_stok
        ) THEN '1. tidak punya PPJB sama sekali'
        WHEN NOT EXISTS (
            SELECT 1 FROM public.sr_ppjb AS q
            WHERE BTRIM(CAST(q.stok_id AS TEXT)) = s.kunci_stok
              AND UPPER(BTRIM(COALESCE(CAST(q.flag_aktif AS TEXT), ''))) = 'A'
        ) THEN '2. punya PPJB tetapi tidak ada yang aktif'
        WHEN NOT EXISTS (
            SELECT 1 FROM public.sr_ppjb AS q
            WHERE BTRIM(CAST(q.stok_id AS TEXT)) = s.kunci_stok
              AND UPPER(BTRIM(COALESCE(CAST(q.flag_aktif AS TEXT), ''))) = 'A'
              AND q.parent_id IS NULL
        ) THEN '3. PPJB aktif ada tetapi semuanya turunan'
        WHEN NOT EXISTS (
            SELECT 1
            FROM public.sr_ppjb AS q
            INNER JOIN public.sr_pembeli_ppjb AS p
                ON BTRIM(CAST(p.ppjb_id AS TEXT)) = BTRIM(CAST(q.ppjb_id AS TEXT))
               AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
            WHERE BTRIM(CAST(q.stok_id AS TEXT)) = s.kunci_stok
              AND UPPER(BTRIM(COALESCE(CAST(q.flag_aktif AS TEXT), ''))) = 'A'
              AND q.parent_id IS NULL
        ) THEN '4. PPJB induk aktif ada tetapi tidak ada pembeli aktif'
        ELSE '5. lengkap, ikut tampil'
    END AS keadaan,
    COUNT(*) AS jumlah_stok
FROM stok_ber_imb AS s
GROUP BY 1
ORDER BY 1;
