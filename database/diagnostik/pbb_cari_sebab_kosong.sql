/* ============================================================
 * DAFTAR PBB TIBA TIBA KOSONG, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * DDL, maupun CREATE INDEX. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * YANG SUDAH DIPASTIKAN SEBELUM MENDUGA APA PUN
 *
 * dftr_pbb_m.php TIDAK DISENTUH. Perubahan terakhirnya 22
 * September, dan itu pun hanya pembuangan komentar. Viewnya
 * juga sama, terakhir berubah 22 September.
 *
 * Sejak 23 September berkas yang berubah hanya:
 *
 *     rekap_ajb_m.php
 *     rekap_estimasi_biaya_ajb_m.php
 *     dftr_jaminan_bank_m.php
 *     dftr_undangan_surat_rumah_m.php
 *     database/diagnostik/...
 *
 * Tidak satu pun dipakai bersama oleh Daftar PBB. Model PBB
 * memegang salinan awalanUnit() dan pastikanKeluargaAda()-nya
 * sendiri. Jadi kodenya hari ini sama persis dengan kode yang
 * kemarin masih mengeluarkan data.
 *
 * Artinya yang berubah ADA DI DATANYA.
 *
 * ------------------------------------------------------------
 * SATU PETUNJUK PENTING DARI LAYAR
 *
 * Yang muncul "Data tidak ditemukan", BUKAN pesan penolakan.
 * Itu berarti pastikanKeluargaAda() LOLOS, jadi sr_pbb masih
 * memuat baris yang pasti milik keluarga DBPSA-. Tabelnya
 * tidak kosong; yang terjadi barisnya tersaring habis.
 *
 * Model menyaring dengan tiga syarat berurutan:
 *
 *     pbb.tahun_pbb  >= 2000  AND <= 2026
 *     pbb.tgl_input  >= '2023-07-01'
 *     pbb.tgl_input  <  '2026-09-25'
 *
 * TERSANGKA UTAMA: tgl_input. Kalau kolom itu menjadi kosong
 * atau tanggalnya bergeser waktu sr_pbb dipindahkan ulang,
 * SELURUH baris terbuang, sebab NULL tidak pernah lolos
 * perbandingan >= maupun <.
 *
 * ------------------------------------------------------------
 * PEMERIKSAAN CEPAT DI APLIKASI, SEBELUM MENJALANKAN INI
 *
 * Buka Daftar PBB lagi, lalu KOSONGKAN saringan Tgl Input
 * (atau lebarkan ke 01-01-1990 sampai 31-12-2030). Kalau
 * datanya muncul, dugaan di atas langsung terbukti dan KUERI 3
 * di bawah akan menunjukkan angkanya.
 * ============================================================ */


/* ------------------------------------------------------------
 * KUERI 1. APAKAH sr_pbb BARU DIISI ULANG
 *
 * n_tup_del besar bersama n_tup_ins besar adalah ciri tabel
 * dikosongkan lalu diisi lagi. Kalau TRUNCATE yang dipakai,
 * n_tup_del tetap nol, jadi bandingkan juga n_tup_ins dengan
 * jumlah baris hidupnya: kalau kelipatan, tabelnya pernah
 * dimuat lebih dari sekali.
 * ------------------------------------------------------------ */
SELECT
    relname                AS "TABEL",
    n_live_tup             AS "BARIS_HIDUP",
    n_tup_ins              AS "PERNAH_DISISIPKAN",
    n_tup_upd              AS "PERNAH_DIUBAH",
    n_tup_del              AS "PERNAH_DIHAPUS",
    last_vacuum            AS "VACUUM_TERAKHIR",
    last_autovacuum        AS "AUTOVACUUM_TERAKHIR",
    last_analyze           AS "ANALYZE_TERAKHIR",
    last_autoanalyze       AS "AUTOANALYZE_TERAKHIR"
FROM pg_stat_user_tables
WHERE schemaname = 'public'
  AND relname IN ('sr_pbb', 'sr_imb', 'sr_sertipikat', 'sr_stok')
ORDER BY relname;


/* ------------------------------------------------------------
 * KUERI 2. KEADAAN KOLOM tgl_input
 *
 * Inilah pemeriksaan yang menentukan. Kalau TGL_INPUT_KOSONG
 * hampir sama dengan SELURUH_BARIS, sebabnya sudah ketemu.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                                            AS "SELURUH_BARIS",
    COUNT(*) FILTER (WHERE pbb.tgl_input IS NULL)       AS "TGL_INPUT_KOSONG",
    COUNT(*) FILTER (WHERE pbb.tgl_input IS NOT NULL)   AS "TGL_INPUT_TERISI",
    MIN(pbb.tgl_input)                                  AS "TERAWAL",
    MAX(pbb.tgl_input)                                  AS "TERAKHIR"
FROM public.sr_pbb AS pbb;


/* ------------------------------------------------------------
 * KUERI 3. CORONG SARINGAN, SATU DEMI SATU
 *
 * Tahap mana yang menghabiskan barisnya akan langsung terlihat.
 * ------------------------------------------------------------ */
SELECT 1 AS urut, 'TAHAP 1  sr_pbb seluruhnya' AS tahap,
       (SELECT COUNT(*) FROM public.sr_pbb) AS baris
