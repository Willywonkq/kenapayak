<?php

// MODEL SQL SERVER LEGACY V1 - DAFTAR AKTA JUAL BELI, ODBC SAFE

// MODEL VERSION SQLSERVER-WEBSA-SRIS-PUSAT-V1-20260902
// Sumber query: aplikasi desktop SRIS / SQL Server.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class daftar_akta_jual_beli_m extends Model
{
    use HasFactory;

    /**
     * Menggunakan koneksi SQL Server yang sudah ada pada config/database.php,
     * yaitu "websa". Objek sumber tetap diarahkan eksplisit ke SRIS_PUSAT.
     */
    private const CONNECTION = 'websa';

    /**
     * Master lokasi.
     *
     * Data lokasi memang tersimpan di tabel LOKASI. Query laporan desktop
     * membuktikannya lewat dua baris berikut:
     *
     *     ( SELECT DESKRIPSI FROM LOKASI WHERE KD_LOKASI = STOK.KD_LOKASI )
     *     ( STOK.KD_LOKASI = :lokasi OR :lokasi = '*' )
     *
     * Jadi LOKASI menyimpan KD_LOKASI dan DESKRIPSI, sedangkan STOK
     * menyimpan KD_LOKASI per unit.
     *
     * Daftar dibaca langsung dan apa adanya seperti lookup desktop, tanpa
     * disaring lewat STOK. Penyaringan lewat STOK harus membandingkan kolom
     * kode yang sudah di-RTRIM, sehingga index tidak terpakai dan pencarian
     * menjadi berat pada tabel STOK yang besar.
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
     * Master sektor, mengikuti pola fitur Daftar Sertipikat Pecahan.
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
     * Laporan Daftar Akta Jual Beli.
     *
     * Query mengikuti query desktop yang diberikan, dengan tiga penyesuaian:
     *
     * 1. Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi antar
     *    tabel dan seluruh kondisi WHERE tidak berubah.
     * 2. Batas blok bawah pada cabang kedua memakai :blok_awal_blok. Pada
     *    query asli cabang itu tertulis
     *    ( STOK.BLOK >= :BLOK_AKHIR AND STOK.BLOK <= :BLOK_AKHIR ),
     *    sehingga hanya cocok untuk satu blok saja.
     * 3. Batas tanggal atas dibuat eksklusif (tanggal akhir + 1 hari) agar
     *    baris yang jam-nya bukan 00:00 pada tanggal akhir tetap ikut,
     *    sama seperti fitur lain di aplikasi ini.
     */
    public function obtainDaftarAktaJualBeli($request): array
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

        // Format SQL Server style 112 (YYYYMMDD) tidak bergantung pada
        // bahasa/DATEFORMAT server.
        $tglAwal = $this->normalizeDate112(
            $request->tgl_awal ?? date('Y-m-d')
        );
        $tglAkhirEksklusif = $this->normalizeDate112(
            $request->tgl_akhir ?? date('Y-m-d'),
            1
        );

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
                AKTA.TGL_INPUT AS TGL_INPUT,
                AKTA.TTD_AKTA AS TTD_AKTA,
                AKTA.TGL_ENTRY AS TGL_ENTRY,
                AKTA.USER_ENTRY AS USER_ENTRY,

                PENGAMBILAN.TGL_AMBIL_AKTA AS TGL_AMBIL_AKTA,
                PENGAMBILAN.TGL_CETAK_AKTA AS TGL_CETAK_AKTA,

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

                (
                    SELECT TOP (1) LOKASI.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[LOKASI] AS LOKASI WITH (NOLOCK)
                    WHERE LOKASI.KD_LOKASI = STOK.KD_LOKASI
                ) AS NAMA_LOKASI,
                (
                    SELECT TOP (1) SEKTOR.DESKRIPSI
                    FROM [SRIS_PUSAT].[dbo].[SEKTOR] AS SEKTOR WITH (NOLOCK)
                    WHERE SEKTOR.KD_SEKTOR = STOK.KD_SEKTOR
                ) AS NAMA_SEKTOR,
                (
                    SELECT TOP (1) ANGSURAN.TGL_KUITANSI
                    FROM [SRIS_PUSAT].[dbo].[ANGSURAN] AS ANGSURAN WITH (NOLOCK)
                    WHERE ANGSURAN.PPJB_ID = PPJB.PPJB_ID
                      AND ANGSURAN.KD_TRANSAKSI = 'BBN'
                ) AS TGL_KUITANSI_BBN

            FROM [SRIS_PUSAT].[dbo].[AKTA] AS AKTA WITH (NOLOCK)
            INNER JOIN [SRIS_PUSAT].[dbo].[PPJB] AS PPJB WITH (NOLOCK)
                ON PPJB.PPJB_ID = AKTA.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[PEMBELI_PPJB] AS PEMBELI_PPJB WITH (NOLOCK)
                ON PEMBELI_PPJB.PPJB_ID = PPJB.PPJB_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[NASABAH] AS NASABAH WITH (NOLOCK)
                ON NASABAH.NASABAH_ID = PEMBELI_PPJB.NASABAH_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[SERTIPIKAT] AS SERTIPIKAT WITH (NOLOCK)
                ON SERTIPIKAT.SERTIPIKAT_ID = AKTA.SERTIPIKAT_ID
            LEFT OUTER JOIN [SRIS_PUSAT].[dbo].[PENGAMBILAN] AS PENGAMBILAN WITH (NOLOCK)
                ON PENGAMBILAN.SERTIPIKAT_ID = SERTIPIKAT.SERTIPIKAT_ID
            INNER JOIN [SRIS_PUSAT].[dbo].[STOK] AS STOK WITH (NOLOCK)
                ON STOK.STOK_ID = SERTIPIKAT.STOK_ID

            /*
             * Pada database legacy kolom tanggal dapat berisi karakter
             * kosong/tidak valid, sehingga tanggal filter dikonversi
             * secara aman lebih dulu.
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
              AND TGL_REF.TGL_AKTA_VALID
                    >= CONVERT(DATETIME, :tgl_awal, 112)
              AND TGL_REF.TGL_AKTA_VALID
                    < CONVERT(DATETIME, :tgl_akhir_gpt, 112)
              AND UPPER(RTRIM(LTRIM(STOK.KD_PERUSAHAAN))) = :perusahaan
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
              AND STOK.BLOK IS NOT NULL
              AND STOK.NOMOR IS NOT NULL
              AND SERTIPIKAT.STOK_ID IS NOT NULL

            ORDER BY
                STOK.KD_SEKTOR ASC,
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

        /*
         * Urutan elemen binding harus sama dengan urutan kemunculan
         * placeholder pada SQL. ODBC SQL Server memperlakukan named
         * placeholder sebagai positional binding, sehingga satu nama
         * tidak boleh dipakai dua kali.
         */
        return DB::connection(self::CONNECTION)->select($sql, [
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_gpt' => $tglAkhirEksklusif,
            'perusahaan' => $perusahaan,
            'lokasi_filter' => $lokasi,
            'lokasi_semua' => $lokasi,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
        ]);
    }

    /**
     * Menormalisasi tanggal request menjadi format SQL Server style 112.
     * Mendukung nilai HTML date (Y-m-d) dan dua format slash umum.
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
