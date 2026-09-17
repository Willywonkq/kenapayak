/*
 * DIAGNOSTIK — dijalankan di SQL SERVER, bukan PostgreSQL
 *
 * ==========================================================================
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE, maupun DDL.
 * Aman dijalankan dengan akun read only.
 * ==========================================================================
 *
 * Pilih dulu database SRIS-nya, jangan master.
 */

-- USE SRIS_PUSAT;


/* =====================================================================
 * PERTANYAANNYA
 *
 * Pada PostgreSQL, JAMINAN.SERTIPIKAT_ID bertipe numeric sehingga
 * awalannya terbuang, dan 4.689 dari 5.190 barisnya menjadi rancu karena
 * angkanya ada pada dua keluarga sekaligus, DBPSA- dan DBPSS-.
 *
 * Di sumbernya kolom itu seharusnya masih utuh. Kedua query di bawah
 * memastikan sebenarnya milik keluarga mana, dan sekaligus mengambil arti
 * kode JENIS_JAMINAN yang pada PostgreSQL hanya berisi satu huruf.
 * ===================================================================== */


/* =====================================================================
 * QUERY 1 — JAMINAN milik keluarga awalan yang mana
 *
 * Kalau seluruhnya satu awalan, model di PostgreSQL boleh memakai awalan
 * itu untuk semua baris dan datanya pulih utuh. Kalau terbagi dua, data
 * jaminan tidak bisa dipulihkan dari PostgreSQL dan migrasinya yang perlu
 * diperbaiki, seperti kasus AKTA dan SERTIPIKAT_IDK pada unit DTSA.
 * ===================================================================== */
SELECT
    CASE
        WHEN CHARINDEX('-', j.SERTIPIKAT_ID) > 0
        THEN LEFT(j.SERTIPIKAT_ID, CHARINDEX('-', j.SERTIPIKAT_ID))
        ELSE '(tanpa awalan)'
    END                         AS awalan,
    COUNT(*)                    AS jumlah,
    MIN(j.SERTIPIKAT_ID)        AS contoh_terkecil,
    MAX(j.SERTIPIKAT_ID)        AS contoh_terbesar
FROM JAMINAN j WITH (NOLOCK)
WHERE j.SERTIPIKAT_ID IS NOT NULL
GROUP BY
    CASE
        WHEN CHARINDEX('-', j.SERTIPIKAT_ID) > 0
        THEN LEFT(j.SERTIPIKAT_ID, CHARINDEX('-', j.SERTIPIKAT_ID))
        ELSE '(tanpa awalan)'
    END
ORDER BY COUNT(*) DESC;


/* =====================================================================
 * QUERY 2 — arti kode JENIS_JAMINAN
 *
 * Pada PostgreSQL kolom ini varchar(1) berisi 4, 2, A, H, P, 3, 5, dan T,
 * sedangkan dropdown pada layar mengirim tulisan IMB, Akta Jual Beli,
 * Sertipikat, PPJB, dan Peralihan Hak. Jumlah per kode di bawah bisa
 * dibandingkan dengan jumlah baris yang keluar di aplikasi desktop ketika
 * tiap pilihan dropdown dipakai, sehingga artinya ketahuan tanpa menebak.
 * ===================================================================== */
SELECT
    ISNULL(LTRIM(RTRIM(j.JENIS_JAMINAN)), '(kosong)') AS kode,
    COUNT(*)                                          AS jumlah
FROM JAMINAN j WITH (NOLOCK)
WHERE j.NO_JAMINAN IS NOT NULL
  AND j.NO_LUNAS IS NULL
  AND j.NO_BATAL IS NULL
GROUP BY ISNULL(LTRIM(RTRIM(j.JENIS_JAMINAN)), '(kosong)')
ORDER BY COUNT(*) DESC;
