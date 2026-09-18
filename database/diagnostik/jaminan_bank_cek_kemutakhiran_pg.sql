/* ============================================================
 * CEK KEMUTAKHIRAN DATA HASIL MIGRASI, SISI POSTGRESQL
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Latar belakang. Untuk filter yang sama persis, aplikasi desktop
 * menampilkan 163 baris sedangkan web menampilkan 8. Corong pada
 * berkas jaminan_bank_uji_filter_sbks.sql menunjukkan penyusutan
 * terjadi di satu tempat saja:
 *
 *   siap tampil (semua tanggal) : 3.732
 *   setelah rentang TGL BANK    :    22     <-- di sini
 *   setelah unit SBKS           :     9
 *
 * Jadi yang perlu dibuktikan bukan logika modelnya, melainkan
 * apakah barisnya memang ada di PostgreSQL. Berkas ini mengukur
 * sampai tanggal berapa data hasil migrasi berhenti.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Sebaran tahun TGL_JAMINAN pada SELURUH sr_jaminan, tanpa
 * menyaring unit dan tanpa menyaring tanggal.
 *
 * Yang dicari: di tahun berapa jumlahnya jatuh mendadak. Tahun
 * setelah titik jatuh itulah yang belum ikut termigrasi.
 * ------------------------------------------------------------ */
SELECT
    COALESCE(CAST(EXTRACT(YEAR FROM tgl_jaminan) AS TEXT), '(KOSONG)') AS tahun,
    COUNT(*) AS baris_semua,
    SUM(CASE WHEN no_jaminan IS NOT NULL
              AND no_lunas IS NULL
              AND no_batal IS NULL
             THEN 1 ELSE 0 END) AS baris_siap_tampil
FROM public.sr_jaminan
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 2
 * Rincian bulanan sejak 2023, untuk menunjuk bulan terakhir yang
 * datanya masih masuk.
 * ------------------------------------------------------------ */
SELECT
    TO_CHAR(tgl_jaminan, 'YYYY-MM') AS bulan,
    COUNT(*) AS baris
FROM public.sr_jaminan
WHERE tgl_jaminan >= DATE '2023-01-01'
  AND tgl_jaminan < DATE '2030-01-01'
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * Tanggal terakhir pada sr_jaminan, dilihat dari beberapa kolom
 * tanggal sekaligus.
 *
 * TGL_ENTRY dan TGL_UPDATE menunjukkan kapan barisnya direkam,
 * bukan kapan kejadiannya. Kalau TGL_ENTRY juga berhenti di titik
 * yang sama, berarti memang penyalinannya yang berhenti, bukan
 * kebetulan tidak ada transaksi.
 * ------------------------------------------------------------ */
SELECT
    'tgl_jaminan'   AS kolom, MIN(tgl_jaminan)::text AS paling_awal,
    MAX(tgl_jaminan)::text AS paling_akhir, COUNT(tgl_jaminan) AS terisi
FROM public.sr_jaminan
UNION ALL
SELECT 'tgl_entry',  MIN(tgl_entry)::text,  MAX(tgl_entry)::text,  COUNT(tgl_entry)
FROM public.sr_jaminan
UNION ALL
SELECT 'tgl_update', MIN(tgl_update)::text, MAX(tgl_update)::text, COUNT(tgl_update)
FROM public.sr_jaminan
UNION ALL
SELECT 'tgl_pengajuan', MIN(tgl_pengajuan)::text, MAX(tgl_pengajuan)::text, COUNT(tgl_pengajuan)
FROM public.sr_jaminan;


/* ------------------------------------------------------------
 * QUERY 4
 * INI YANG PALING MENENTUKAN.
 *
 * Tanggal perekaman terakhir untuk SETIAP tabel hasil migrasi
 * yang punya kolom tgl_entry, diurutkan dari yang paling baru.
 *
 * Cara membacanya:
 *   - Kalau hampir semua tabel berhenti di tanggal yang
 *     berdekatan, berarti seluruh salinan ini diambil sekali pada
 *     tanggal itu dan sejak itu tidak diperbarui. Masalahnya
 *     bukan pada sr_jaminan saja.
 *   - Kalau hanya sebagian tabel yang tertinggal, berarti
 *     migrasinya sebagian, dan tabel yang tertinggal itulah yang
 *     perlu diulang.
 *
 * query_to_xml dipakai supaya satu query bisa membaca banyak
 * tabel tanpa perlu menulisnya satu per satu. Tetap hanya membaca.
 * ------------------------------------------------------------ */
SELECT
    kolom.table_name AS tabel,
    nilai.maks       AS perekaman_terakhir
