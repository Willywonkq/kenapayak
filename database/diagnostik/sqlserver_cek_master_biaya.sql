/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun
 * perintah DDL. Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * KENAPA PERLU
 * Pada PostgreSQL ada kode biaya yang dipakai sr_biaya_dp tetapi tidak punya
 * baris master di sr_biaya, sehingga nilainya tidak pernah ikut terjumlah di
 * laporan web. Untuk laporan Penjualan per Tanggal Tanda Jadi per Agen, dua
 * kode yang benar-benar berpengaruh adalah DSL dan D50.
 *
 * Nilai BALANCE-nya tidak boleh ditebak, karena BALANCE = -1 berarti masuk
 * kolom Discount sedangkan BALANCE = 1 berarti masuk kolom Biaya-Biaya.
 * Query ini mengambil nilai sebenarnya dari SQL Server.
 */


/* =====================================================================
 * QUERY 1 — Baris master untuk kode yang belum ada di PostgreSQL
 *
 * DSL dan D50 yang dipakai laporan ini. Sepuluh kode lain disertakan
 * sekalian karena dipakai laporan lain.
 * ===================================================================== */
SELECT
    '[' + KD_BIAYA + ']'                    AS kd_biaya_apa_adanya,
    LTRIM(RTRIM(KD_BIAYA))                  AS kd_biaya_rapi,
    LEN(KD_BIAYA)                           AS panjang_karakter,
    BALANCE,
    CASE BALANCE
        WHEN -1 THEN 'masuk kolom Discount'
        WHEN  1 THEN 'masuk kolom Biaya-Biaya'
        ELSE        'nilai lain, perlu dicek'
    END                                     AS masuk_ke_kolom
FROM BIAYA WITH (NOLOCK)
WHERE LTRIM(RTRIM(KD_BIAYA)) IN (
    'DSL', 'D50',
    'DCB2', 'DCG', 'DCK', 'DIRGR', 'DSF', 'DSH', 'DST', 'DUT', 'DXT', 'PHJ2'
)
ORDER BY kd_biaya_rapi;


/* =====================================================================
 * QUERY 2 — Pembuktian silang untuk DSL
 *
 * HE/032 di desktop menampilkan Discount 34.220.000, dan satu-satunya
 * baris BIAYA_DP yang bernilai segitu berkode DSL. Query ini memastikan
 * DSL memang ikut terhitung sebagai discount di SQL Server.
 * ===================================================================== */
SELECT
    UANG_MUKA.NO_UANG_MUKA,
    '[' + BIAYA_DP.KD_BIAYA + ']'  AS kd_biaya,
    BIAYA_DP.JUMLAH,
    BIAYA.BALANCE,
    ( SELECT SUM(BD2.JUMLAH)
        FROM BIAYA_DP AS BD2 WITH (NOLOCK), BIAYA AS B2 WITH (NOLOCK)
       WHERE BD2.KD_BIAYA = B2.KD_BIAYA
         AND B2.BALANCE = -1
         AND BD2.UANG_MUKA_ID = UANG_MUKA.UANG_MUKA_ID ) AS discount_versi_desktop
FROM UANG_MUKA WITH (NOLOCK)
INNER JOIN BIAYA_DP WITH (NOLOCK)
        ON BIAYA_DP.UANG_MUKA_ID = UANG_MUKA.UANG_MUKA_ID
LEFT JOIN BIAYA WITH (NOLOCK)
        ON BIAYA.KD_BIAYA = BIAYA_DP.KD_BIAYA
WHERE UANG_MUKA.NO_UANG_MUKA = 'DTSA/2026-B/0064'
ORDER BY BIAYA_DP.KD_BIAYA;


/* =====================================================================
 * CARA MEMBACA
 *
 * QUERY 1 memberi nilai BALANCE yang sebenarnya. Nilai itulah yang harus
 * dipakai ketika baris master ditambahkan ke sr_biaya di PostgreSQL.
 * Penambahan barisnya harus dikerjakan oleh yang berwenang, karena berupa
 * perubahan data.
 *
 * Perhatikan juga panjang_karakter. Di PostgreSQL kd_biaya pada sr_biaya
 * disimpan rata lima karakter, misalnya [DCRB ] dan [  PPN], jadi baris
 * baru sebaiknya mengikuti bentuk yang sama persis dengan SQL Server.
 *
 * Sesudah baris masternya ada, model web menghitungnya dengan benar tanpa
 * perlu diubah sama sekali.
 * ===================================================================== */
