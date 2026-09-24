<?php

namespace App\Models\SRIS\AktaJualBeli;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class rekap_estimasi_biaya_ajb_m extends Model
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

    public function obtainBlok($kdPerusahaan): array
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return [];
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokLokasi = $this->kolomKode('sr_stok', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $lokasiKode = $this->kolomKode('sr_lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $stokJenis = $this->kolomKode('sr_stok', ['kd_jenis_bgn', 'kd_jenis']);
        $stokTipe = $this->kolomKode('sr_stok', ['kd_tipe_bgn', 'kd_tipe']);

        $sql = <<<SQL
            WITH tipe_unik AS (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(t.kd_jenis AS TEXT), '')) || '|'
                            || BTRIM(COALESCE(CAST(t.kd_tipe AS TEXT), '')) AS kode,
                        t.deskripsi AS deskripsi,
                        t.ctid AS urutan_fisik
                    FROM public.sr_tipe AS t
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            lokasi_unik AS (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(l.{$lokasiKode} AS TEXT), '')) AS kode,
                        l.deskripsi AS deskripsi,
                        l.ctid AS urutan_fisik
                    FROM public.sr_lokasi AS l
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            sektor_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(s.{$sektorKode} AS TEXT), '')) AS kode,
                        s.deskripsi AS deskripsi,
                        s.ctid AS urutan_fisik
                    FROM public.sr_sektor AS s
                ) AS daftar
                ORDER BY kode, urutan_fisik
            )

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || COALESCE(CAST(stok.nomor AS TEXT), '') AS "BLOK_NOMOR",
                ppjb.ppjb_id AS "PPJB_ID",
                nasabah.nama AS "NAMA_PEMBELI",
                ppjb.no_ppjb AS "NO_PPJB",
                ppjb.tgl_ppjb AS "TGL_PPJB",
                tipe_unik.deskripsi AS "TIPE",
                lokasi_unik.deskripsi AS "LOKASI",
                stok.stok_id AS "STOK_ID",
                stok.no_virtual_acc AS "NO_VIRTUAL_ACC",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                sektor_unik.deskripsi AS "NM_CLUSTER"

            FROM public.sr_ppjb AS ppjb

            INNER JOIN public.sr_stok AS stok
                ON BTRIM(CAST(stok.stok_id AS TEXT))
                 = BTRIM(CAST(ppjb.stok_id AS TEXT))

            INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
                 = BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))

            LEFT JOIN public.sr_nasabah AS nasabah
                ON BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                 = BTRIM(CAST(nasabah.nasabah_id AS TEXT))

            LEFT JOIN tipe_unik
                ON tipe_unik.kode
                 = BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')) || '|'
                   || BTRIM(COALESCE(CAST(stok.{$stokTipe} AS TEXT), ''))
            LEFT JOIN lokasi_unik
                ON lokasi_unik.kode
                 = BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), ''))
            LEFT JOIN sektor_unik
                ON sektor_unik.kode
                 = BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), ''))

            WHERE UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')))
                    = 'Y'
              AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
              AND ppjb.parent_id IS NULL
              AND stok.parent_id IS NULL
              AND UPPER(BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                    = :perusahaan
              AND stok.blok IS NOT NULL
              AND stok.nomor IS NOT NULL

            ORDER BY
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
                stok.nomor ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'perusahaan' => $kdPerusahaan,
        ]);
    }

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

        if ($blokAkhir === '' || $blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
        }

        return $this->obtainEstimasiBiaya(
            $perusahaan,
            $cluster,
            $blokAwal,
            $blokAkhir,
            $this->normalizeDate($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    private function obtainEstimasiBiaya(
        string $perusahaan,
        string $cluster,
        string $blokAwal,
        string $blokAkhir,
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

        $syaratStok = <<<SQL
        UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
                  AND UPPER(BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                        = :perusahaan
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :cluster_filter
                        OR :cluster_semua = '*'
                      )
                  AND (
                        (
                            UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                            || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                            BETWEEN :blok_awal_unit AND :blok_akhir_unit
                        )
                        OR
                        (
                            UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                            BETWEEN :blok_awal_blok AND :blok_akhir_blok
                        )
                      )
        SQL;

        $sql = <<<SQL
            WITH awalan_unit AS MATERIALIZED (
                SELECT DISTINCT ON (kode_unit) kode_unit, awalan
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '')))
                            AS kode_unit,
                        REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '')
                            AS awalan,
                        COUNT(*) AS jumlah
                    FROM public.sr_stok
                    WHERE stok_id IS NOT NULL
                    GROUP BY 1, 2
                ) AS daftar
                WHERE kode_unit <> ''
                ORDER BY kode_unit, jumlah DESC, awalan
            ),
            biaya_terpilih AS (
                SELECT
                    biaya_ajb.*,
                    CASE
                        WHEN BTRIM(CAST(biaya_ajb.ppjb_id AS TEXT)) !~ '^[0-9]+$'
                        THEN BTRIM(CAST(biaya_ajb.ppjb_id AS TEXT))
                        ELSE awalan_unit.awalan
                             || BTRIM(CAST(biaya_ajb.ppjb_id AS TEXT))
                    END AS kunci_ppjb,
                    CASE
                        WHEN COALESCE(CAST(biaya_ajb.tgl_dokumen AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(biaya_ajb.tgl_dokumen AS TIMESTAMP)
                    END AS tgl_dokumen_valid
                FROM public.sr_biaya_ajb AS biaya_ajb
                INNER JOIN awalan_unit
                    ON awalan_unit.kode_unit
                     = UPPER(BTRIM(COALESCE(CAST(biaya_ajb.kd_perusahaan AS TEXT), '')))
                WHERE CASE
                          WHEN COALESCE(CAST(biaya_ajb.tgl_dokumen AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(biaya_ajb.tgl_dokumen AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(biaya_ajb.tgl_dokumen AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(biaya_ajb.tgl_dokumen AS TIMESTAMP)
                      END < CAST(:tgl_akhir_eksklusif AS DATE)
            ),
            stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE {$syaratStok}
            ),
            sektor_unik AS (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(a.{$sektorKode} AS TEXT), '')))
                            AS kode,
                        a.deskripsi AS deskripsi,
                        a.ctid AS urutan_fisik
                    FROM public.sr_sektor AS a
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            jenis_bgn_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, flag_laporan
                FROM (
                    SELECT
                        BTRIM(CAST(a.kd_jenis AS TEXT)) AS kode,
                        a.flag_laporan AS flag_laporan,
                        a.ctid AS urutan_fisik
                    FROM public.sr_jenis_bangunan AS a
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            ppjb_unit AS (
                SELECT DISTINCT BTRIM(CAST(p.ppjb_id AS TEXT)) AS kode
                FROM public.sr_ppjb AS p
                INNER JOIN public.sr_stok AS stok
                    ON BTRIM(CAST(stok.stok_id AS TEXT))
                     = BTRIM(CAST(p.stok_id AS TEXT))
                WHERE {$syaratStok}
            ),
            pembeli_ppjb_nama AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kode,
                    STRING_AGG(
                        DISTINCT UPPER(BTRIM(CAST(nasabah.nama AS TEXT))),
                        ', '
                        ORDER BY UPPER(BTRIM(CAST(nasabah.nama AS TEXT)))
                    ) AS nama_pembeli
                FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                INNER JOIN ppjb_unit
                    ON ppjb_unit.kode = BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))
                INNER JOIN public.sr_nasabah AS nasabah
                    ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                     = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                WHERE UPPER(BTRIM(COALESCE(
                          CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
                  AND NULLIF(BTRIM(CAST(nasabah.nama AS TEXT)), '') IS NOT NULL
                GROUP BY 1
            )

            SELECT
                biaya_ajb.no_dokumen AS "NO_DOKUMEN",
                biaya_ajb.tgl_dokumen_valid AS "TGL_DOKUMEN",

                stok.{$stokSektor} AS "KD_SEKTOR",
                sektor_unik.deskripsi AS "NM_CLUSTER",

                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",

                pembeli_ppjb_nama.nama_pembeli AS "NAMA_PEMBELI",

                ppjb.tgl_ppjb AS "TGL_PPJB",
                ppjb.harga_jual AS "HARGA_JUAL",
                ppjb.dpp AS "DPP",
                ppjb.ppn AS "PPN",

                stok.no_virtual_acc AS "NO_VIRTUAL_ACC",
                stok.atas_nama_va AS "ATAS_NAMA_VA",

                biaya_ajb.lb AS "LB",
                biaya_ajb.lbb AS "LBB",
                biaya_ajb.lt AS "LT",
                biaya_ajb.njop_lb AS "NJOP_LB",
                biaya_ajb.njop_lbb AS "NJOP_LBB",
                biaya_ajb.njop_lt AS "NJOP_LT",
                biaya_ajb.bea_surat AS "BEA_SURAT",
                biaya_ajb.bea_hgb AS "BEA_HGB",
                biaya_ajb.selisih_njop AS "SELISIH_NJOP",
                biaya_ajb.bea_pnbp AS "BEA_PNBP",
                biaya_ajb.bea_bphtb AS "BEA_BPHTB",
                biaya_ajb.bea_cadangan AS "BEA_CADANGAN",
                biaya_ajb.fee_pajak AS "FEE_PAJAK",
                biaya_ajb.total_dev AS "TOTAL_DEV",
                biaya_ajb.total_notaris AS "TOTAL_NOTARIS",
                biaya_ajb.keterangan AS "KETERANGAN",
                biaya_ajb.flag_aktif AS "FLAG_AKTIF",
                biaya_ajb.tgl_entry AS "TGL_ENTRY",
                biaya_ajb.user_entry AS "USER_ENTRY",
                biaya_ajb.tgl_update AS "TGL_UPDATE",
                biaya_ajb.user_update AS "USER_UPDATE",
                biaya_ajb.kd_notaris AS "KD_NOTARIS",

                tbl_notaris.nm_notaris AS "NM_NOTARIS",
                tbl_notaris.no_rekening AS "NO_REKENING",
                tbl_notaris.cabang_bank AS "CABANG_BANK",

                jenis_bgn_unik.flag_laporan AS "JENIS_BGN",

                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK"

            FROM biaya_terpilih AS biaya_ajb

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT)) = biaya_ajb.kunci_ppjb
               AND UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

            LEFT JOIN public.sr_tbl_notaris AS tbl_notaris
                ON BTRIM(CAST(biaya_ajb.kd_notaris AS TEXT))
                 = BTRIM(CAST(tbl_notaris.kd_notaris AS TEXT))

            LEFT JOIN public.sr_bank AS bank
                ON BTRIM(CAST(tbl_notaris.kd_bank AS TEXT))
                 = BTRIM(CAST(bank.kd_bank AS TEXT))

            LEFT JOIN sektor_unik
                ON sektor_unik.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
            LEFT JOIN jenis_bgn_unik
                ON jenis_bgn_unik.kode
                 = BTRIM(CAST(stok.{$stokJenis} AS TEXT))
            LEFT JOIN pembeli_ppjb_nama
                ON pembeli_ppjb_nama.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            ORDER BY
                "NM_CLUSTER" ASC,
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
                biaya_ajb.no_dokumen ASC
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

    private function kolomKode(string $tabel, array $kandidat): string
    {
        static $kolomTabel = [];

        if (!isset($kolomTabel[$tabel])) {
            $baris = DB::connection(self::CONNECTION)->select(
                'SELECT column_name
                   FROM information_schema.columns
                  WHERE table_schema = :schema
                    AND table_name = :tabel',
                ['schema' => self::SCHEMA, 'tabel' => $tabel]
            );

            $kolomTabel[$tabel] = array_map(
                static fn ($item) => strtolower($item->column_name),
                $baris
            );
        }

        foreach ($kandidat as $kolom) {
            if (in_array(strtolower($kolom), $kolomTabel[$tabel], true)) {
                return strtolower($kolom);
            }
        }

        throw new RuntimeException(
            'Kolom kode tidak ditemukan pada tabel ' . $tabel . ': '
            . implode(', ', $kandidat)
        );
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
