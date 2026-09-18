/* ============================================================
 * CEK KEMUTAKHIRAN DATA, SISI SQL SERVER (SRIS_PUSAT)
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Semua pembacaan memakai WITH (NOLOCK) seperti
 * query aplikasi desktop, supaya tidak mengunci tabel produksi.
 *
 * Dijalankan pada database SRIS_PUSAT, karena judul aplikasi
 * desktop menunjukkan SBKS memang dilayani dari sana:
 *   SRIS [v1.0] | 172.16.0.100 | SRIS_PUSAT | (SBKS) Summarecon Bekasi
 *
 * Tujuannya membandingkan isi sumber dengan isi hasil migrasi.
 * Pasangannya adalah jaminan_bank_cek_kemutakhiran_pg.sql.
 *
 * CATATAN: QUERY di sini sengaja tidak memakai subquery di dalam
 * fungsi agregat, supaya tidak kena Error 130 seperti dua kali
 * sebelumnya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Sebaran tahun TGL_JAMINAN pada SELURUH tabel JAMINAN.
 *
 * Dibandingkan dengan QUERY 1 sisi PostgreSQL. Kalau di sini
 * tahun 2024 dan 2025 berisi ratusan baris sedangkan di
 * PostgreSQL kosong, berarti barisnya memang belum ikut disalin.
 * ------------------------------------------------------------ */
SELECT
    CASE WHEN JAMINAN.TGL_JAMINAN IS NULL
         THEN '(KOSONG)'
         ELSE CAST(YEAR(JAMINAN.TGL_JAMINAN) AS VARCHAR(10))
    END AS TAHUN,
    COUNT(*) AS BARIS_SEMUA,
    SUM(CASE WHEN JAMINAN.NO_JAMINAN IS NOT NULL
              AND JAMINAN.NO_LUNAS IS NULL
              AND JAMINAN.NO_BATAL IS NULL
             THEN 1 ELSE 0 END) AS BARIS_SIAP_TAMPIL
FROM [SRIS_PUSAT].[dbo].[JAMINAN] AS JAMINAN WITH (NOLOCK)
GROUP BY
    CASE WHEN JAMINAN.TGL_JAMINAN IS NULL
         THEN '(KOSONG)'
         ELSE CAST(YEAR(JAMINAN.TGL_JAMINAN) AS VARCHAR(10))
    END
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 2
 * JUMLAH BARIS DENGAN FILTER YANG SAMA PERSIS SEPERTI DI LAYAR.
 *
 * Meniru query desktop apa adanya, termasuk bentuk penyaring
 * bloknya yang memang begitu di sumbernya. Sektor dan Jenis
 * Jaminan tidak ditulis karena keduanya "Semua", yang pada query
 * desktop membuat syaratnya selalu benar.
 *
 * Hasilnya seharusnya 163, sama dengan yang Anda lihat di
 * desktop. Kalau ya, berarti angka 163 memang benar dan yang
 * kurang ada di sisi PostgreSQL.
 * ------------------------------------------------------------ */
SELECT COUNT(*) AS JUMLAH_BARIS_DESKTOP
FROM [SRIS_PUSAT].[dbo].[JAMINAN]       AS JAMINAN       WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[SERTIPIKAT]    AS SERTIPIKAT    WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[STOK]          AS STOK          WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[PPJB]          AS PPJB          WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[PEMBELI_PPJB]  AS PEMBELI_PPJB  WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[NASABAH]       AS NASABAH       WITH (NOLOCK)
WHERE PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID
  AND PPJB.PPJB_ID            = PEMBELI_PPJB.PPJB_ID
  AND PPJB.STOK_ID            = STOK.STOK_ID
  AND SERTIPIKAT.SERTIPIKAT_ID = JAMINAN.SERTIPIKAT_ID
  AND STOK.STOK_ID            = SERTIPIKAT.STOK_ID
  AND STOK.FLAG_AKTIF         = 'A'
  AND PPJB.FLAG_AKTIF         = 'A'
  AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
  AND (
        (
            (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) >= 'A'
            AND (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) <= 'ZZ'
        )
        OR (STOK.BLOK >= 'ZZ' AND STOK.BLOK <= 'ZZ')
      )
  AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = 'SBKS'
  AND STOK.BLOK        IS NOT NULL
  AND STOK.NOMOR       IS NOT NULL
  AND SERTIPIKAT.STOK_ID IS NOT NULL
  AND PPJB.PARENT_ID   IS NULL
  AND JAMINAN.NO_JAMINAN IS NOT NULL
  AND JAMINAN.NO_LUNAS IS NULL
  AND JAMINAN.NO_BATAL IS NULL
  AND JAMINAN.TGL_JAMINAN >= CONVERT(DATETIME, '20230701', 112)
  AND JAMINAN.TGL_JAMINAN <= CONVERT(DATETIME, '20260918', 112);


