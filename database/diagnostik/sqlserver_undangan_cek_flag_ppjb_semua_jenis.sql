/* ============================================================
 * APAKAH SYARAT PPJB.FLAG_AKTIF BERLAKU DI JENIS LAIN JUGA
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK), tidak ada
 * subquery di dalam agregat, dan kunci penyambungnya dibiarkan
 * telanjang supaya index bisa dipakai.
 *
 * Sudah terbukti bahwa jenis Undangan Serah Terima TIDAK
 * menyaring PPJB.FLAG_AKTIF: dengan syarat itu keluar 1.268,
 * tanpa syarat itu keluar 1.293, dan 1.293 itulah angka desktop.
 *
 * Yang belum diketahui: bagaimana dengan jenis yang lain.
 *
 * Untuk Undangan PPJB angkanya sudah cocok persis, 294 lawan
 * 294, DENGAN syarat itu terpasang. Tetapi itu belum tentu
 * membuktikan apa-apa, sebab kalau kebetulan tidak ada PPJB
 * tidak aktif di sana, dipasang atau tidak hasilnya sama saja.
 *
 * Berkas ini menghitung kedua angka itu sekaligus untuk semua
 * jenis yang bertumpu pada PPJB. Cara membacanya:
 *
 *   SELISIH = 0   syarat itu tidak berpengaruh di jenis ini,
 *                 jadi dipasang atau tidak sama saja dan tidak
 *                 perlu diuji ke desktop
 *
 *   SELISIH > 0   syarat itu berpengaruh, jadi jenis itu HARUS
 *                 dibuka di desktop dan jumlah barisnya
 *                 disandingkan dengan kedua angka di sini
 *
 * Jenis 6, Perpanjangan Sertipikat, sengaja tidak dihitung di
 * sini. Jalannya lewat sertipikat, dan PPJB-nya hanya
 * disambung longgar untuk mengambil nomor PPJB, jadi syarat itu
 * tidak bisa membuang baris di sana, hanya bisa mengubah nomor
 * PPJB mana yang tertulis.
 *
 * Filter sama: UNIT SBKS, PERIODE 01-07-2023 s/d 21-09-2026.
 * ============================================================ */

WITH PEMBELI_RINGKAS AS (
    SELECT
        PB.PPJB_ID,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                  AND NS.NASABAH_ID IS NOT NULL
                 THEN 1 ELSE 0 END) AS N_AKTIF_BERNASABAH
    FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
    LEFT JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON NS.NASABAH_ID = PB.NASABAH_ID
    GROUP BY PB.PPJB_ID
),
SURAT AS (
            SELECT '1 Undangan PPJB (PPSRS)'   AS JENIS, U.PPJB_ID
            FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
            WHERE LTRIM(ISNULL(CAST(U.JENIS_SURAT AS VARCHAR(10)), '')) = '1'
              AND U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
              AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)

UNION ALL   SELECT '2 Undangan PPJB (Notaris)', U.PPJB_ID
            FROM [SRIS_PUSAT].[dbo].[UNDANGAN_PPJB] AS U WITH (NOLOCK)
            WHERE LTRIM(ISNULL(CAST(U.JENIS_SURAT AS VARCHAR(10)), '')) = '2'
              AND U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
              AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)

UNION ALL   SELECT '3 Undangan Serah Terima', U.PPJB_ID
            FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] AS U WITH (NOLOCK)
            WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
              AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)

UNION ALL   SELECT '4 Undangan AJB', U.PPJB_ID
            FROM [SRIS_PUSAT].[dbo].[UNDANGAN_AJB] AS U WITH (NOLOCK)
            WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
              AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)

UNION ALL   SELECT '5 Undangan SKB', U.PPJB_ID
            FROM [SRIS_PUSAT].[dbo].[UNDANGAN_SKB] AS U WITH (NOLOCK)
            WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
              AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
DASAR AS (
    SELECT
        SURAT.JENIS,
        UPPER(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))  AS PPJB_AKTIF,
        PPJB.PARENT_ID                             AS PPJB_PARENT,
        UPPER(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T'))) AS STOK_AKTIF,
        STOK.BLOK,
        STOK.NOMOR,
        ISNULL(P.N_AKTIF_BERNASABAH, 0)            AS N
    FROM SURAT
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON PPJB.PPJB_ID = SURAT.PPJB_ID
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = PPJB.STOK_ID
    LEFT JOIN PEMBELI_RINGKAS AS P
        ON P.PPJB_ID = PPJB.PPJB_ID
    WHERE UPPER(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, ''))) = 'SBKS'
)
SELECT
    JENIS,
    SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
              AND STOK_AKTIF = 'A'
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN N ELSE 0 END)                        AS DENGAN_SYARAT,
    SUM(CASE WHEN PPJB_PARENT IS NULL
              AND STOK_AKTIF = 'A'
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN N ELSE 0 END)                        AS TANPA_SYARAT,
    SUM(CASE WHEN PPJB_PARENT IS NULL
              AND STOK_AKTIF = 'A'
              AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
             THEN N ELSE 0 END)
    - SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                AND STOK_AKTIF = 'A'
                AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
               THEN N ELSE 0 END)                      AS SELISIH
FROM DASAR
GROUP BY JENIS
ORDER BY JENIS;
GO
