<?php

namespace App\Models\SRIS\Suratrumah;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class dftr_sertifikat_balik_nama_m extends Model
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

    public function obtainTTDMengetahui($kdPerusahaan)
    {
        if (!$this->adaTabel('sr_pegawai')) {
            return collect([]);
        }

        $pegawaiKode = $this->kolomKode('sr_pegawai', [
            'pegawai_id', 'kd_pegawai', 'nip',
        ]);

        $sql = <<<SQL
            SELECT
                BTRIM(CAST(pegawai.{$pegawaiKode} AS TEXT)) AS "KODE",
                BTRIM(CAST(pegawai.nama AS TEXT)) AS "NAMA"
            FROM public.sr_pegawai AS pegawai
            WHERE pegawai.{$pegawaiKode} IS NOT NULL
              AND BTRIM(CAST(pegawai.{$pegawaiKode} AS TEXT)) <> ''
              AND pegawai.nama IS NOT NULL
              AND BTRIM(CAST(pegawai.nama AS TEXT)) <> ''
            ORDER BY
                BTRIM(CAST(pegawai.nama AS TEXT)),
                BTRIM(CAST(pegawai.{$pegawaiKode} AS TEXT))
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql)
        );
    }

    public function obtainDaftarSertipikatBalikNama($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );

        $sektor = $this->normalizeText($request->sektor ?? '*');
        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'Z');

        $tglAwal = $this->normalizeDate($request->tgl_awal);

        $tglAkhirEksklusif = $this->normalizeDate($request->tgl_akhir, 1);

        $apartemen = filter_var(
            $request->apartemen ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        $sektor = $sektor === '' ? '*' : $sektor;
        $blokAwal = $blokAwal === '' ? 'A' : $blokAwal;
        $blokAkhir = $blokAkhir === '' ? 'Z' : $blokAkhir;

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $stokJenis = $this->kolomKode('sr_stok', [
            'kd_jenis_bgn', 'kd_jenis',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorPerusahaan = $this->kolomKode('sr_sektor', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $ekspresiJenis =
            "UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))";
        $jenisFilter = $apartemen
            ? "AND {$ekspresiJenis} = 'APT'"
            : "AND {$ekspresiJenis} NOT IN ('APT', 'KTR')";

        $syaratBlok = $this->syaratBlok(
            'hasil_dasar."BLOK"',
            'hasil_dasar."NOMOR"'
        );

        $telpRumah = $this->kolomOpsional('sr_nasabah', 'nasabah', 'telp_rmh');
        $noHp = $this->kolomOpsional('sr_nasabah', 'nasabah', 'no_hp');
        $telpKantor = $this->kolomOpsional('sr_nasabah', 'nasabah', 'telp_ktr');

        $sql = <<<SQL
            WITH stok_terpilih AS (
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
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND (
                        stok.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            sertipikat_terpilih AS (
                SELECT
                    s.*,
                    CASE
                        WHEN COALESCE(CAST(s.tgl_input_blk_nm AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(s.tgl_input_blk_nm AS TIMESTAMP)
                    END AS tgl_input_blk_nm_valid
                FROM public.sr_sertipikat AS s
                WHERE 
                      (
                        s.status_blk_nm = 'Y'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(s.status_blk_nm AS TEXT), ''))) = 'Y'
                      )
                  AND s.stok_id IS NOT NULL
                  AND CASE
                          WHEN COALESCE(CAST(s.tgl_input_blk_nm AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(s.tgl_input_blk_nm AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(s.tgl_input_blk_nm AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(s.tgl_input_blk_nm AS TIMESTAMP)
                      END < CAST(:tgl_akhir_eksklusif AS DATE)
            ),
            pembeli_nama AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kode,
                    STRING_AGG(
                        DISTINCT UPPER(BTRIM(CAST(nasabah.nama AS TEXT))),
                        ', '
                        ORDER BY UPPER(BTRIM(CAST(nasabah.nama AS TEXT)))
                    ) AS nasabah_nama
                FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                INNER JOIN public.sr_nasabah AS nasabah
                    ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                     = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                WHERE UPPER(BTRIM(COALESCE(
                          CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
                  AND NULLIF(BTRIM(CAST(nasabah.nama AS TEXT)), '') IS NOT NULL
                GROUP BY 1
            ),
            nasabah_kontak AS MATERIALIZED (
                SELECT DISTINCT ON (kode)
                    kode, telp_rmh, no_hp, telp_ktr
                FROM (
                    SELECT
                        BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kode,
                        {$telpRumah} AS telp_rmh,
                        {$noHp} AS no_hp,
                        {$telpKantor} AS telp_ktr,
                        nasabah.ctid AS urutan_fisik
                    FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                    INNER JOIN public.sr_nasabah AS nasabah
                        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                         = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                    WHERE UPPER(BTRIM(COALESCE(
                              CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
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
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                     CAST(sektor.{$sektorPerusahaan} AS TEXT), '')))
                                 = :perusahaan_sektor
                            THEN 0
                            ELSE 1
                        END AS urut_unit,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                     CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
                            THEN 0
                            ELSE 1
                        END AS urut_aktif
                    FROM public.sr_sektor AS sektor
                ) AS daftar
                ORDER BY kode, urut_unit, urut_aktif
            ),
            hasil_dasar AS MATERIALIZED (

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",

                COALESCE(pembeli_nama.nasabah_nama, '-') AS "NASABAH_NAMA",

                stok.luas_tanah AS "LUAS_TANAH",
                stok.luas_bangunan AS "LUAS_BANGUNAN",
                stok.jalan AS "JALAN",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(
                             CAST(stok.{$stokJenis} AS TEXT), ''))) = 'APT'
                    THEN sertipikat.no_sarusun
                    ELSE sertipikat.no_sertipikat
                END AS "NO_SERTIPIKAT",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(
                             CAST(stok.{$stokJenis} AS TEXT), ''))) = 'APT'
                    THEN sertipikat.tgl_sarusun
                    ELSE sertipikat.tgl_sertipikat
                END AS "TGL_SERTIPIKAT",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(
                             CAST(stok.{$stokJenis} AS TEXT), ''))) = 'APT'
                    THEN sertipikat.no_denah
                    ELSE sertipikat.su_pisah
                END AS "SU_PISAH",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(
                             CAST(stok.{$stokJenis} AS TEXT), ''))) = 'APT'
                    THEN sertipikat.tgl_denah
                    ELSE sertipikat.tgl_su_pisah
                END AS "TGL_SU_PISAH",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(
                             CAST(stok.{$stokJenis} AS TEXT), ''))) = 'APT'
                    THEN sertipikat.luas_denah
                    ELSE sertipikat.luas_sup
                END AS "LUAS_SUP",

                sertipikat.tgl_input_blk_nm_valid AS "TGL_INPUT_BLK_NM",

                nasabah_kontak.telp_rmh AS "TELP_RMH",
                nasabah_kontak.no_hp AS "NO_HP",
                nasabah_kontak.telp_ktr AS "TELP_KTR",

                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",

                CURRENT_TIMESTAMP AS "TGL_CETAK",

                sektor_ref.deskripsi AS "NAMA_SEKTOR",

                ppjb.user_entry AS "USER_ENTRY",

                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                stok.{$stokSektor} AS "KD_SEKTOR",
                stok.{$stokJenis} AS "KD_JENIS",
                stok.stok_id AS "STOK_ID",
                ppjb.ppjb_id AS "PPJB_ID"

            FROM stok_terpilih AS stok

            INNER JOIN sertipikat_terpilih AS sertipikat
                ON BTRIM(CAST(sertipikat.stok_id AS TEXT)) = stok.kunci_stok

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.stok_id AS TEXT)) = stok.kunci_stok

            INNER JOIN pembeli_nama
                ON pembeli_nama.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            LEFT JOIN nasabah_kontak
                ON nasabah_kontak.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
            LEFT JOIN sektor_ref
                ON sektor_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))

            WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
              AND ppjb.parent_id IS NULL
              {$jenisFilter}
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            WHERE {$syaratBlok}
            ORDER BY
                UPPER(BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), ''))) ASC,
                CASE
                    WHEN BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                         ~ '^[0-9]+$'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                         ~ '^[0-9]+$'
                    THEN LPAD(BTRIM(CAST(hasil_dasar."NOMOR" AS TEXT)), 50, '0')
                    ELSE ''
                END ASC,
                hasil_dasar."NOMOR" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'perusahaan_langsung' => $perusahaan,
            'perusahaan' => $perusahaan,
            'perusahaan_sektor' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_akhir_blok_min' => $blokAkhir,
            'blok_akhir_blok_max' => $blokAkhir,
        ]);
    }

    private function syaratBlok(string $blok, string $nomor): string
    {
        return <<<SQL
            (
                (
                    UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST({$nomor} AS TEXT), '')))
                        >= :blok_awal_unit
                    AND
                    UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST({$nomor} AS TEXT), '')))
                        <= :blok_akhir_unit
                )
                OR
                (
                    UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), '')))
                        >= :blok_akhir_blok_min
                    AND UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), '')))
                        <= :blok_akhir_blok_max
                )
            )
            SQL;
    }

    private function kolomKode(string $tabel, array $kandidat): string
    {
        $tersedia = $this->kolomTabel($tabel);

        foreach ($kandidat as $kolom) {
            if (in_array(strtolower($kolom), $tersedia, true)) {
                return $kolom;
            }
        }

        return $kandidat[0];
    }

    private function kolomOpsional(
        string $tabel,
        string $alias,
        string $kolom
    ): string {
        return in_array(strtolower($kolom), $this->kolomTabel($tabel), true)
            ? $alias . '.' . $kolom
            : 'NULL';
    }

    private function adaTabel(string $tabel): bool
    {
        return $this->kolomTabel($tabel) !== [];
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
            'Format tanggal tidak valid: ' . $text
            . '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }
}
