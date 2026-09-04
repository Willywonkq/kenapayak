<?php

// MODEL SQL SERVER LEGACY V1 - REKAP PERALIHAN HAK, ODBC SAFE

// MODEL VERSION SQLSERVER-WEBSA-SRIS-PUSAT-V1-20260904
// Sumber query: aplikasi desktop SRIS / SQL Server.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class rekap_peralihan_hak_m extends Model
{
    /**
     * Koneksi SQL Server yang sudah ada pada config/database.php.
     */
    private const CONNECTION = 'websa';

    use HasFactory;

    /**
     * Master cluster, sama dengan fitur Rekap Estimasi Biaya AJB.
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
     * Entry utama data laporan.
     *
     * Query desktop untuk seluruh kombinasi Status Entry dan Status Approve
     * isinya sama persis; yang membedakan hanya nilai kedua parameternya.
     * Karena itu di sini cukup satu query dengan dua parameter tersebut.
     */
    public function obtainRekapPeralihanHak($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );
        $cluster = $this->normalizeText($request->cluster ?? '*');
        $stsEntry = $this->normalizeStatus($request->sts_entry ?? '*');
        $stsApprove = $this->normalizeStatus($request->sts_approve ?? '*');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($cluster === '') {
            $cluster = '*';
        }

        return $this->obtainPeralihan(
            $perusahaan,
            $cluster,
            $stsEntry,
            $stsApprove,
            $this->normalizeDate112($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate112($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    /**
     * Query laporan.
     *
     * Penyesuaian terhadap query desktop dijelaskan pada komentar di dalam
     * SQL. Placeholder ODBC bersifat posisional, sehingga setiap nama hanya
     * dipakai satu kali dan urutan binding mengikuti urutan kemunculannya.
     */
    private function obtainPeralihan(
        string $perusahaan,
        string $cluster,
        string $stsEntry,
        string $stsApprove,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        $sql = <<<'SQL'
            SELECT
                RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR) AS BLOK_NOMOR,
                STOK.BLOK AS BLOK,
                STOK.NOMOR AS NOMOR,

                STOK.LUAS_TANAH AS LUAS_TANAH,
                PPJB.LUAS_BANGUNAN AS LUAS_BANGUNAN,
                STOK.LUAS_SEMI_GROSS AS LUAS_SEMI_GROSS,
                TIPE.DESKRIPSI AS TIPE_BANGUNAN,
                SEKTOR.DESKRIPSI AS NAMA_CLUSTER,

                PERALIHAN.PERALIHAN_ID AS PERALIHAN_ID,
                TGL_REF.TGL_PERALIHAN_VALID AS TGL_PERALIHAN,
                PERALIHAN.NOTARIS AS NOTARIS,
                PERALIHAN.TGL_NOTARIS AS TGL_NOTARIS,
                PERALIHAN.NO_KUITANSI AS NO_KUITANSI,
                PERALIHAN.TGL_KUITANSI AS TGL_KUITANSI,
                PERALIHAN.JML_KUITANSI AS JML_KUITANSI,
                PERALIHAN.HARGA_PASAR AS HARGA_PASAR,
                PERALIHAN.NM_AGEN AS NM_AGEN,
                PERALIHAN.NM_SALES AS NM_SALES,
                PERALIHAN.NO_TELP AS NO_TELP,

                (
                    SELECT TOP (1) A.NAMA
                    FROM [SRIS_PUSAT].[dbo].[NASABAH] AS A WITH (NOLOCK)
                    WHERE A.NASABAH_ID = PEMBELI_LAMA.NASABAH_ID
                ) AS PEMBELI_LAMA,
                (
                    SELECT TOP (1) A.NAMA
                    FROM [SRIS_PUSAT].[dbo].[NASABAH] AS A WITH (NOLOCK)
                    WHERE A.NASABAH_ID = PEMBELI_BARU.NASABAH_ID
                ) AS PEMBELI_BARU,

                CASE
                    WHEN ISNULL(PERALIHAN.FLAG_ENTRY, 'T') = 'Y'
                    THEN 'Sudah Entry'
                    ELSE 'Belum Entry'
                END AS STS_PEMBELI_BARU,

                (
                    SELECT TOP (1) X.TGL_KUITANSI
                    FROM [SRIS_PUSAT].[dbo].[ANGSURAN] AS X WITH (NOLOCK)
                    WHERE X.PPJB_ID = PPJB.PPJB_ID
                      AND X.KD_TRANSAKSI = 'BPH'
                ) AS TGL_KUITANSI_BPH,

                PPJB.DPP AS HARGA_EXCLUDE_PPN,
                PPJB.HARGA_JUAL AS HARGA_INCLUDE_PPN,

                STOK.KD_SEKTOR AS KD_SEKTOR,
                STOK.KD_PERUSAHAAN AS KD_PERUSAHAAN,
                GETDATE() AS TGL_CETAK

            /*
             * Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi
             * antar tabel tidak berubah, termasuk PEMBELI_LAMA dan
             * PEMBELI_BARU yang tetap dipasangkan lewat PERALIHAN_ID.
             */
            FROM [SRIS_PUSAT].[dbo].[PERALIHAN] AS PERALIHAN WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
                ON PERALIHAN.PPJB_ID = PPJB.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_LAMA] AS PEMBELI_LAMA WITH (NOLOCK)
                ON PERALIHAN.PERALIHAN_ID = PEMBELI_LAMA.PERALIHAN_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_BARU] AS PEMBELI_BARU WITH (NOLOCK)
                ON PERALIHAN.PERALIHAN_ID = PEMBELI_BARU.PERALIHAN_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON PPJB.STOK_ID = STOK.STOK_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[TIPE] AS TIPE WITH (NOLOCK)
                ON STOK.KD_JENIS = TIPE.KD_JENIS
               AND STOK.KD_TIPE = TIPE.KD_TIPE
            INNER JOIN [SRIS_PUSAT].[dbo].[SEKTOR] AS SEKTOR WITH (NOLOCK)
                ON STOK.KD_SEKTOR = SEKTOR.KD_SEKTOR

            /*
             * Pada database legacy kolom tanggal dapat berisi nilai yang
             * tidak valid, sehingga tanggal peralihan dikonversi aman dulu
             * sebelum dibandingkan dengan rentang filter.
             */
            OUTER APPLY (
                SELECT
                    CONVERT(
                        DATETIME,
                        CASE
                            WHEN ISDATE(
                                CONVERT(VARCHAR(50), PERALIHAN.TGL_PERALIHAN)
                            ) = 1
                            THEN CONVERT(VARCHAR(50), PERALIHAN.TGL_PERALIHAN)
                            ELSE NULL
                        END
                    ) AS TGL_PERALIHAN_VALID
            ) AS TGL_REF

            WHERE (
                    ISNULL(PERALIHAN.FLAG_ENTRY, 'T') = :sts_entry_filter
                    OR :sts_entry_semua = '*'
                  )

              /*
               * Batas atas dibuat eksklusif (tanggal akhir + 1 hari) agar
               * baris yang jamnya bukan 00:00 pada tanggal akhir tetap
               * ikut. Query asli memakai <= tanggal akhir, sehingga baris
               * seperti itu terlewat.
               */
              AND TGL_REF.TGL_PERALIHAN_VALID
                    >= CONVERT(DATETIME, :tgl_awal, 112)
              AND TGL_REF.TGL_PERALIHAN_VALID
                    < CONVERT(DATETIME, :tgl_akhir_eksklusif, 112)

              AND (
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))))
                        = :cluster_filter
                    OR :cluster_semua = '*'
                  )

              AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = :perusahaan

              /*
               * Status approval. Bentuk ISNULL((SELECT COUNT(*) ...), 0) > 0
               * pada query asli ditulis ulang menjadi EXISTS dengan arti
               * yang sama persis: COUNT(*) pada subquery skalar tidak pernah
               * NULL, dan "lebih dari nol" sama dengan "ada barisnya".
               */
              AND (
                    (
                        'T' = :sts_approve_belum
                        AND EXISTS (
                            SELECT 1
                            FROM [SRIS_PUSAT].[dbo].[APPROVAL] AS A WITH (NOLOCK)
                            WHERE A.PARENT_ID = PERALIHAN.PERALIHAN_ID
                              AND A.JENIS = '6'
                              AND ISNULL(A.APPROVE1, 'T') = 'T'
                              AND ISNULL(A.APPROVE2, 'T') = 'T'
                        )
                    )
                    OR
                    (
                        'Y' = :sts_approve_sudah
                        AND EXISTS (
                            SELECT 1
                            FROM [SRIS_PUSAT].[dbo].[APPROVAL] AS A WITH (NOLOCK)
                            WHERE A.PARENT_ID = PERALIHAN.PERALIHAN_ID
                              AND A.JENIS = '6'
                              AND ISNULL(A.APPROVE1, 'T') = 'Y'
                              AND ISNULL(A.APPROVE2, 'T') = 'Y'
                        )
                    )
                    OR
                    (
                        '*' = :sts_approve_semua
                    )
                  )

            /*
             * Query asli tidak memiliki ORDER BY. Urutan ditambahkan supaya
             * baris dapat dikelompokkan per cluster pada laporan, persis
             * seperti tampilan desktop.
             */
            ORDER BY
                SEKTOR.DESKRIPSI ASC,
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
                TGL_REF.TGL_PERALIHAN_VALID ASC,
                PERALIHAN.PERALIHAN_ID ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'sts_entry_filter' => $stsEntry,
            'sts_entry_semua' => $stsEntry,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'cluster_filter' => $cluster,
            'cluster_semua' => $cluster,
            'perusahaan' => $perusahaan,
            'sts_approve_belum' => $stsApprove,
            'sts_approve_sudah' => $stsApprove,
            'sts_approve_semua' => $stsApprove,
        ]);
    }

    /**
     * Label status untuk header laporan.
     */
    public function getStatusEntryLabel(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'Y' => 'Sudah Entry',
            'T' => 'Belum Entry',
            default => 'Semua',
        };
    }

    public function getStatusApproveLabel(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'Y' => 'Sudah',
            'T' => 'Belum',
            default => 'Semua',
        };
    }

    /**
     * Status hanya mengenal tiga nilai: 'Y', 'T', dan '*' untuk semua.
     */
    private function normalizeStatus($value): string
    {
        $status = strtoupper(trim((string) $value));

        return in_array($status, ['Y', 'T'], true) ? $status : '*';
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
