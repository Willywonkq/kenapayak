/* ============================================================
 * MENCARI SYARAT YANG BERLEBIH PADA UNDANGAN SERAH TERIMA
 *
 * BERKAS INI HANYA MEMBACA. Memakai WITH (NOLOCK), dan tidak
 * ada subquery di dalam agregat sehingga aman dari Error 130.
 *
 * Kenapa berkas ini ada:
 *
 *   Layar desktop menampilkan  1.293 baris.
 *   Corong sisi SQL Server keluar  1.268 baris.
 *
 * Selisih 25 baris. Kecil, tetapi tidak boleh dibiarkan. Pada
 * jenis Undangan PPJB angkanya cocok persis, 294 lawan 294,
 * jadi kalau di sini meleset berarti ada satu syarat yang saya
 * pasang tetapi desktop tidak memasangnya, khusus jenis ini.
 * Itu kesalahan model, bukan soal data.
 *
 * Berkas ini melepas syarat-syarat itu satu per satu lalu
 * menghitung ulang. Varian yang keluar 1.293 itulah yang
 * dipakai desktop.
 *
 * Filter tetap sama: UNIT SBKS, PERIODE 01-07-2023 s/d
 * 21-09-2026, tanpa penyaring blok dan sektor.
 *
 * ------------------------------------------------------------
 * CATATAN KECEPATAN
 *
 * Tulisan pertama berkas ini berat sekali dan harus dibatalkan.
 * Dua sebabnya, dan keduanya kesalahan saya:
 *
 *   1. Kunci penyambungnya saya bungkus RTRIM di KEDUA sisi.
 *      Begitu dibungkus fungsi, SQL Server tidak bisa memakai
 *      index dan terpaksa menyisir seluruh tabel. Padahal RTRIM
 *      di situ memang tidak perlu: pada SQL Server, spasi di
 *      belakang diabaikan saat membandingkan, jadi 'A' dan 'A '
 *      sudah dianggap sama tanpa dibantu.
 *
 *   2. Ada tiga CROSS APPLY yang masing-masing menyisir tabel
 *      pembeli dan nasabah, berulang untuk setiap baris.
 *
 * Sekarang pembeli dan nasabah diringkas SEKALI SAJA di depan,
 * lalu disambungkan biasa. Satu kali baca, bukan ribuan kali.
 * ------------------------------------------------------------ */

WITH PEMBELI_RINGKAS AS (
    SELECT
        PB.PPJB_ID,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                  AND NS.NASABAH_ID IS NOT NULL
                 THEN 1 ELSE 0 END) AS N_AKTIF_BERNASABAH,
        SUM(CASE WHEN NS.NASABAH_ID IS NOT NULL
                 THEN 1 ELSE 0 END) AS N_SEMUA_BERNASABAH,
        SUM(CASE WHEN UPPER(LTRIM(ISNULL(PB.FLAG_AKTIF, ''))) = 'Y'
                 THEN 1 ELSE 0 END) AS N_AKTIF_SAJA
    FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PB WITH (NOLOCK)
    LEFT JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NS WITH (NOLOCK)
        ON NS.NASABAH_ID = PB.NASABAH_ID
    GROUP BY PB.PPJB_ID
),
SURAT AS (
    SELECT U.PPJB_ID
    FROM [SRIS_PUSAT].[dbo].[UNDANGAN_ST] AS U WITH (NOLOCK)
    WHERE U.TGL_SURAT >= CONVERT(DATETIME, '20230701', 112)
      AND U.TGL_SURAT <  CONVERT(DATETIME, '20260922', 112)
),
DASAR AS (
    SELECT
        UPPER(LTRIM(ISNULL(PPJB.FLAG_AKTIF, '')))  AS PPJB_AKTIF,
        PPJB.PARENT_ID                             AS PPJB_PARENT,
        UPPER(LTRIM(ISNULL(STOK.FLAG_AKTIF, 'T'))) AS STOK_AKTIF,
        STOK.PARENT_ID                             AS STOK_PARENT,
        STOK.BLOK,
        STOK.NOMOR,
        ISNULL(P.N_AKTIF_BERNASABAH, 0) AS N_AKTIF_BERNASABAH,
        ISNULL(P.N_SEMUA_BERNASABAH, 0) AS N_SEMUA_BERNASABAH,
        ISNULL(P.N_AKTIF_SAJA, 0)       AS N_AKTIF_SAJA
    FROM SURAT
    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
        ON PPJB.PPJB_ID = SURAT.PPJB_ID
    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
        ON STOK.STOK_ID = PPJB.STOK_ID
    LEFT JOIN PEMBELI_RINGKAS AS P
        ON P.PPJB_ID = PPJB.PPJB_ID
    WHERE UPPER(LTRIM(ISNULL(STOK.KD_PERUSAHAAN, ''))) = 'SBKS'
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
