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


/* =====================================================================
 * QUERY 3 — Cari tabel acuan arti kode JENIS_JAMINAN di sumbernya
 *
 * Pencarian di PostgreSQL tidak menemukan tabel acuan apa pun. Tabel itu
 * mungkin ada di SQL Server tetapi tidak ikut termigrasi, persis seperti
 * AKTA dan SERTIPIKAT_IDK yang sebagian barisnya tertinggal.
 *
 * Yang dicari: tabel bernama mirip jaminan, jenis, kode, atau tabel
 * acuan, beserta kolomnya.
 * ===================================================================== */
SELECT
    t.TABLE_NAME  AS nama_tabel,
    c.COLUMN_NAME AS nama_kolom,
    c.DATA_TYPE   AS tipe,
    c.CHARACTER_MAXIMUM_LENGTH AS panjang
FROM INFORMATION_SCHEMA.TABLES t WITH (NOLOCK)
INNER JOIN INFORMATION_SCHEMA.COLUMNS c WITH (NOLOCK)
    ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
   AND c.TABLE_NAME = t.TABLE_NAME
WHERE t.TABLE_TYPE = 'BASE TABLE'
  AND (
        t.TABLE_NAME LIKE '%JAMIN%'
     OR t.TABLE_NAME LIKE '%JENIS%'
     OR c.COLUMN_NAME LIKE '%JENIS_JAMINAN%'
      )
ORDER BY t.TABLE_NAME, c.ORDINAL_POSITION;


/* =====================================================================
 * QUERY 4 — Perilaku tiap kode, dihitung di sumbernya
 *
 * Padanan QUERY 2 pada berkas PostgreSQL, tetapi memakai data SQL Server
 * yang kuncinya masih utuh sehingga tidak perlu menyusun ulang awalan.
 * Inilah pembanding yang paling bersih.
 *
 * Cara membacanya:
 *   persen_punya_akta tinggi  -> kode itu berarti Akta Jual Beli
 *   persen_no_sertipikat tinggi -> kode itu berarti Sertipikat
 *   persen_punya_akta rendah  -> kode itu berarti PPJB
 * ===================================================================== */
SELECT
    ISNULL(NULLIF(LTRIM(RTRIM(j.JENIS_JAMINAN)), ''), '-') AS kode,
    COUNT(*)                                               AS baris,
    CAST(100.0 * SUM(CASE WHEN EXISTS (
            SELECT 1 FROM AKTA a WITH (NOLOCK)
            WHERE a.SERTIPIKAT_ID = j.SERTIPIKAT_ID
              AND a.NO_AKTA IS NOT NULL
         ) THEN 1 ELSE 0 END) / COUNT(*) AS DECIMAL(5,1))  AS persen_punya_akta,
    CAST(100.0 * SUM(CASE WHEN EXISTS (
            SELECT 1 FROM SERTIPIKAT s WITH (NOLOCK)
            WHERE s.SERTIPIKAT_ID = j.SERTIPIKAT_ID
              AND LTRIM(RTRIM(ISNULL(s.NO_SERTIPIKAT, ''))) <> ''
         ) THEN 1 ELSE 0 END) / COUNT(*) AS DECIMAL(5,1))  AS persen_no_sertipikat
FROM JAMINAN j WITH (NOLOCK)
WHERE j.NO_JAMINAN IS NOT NULL
  AND j.NO_LUNAS IS NULL
  AND j.NO_BATAL IS NULL
GROUP BY ISNULL(NULLIF(LTRIM(RTRIM(j.JENIS_JAMINAN)), ''), '-')
ORDER BY COUNT(*) DESC;
