<?php

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_jaminan_bank_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    public function obtainSektor($kdPerusahaan)
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
                    AS "KD_SEKTOR",
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')) AS "DESKRIPSI",
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                    AS "KD_PERUSAHAAN"
            FROM public.sr_sektor AS sektor
            WHERE UPPER(BTRIM(COALESCE(CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                    = :kd_perusahaan
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

    private const JENIS_JAMINAN_KODE = [
        'IMB' => '1',
        'Akta Jual Beli' => '2',
        'Sertipikat' => '3',
        'PPJB' => '4',
        'Peralihan Hak' => '5',
    ];

    public static function jenisJaminanDiterima(): array
    {
        $nilai = ['*', 'Semua', 'SEMUA'];

        foreach (self::JENIS_JAMINAN_KODE as $tulisan => $kode) {
            $nilai[] = $tulisan;
            $nilai[] = strtoupper($tulisan);
            $nilai[] = $kode;
        }

        return array_values(array_unique($nilai));
    }

    public function obtainJenisJaminan()
    {
        $sql = <<<SQL
            SELECT
                UPPER(BTRIM(COALESCE(CAST(jaminan.jenis_jaminan AS TEXT), '')))
                    AS "KODE",
                COUNT(*) AS "JUMLAH"
            FROM public.sr_jaminan AS jaminan
            WHERE jaminan.no_jaminan IS NOT NULL
              AND jaminan.no_lunas IS NULL
              AND jaminan.no_batal IS NULL
            GROUP BY 1
            ORDER BY 2 DESC, 1
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql)
        );
    }

    public function obtainRekapJaminanBank($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );

        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ');
        $sektor = $this->normalizeText($request->sektor ?? '*');
        $jaminan = $this->normalizeJenisJaminan($request->jenis_jaminan ?? '*');
        $statusAjb = $this->normalizeAjbStatus($request->status_ajb ?? 'SEMUA');

        $tglAwalBank = $this->normalizeDateNullable($request->tgl_awal_bank ?? null);
        $tglAkhirBank = $this->normalizeDateNullable($request->tgl_akhir_bank ?? null);

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '' || $blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
        }

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($jaminan === '') {
            $jaminan = '*';
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $stokLokasi = $this->kolomKode('sr_stok', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster',
        ]);
        $lokasiKode = $this->kolomKode('sr_lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $ajbFilterSql = $this->buildAjbFilterSql($statusAjb);
        $tanggalFilterSql = $this->buildTanggalFilterSql($tglAwalBank, $tglAkhirBank);

        $pakaiUnit = $this->adaKolom('sr_jaminan', 'kd_perusahaan');
        $awalanTunggal = $pakaiUnit ? '' : $this->awalanJaminanDariYangPasti();
        $pakaiTunggal = !$pakaiUnit && $awalanTunggal !== '';

        $cteAwalanUnit = $pakaiUnit ? $this->cteAwalanUnit() : '';
        $joinAwalanUnit = $pakaiUnit
            ? "INNER JOIN awalan_unit
                    ON awalan_unit.kode_unit = UPPER(BTRIM(COALESCE(
                           CAST(jaminan.kd_perusahaan AS TEXT), '')))"
            : '';

        $pakaiUnik = !$pakaiUnit && !$pakaiTunggal;
        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                    ON sertipikat_unik.angka
                     = BTRIM(CAST(jaminan.sertipikat_id AS TEXT))"
            : '';

        $kunciJaminan = $this->kunciSertipikatJaminan($pakaiUnit, $pakaiTunggal);

        $sql = <<<SQL
            WITH {$cteAwalanUnit}{$cteSertipikatUnik}stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE (
                        stok.{$stokPerusahaan} = :perusahaan_langsung
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                            = :perusahaan
                      )
                  AND (
                        stok.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            jaminan_terpilih AS (
                SELECT
                    jaminan.*,
                    {$kunciJaminan} AS kunci_sertipikat
                FROM public.sr_jaminan AS jaminan
                {$joinAwalanUnit}
                {$joinSertipikatUnik}
                WHERE jaminan.no_jaminan IS NOT NULL
                  AND jaminan.no_lunas IS NULL
                  AND jaminan.no_batal IS NULL
                  AND (
                        UPPER(BTRIM(COALESCE(
                            CAST(jaminan.jenis_jaminan AS TEXT), '')))
                            = :jaminan_filter
                        OR :jaminan_semua = '*'
                      )
                  {$tanggalFilterSql}
            ),
            lokasi_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode)
                    kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(lokasi.{$lokasiKode} AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(lokasi.deskripsi AS TEXT), '')) AS deskripsi,
                        lokasi.ctid AS urutan_fisik
                    FROM public.sr_lokasi AS lokasi
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            sektor_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode)
                    kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(sektor.{$sektorKode} AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(sektor.deskripsi AS TEXT), '')) AS deskripsi,
                        sektor.ctid AS urutan_fisik
                    FROM public.sr_sektor AS sektor
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
                FROM public.sr_ppjb AS ppjb
                WHERE (
                        ppjb.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND ppjb.parent_id IS NULL
            ),
            pembeli_nasabah AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    nasabah.nama AS nama
                FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                INNER JOIN public.sr_nasabah AS nasabah
                    ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                     = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                WHERE (
                        pembeli_ppjb.flag_aktif = 'Y'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
                      )
            ),
            plafond_kpr AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(jadwal.ppjb_id AS TEXT)) AS kunci_ppjb,
                    SUM(jadwal.jumlah) AS jumlah
                FROM public.sr_jadwal_angsuran AS jadwal
                WHERE UPPER(BTRIM(COALESCE(
                          CAST(jadwal.flag_kpr AS TEXT), 'T'))) = 'Y'
                GROUP BY 1
            ),
            hasil_dasar AS MATERIALIZED (

            SELECT
                BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                    || COALESCE(CAST(stok.nomor AS TEXT), '') AS "BLOK_NOMOR",
                nasabah.nama AS "NAMA",
                jaminan.pengajuan AS "PENGAJUAN",
                jaminan.tgl_pengajuan AS "TGL_PENGAJUAN",
                jaminan.no_jaminan AS "NO_JAMINAN",
                jaminan.tgl_jaminan AS "TGL_JAMINAN",
                jaminan.nama_bank AS "NAMA_BANK",
                jaminan.alamat_bank AS "ALAMAT_BANK",
                jaminan.jenis_jaminan AS "JENIS_JAMINAN",
                jaminan.nama_ambil AS "NAMA_AMBIL",
                jaminan.tgl_ambil AS "TGL_AMBIL",
                jaminan.no_lunas AS "NO_LUNAS",
                jaminan.tgl_lunas AS "TGL_LUNAS",
                jaminan.no_batal AS "NO_BATAL",
                jaminan.tgl_batal AS "TGL_BATAL",
                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                lokasi_ref.deskripsi AS "NAMA_LOKASI",
                sektor_ref.deskripsi AS "NAMA_SEKTOR",
                COALESCE(plafond_kpr.jumlah, 0) AS "PLAFOND_KPR",

                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR"

            FROM jaminan_terpilih AS jaminan

            INNER JOIN public.sr_sertipikat AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = jaminan.kunci_sertipikat

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            INNER JOIN ppjb_aktif AS ppjb
                ON ppjb.kunci_stok = stok.kunci_stok

            INNER JOIN pembeli_nasabah AS nasabah
                ON nasabah.kunci_ppjb = ppjb.kunci_ppjb

            LEFT JOIN lokasi_ref
                ON lokasi_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))
            LEFT JOIN sektor_ref
                ON sektor_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
            LEFT JOIN plafond_kpr
                ON plafond_kpr.kunci_ppjb = ppjb.kunci_ppjb

            WHERE sertipikat.stok_id IS NOT NULL
              {$ajbFilterSql}
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            WHERE (
                    (
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), '')
                            >= :blok_awal_unit
                        AND
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), '')
                            <= :blok_akhir_unit
                    )
                    OR
                    (
                        hasil_dasar."BLOK" >= :blok_awal_blok
                        AND hasil_dasar."BLOK" <= :blok_akhir_blok
                    )
                  )
            ORDER BY
                hasil_dasar."BLOK",
                hasil_dasar."NOMOR",
                hasil_dasar."NAMA",
                hasil_dasar."TGL_JAMINAN",
                hasil_dasar."NO_JAMINAN"
        SQL;

        $bindings = [
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,

            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,

            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'jaminan_filter' => $jaminan,
            'jaminan_semua' => $jaminan,
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
        ];

        if ($pakaiTunggal) {
            $bindings['awalan_jaminan'] = $awalanTunggal;
        }

        if ($tglAwalBank !== null && $tglAkhirBank !== null) {
            $bindings['tgl_awal_bank'] = $tglAwalBank;
            $bindings['tgl_akhir_bank'] = $tglAkhirBank;
        }

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    private function kunciSertipikatJaminan(
        bool $pakaiUnit,
        bool $pakaiTunggal = false
    ): string {
        if ($pakaiUnit) {
            $awalan = 'awalan_unit.awalan';
        } elseif ($pakaiTunggal) {
            $awalan = ':awalan_jaminan';
        } else {
            $awalan = 'sertipikat_unik.awalan';
        }

        return <<<SQL
            CASE
                WHEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
                ELSE {$awalan}
                     || BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
            END
            SQL;
    }

    private function awalanJaminanDariYangPasti(): string
    {
        static $awalan = null;

        if ($awalan !== null) {
            return $awalan;
        }

        $sql = <<<SQL
            WITH pasti AS (
                SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
                FROM (
                    SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
                    FROM public.sr_jaminan AS j
                    WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
                ) AS jm
                INNER JOIN (
                    SELECT
                        REGEXP_REPLACE(
                            BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', ''
                        ) AS angka,
                        MIN(REGEXP_REPLACE(
                            BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''
                        )) AS awalan,
                        COUNT(*) AS banyak
                    FROM public.sr_sertipikat
                    WHERE sertipikat_id IS NOT NULL
                    GROUP BY 1
                ) AS ser
                    ON ser.angka = jm.angka
                WHERE ser.banyak = 1
                GROUP BY 1
            )
            SELECT awalan
            FROM pasti
            WHERE jumlah >= 0.95 * (SELECT SUM(jumlah) FROM pasti)
            ORDER BY jumlah DESC
            LIMIT 1
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql);
        $awalan = $baris ? (string) $baris[0]->awalan : '';

        return $awalan;
    }

    private function cteAwalanUnit(): string
    {
        return <<<SQL
        awalan_unit AS MATERIALIZED (
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

        SQL;
    }

    private function cteSertipikatUnik(): string
    {
        return <<<SQL
        sertipikat_unik AS MATERIALIZED (
                SELECT angka, MIN(awalan) AS awalan
                FROM (
                    SELECT
                        REGEXP_REPLACE(
                            BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', ''
                        ) AS angka,
                        REGEXP_REPLACE(
                            BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''
                        ) AS awalan
                    FROM public.sr_sertipikat
                    WHERE sertipikat_id IS NOT NULL
                ) AS daftar
                GROUP BY angka
                HAVING COUNT(*) = 1
            ),

        SQL;
    }

    private function adaKolom(string $tabel, string $kolom): bool
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

        return in_array(strtolower($kolom), $ingatan[$tabel], true);
    }

    private function buildAjbFilterSql(string $statusAjb): string
    {
        if ($statusAjb === 'SEMUA') {
            return '';
        }

        $kunci = <<<SQL
CASE
                  WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                  THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
                  WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                  THEN REGEXP_REPLACE(
                           BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', ''
                       ) || BTRIM(CAST(a.sertipikat_id AS TEXT))
                  ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
              END
SQL;

        if ($statusAjb === 'SUDAH') {
            return <<<SQL
AND EXISTS (
                  SELECT 1
                  FROM public.sr_akta AS a
                  WHERE {$kunci}
                      = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                    AND a.no_akta IS NOT NULL
              )
SQL;
        }

        return <<<SQL
AND NOT EXISTS (
                  SELECT 1
                  FROM public.sr_akta AS a
                  WHERE {$kunci}
                      = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                    AND a.no_akta IS NOT NULL
              )
SQL;
    }

    private function buildTanggalFilterSql(?string $tglAwalBank, ?string $tglAkhirBank): string
    {
        if ($tglAwalBank === null || $tglAkhirBank === null) {
            return '';
        }

        return <<<SQL
AND jaminan.tgl_jaminan >= CAST(:tgl_awal_bank AS DATE)
                  AND jaminan.tgl_jaminan <= CAST(:tgl_akhir_bank AS DATE)
SQL;
    }

    private function kolomKode(string $tabel, array $kandidat): string
    {
        static $ingatan = [];

        $kunci = $tabel . '|' . implode(',', $kandidat);

        if (isset($ingatan[$kunci])) {
            return $ingatan[$kunci];
        }

        $baris = DB::connection(self::CONNECTION)->select(
            'SELECT column_name
               FROM information_schema.columns
              WHERE table_schema = :schema
                AND table_name = :tabel',
            ['schema' => self::SCHEMA, 'tabel' => $tabel]
        );

        $tersedia = array_map(
            static fn ($item) => strtolower($item->column_name),
            $baris
        );

        foreach ($kandidat as $kolom) {
            if (in_array(strtolower($kolom), $tersedia, true)) {
                return $ingatan[$kunci] = $kolom;
            }
        }

        return $ingatan[$kunci] = $kandidat[0];
    }

    private function normalizeDateNullable($value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $formats = ['!Y-m-d', '!Ymd', '!d/m/Y', '!m/d/Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $text);
            $errors = DateTimeImmutable::getLastErrors();
            $valid = $date !== false
                && ($errors === false
                    || ($errors['warning_count'] === 0
                        && $errors['error_count'] === 0));

            if ($valid) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException(
            'Format tanggal tidak valid: ' . $text . '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizeJenisJaminan($value): string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '' || $normalized === 'SEMUA') {
            return '*';
        }

        foreach (self::JENIS_JAMINAN_KODE as $tulisan => $kode) {
            if (strtoupper($tulisan) === $normalized) {
                return $kode;
            }
        }

        if (preg_match('/^[A-Z0-9]$/', $normalized) === 1) {
            return $normalized;
        }

        return '*';
    }

    private function normalizeAjbStatus($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['SUDAH', 'BELUM', 'SEMUA'], true)
            ? $normalized
            : 'SEMUA';
    }
}
