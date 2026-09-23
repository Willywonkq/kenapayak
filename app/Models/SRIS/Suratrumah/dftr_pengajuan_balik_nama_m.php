<?php

namespace App\Models\SRIS\Suratrumah;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class dftr_pengajuan_balik_nama_m extends Model
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

    public function obtainDaftarPengajuanBalikNama($request): array
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

        $tglAwal = $this->normalizeDate(
            $request->tgl_awal ?? date('Y-m-d')
        );

        $tglAkhirEksklusif = $this->normalizeDate(
            $request->tgl_akhir ?? date('Y-m-d'),
            1
        );

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        $this->pastikanKeluargaAda($perusahaan);

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '' || $blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorPerusahaan = $this->kolomKode('sr_sektor', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $stokJenis = $this->kolomKode('sr_stok', ['kd_jenis_bgn', 'kd_jenis']);

        $kunciAkta = $this->kunciSertipikat('akta.sertipikat_id');
        $kunciIdk = $this->kunciSertipikatIdk();
        $syaratBlok = $this->syaratBlok('hasil_dasar."BLOK"', 'hasil_dasar."NOMOR"');

        $sql = <<<SQL
            WITH akta_terpilih AS (
                SELECT
                    akta.*,
                    {$kunciAkta} AS kunci_sertipikat
                FROM public.sr_akta AS akta
                WHERE CASE
                          WHEN COALESCE(CAST(akta.tgl_input AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(akta.tgl_input AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(akta.tgl_input AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(akta.tgl_input AS TIMESTAMP)
                      END < CAST(:tgl_akhir_gpt AS DATE)
            ),
            stok_terpilih AS (
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
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, ppjb_id
                FROM (
                    SELECT
                        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS ppjb_id,
                        ppjb.tgl_ppjb AS tgl_ppjb
                    FROM public.sr_ppjb AS ppjb
                    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), '')))
                          = 'A'
                      AND ppjb.parent_id IS NULL
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST, ppjb_id DESC
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
            sektor_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                            AS kode,
                        sektor.deskripsi AS deskripsi,
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

                sertipikat_idk.nama_pt AS "NAMA_PT",
                stok.luas_tanah AS "LUAS_TANAH",
                stok.luas_bangunan AS "LUAS_BANGUNAN",
                stok.jalan AS "JALAN",

                sertipikat_idk.sertipikat_idk AS "SERTIPIKAT_IDK",
                sertipikat_idk.tgl_ser_idk AS "TGL_SER_IDK",
                sertipikat_idk.su_induk AS "SU_INDUK",
                sertipikat_idk.tgl_su_induk AS "TGL_SU_INDUK",
                sertipikat_idk.luas_su_induk AS "LUAS_SU_INDUK",
                sertipikat_idk.mohon_pisah AS "MOHON_PISAH",
                sertipikat_idk.tgl_mohon_pisah AS "TGL_MOHON_PISAH",
                sertipikat_idk.su_pisah AS "SU_PISAH_IDK",
                sertipikat_idk.tgl_su_pisah AS "TGL_SU_PISAH_IDK",
                sertipikat_idk.ser_pisah AS "SER_PISAH_IDK",
                sertipikat_idk.tgl_ser_pisah AS "TGL_SER_PISAH_IDK",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))
                         = 'APT'
                    THEN sertipikat.no_sarusun
                    ELSE sertipikat.no_sertipikat
                END AS "NO_SERTIPIKAT",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))
                         = 'APT'
                    THEN sertipikat.tgl_sarusun
                    ELSE sertipikat.tgl_sertipikat
                END AS "TGL_SERTIPIKAT",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))
                         = 'APT'
                    THEN sertipikat.no_denah
                    ELSE sertipikat.su_pisah
                END AS "SU_PISAH",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))
                         = 'APT'
                    THEN sertipikat.tgl_denah
                    ELSE sertipikat.tgl_su_pisah
                END AS "TGL_SU_PISAH",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(stok.{$stokJenis} AS TEXT), '')))
                         = 'APT'
                    THEN sertipikat.luas_denah
                    ELSE sertipikat.luas_sup
                END AS "LUAS_SUP",

                sertipikat.tgl_berlaku AS "TGL_BERLAKU",
                sertipikat.mohon_blk_nm AS "MOHON_BLK_NM",
                sertipikat.tgl_mohon_blk_nm AS "TGL_MOHON_BLK_NM",

                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",

                akta.no_akta AS "NO_AKTA",
                akta.tgl_akta AS "TGL_AKTA",
                akta.no_notaris AS "NO_NOTARIS",
                akta.harga AS "HARGA",
                akta.notaris AS "NOTARIS",
                akta.tgl_input AS "TGL_INPUT_AJB",

                sektor_ref.deskripsi AS "NAMA_SEKTOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                stok.{$stokSektor} AS "KD_SEKTOR",
                stok.{$stokJenis} AS "KD_JENIS",
                stok.stok_id AS "STOK_ID"

            FROM public.sr_sertipikat_idk AS sertipikat_idk

            INNER JOIN public.sr_sertipikat AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = {$kunciIdk}

            INNER JOIN akta_terpilih AS akta
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = akta.kunci_sertipikat

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            LEFT JOIN ppjb_aktif
                ON ppjb_aktif.kunci_stok = stok.kunci_stok
            LEFT JOIN pembeli_nama
                ON pembeli_nama.kode = ppjb_aktif.ppjb_id
            LEFT JOIN sektor_ref
                ON sektor_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))

            WHERE (
                    UPPER(BTRIM(COALESCE(CAST(sertipikat.status_blk_nm AS TEXT), '')))
                        = 'T'
                    OR sertipikat.status_blk_nm IS NULL
                  )
              AND sertipikat.stok_id IS NOT NULL
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
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."TGL_INPUT_AJB" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'awalan_idk' => $this->awalanUnit($perusahaan),
            'perusahaan_langsung' => $perusahaan,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_gpt' => $tglAkhirEksklusif,
            'perusahaan' => $perusahaan,
            'perusahaan_sektor' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
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
                        BETWEEN :blok_awal_blok AND :blok_akhir_blok
                )
            )
            SQL;
    }

    private function kunciSertipikatIdk(): string
    {
        return <<<SQL
            CASE
                WHEN BTRIM(CAST(sertipikat_idk.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(sertipikat_idk.sertipikat_id AS TEXT))
                ELSE :awalan_idk
                     || BTRIM(CAST(sertipikat_idk.sertipikat_id AS TEXT))
            END
            SQL;
    }

    private function awalanUnit(string $kdPerusahaan): string
    {
        static $ingatan = [];

        if (isset($ingatan[$kdPerusahaan])) {
            return $ingatan[$kdPerusahaan];
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $sql = <<<SQL
            SELECT
                REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '')
                    AS awalan,
                COUNT(*) AS jumlah
            FROM public.sr_stok
            WHERE stok_id IS NOT NULL
              AND UPPER(BTRIM(COALESCE(
                      CAST({$stokPerusahaan} AS TEXT), ''))) = :kd_perusahaan
            GROUP BY 1
            ORDER BY jumlah DESC, awalan
            LIMIT 1
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql, [
            'kd_perusahaan' => $kdPerusahaan,
        ]);

        return $ingatan[$kdPerusahaan] = $baris
            ? (string) $baris[0]->awalan
            : '';
    }

    private function pastikanKeluargaAda(string $kdPerusahaan): void
    {
        static $ingatan = [];

        if (isset($ingatan[$kdPerusahaan])) {
            if ($ingatan[$kdPerusahaan] === false) {
                $this->tolakKeluargaKosong($kdPerusahaan);
            }

            return;
        }

        $awalan = $this->awalanUnit($kdPerusahaan);

        if ($awalan === '') {
            $ingatan[$kdPerusahaan] = false;
            $this->tolakKeluargaKosong($kdPerusahaan);
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $sql = <<<SQL
            WITH angka_sertipikat AS (
                SELECT
                    REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)),
                                   '^[^0-9]+', '') AS angka,
                    MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)),
                                       '[0-9]+$', '')) AS awalan,
                    COUNT(DISTINCT REGEXP_REPLACE(
                        BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''))
                        AS banyak_keluarga
                FROM public.sr_sertipikat
                WHERE sertipikat_id IS NOT NULL
                GROUP BY 1
            ),
            pasti AS (
                SELECT angka_sertipikat.awalan || angka_sertipikat.angka AS kunci
                FROM public.sr_sertipikat_idk AS idk
                INNER JOIN angka_sertipikat
                    ON angka_sertipikat.angka
                     = BTRIM(CAST(idk.sertipikat_id AS TEXT))
                WHERE angka_sertipikat.banyak_keluarga = 1
                  AND angka_sertipikat.awalan = :awalan
                  AND BTRIM(CAST(idk.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
            )
            SELECT COUNT(*) AS jumlah
            FROM pasti
            INNER JOIN public.sr_sertipikat AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) = pasti.kunci
            INNER JOIN public.sr_stok AS stok
                ON BTRIM(CAST(stok.stok_id AS TEXT))
                 = BTRIM(CAST(sertipikat.stok_id AS TEXT))
            WHERE UPPER(BTRIM(COALESCE(
                      CAST(stok.{$stokPerusahaan} AS TEXT), ''))) = :kd_perusahaan
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql, [
            'awalan' => $awalan,
            'kd_perusahaan' => $kdPerusahaan,
        ]);

        $ada = $baris && (int) $baris[0]->jumlah > 0;
        $ingatan[$kdPerusahaan] = $ada;

        if (!$ada) {
            $this->tolakKeluargaKosong($kdPerusahaan);
        }
    }

    private function tolakKeluargaKosong(string $kdPerusahaan): void
    {
        throw new RuntimeException(
            'Data SERTIPIKAT_IDK untuk unit ' . $kdPerusahaan
            . ' belum termigrasi. Tidak ada satu pun baris yang asal'
            . ' databasenya dapat dipastikan sekaligus terhubung ke stok'
            . ' unit ini, sehingga laporannya tidak bisa disusun tanpa'
            . ' mengarang. Laporan sengaja dikosongkan daripada'
            . ' menampilkan baris milik unit lain. Silakan teruskan ke'
            . ' tim migrasi.'
        );
    }

    private function kunciSertipikat(string $kolom): string
    {
        return <<<SQL
            CASE
                WHEN BTRIM(CAST({$kolom} AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST({$kolom} AS TEXT))
                WHEN BTRIM(CAST(akta.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                THEN REGEXP_REPLACE(
                         BTRIM(CAST(akta.ppjb_id AS TEXT)), '[0-9]+$', ''
                     ) || BTRIM(CAST({$kolom} AS TEXT))
                ELSE BTRIM(CAST({$kolom} AS TEXT))
            END
            SQL;
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
                && (
                    $errors === false
                    || (
                        $errors['warning_count'] === 0
                        && $errors['error_count'] === 0
                    )
                );

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
