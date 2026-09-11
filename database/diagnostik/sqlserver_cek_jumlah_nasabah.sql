/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pilih dulu database SRIS-nya, jangan master. Kalau muncul galat
 * "Invalid object name 'NASABAH'", berarti databasenya belum dipilih.
 * Pada judul aplikasi desktop tertulis SRIS_PUSAT.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * QUERY 1 — Jumlah baris yang sebenarnya
 *
 * Cara paling langsung. Untuk tabel sebesar NASABAH masih cepat, tetapi
 * tetap membaca seluruh tabel.
 * ===================================================================== */
SELECT COUNT(*) AS jumlah_baris
FROM NASABAH WITH (NOLOCK);


/* =====================================================================
 * QUERY 2 — Jumlah baris versi cepat, dari katalog
 *
 * Dibaca dari statistik penyimpanan, jadi tidak memindai tabel sama sekali.
 * Angkanya hampir selalu tepat, dan berguna kalau tabelnya besar atau
 * servernya sedang sibuk. Sekalian menampilkan ukuran datanya.
 * ===================================================================== */
SELECT
    t.name                                        AS tabel,
    SUM(p.rows)                                   AS perkiraan_jumlah_baris,
    CAST(SUM(a.total_pages) * 8.0 / 1024 AS DECIMAL(18,2)) AS ukuran_mb
FROM sys.tables AS t
JOIN sys.indexes AS i      ON i.object_id = t.object_id
JOIN sys.partitions AS p   ON p.object_id = t.object_id AND p.index_id = i.index_id
JOIN sys.allocation_units AS a ON a.container_id = p.partition_id
WHERE t.name = 'NASABAH'
  AND i.index_id IN (0, 1)
GROUP BY t.name;


/* =====================================================================
 * QUERY 3 — Apakah ada NASABAH_ID ganda
 *
 * Kalau jumlah_baris dan jumlah_nasabah_id berbeda, berarti ada kode yang
 * muncul lebih dari sekali, dan itu bisa melipatgandakan baris laporan.
 * ===================================================================== */
SELECT
    COUNT(*)                       AS jumlah_baris,
    COUNT(DISTINCT NASABAH_ID)     AS jumlah_nasabah_id,
    COUNT(*) - COUNT(DISTINCT NASABAH_ID) AS selisih
FROM NASABAH WITH (NOLOCK);


/* =====================================================================
 * QUERY 4 — Berapa yang benar-benar dipakai PEMBELI_PPJB
 *
 * Pada PostgreSQL sudah terukur, dari 63.447 baris sr_pembeli_ppjb hanya
 * 34.560 yang punya pasangan di sr_nasabah. Query ini menghitung hal yang
 * sama di sini, supaya ketahuan apakah sr_nasabah memang belum lengkap.
 * ===================================================================== */
SELECT
    (SELECT COUNT(*) FROM PEMBELI_PPJB WITH (NOLOCK))            AS pembeli_semua,
    (SELECT COUNT(*) FROM PEMBELI_PPJB AS pb WITH (NOLOCK)
      WHERE EXISTS (SELECT 1 FROM NASABAH AS n WITH (NOLOCK)
                     WHERE n.NASABAH_ID = pb.NASABAH_ID))        AS pembeli_punya_nasabah,
    (SELECT COUNT(*) FROM NASABAH WITH (NOLOCK))                 AS nasabah_semua;


/*
 * CARA MEMBACA
 *
 * Bandingkan QUERY 1 dengan hasil versi PostgreSQL pada berkas
 * database/diagnostik/cek_jumlah_nasabah.sql.
 *
 * - Jumlahnya jauh lebih besar di sini
 *   -> sr_nasabah di PostgreSQL memang belum lengkap tersalin. Itu yang
 *      membuat kolom Nama Pembeli kosong pada sebagian laporan.
 *
 * - Jumlahnya sama
 *   -> tabelnya sudah lengkap, dan kolom nama yang kosong berasal dari
 *      sebab lain, misalnya cara menyambungkan nasabah_id.
 *
 * QUERY 4 yang paling menentukan. Kalau di sini hampir seluruh baris
 * PEMBELI_PPJB punya pasangan sedangkan di PostgreSQL hanya 34.560 dari
 * 63.447, berarti yang kurang memang data nasabahnya.
 */