/* ------------------------------------------------------------
 * QUERY 3
 * 163 baris itu tahunnya apa saja.
 *
 * Inilah daftar yang langsung bisa dibandingkan dengan sisi
 * PostgreSQL. Tahun yang ada di sini tetapi kosong di PostgreSQL
 * adalah tahun yang belum termigrasi.
 * ------------------------------------------------------------ */
SELECT
    YEAR(JAMINAN.TGL_JAMINAN) AS TAHUN,
    COUNT(*) AS BARIS
FROM [SRIS_PUSAT].[dbo].[JAMINAN]       AS JAMINAN       WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[SERTIPIKAT]    AS SERTIPIKAT    WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[STOK]          AS STOK          WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[PPJB]          AS PPJB          WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[PEMBELI_PPJB]  AS PEMBELI_PPJB  WITH (NOLOCK),
     [SRIS_PUSAT].[dbo].[NASABAH]       AS NASABAH       WITH (NOLOCK)
WHERE PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID
  AND PPJB.PPJB_ID            = PEMBELI_PPJB.PPJB_ID
  AND PPJB.STOK_ID            = STOK.STOK_ID
  AND SERTIPIKAT.SERTIPIKAT_ID = JAMINAN.SERTIPIKAT_ID
  AND STOK.STOK_ID            = SERTIPIKAT.STOK_ID
  AND STOK.FLAG_AKTIF         = 'A'
  AND PPJB.FLAG_AKTIF         = 'A'
  AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
  AND (
        (
            (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) >= 'A'
            AND (RTRIM(STOK.BLOK) + '/' + STOK.NOMOR) <= 'ZZ'
        )
        OR (STOK.BLOK >= 'ZZ' AND STOK.BLOK <= 'ZZ')
      )
  AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = 'SBKS'
  AND STOK.BLOK        IS NOT NULL
  AND STOK.NOMOR       IS NOT NULL
  AND SERTIPIKAT.STOK_ID IS NOT NULL
  AND PPJB.PARENT_ID   IS NULL
  AND JAMINAN.NO_JAMINAN IS NOT NULL
  AND JAMINAN.NO_LUNAS IS NULL
  AND JAMINAN.NO_BATAL IS NULL
  AND JAMINAN.TGL_JAMINAN >= CONVERT(DATETIME, '20230701', 112)
  AND JAMINAN.TGL_JAMINAN <= CONVERT(DATETIME, '20260918', 112)
GROUP BY YEAR(JAMINAN.TGL_JAMINAN)
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 4
 * Tanggal perekaman terakhir pada tabel-tabel yang dipakai
 * laporan ini.
 *
 * Dibandingkan dengan QUERY 4 dan 5 sisi PostgreSQL. Selisih
 * antara keduanya adalah rentang waktu yang belum ikut disalin.
 * ------------------------------------------------------------ */
SELECT 'JAMINAN'        AS TABEL, MAX(TGL_ENTRY) AS PEREKAMAN_TERAKHIR
FROM [SRIS_PUSAT].[dbo].[JAMINAN] WITH (NOLOCK)
UNION ALL
SELECT 'SERTIPIKAT',    MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] WITH (NOLOCK)
UNION ALL
SELECT 'STOK',          MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[STOK] WITH (NOLOCK)
UNION ALL
SELECT 'PPJB',          MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[PPJB] WITH (NOLOCK)
UNION ALL
SELECT 'PEMBELI_PPJB',  MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] WITH (NOLOCK)
UNION ALL
SELECT 'NASABAH',       MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[NASABAH] WITH (NOLOCK)
UNION ALL
SELECT 'AKTA',          MAX(TGL_ENTRY) FROM [SRIS_PUSAT].[dbo].[AKTA] WITH (NOLOCK)
ORDER BY 2 DESC;