FROM information_schema.columns AS kolom
CROSS JOIN LATERAL (
    SELECT (xpath(
        '/row/m/text()',
        query_to_xml(
            format('SELECT MAX(%I)::text AS m FROM public.%I',
                   kolom.column_name, kolom.table_name),
            false, true, ''
        )
    ))[1]::text AS maks
) AS nilai
WHERE kolom.table_schema = 'public'
  AND kolom.column_name = 'tgl_entry'
  AND kolom.table_name LIKE 'sr\_%'
  AND nilai.maks IS NOT NULL
ORDER BY nilai.maks DESC
LIMIT 60;


/* ------------------------------------------------------------
 * QUERY 5
 * Pembanding untuk tabel-tabel yang dipakai laporan ini saja,
 * supaya mudah dibaca berdampingan.
 * ------------------------------------------------------------ */
SELECT
    kolom.table_name AS tabel,
    kolom.column_name AS kolom_tanggal,
    nilai.maks       AS paling_akhir
FROM information_schema.columns AS kolom
CROSS JOIN LATERAL (
    SELECT (xpath(
        '/row/m/text()',
        query_to_xml(
            format('SELECT MAX(%I)::text AS m FROM public.%I',
                   kolom.column_name, kolom.table_name),
            false, true, ''
        )
    ))[1]::text AS maks
) AS nilai
WHERE kolom.table_schema = 'public'
  AND kolom.table_name IN (
        'sr_jaminan', 'sr_sertipikat', 'sr_stok', 'sr_ppjb',
        'sr_pembeli_ppjb', 'sr_nasabah', 'sr_akta', 'sr_peralihan',
        'sr_jadwal_angsuran'
      )
  AND kolom.column_name IN ('tgl_entry', 'tgl_update')
  AND nilai.maks IS NOT NULL
ORDER BY kolom.table_name, kolom.column_name;


/* ------------------------------------------------------------
 * QUERY 6
 * DAFTAR TABEL YANG TERTINGGAL, diurutkan dari yang PALING LAMA.
 *
 * QUERY 4 mengurutkan dari yang terbaru dan dibatasi 60 baris,
 * sehingga tabel yang tertinggal justru tidak terlihat. Query ini
 * kebalikannya, dan sekaligus menghitung berapa lama tertinggal.
 *
 * Sudah diketahui sr_jaminan, sr_akta, dan sr_peralihan berhenti
 * pada 26 sampai 27 Februari 2024 sementara tabel lain masih
 * terbarui sampai September 2026. Query ini memastikan apakah ada
 * tabel lain yang ikut tertinggal, supaya pemindahan ulangnya
 * tidak setengah-setengah.
 *
 * Kolom tertinggal_hari dihitung terhadap tabel yang paling
 * mutakhir, bukan terhadap hari ini, supaya jeda yang wajar
 * antara tabel induk dan tabel rincian tidak ikut terhitung.
 * ------------------------------------------------------------ */
WITH terakhir AS (
    SELECT
        kolom.table_name AS tabel,
        nilai.maks       AS perekaman_terakhir
    FROM information_schema.columns AS kolom
    CROSS JOIN LATERAL (
        SELECT (xpath(
            '/row/m/text()',
            query_to_xml(
                format('SELECT MAX(%I)::text AS m FROM public.%I',
                       kolom.column_name, kolom.table_name),
                false, true, ''
            )
        ))[1]::text AS maks
    ) AS nilai
    WHERE kolom.table_schema = 'public'
      AND kolom.column_name = 'tgl_entry'
      AND kolom.table_name LIKE 'sr\_%'
      AND nilai.maks IS NOT NULL
)
SELECT
    tabel,
    perekaman_terakhir,
    DATE_TRUNC('day',
        MAX(CAST(perekaman_terakhir AS TIMESTAMP)) OVER ()
        - CAST(perekaman_terakhir AS TIMESTAMP)
    )::text AS tertinggal_dari_tabel_termutakhir,
    CASE
        WHEN MAX(CAST(perekaman_terakhir AS TIMESTAMP)) OVER ()
             - CAST(perekaman_terakhir AS TIMESTAMP) > INTERVAL '365 days'
        THEN 'TERTINGGAL JAUH, perlu dipindahkan ulang'
        WHEN MAX(CAST(perekaman_terakhir AS TIMESTAMP)) OVER ()
             - CAST(perekaman_terakhir AS TIMESTAMP) > INTERVAL '90 days'
        THEN 'perlu diperiksa'
        ELSE 'wajar'
    END AS penilaian
FROM terakhir
ORDER BY CAST(perekaman_terakhir AS TIMESTAMP) ASC;
