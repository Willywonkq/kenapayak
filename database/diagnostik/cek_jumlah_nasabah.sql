/*
 * DIAGNOSTIK — dijalankan di POSTGRESQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE, DROP,
 * maupun ALTER. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pasangan dari database/diagnostik/sqlserver_cek_jumlah_nasabah.sql.
 * Jalankan keduanya lalu bandingkan angkanya.
 */


/* =====================================================================
 * QUERY 1 — Jumlah baris yang sebenarnya
 * ===================================================================== */
SELECT COUNT(*) AS jumlah_baris
FROM public.sr_nasabah;


/* =====================================================================
 * QUERY 2 — Jumlah baris versi cepat, dari katalog
 *
 * Dibaca dari statistik perencana, jadi tidak memindai tabel. Angkanya
 * perkiraan dan ikut berubah sesudah ANALYZE dijalankan.
 * ===================================================================== */
SELECT
    relname                                   AS tabel,
    reltuples::bigint                         AS perkiraan_jumlah_baris,
    PG_SIZE_PRETTY(PG_TOTAL_RELATION_SIZE(oid)) AS ukuran
FROM pg_class
WHERE relname = 'sr_nasabah'
  AND relkind = 'r';


/* =====================================================================
 * QUERY 3 — Apakah ada nasabah_id ganda
 * ===================================================================== */
SELECT
    COUNT(*)                                                  AS jumlah_baris,
    COUNT(DISTINCT BTRIM(CAST(nasabah_id AS text)))           AS jumlah_nasabah_id,
    COUNT(*) - COUNT(DISTINCT BTRIM(CAST(nasabah_id AS text))) AS selisih
FROM public.sr_nasabah;


/* =====================================================================
 * QUERY 4 — Berapa yang benar-benar dipakai sr_pembeli_ppjb
 *
 * Pembandingnya QUERY 4 pada berkas SQL Server.
 * ===================================================================== */
SELECT
    (SELECT COUNT(*) FROM public.sr_pembeli_ppjb)              AS pembeli_semua,
    (SELECT COUNT(*) FROM public.sr_pembeli_ppjb AS pb
      WHERE EXISTS (
        SELECT 1 FROM public.sr_nasabah AS n
         WHERE BTRIM(CAST(pb.nasabah_id AS text)) = BTRIM(CAST(n.nasabah_id AS text))
      ))                                                       AS pembeli_punya_nasabah,
    (SELECT COUNT(*) FROM public.sr_nasabah)                   AS nasabah_semua;


/* =====================================================================
 * ==== HASIL PENGUKURAN ====
 *
 * QUERY 4 pada kedua sisi:
 *
 *                              SQL Server    PostgreSQL     selisih
 *     baris pembeli_ppjb           38.821        63.447     +24.626
 *     punya pasangan nasabah       38.821        34.560      -4.261
 *     baris nasabah                46.818        42.464      -4.354
 *
 *     tingkat kecocokan           100,0%         54,5%
 *
 * Di SQL Server seluruh baris PEMBELI_PPJB punya pasangan di NASABAH. Di
 * PostgreSQL hanya 34.560 dari 63.447, jadi 28.887 baris kehilangan nama
 * pembelinya. Itulah sebabnya kolom Nama Pembeli menjadi tanda hubung pada
 * sebagian laporan.
 *
 * Angka 28.887 itu terurai rapi menjadi dua sebab yang berbeda:
 *
 *     24.626  baris sr_pembeli_ppjb yang berlebih dibanding SQL Server,
 *             dan tidak satu pun punya pasangan di sr_nasabah
 *      4.261  baris yang seharusnya punya pasangan, tetapi baris nasabahnya
 *             belum tersalin. Sejalan dengan sr_nasabah yang kurang 4.354
 *     ------
 *     28.887
 *
 * Catatan untuk laporan yang sudah dikerjakan: kelebihan 24.626 baris itu
 * TIDAK melipatgandakan hasil Daftar Serah Terima maupun Daftar Rencana
 * Serah Terima. Pada kedua laporan itu sudah terukur jumlah_baris selalu
 * sama dengan jumlah_ppjb di setiap tahun, jadi dalam cakupan DTSA dengan
 * pembeli aktif dan di dalam rentang tanggal, tidak ada PPJB yang punya
 * lebih dari satu pembeli. Kelebihan itu berada di luar cakupan laporan.
 *
 * Yang terpengaruh hanya kolom Nama Pembeli, dan penyebabnya data, bukan
 * kode. Sambungan ke sr_nasabah pada model sudah diperbaiki sehingga nama
 * muncul untuk baris yang pasangannya memang ada.
 * ===================================================================== */
