/* ============================================================
 * CEK KELENGKAPAN sr_nasabah
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Latar belakang. Corong Daftar IMB unit SBKS menunjukkan tahap
 * yang menghabiskan baris bukan tahap IMB, melainkan tahap
 * penyambungan ke nasabah:
 *
 *   tahap            PostgreSQL   SQL Server   selisih
 *   + pembeli aktif       6.029        6.144       115
 *   + nasabah ketemu      5.596        6.144       548
 *
 * Jadi 433 baris hilang HANYA karena nasabahnya tidak ada.
 * Diukur pada seluruh tabel, 28.249 dari 62.326 baris pembeli
 * aktif menunjuk ke nasabah yang tidak ada, yaitu 45 persen.
 *
 * Sudah dipastikan ini BUKAN soal bentuk nilai: perbandingan
 * sebagai teks dan sebagai angka sama-sama menghasilkan 34.077.
 *
 * Temuan ini penting karena sr_nasabah dipakai hampir semua
 * laporan yang menampilkan nama pembeli, bukan Daftar IMB saja.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Bentuk dan rentang nilai nasabah_id pada kedua tabel.
 *
 * Memastikan sekali lagi bahwa persoalannya bukan awalan yang
 * terbuang seperti pada kolom kunci lain.
 * ------------------------------------------------------------ */
SELECT 'sr_nasabah' AS tabel, 'nasabah_id' AS kolom,
       COUNT(*) AS baris,
       SUM(CASE WHEN BTRIM(CAST(nasabah_id AS TEXT)) ~ '^[0-9]+$'
                THEN 1 ELSE 0 END) AS angka_polos,
       MIN(LENGTH(BTRIM(CAST(nasabah_id AS TEXT)))) AS panjang_terpendek,
       MAX(LENGTH(BTRIM(CAST(nasabah_id AS TEXT)))) AS panjang_terpanjang
FROM public.sr_nasabah
WHERE nasabah_id IS NOT NULL
UNION ALL
SELECT 'sr_pembeli_ppjb', 'nasabah_id',
       COUNT(*),
       SUM(CASE WHEN BTRIM(CAST(nasabah_id AS TEXT)) ~ '^[0-9]+$'
                THEN 1 ELSE 0 END),
       MIN(LENGTH(BTRIM(CAST(nasabah_id AS TEXT)))),
       MAX(LENGTH(BTRIM(CAST(nasabah_id AS TEXT))))
FROM public.sr_pembeli_ppjb
WHERE nasabah_id IS NOT NULL;


/* ------------------------------------------------------------
 * QUERY 2
 * Nasabah yang dirujuk tetapi tidak ada, dikelompokkan per juta.
 *
 * Kalau yang hilang menumpuk pada rentang nomor tertentu, itu
 * petunjuk kuat bahwa sebagian sumber tidak ikut dipindahkan,
 * bukan hilang sana sini secara acak.
 * ------------------------------------------------------------ */
WITH dirujuk AS (
    SELECT DISTINCT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_pembeli_ppjb
    WHERE nasabah_id IS NOT NULL
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), ''))) = 'Y'
),
punya AS (
    SELECT BTRIM(CAST(nasabah_id AS TEXT)) AS kunci
    FROM public.sr_nasabah
    WHERE nasabah_id IS NOT NULL
)
SELECT
    CASE WHEN dirujuk.kunci ~ '^[0-9]+$'
         THEN (dirujuk.kunci::numeric / 1000000)::int
         ELSE NULL END AS juta_ke,
    COUNT(*) AS nasabah_dirujuk,
    SUM(CASE WHEN punya.kunci IS NULL THEN 1 ELSE 0 END) AS tidak_ada,
    ROUND(100.0 * SUM(CASE WHEN punya.kunci IS NULL THEN 1 ELSE 0 END)
          / NULLIF(COUNT(*), 0), 1) AS persen_hilang
FROM dirujuk
LEFT JOIN punya ON punya.kunci = dirujuk.kunci
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * Dampaknya pada laporan lain yang sudah dimigrasi.
 *
 * Semua laporan berikut menyambung ke sr_nasabah lewat
 * sr_pembeli_ppjb, jadi kekurangan yang sama juga mengurangi
 * barisnya. Angka di bawah memperkirakan seberapa besar.
 * ------------------------------------------------------------ */
SELECT
    'baris pembeli aktif' AS keterangan,
    COUNT(*) AS jumlah
FROM public.sr_pembeli_ppjb AS p
WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
UNION ALL
SELECT
    'di antaranya nasabahnya ADA',
    COUNT(*)
FROM public.sr_pembeli_ppjb AS p
WHERE UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
  AND EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
        WHERE BTRIM(CAST(n.nasabah_id AS TEXT)) = BTRIM(CAST(p.nasabah_id AS TEXT))
      )
UNION ALL
SELECT
    'PPJB induk aktif yang kehilangan SELURUH pembelinya',
    COUNT(*)
FROM (
    SELECT BTRIM(CAST(q.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS q
    WHERE UPPER(BTRIM(COALESCE(CAST(q.flag_aktif AS TEXT), ''))) = 'A'
      AND q.parent_id IS NULL
      AND EXISTS (
            SELECT 1 FROM public.sr_pembeli_ppjb AS p
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = BTRIM(CAST(q.ppjb_id AS TEXT))
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
          )
      AND NOT EXISTS (
            SELECT 1
            FROM public.sr_pembeli_ppjb AS p
            INNER JOIN public.sr_nasabah AS n
                ON BTRIM(CAST(n.nasabah_id AS TEXT))
                 = BTRIM(CAST(p.nasabah_id AS TEXT))
            WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) = BTRIM(CAST(q.ppjb_id AS TEXT))
              AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS TEXT), ''))) = 'Y'
          )
) AS d;