UNION ALL
SELECT 2, 'TAHAP 2  tahun_pbb 2000 sampai 2026',
       (SELECT COUNT(*) FROM public.sr_pbb
        WHERE tahun_pbb >= 2000 AND tahun_pbb <= 2026)
UNION ALL
SELECT 3, 'TAHAP 3  + tgl_input terisi',
       (SELECT COUNT(*) FROM public.sr_pbb
        WHERE tahun_pbb >= 2000 AND tahun_pbb <= 2026
          AND tgl_input IS NOT NULL)
UNION ALL
SELECT 4, 'TAHAP 4  + tgl_input dalam rentang  <- saringan layar',
       (SELECT COUNT(*) FROM public.sr_pbb
        WHERE tahun_pbb >= 2000 AND tahun_pbb <= 2026
          AND tgl_input >= CAST('2023-07-01' AS TIMESTAMP)
          AND tgl_input <  CAST('2026-09-25' AS TIMESTAMP))
ORDER BY urut;


/* ------------------------------------------------------------
 * KUERI 4. SEBARAN TAHUN tgl_input DAN tahun_pbb
 *
 * Kalau tgl_input berhenti pada tahun tertentu sementara
 * tahun_pbb tetap sampai 2023, berarti kolom tanggalnya yang
 * tertinggal, bukan barisnya.
 * ------------------------------------------------------------ */
SELECT
    'tgl_input'                              AS "KOLOM",
    EXTRACT(YEAR FROM pbb.tgl_input)::int    AS "TAHUN",
    COUNT(*)                                 AS "BARIS"
FROM public.sr_pbb AS pbb
WHERE pbb.tgl_input IS NOT NULL
GROUP BY 1, 2
UNION ALL
SELECT 'tahun_pbb', pbb.tahun_pbb::int, COUNT(*)
FROM public.sr_pbb AS pbb
WHERE pbb.tahun_pbb IS NOT NULL
GROUP BY 1, 2
ORDER BY 1, 2 DESC;


/* ------------------------------------------------------------
 * KUERI 5. AWALAN YANG DIPILIH MODEL UNTUK SBKS
 *
 * Ini salinan persis awalanUnit(). Hasilnya dipakai menyusun
 * ulang kunci sertipikat pada sr_pbb. Kalau awalannya berubah,
 * sambungannya tidak ketemu dan laporannya kosong TANPA pesan
 * apa pun, persis yang terjadi sekarang.
 *
 * Ganti 'SBKS' kalau unitnya lain.
 * ------------------------------------------------------------ */
SELECT
    REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '') AS "AWALAN",
    COUNT(*)                                                    AS "JUMLAH"
FROM public.sr_stok
WHERE stok_id IS NOT NULL
  AND UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
GROUP BY 1
ORDER BY 2 DESC, 1;


/* ------------------------------------------------------------
 * KUERI 6. CORONG SAMPAI STOK SBKS
 *
 * Meniru sambungan model: kunci sr_pbb disusun ulang dengan
 * awalan unit, disambungkan ke sr_sertipikat, lalu ke sr_stok.
 * ------------------------------------------------------------ */
WITH pbb_kunci AS (
    SELECT
        CASE
            WHEN BTRIM(CAST(pbb.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(pbb.sertipikat_id AS TEXT))
            ELSE 'DBPSA-' || BTRIM(CAST(pbb.sertipikat_id AS TEXT))
        END           AS kunci_sertipikat,
        pbb.tahun_pbb AS tahun_pbb,
        pbb.tgl_input AS tgl_input
    FROM public.sr_pbb AS pbb
),
sertipikat AS (
    SELECT
        BTRIM(CAST(x.sertipikat_id AS TEXT)) AS kunci_sertipikat,
        BTRIM(CAST(x.stok_id AS TEXT))       AS kunci_stok
    FROM public.sr_sertipikat AS x
),
stok_unit AS (
    SELECT BTRIM(CAST(s.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS s
    WHERE UPPER(BTRIM(COALESCE(CAST(s.kd_perusahaan AS TEXT), ''))) = 'SBKS'
)
SELECT 1 AS urut, 'TAHAP A  sr_pbb seluruhnya' AS tahap,
       (SELECT COUNT(*) FROM pbb_kunci) AS baris
UNION ALL
SELECT 2, 'TAHAP B  kuncinya ketemu di sr_sertipikat',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat)
UNION ALL
SELECT 3, 'TAHAP C  stoknya milik SBKS',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat
        INNER JOIN stok_unit AS u ON u.kunci_stok = s.kunci_stok)
UNION ALL
SELECT 4, 'TAHAP D  + tahun_pbb dan tgl_input  <- yang tampil',
       (SELECT COUNT(*) FROM pbb_kunci AS p
        INNER JOIN sertipikat AS s
            ON s.kunci_sertipikat = p.kunci_sertipikat
        INNER JOIN stok_unit AS u ON u.kunci_stok = s.kunci_stok
        WHERE p.tahun_pbb >= 2000 AND p.tahun_pbb <= 2026
          AND p.tgl_input >= CAST('2023-07-01' AS TIMESTAMP)
          AND p.tgl_input <  CAST('2026-09-25' AS TIMESTAMP))
ORDER BY urut;
