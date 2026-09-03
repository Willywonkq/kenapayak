<?php

// MODEL SQL SERVER LEGACY V1 - REKAP ESTIMASI BIAYA AJB, ODBC SAFE

// MODEL VERSION SQLSERVER-WEBSA-SRIS-PUSAT-V1-20260903
// Sumber query: aplikasi desktop SRIS / SQL Server.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class rekap_estimasi_biaya_ajb_m extends Model
{
    /**
     * Koneksi SQL Server yang sudah ada pada config/database.php.
     */
    private const CONNECTION = 'websa';

    use HasFactory;

    /**
     * Master cluster.
     *
     * Desktop menyebutnya Cluster, sumber datanya tabel SEKTOR, sama dengan
     * lookup Sektor/Cluster pada fitur Daftar Undangan Surat Rumah.
     */
    public function obtainCluster($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sql = <<<'SQL'
            SELECT
                SEKTOR.KD_SEKTOR AS KD_CLUSTER,
                SEKTOR.KD_SEKTOR AS KD_SEKTOR,
                SEKTOR.DESKRIPSI AS DESKRIPSI,
                SEKTOR.KD_PERUSAHAAN AS KD_PERUSAHAAN
            FROM [SRIS_PUSAT].[dbo].[SEKTOR] AS SEKTOR WITH (NOLOCK)
            WHERE SEKTOR.FLAG_AKTIF = 'A'
              AND UPPER(RTRIM(LTRIM(SEKTOR.KD_PERUSAHAAN))) = :kd_perusahaan
            ORDER BY
                SEKTOR.DESKRIPSI,
                SEKTOR.KD_SEKTOR
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql, [
                'kd_perusahaan' => $kdPerusahaan,
            ])
        );
    }

    /**
     * Lookup Blok/Nomor.
     *
     * Query mengikuti obtainBlok() pada model Daftar Undangan Surat Rumah,
     * sehingga daftar unit yang muncul sama persis dengan fitur tersebut.
     */
    public function obtainBlok($kdPerusahaan): array
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return [];
        }

        $sql = <<<'SQL'
            SELECT
                RTRIM(STOK.BLOK) + '/' + STOK.NOMOR AS BLOK_NOMOR,
                PPJB.PPJB_ID,
                NASABAH.NAMA AS NAMA_PEMBELI,
                PPJB.NO_PPJB,
                PPJB.TGL_PPJB,
                (
                    SELECT TOP (1) TIPE_REF.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[TIPE] AS TIPE_REF WITH (NOLOCK)
                    WHERE TIPE_REF.KD_JENIS = STOK.KD_JENIS
                      AND TIPE_REF.KD_TIPE = STOK.KD_TIPE
                ) AS TIPE,
                (
                    SELECT TOP (1) LOKASI_REF.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[LOKASI] AS LOKASI_REF WITH (NOLOCK)
                    WHERE LOKASI_REF.KD_LOKASI = STOK.KD_LOKASI
                ) AS LOKASI,
                STOK.STOK_ID,
                STOK.NO_VIRTUAL_ACC,
                STOK.BLOK,
                STOK.NOMOR,
                (
                    SELECT TOP (1) SEKTOR_REF.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[SEKTOR] AS SEKTOR_REF WITH (NOLOCK)
                    WHERE SEKTOR_REF.KD_SEKTOR = STOK.KD_SEKTOR
                ) AS NM_CLUSTER
            FROM [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON STOK.STOK_ID = PPJB.STOK_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PEMBELI_PPJB WITH (NOLOCK)
                ON PPJB.PPJB_ID = PEMBELI_PPJB.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NASABAH WITH (NOLOCK)
                ON PEMBELI_PPJB.NASABAH_ID = NASABAH.NASABAH_ID
            WHERE PEMBELI_PPJB.FLAG_AKTIF = 'Y'
              AND PPJB.FLAG_AKTIF = 'A'
              AND PPJB.PARENT_ID IS NULL
              AND STOK.PARENT_ID IS NULL
              AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = :perusahaan
              AND STOK.BLOK IS NOT NULL
              AND STOK.NOMOR IS NOT NULL
            ORDER BY
                STOK.BLOK ASC,
                CASE
                    WHEN LTRIM(RTRIM(ISNULL(STOK.NOMOR, ''))) <> ''
                     AND LTRIM(RTRIM(STOK.NOMOR)) NOT LIKE '%[^0-9]%'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN LTRIM(RTRIM(ISNULL(STOK.NOMOR, ''))) <> ''
                     AND LTRIM(RTRIM(STOK.NOMOR)) NOT LIKE '%[^0-9]%'
                    THEN RIGHT(
                        REPLICATE('0', 50) + LTRIM(RTRIM(STOK.NOMOR)),
                        50
                    )
                    ELSE ''
                END ASC,
                STOK.NOMOR ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'perusahaan' => $kdPerusahaan,
        ]);
    }

    /**
     * Entry utama data laporan Rekap Estimasi Biaya AJB.
     */
    public function obtainRekapEstimasiBiayaAjb($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );
        $cluster = $this->normalizeText($request->cluster ?? '*');
        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($cluster === '') {
            $cluster = '*';
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '') {
            $blokAkhir = 'ZZ';
        }

        return $this->obtainEstimasiBiaya(
            $perusahaan,
            $cluster,
            $blokAwal,
            $blokAkhir,
            $this->normalizeDate112($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate112($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    /**
     * Query laporan.
     *
     * Query desktop dipertahankan apa adanya, kecuali beberapa penyesuaian
     * yang dijelaskan pada komentar di dalam SQL. Join implisit pada
     * subquery JENIS_BGN diubah menjadi JOIN eksplisit, dan seluruh object
     * dipanggil lengkap dari [SRIS_PUSAT].[dbo] agar koneksi "websa" tidak
     * bergantung pada default database SQL Server.
     *
     * Placeholder ODBC bersifat posisional, sehingga setiap nama hanya
     * dipakai satu kali dan urutan binding mengikuti urutan kemunculannya
     * pada SQL.
     */
    private function obtainEstimasiBiaya(
        string $perusahaan,
        string $cluster,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        $sql = <<<'SQL'
            SELECT
                BIAYA_AJB.NO_DOKUMEN AS NO_DOKUMEN,
                TGL_REF.TGL_DOKUMEN_VALID AS TGL_DOKUMEN,

                STOK.KD_SEKTOR AS KD_SEKTOR,
                (
                    SELECT TOP (1) A.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[SEKTOR] AS A WITH (NOLOCK)
                    WHERE A.KD_SEKTOR = STOK.KD_SEKTOR
                ) AS NM_CLUSTER,

                RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR) AS BLOK_NOMOR,
                STOK.BLOK AS BLOK,
                STOK.NOMOR AS NOMOR,

                UPPER(
                    [SRIS_PUSAT].[dbo].[F_GET_PEMBELI](PPJB.PPJB_ID)
                ) AS NAMA_PEMBELI,

                PPJB.TGL_PPJB AS TGL_PPJB,
                PPJB.HARGA_JUAL AS HARGA_JUAL,
                PPJB.DPP AS DPP,
                PPJB.PPN AS PPN,

                STOK.NO_VIRTUAL_ACC AS NO_VIRTUAL_ACC,
                STOK.ATAS_NAMA_VA AS ATAS_NAMA_VA,

                BIAYA_AJB.LB AS LB,
                BIAYA_AJB.LBB AS LBB,
                BIAYA_AJB.LT AS LT,
                BIAYA_AJB.NJOP_LB AS NJOP_LB,
                BIAYA_AJB.NJOP_LBB AS NJOP_LBB,
                BIAYA_AJB.NJOP_LT AS NJOP_LT,
                BIAYA_AJB.BEA_SURAT AS BEA_SURAT,
                BIAYA_AJB.BEA_HGB AS BEA_HGB,
                BIAYA_AJB.SELISIH_NJOP AS SELISIH_NJOP,
                BIAYA_AJB.BEA_PNBP AS BEA_PNBP,
                BIAYA_AJB.BEA_BPHTB AS BEA_BPHTB,
                BIAYA_AJB.BEA_CADANGAN AS BEA_CADANGAN,
                BIAYA_AJB.FEE_PAJAK AS FEE_PAJAK,
                BIAYA_AJB.TOTAL_DEV AS TOTAL_DEV,
                BIAYA_AJB.TOTAL_NOTARIS AS TOTAL_NOTARIS,
                BIAYA_AJB.KETERANGAN AS KETERANGAN,
                BIAYA_AJB.FLAG_AKTIF AS FLAG_AKTIF,
                BIAYA_AJB.TGL_ENTRY AS TGL_ENTRY,
                BIAYA_AJB.USER_ENTRY AS USER_ENTRY,
                BIAYA_AJB.TGL_UPDATE AS TGL_UPDATE,
                BIAYA_AJB.USER_UPDATE AS USER_UPDATE,
                BIAYA_AJB.KD_NOTARIS AS KD_NOTARIS,

                TBL_NOTARIS.NM_NOTARIS AS NM_NOTARIS,
                TBL_NOTARIS.NO_REKENING AS NO_REKENING,
                TBL_NOTARIS.CABANG_BANK AS CABANG_BANK,

                (
                    SELECT TOP (1) A.FLAG_LAPORAN
                    FROM [SRIS_PUSAT].[dbo].[JENIS_BANGUNAN] AS A WITH (NOLOCK)
                    INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS B WITH (NOLOCK)
                        ON B.KD_JENIS = A.KD_JENIS
                    INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS C WITH (NOLOCK)
                        ON C.STOK_ID = B.STOK_ID
                    WHERE C.PPJB_ID = BIAYA_AJB.PPJB_ID
                ) AS JENIS_BGN,

                STOK.KD_PERUSAHAAN AS KD_PERUSAHAAN,
                GETDATE() AS TGL_CETAK

            FROM [SRIS_PUSAT].[dbo].[BIAYA_AJB] AS BIAYA_AJB WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
                ON BIAYA_AJB.PPJB_ID = PPJB.PPJB_ID
               AND PPJB.FLAG_AKTIF = 'A'
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON PPJB.STOK_ID = STOK.STOK_ID
               AND STOK.FLAG_AKTIF = 'A'
            LEFT OUTER JOIN [SRIS_PUSAT].[dbo].[TBL_NOTARIS] AS TBL_NOTARIS WITH (NOLOCK)
                ON BIAYA_AJB.KD_NOTARIS = TBL_NOTARIS.KD_NOTARIS
            LEFT OUTER JOIN [SRIS_PUSAT].[dbo].[BANK] AS BANK WITH (NOLOCK)
                ON TBL_NOTARIS.KD_BANK = BANK.KD_BANK

            /*
             * Pada database legacy kolom tanggal dapat berisi nilai yang
             * tidak valid, sehingga tanggal dokumen dikonversi aman dulu
             * sebelum dibandingkan dengan rentang filter.
             */
            OUTER APPLY (
                SELECT
                    CONVERT(
                        DATETIME,
                        CASE
                            WHEN ISDATE(
                                CONVERT(VARCHAR(50), BIAYA_AJB.TGL_DOKUMEN)
                            ) = 1
                            THEN CONVERT(VARCHAR(50), BIAYA_AJB.TGL_DOKUMEN)
                            ELSE NULL
                        END
                    ) AS TGL_DOKUMEN_VALID
            ) AS TGL_REF

            WHERE
                /*
                 * Batas atas dibuat eksklusif (tanggal akhir + 1 hari) agar
                 * baris yang jamnya bukan 00:00 pada tanggal akhir tetap
                 * ikut. Query asli memakai <= tanggal akhir, sehingga baris
                 * seperti itu terlewat.
                 */
                TGL_REF.TGL_DOKUMEN_VALID
                    >= CONVERT(DATETIME, :tgl_awal, 112)
              AND TGL_REF.TGL_DOKUMEN_VALID
                    < CONVERT(DATETIME, :tgl_akhir_eksklusif, 112)

              /*
               * Cabang pertama membandingkan BLOK/NOMOR sebagai satu teks,
               * cabang kedua hanya blok. RTRIM dipasang pada NOMOR supaya
               * kolom CHAR yang dipadding spasi tidak menggeser hasil
               * perbandingan.
               */
              AND (
                    (
                        RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR)
                        BETWEEN :blok_awal_unit AND :blok_akhir_unit
                    )
                    OR
                    (
                        STOK.BLOK
                        BETWEEN :blok_awal_blok AND :blok_akhir_blok
                    )
                  )

              AND (
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))))
                        = :cluster_filter
                    OR :cluster_semua = '*'
                  )

              AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = :perusahaan
              AND STOK.BLOK IS NOT NULL
              AND STOK.NOMOR IS NOT NULL

            /*
             * Query asli tidak memiliki ORDER BY. Urutan ditambahkan supaya
             * baris dapat dikelompokkan per cluster pada laporan, persis
             * seperti tampilan desktop.
             */
            ORDER BY
                NM_CLUSTER ASC,
                STOK.BLOK ASC,
                CASE
                    WHEN LTRIM(RTRIM(ISNULL(STOK.NOMOR, ''))) <> ''
                     AND LTRIM(RTRIM(STOK.NOMOR)) NOT LIKE '%[^0-9]%'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN LTRIM(RTRIM(ISNULL(STOK.NOMOR, ''))) <> ''
                     AND LTRIM(RTRIM(STOK.NOMOR)) NOT LIKE '%[^0-9]%'
                    THEN RIGHT(
                        REPLICATE('0', 50) + LTRIM(RTRIM(STOK.NOMOR)),
                        50
                    )
                    ELSE ''
                END ASC,
                STOK.NOMOR ASC,
                BIAYA_AJB.NO_DOKUMEN ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'cluster_filter' => $cluster,
            'cluster_semua' => $cluster,
            'perusahaan' => $perusahaan,
        ]);
    }

    /**
     * Menormalisasi tanggal request menjadi format SQL Server style 112.
     */
    private function normalizeDate112($value, int $addDays = 0): string
    {
        $text = trim((string) $value);
        $formats = ['!Y-m-d', '!Ymd', '!d/m/Y', '!m/d/Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $text);
            $errors = DateTimeImmutable::getLastErrors();
            $valid = $date !== false
                && ($errors === false
                    || ($errors['warning_count'] === 0
                        && $errors['error_count'] === 0));

            if ($valid) {
                if ($addDays !== 0) {
                    $date = $date->modify(
                        ($addDays > 0 ? '+' : '') . $addDays . ' day'
                    );
                }

                return $date->format('Ymd');
            }
        }

        throw new RuntimeException(
            'Format tanggal tidak valid: ' . $text .
            '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }
}
