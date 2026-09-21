/* ============================================================
 * MENCARI SYARAT YANG BERLEBIH PADA UNDANGAN SERAH TERIMA
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK), dan seluruh
 * hitungan bersarang dikerjakan lewat CROSS APPLY di bagian
 * FROM, bukan di dalam agregat, sehingga aman dari Error 130.
 *
 * Kenapa berkas ini ada:
 *
 *   Layar desktop menampilkan  1.293 baris.
 *   Corong sisi SQL Server keluar  1.268 baris.
 *
 * Selisih 25 baris. Kecil, tetapi tidak boleh dibiarkan. Pada
 * jenis Undangan PPJB kemarin angkanya cocok persis, 294 lawan
 * 294, jadi kalau di sini meleset berarti ada satu syarat yang
 * saya pasang tetapi desktop tidak memasangnya, khusus jenis
 * ini. Itu kesalahan model, bukan soal data.
 *
 * Berkas ini melepas syarat-syarat itu satu per satu dan
 * menghitung ulang. Varian yang keluar 1.293 itulah yang
 * dipakai desktop.
 *
 * Filter tetap sama: UNIT SBKS, PERIODE 01-07-2023 s/d
 * 21-09-2026, tanpa penyaring blok dan sektor.
 * ============================================================ */

WITH SURAT AS (
    SELECT U.PPJB_ID
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] AS U WITH (NOLOCK)
    WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
DASAR AS (
    SELECT
        UPPER(RTRIM(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))) AS PPJB_AKTIF,
        PPJB.PARENT_ID                                   AS PPJB_PARENT,
        UPPER(RTRIM(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T')))) AS STOK_AKTIF,
        STOK.PARENT_ID                                   AS STOK_PARENT,
        STOK.BLOK,
        STOK.NOMOR,
        PN.N AS N_AKTIF_BERNASABAH,
        PS.N AS N_SEMUA_BERNASABAH,
        PA.N AS N_AKTIF_SAJA
    FROM SURAT
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON RTRIM(PPJB.PPJB_ID) = RTRIM(SURAT.PPJB_ID)
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON RTRIM(STOK.STOK_ID) = RTRIM(PPJB.STOK_ID)
       AND UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, '')))) = 'SBKS'
    CROSS APPLY (
        SELECT COUNT(*) AS N
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
            ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(PPJB.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    ) AS PN
    CROSS APPLY (
        SELECT COUNT(*) AS N
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
            ON RTRIM(NS.NASABAH_ID) = RTRIM(PB.NASABAH_ID)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(PPJB.PPJB_ID)
    ) AS PS
    CROSS APPLY (
        SELECT COUNT(*) AS N
        FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
        WHERE RTRIM(PB.PPJB_ID) = RTRIM(PPJB.PPJB_ID)
          AND UPPER(RTRIM(LTRIM(ISNULL(PB.FLAG_AKTIF, '')))) = 'Y'
    ) AS PA
)
            SELECT 1 AS URUT,
                   'A  seperti corong sekarang'                       AS VARIAN,
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A'
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)       AS BARIS
            FROM DASAR

UNION ALL   SELECT 2, 'B  tanpa syarat STOK.FLAG_AKTIF = A',
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 3, 'C  tanpa syarat PPJB.PARENT_ID IS NULL',
                   SUM(CASE WHEN PPJB_AKTIF = 'A'
                             AND STOK_AKTIF = 'A'
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 4, 'D  tanpa syarat PPJB.FLAG_AKTIF = A',
                   SUM(CASE WHEN PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A'
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 5, 'E  tanpa syarat BLOK dan NOMOR terisi',
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A'
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 6, 'F  pembeli tidak harus aktif',
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A'
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_SEMUA_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 7, 'G  pembeli tidak harus ketemu di NASABAH',
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A'
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_SAJA ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 8, 'H  tambah syarat STOK.PARENT_ID IS NULL',
                   SUM(CASE WHEN PPJB_AKTIF = 'A' AND PPJB_PARENT IS NULL
                             AND STOK_AKTIF = 'A' AND STOK_PARENT IS NULL
                             AND BLOK IS NOT NULL AND NOMOR IS NOT NULL
                            THEN N_AKTIF_BERNASABAH ELSE 0 END)
            FROM DASAR

UNION ALL   SELECT 9, 'I  hanya unit SBKS, syarat lain dilepas semua',
                   SUM(N_AKTIF_SAJA)
            FROM DASAR

ORDER BY URUT;
GO
