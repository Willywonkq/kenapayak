<?php

namespace App\Models\SRIS\PeralihanHak;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_peralihan_hak_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    public function obtainCluster($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorPerusahaan = $this->kolomKode('sr_sektor', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $sql = <<<SQL
            SELECT
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                    AS "KD_CLUSTER",
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                    AS "KD_SEKTOR",
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')) AS "DESKRIPSI",
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                    AS "KD_PERUSAHAAN"
            FROM public.sr_sektor AS sektor
            WHERE UPPER(BTRIM(COALESCE(CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), ''))) <> ''
              AND (
                    UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                        = :kd_perusahaan
                    OR UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                        = ''
                  )
            ORDER BY
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')),
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql, [
                'kd_perusahaan' => $kdPerusahaan,
            ])
        );
    }

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
            $this->normalizeDate($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    private function obtainPeralihan(
        string $perusahaan,
        string $cluster,
        string $stsEntry,
        string $stsApprove,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $stokJenis = $this->kolomKode('sr_stok', ['kd_jenis_bgn', 'kd_jenis']);
        $stokTipe = $this->kolomKode('sr_stok', ['kd_tipe_bgn', 'kd_tipe']);

        $nmAgen = $this->kolomOpsional('sr_peralihan', 'peralihan', 'nm_agen');
        $nmSales = $this->kolomOpsional('sr_peralihan', 'peralihan', 'nm_sales');
        $noTelp = $this->kolomOpsional('sr_peralihan', 'peralihan', 'no_telp');

        $sql = <<<SQL
            WITH nasabah_kunci AS (
                SELECT
                    REGEXP_REPLACE(BTRIM(CAST(nasabah_id AS TEXT)), '^[^0-9]+', '')
                        AS angka,
                    MIN(BTRIM(CAST(nasabah_id AS TEXT))) AS id_lengkap,
                    COUNT(*) AS jumlah
                FROM public.sr_nasabah
                WHERE nasabah_id IS NOT NULL
                GROUP BY 1
            ),
            peralihan_terpilih AS (
                SELECT
                    peralihan.*,
                    CASE
                        WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                    END AS tgl_peralihan_valid,
                    BTRIM(CAST(peralihan.peralihan_id AS TEXT)) AS kunci_peralihan,
                    CASE
                        WHEN BTRIM(CAST(peralihan.ppjb_id AS TEXT)) !~ '^[0-9]+$'
                        THEN BTRIM(CAST(peralihan.ppjb_id AS TEXT))
                        ELSE :awalan_peralihan
                             || BTRIM(CAST(peralihan.ppjb_id AS TEXT))
                    END AS kunci_ppjb
                FROM public.sr_peralihan AS peralihan
                WHERE CASE
                          WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                      END < CAST(:tgl_akhir_eksklusif AS DATE)
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(peralihan.flag_entry AS TEXT), 'T')))
                            = :sts_entry_filter
                        OR :sts_entry_semua = '*'
                      )
            ),
            stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE UPPER(BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                        = :perusahaan
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :cluster_filter
                        OR :cluster_semua = '*'
                      )
            ),
            angsuran_bph AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, tgl_kuitansi
                FROM (
                    SELECT
                        BTRIM(CAST(x.ppjb_id AS TEXT)) AS kode,
                        x.tgl_kuitansi AS tgl_kuitansi,
                        x.ctid AS urutan_fisik
                    FROM public.sr_angsuran AS x
                    WHERE UPPER(BTRIM(COALESCE(CAST(x.kd_transaksi AS TEXT), '')))
                          = 'BPH'
                ) AS daftar
                ORDER BY kode, urutan_fisik
            )

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",

                stok.luas_tanah AS "LUAS_TANAH",
                ppjb.luas_bangunan AS "LUAS_BANGUNAN",
                stok.luas_semi_gross AS "LUAS_SEMI_GROSS",
                tipe.deskripsi AS "TIPE_BANGUNAN",
                sektor.deskripsi AS "NAMA_CLUSTER",

                peralihan.peralihan_id AS "PERALIHAN_ID",
                peralihan.tgl_peralihan_valid AS "TGL_PERALIHAN",
                peralihan.notaris AS "NOTARIS",
                peralihan.tgl_notaris AS "TGL_NOTARIS",
                peralihan.no_kuitansi AS "NO_KUITANSI",
                peralihan.tgl_kuitansi AS "TGL_KUITANSI",
                peralihan.jml_kuitansi AS "JML_KUITANSI",
                peralihan.harga_pasar AS "HARGA_PASAR",
                {$nmAgen} AS "NM_AGEN",
                {$nmSales} AS "NM_SALES",
                {$noTelp} AS "NO_TELP",

                nasabah_lama.nama AS "PEMBELI_LAMA",
                nasabah_baru.nama AS "PEMBELI_BARU",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(peralihan.flag_entry AS TEXT), 'T')))
                         = 'Y'
                    THEN 'Sudah Entry'
                    ELSE 'Belum Entry'
                END AS "STS_PEMBELI_BARU",

                angsuran_bph.tgl_kuitansi AS "TGL_KUITANSI_BPH",

                ppjb.dpp AS "HARGA_EXCLUDE_PPN",
                ppjb.harga_jual AS "HARGA_INCLUDE_PPN",

                stok.{$stokSektor} AS "KD_SEKTOR",
                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK"

            FROM peralihan_terpilih AS peralihan

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT)) = peralihan.kunci_ppjb

            INNER JOIN public.sr_pembeli_lama AS pembeli_lama
                ON BTRIM(CAST(pembeli_lama.peralihan_id AS TEXT))
                 = peralihan.kunci_peralihan

            INNER JOIN public.sr_pembeli_baru AS pembeli_baru
                ON BTRIM(CAST(pembeli_baru.peralihan_id AS TEXT))
                 = peralihan.kunci_peralihan

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

            LEFT JOIN nasabah_kunci AS kunci_nasabah_lama
                ON kunci_nasabah_lama.angka
                 = REGEXP_REPLACE(
                       BTRIM(CAST(pembeli_lama.nasabah_id AS TEXT)), '^[^0-9]+', ''
                   )
               AND kunci_nasabah_lama.jumlah = 1
            LEFT JOIN public.sr_nasabah AS nasabah_lama
                ON BTRIM(CAST(nasabah_lama.nasabah_id AS TEXT))
                 = CASE
                       WHEN BTRIM(CAST(pembeli_lama.nasabah_id AS TEXT)) !~ '^[0-9]+$'
                       THEN BTRIM(CAST(pembeli_lama.nasabah_id AS TEXT))
                       ELSE kunci_nasabah_lama.id_lengkap
                   END

            LEFT JOIN nasabah_kunci AS kunci_nasabah_baru
                ON kunci_nasabah_baru.angka
                 = REGEXP_REPLACE(
                       BTRIM(CAST(pembeli_baru.nasabah_id AS TEXT)), '^[^0-9]+', ''
                   )
               AND kunci_nasabah_baru.jumlah = 1
            LEFT JOIN public.sr_nasabah AS nasabah_baru
                ON BTRIM(CAST(nasabah_baru.nasabah_id AS TEXT))
                 = CASE
                       WHEN BTRIM(CAST(pembeli_baru.nasabah_id AS TEXT)) !~ '^[0-9]+$'
                       THEN BTRIM(CAST(pembeli_baru.nasabah_id AS TEXT))
                       ELSE kunci_nasabah_baru.id_lengkap
                   END

            LEFT JOIN public.sr_tipe AS tipe
                ON BTRIM(CAST(tipe.kd_jenis AS TEXT))
                 = BTRIM(CAST(stok.{$stokJenis} AS TEXT))
               AND BTRIM(CAST(tipe.kd_tipe AS TEXT))
                 = BTRIM(CAST(stok.{$stokTipe} AS TEXT))

            LEFT JOIN public.sr_sektor AS sektor
                ON UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))

            LEFT JOIN angsuran_bph
                ON angsuran_bph.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            WHERE (
                    (
                        'T' = :sts_approve_belum
                        AND EXISTS (
                            SELECT 1
                            FROM public.sr_approval AS a
                            WHERE BTRIM(CAST(a.parent_id AS TEXT))
                                = peralihan.kunci_peralihan
                              AND BTRIM(CAST(a.jenis AS TEXT)) = '6'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve1 AS TEXT), 'T'))) = 'T'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve2 AS TEXT), 'T'))) = 'T'
                        )
                    )
                    OR
                    (
                        'Y' = :sts_approve_sudah
                        AND EXISTS (
                            SELECT 1
                            FROM public.sr_approval AS a
                            WHERE BTRIM(CAST(a.parent_id AS TEXT))
                                = peralihan.kunci_peralihan
                              AND BTRIM(CAST(a.jenis AS TEXT)) = '6'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve1 AS TEXT), 'T'))) = 'Y'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve2 AS TEXT), 'T'))) = 'Y'
                        )
                    )
                    OR
                    (
                        '*' = :sts_approve_semua
                    )
                  )

            ORDER BY
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')) ASC,
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) ASC,
                CASE
                    WHEN BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')) ~ '^[0-9]+$'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')) ~ '^[0-9]+$'
                    THEN LPAD(BTRIM(CAST(stok.nomor AS TEXT)), 50, '0')
                    ELSE ''
                END ASC,
                stok.nomor ASC,
                peralihan.tgl_peralihan_valid ASC,
                peralihan.peralihan_id ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'awalan_peralihan' => $this->awalanPeralihan(),
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

    private function normalizeStatus($value): string
    {
        $status = strtoupper(trim((string) $value));

        return in_array($status, ['Y', 'T'], true) ? $status : '*';
    }

    private function awalanPeralihan(): string
    {
        static $awalan = null;

        if ($awalan !== null) {
            return $awalan;
        }

        $sql = <<<SQL
            SELECT
                pp.awalan AS awalan,
                COUNT(*) FILTER (
                    WHERE pr.tgl_peralihan IS NOT NULL
                      AND pp.tgl_ppjb IS NOT NULL
                      AND pr.tgl_peralihan >= CAST(pp.tgl_ppjb AS TIMESTAMP)
                ) AS layak,
                COUNT(*) FILTER (
                    WHERE pr.tgl_peralihan IS NOT NULL
                      AND pp.tgl_ppjb IS NOT NULL
                      AND pr.tgl_peralihan < CAST(pp.tgl_ppjb AS TIMESTAMP)
                ) AS melanggar
            FROM (
                SELECT
                    REGEXP_REPLACE(BTRIM(CAST(x.ppjb_id AS TEXT)), '[0-9]+$', '')
                        AS awalan,
                    REGEXP_REPLACE(BTRIM(CAST(x.ppjb_id AS TEXT)), '^[^0-9]+', '')
                        AS angka,
                    x.tgl_ppjb AS tgl_ppjb
                FROM public.sr_ppjb AS x
                WHERE x.ppjb_id IS NOT NULL
            ) AS pp
            INNER JOIN (
                SELECT
                    BTRIM(CAST(p.ppjb_id AS TEXT)) AS angka,
                    CASE
                        WHEN COALESCE(CAST(p.tgl_peralihan AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(p.tgl_peralihan AS TIMESTAMP)
                    END AS tgl_peralihan
                FROM public.sr_peralihan AS p
                WHERE BTRIM(CAST(p.ppjb_id AS TEXT)) ~ '^[0-9]+$'
            ) AS pr
                ON pr.angka = pp.angka
            GROUP BY 1
            HAVING COUNT(*) FILTER (
                       WHERE pr.tgl_peralihan IS NOT NULL
                         AND pp.tgl_ppjb IS NOT NULL
                         AND pr.tgl_peralihan >= CAST(pp.tgl_ppjb AS TIMESTAMP)
                   ) > 0
            ORDER BY 3 ASC, 2 DESC
            LIMIT 1
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql);
        $awalan = $baris ? (string) $baris[0]->awalan : '';

        return $awalan;
    }

    private function kolomOpsional(
        string $tabel,
        string $alias,
        string $kolom
    ): string {
        return $this->adaKolom($tabel, $kolom)
            ? $alias . '.' . $kolom
            : 'NULL';
    }

    private function adaKolom(string $tabel, string $kolom): bool
    {
        return in_array(
            strtolower($kolom),
            $this->kolomTabel($tabel),
            true
        );
    }

    private function kolomKode(string $tabel, array $kandidat): string
    {
        $tersedia = $this->kolomTabel($tabel);

        foreach ($kandidat as $kolom) {
            if (in_array(strtolower($kolom), $tersedia, true)) {
                return strtolower($kolom);
            }
        }

        throw new RuntimeException(
            'Kolom kode tidak ditemukan pada tabel ' . $tabel . ': '
            . implode(', ', $kandidat)
        );
    }

    private function kolomTabel(string $tabel): array
    {
        static $ingatan = [];

        if (!isset($ingatan[$tabel])) {
            $baris = DB::connection(self::CONNECTION)->select(
                'SELECT column_name
                   FROM information_schema.columns
                  WHERE table_schema = :schema
                    AND table_name = :tabel',
                ['schema' => self::SCHEMA, 'tabel' => $tabel]
            );

            $ingatan[$tabel] = array_map(
                static fn ($item) => strtolower($item->column_name),
                $baris
            );
        }

        return $ingatan[$tabel];
    }

    private function normalizeDate($value, int $addDays = 0): string
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

                return $date->format('Y-m-d');
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
