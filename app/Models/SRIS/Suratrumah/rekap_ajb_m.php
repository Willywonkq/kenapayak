<?php

// MODEL SQL SERVER LEGACY V1 - REKAP PPAT/AKTA JUAL BELI, ODBC SAFE

// MODEL VERSION SQLSERVER-WEBSA-SRIS-PUSAT-V1-20260902
// Sumber query: aplikasi desktop SRIS / SQL Server.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class rekap_ajb_m extends Model
{
    use HasFactory;

    /**
     * Koneksi SQL Server yang sudah ada pada config/database.php.
     */
    private const CONNECTION = 'websa';

    /**
     * Master lokasi, sama dengan fitur Daftar Akta Jual Beli.
     */
    public function obtainLokasi($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sql = <<<'SQL'
            SELECT
                RTRIM(LTRIM(LOKASI.KD_LOKASI)) AS KD_LOKASI,
                LOKASI.DESKRIPSI AS DESKRIPSI
            FROM [SRIS_PUSAT].[dbo].[LOKASI] AS LOKASI WITH (NOLOCK)
            WHERE LOKASI.KD_LOKASI IS NOT NULL
              AND RTRIM(LTRIM(LOKASI.KD_LOKASI)) <> ''
            ORDER BY
                LOKASI.DESKRIPSI,
                LOKASI.KD_LOKASI
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql)
        );
    }

    /**
     * Master sektor.
     */
    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sql = <<<'SQL'
            SELECT
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
     * Laporan Rekapitulasi PPAT/Akta Jual Beli.
     *
     * Checkbox "Belum Ttd Akta" memilih salah satu dari dua query desktop:
     *
     * - Tidak dicentang: baris berasal dari tabel AKTA dan disaring memakai
     *   rentang Tgl. Akta Jual Beli.
     * - Dicentang: baris berasal dari PPJB yang belum punya AKTA, atau sudah
     *   punya AKTA tetapi NO_AKTA masih kosong. Query desktop untuk keadaan
     *   ini tidak memakai rentang tanggal sama sekali, sehingga isian tanggal
     *   diabaikan. Kolom akta dan harga ikut kosong.
     */
    public function obtainRekapAktaJualBeli($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );
        $lokasi = $this->normalizeText($request->lokasi ?? '*');
        $sektor = $this->normalizeText($request->sektor ?? '*');
        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ');
        $belumTtdAkta = $this->normalizeYesNo($request->belum_ttd_akta ?? 'T');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($lokasi === '') {
            $lokasi = '*';
        }

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '' || $blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
        }

        if ($belumTtdAkta === 'Y') {
            return $this->obtainBelumTtdAkta(
                $perusahaan,
                $lokasi,
                $sektor,
                $blokAwal,
                $blokAkhir
            );
        }

        return $this->obtainSudahAdaAkta(
            $perusahaan,
            $lokasi,
            $sektor,
            $blokAwal,
            $blokAkhir,
            $this->normalizeDate112($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate112($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    /**
     * Query saat "Belum Ttd Akta" tidak dicentang.
     *
     * Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi antar
     * tabel dan seluruh kondisi WHERE tidak berubah, kecuali dua hal yang
     * dijelaskan pada komentar di dalam SQL.
     */
    private function obtainSudahAdaAkta(
        string $perusahaan,
        string $lokasi,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        $sql = <<<'SQL'
            SELECT
                RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR) AS BLOK_NOMOR,
                STOK.BLOK AS BLOK,
                STOK.NOMOR AS NOMOR,
                NASABAH.NAMA AS NAMA,

                STOK.LUAS_TANAH AS LUAS_TANAH,
                STOK.LUAS_BANGUNAN AS LUAS_BANGUNAN,

                AKTA.NO_NOTARIS AS NO_NOTARIS,
                AKTA.TGL_NOTARIS AS TGL_NOTARIS,
                AKTA.NOTARIS AS NOTARIS,
                AKTA.NO_AKTA AS NO_AKTA,
                TGL_REF.TGL_AKTA_VALID AS TGL_AKTA,
                AKTA.TTD_AKTA AS TTD_AKTA,
                AKTA.HARGA AS HARGA,
                AKTA.HARGA_NJOP AS HARGA_NJOP,

                (
                    SELECT TOP (1) X.NO_SERTIPIKAT
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS NO_SERTIPIKAT,
                (
                    SELECT TOP (1) X.TGL_SERTIPIKAT
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS TGL_SERTIPIKAT,
                (
                    SELECT TOP (1) X.LUAS_SUP
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS LUAS_SUP,

                NASABAH.TELP_RMH AS TELP_RMH,
                NASABAH.FAX_RMH AS FAX_RMH,
                NASABAH.TELP_KTR AS TELP_KTR,
                NASABAH.FAX_KTR AS FAX_KTR,
                NASABAH.NO_HP AS NO_HP,
                NASABAH.ALAMAT_RMH AS ALAMAT_RMH,
                NASABAH.KOTA_RMH AS KOTA_RMH,
                NASABAH.KODE_POS_RMH AS KODE_POS_RMH,

                PPJB.NO_PPJB AS NO_PPJB,
                PPJB.TGL_PPJB AS TGL_PPJB,
                PPJB.HARGA_JUAL AS HARGA_JUAL,

                STOK.KD_PERUSAHAAN AS KD_PERUSAHAAN,
                GETDATE() AS TGL_CETAK,
                PPJB.USER_ENTRY AS USER_ENTRY,

                (
                    SELECT TOP (1) LOKASI.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[LOKASI] AS LOKASI WITH (NOLOCK)
                    WHERE LOKASI.KD_LOKASI = STOK.KD_LOKASI
                ) AS NAMA_LOKASI,
                (
                    SELECT TOP (1) A.NAMA
                    FROM [SRIS_PUSAT].[dbo].[TIPE_BAYAR] AS A WITH (NOLOCK)
                    WHERE A.TIPE_BAYAR = PPJB.TIPE_BAYAR
                ) AS TIPE_BAYAR,
                (
                    SELECT TOP (1) A.NAMA
                    FROM [SRIS_PUSAT].[dbo].[BANK] AS A WITH (NOLOCK)
                    INNER JOIN [SRIS_PUSAT].[dbo].[PERJANJIAN_BANK] AS B WITH (NOLOCK)
                        ON B.KD_BANK = A.KD_BANK
                    WHERE B.PERJANJIAN_BANK_ID = PPJB.PERJANJIAN_BANK_ID
                ) AS BANK,
                (
                    SELECT TOP (1) A.NAMA_AGEN
                    FROM [SRIS_PUSAT].[dbo].[AGEN] AS A WITH (NOLOCK)
                    WHERE A.KD_AGEN = PPJB.KD_AGEN
                ) AS NM_AGEN,
                (
                    SELECT TOP (1) A.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[SALES] AS A WITH (NOLOCK)
                    WHERE A.KD_SALES = PPJB.KD_SALES
                ) AS NM_SALES,

                CAST('T' AS VARCHAR(1)) AS BELUM_TTD_AKTA

            FROM [SRIS_PUSAT].[dbo].[AKTA] AS AKTA WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
                ON PPJB.PPJB_ID = AKTA.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PEMBELI_PPJB WITH (NOLOCK)
                ON PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NASABAH WITH (NOLOCK)
                ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON STOK.STOK_ID = PPJB.STOK_ID

            /*
             * Pada database legacy kolom tanggal dapat berisi nilai yang
             * tidak valid, sehingga tanggal filter dikonversi aman dulu.
             */
            OUTER APPLY (
                SELECT
                    CONVERT(
                        DATETIME,
                        CASE
                            WHEN ISDATE(CONVERT(VARCHAR(50), AKTA.TGL_AKTA)) = 1
                            THEN CONVERT(VARCHAR(50), AKTA.TGL_AKTA)
                            ELSE NULL
                        END
                    ) AS TGL_AKTA_VALID
            ) AS TGL_REF

            WHERE STOK.FLAG_AKTIF = 'A'
              AND PPJB.FLAG_AKTIF = 'A'
              AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
              AND PPJB.PARENT_ID IS NULL
              /*
               * Batas bawah cabang kedua memakai blok awal. Pada query asli
               * kedua sisinya memakai BLOK_AKHIR, sehingga hanya cocok untuk
               * satu blok saja.
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
              /*
               * Batas atas dibuat eksklusif (tanggal akhir + 1 hari) agar
               * baris yang jamnya bukan 00:00 pada tanggal akhir tetap ikut.
               */
              AND TGL_REF.TGL_AKTA_VALID
                    >= CONVERT(DATETIME, :tgl_awal, 112)
              AND TGL_REF.TGL_AKTA_VALID
                    < CONVERT(DATETIME, :tgl_akhir_gpt, 112)
              AND (
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_LOKASI, ''))))
                        = :lokasi_filter
                    OR :lokasi_semua = '*'
                  )
              AND (
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))))
                        = :sektor_filter
                    OR :sektor_semua = '*'
                  )
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
                STOK.NOMOR ASC,
                AKTA.NO_AKTA ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_gpt' => $tglAkhirEksklusif,
            'lokasi_filter' => $lokasi,
            'lokasi_semua' => $lokasi,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'perusahaan' => $perusahaan,
        ]);
    }

    /**
     * Query saat "Belum Ttd Akta" dicentang.
     *
     * Sumbernya PPJB, bukan AKTA, sehingga kolom akta dan harga bernilai
     * NULL. Query desktop untuk keadaan ini tidak memakai rentang tanggal.
     */
    private function obtainBelumTtdAkta(
        string $perusahaan,
        string $lokasi,
        string $sektor,
        string $blokAwal,
        string $blokAkhir
    ): array {
        $sql = <<<'SQL'
            SELECT
                RTRIM(STOK.BLOK) + '/' + RTRIM(STOK.NOMOR) AS BLOK_NOMOR,
                STOK.BLOK AS BLOK,
                STOK.NOMOR AS NOMOR,
                NASABAH.NAMA AS NAMA,

                STOK.LUAS_TANAH AS LUAS_TANAH,
                STOK.LUAS_BANGUNAN AS LUAS_BANGUNAN,

                CAST(NULL AS VARCHAR(50)) AS NO_NOTARIS,
                CAST(NULL AS DATETIME) AS TGL_NOTARIS,
                CAST(NULL AS VARCHAR(100)) AS NOTARIS,
                CAST(NULL AS VARCHAR(50)) AS NO_AKTA,
                CAST(NULL AS DATETIME) AS TGL_AKTA,
                CAST(NULL AS VARCHAR(100)) AS TTD_AKTA,
                CAST(NULL AS NUMERIC(18, 2)) AS HARGA,
                CAST(NULL AS NUMERIC(18, 2)) AS HARGA_NJOP,

                (
                    SELECT TOP (1) X.NO_SERTIPIKAT
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS NO_SERTIPIKAT,
                (
                    SELECT TOP (1) X.TGL_SERTIPIKAT
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS TGL_SERTIPIKAT,
                (
                    SELECT TOP (1) X.LUAS_SUP
                    FROM [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS X WITH (NOLOCK)
                    WHERE X.STOK_ID = STOK.STOK_ID
                ) AS LUAS_SUP,

                NASABAH.TELP_RMH AS TELP_RMH,
                NASABAH.FAX_RMH AS FAX_RMH,
                NASABAH.TELP_KTR AS TELP_KTR,
                NASABAH.FAX_KTR AS FAX_KTR,
                NASABAH.NO_HP AS NO_HP,
                NASABAH.ALAMAT_RMH AS ALAMAT_RMH,
                NASABAH.KOTA_RMH AS KOTA_RMH,
                NASABAH.KODE_POS_RMH AS KODE_POS_RMH,

                PPJB.NO_PPJB AS NO_PPJB,
                PPJB.TGL_PPJB AS TGL_PPJB,
                PPJB.HARGA_JUAL AS HARGA_JUAL,

                STOK.KD_PERUSAHAAN AS KD_PERUSAHAAN,
                GETDATE() AS TGL_CETAK,
                PPJB.USER_ENTRY AS USER_ENTRY,

                (
                    SELECT TOP (1) LOKASI.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[LOKASI] AS LOKASI WITH (NOLOCK)
                    WHERE LOKASI.KD_LOKASI = STOK.KD_LOKASI
                ) AS NAMA_LOKASI,
                CAST(NULL AS VARCHAR(100)) AS TIPE_BAYAR,
                CAST(NULL AS VARCHAR(100)) AS BANK,
                (
                    SELECT TOP (1) A.NAMA_AGEN
                    FROM [SRIS_PUSAT].[dbo].[AGEN] AS A WITH (NOLOCK)
                    WHERE A.KD_AGEN = PPJB.KD_AGEN
                ) AS NM_AGEN,
                (
                    SELECT TOP (1) A.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[SALES] AS A WITH (NOLOCK)
                    WHERE A.KD_SALES = PPJB.KD_SALES
                ) AS NM_SALES,

                CAST('Y' AS VARCHAR(1)) AS BELUM_TTD_AKTA

            FROM [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PEMBELI_PPJB WITH (NOLOCK)
                ON PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NASABAH WITH (NOLOCK)
                ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON STOK.STOK_ID = PPJB.STOK_ID

            WHERE STOK.FLAG_AKTIF = 'A'
              AND PPJB.FLAG_AKTIF = 'A'
              AND PEMBELI_PPJB.FLAG_AKTIF = 'Y'
              AND PPJB.PARENT_ID IS NULL
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
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_LOKASI, ''))))
                        = :lokasi_filter
                    OR :lokasi_semua = '*'
                  )
              AND (
                    UPPER(RTRIM(LTRIM(ISNULL(STOK.KD_SEKTOR, ''))))
                        = :sektor_filter
                    OR :sektor_semua = '*'
                  )
              AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = :perusahaan
              AND STOK.BLOK IS NOT NULL
              AND STOK.NOMOR IS NOT NULL

              /*
               * Dasar pengecekan berdasarkan PPJB, karena satu unit dapat
               * memiliki lebih dari satu sertipikat. Mengikuti catatan pada
               * query desktop tertanggal 14 Jan 25.
               *
               * Bentuk NOT IN / IN pada query asli ditulis ulang menjadi
               * NOT EXISTS / EXISTS dengan arti yang sama persis: baris ikut
               * bila PPJB belum punya baris AKTA sama sekali, atau punya
               * baris AKTA yang NO_AKTA-nya masih kosong. Syarat
               * PPJB_ID IS NOT NULL pada query asli hanya untuk mengamankan
               * NOT IN terhadap NULL, dan sudah tercakup oleh korelasi
               * A.PPJB_ID = PPJB.PPJB_ID.
               */
              AND (
                    NOT EXISTS (
                        SELECT 1
                        FROM [SRIS_PUSAT].[dbo].[AKTA] AS A WITH (NOLOCK)
                        WHERE A.PPJB_ID = PPJB.PPJB_ID
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM [SRIS_PUSAT].[dbo].[AKTA] AS A WITH (NOLOCK)
                        WHERE A.PPJB_ID = PPJB.PPJB_ID
                          AND A.NO_AKTA IS NULL
                    )
                  )

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
                STOK.NOMOR ASC,
                PPJB.NO_PPJB ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'lokasi_filter' => $lokasi,
            'lokasi_semua' => $lokasi,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
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

    private function normalizeYesNo($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array(
            $normalized,
            ['Y', '1', 'TRUE', 'ON'],
            true
        ) ? 'Y' : 'T';
    }
}
