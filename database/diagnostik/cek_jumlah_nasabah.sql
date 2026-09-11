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
