<?php

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class daftar_sertifikat_pecahan_m extends Model
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

    public function obtainDaftarSertipikatPemisahan($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );
        $sektor = $this->normalizeText($request->sektor ?? '*');
        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ');
        $tglAwal = $this->normalizeDate(
            $request->tgl_awal ?? date('Y-m-d')
        );
        $tglAkhirEksklusif = $this->normalizeDate(
            $request->tgl_akhir ?? date('Y-m-d'),
            1
        );
        $tampilPenggabungan = $this->normalizeYesNo(
            $request->tampil_penggabungan ?? 'T'
        );
        $kartuSuratTanah = $this->normalizeYesNo(
            $request->kartu_surat_tanah ?? 'T'
        );
        $apartemen = $this->normalizeYesNo(
            $request->apartemen ?? 'T'
        );
        $statusAjb = $this->normalizeAjbStatus(
            $request->status_ajb ?? 'SEMUA'
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

        if ($kartuSuratTanah === 'Y') {
            $tampilPenggabungan = 'T';

            return $this->obtainKartuSuratTanah(
                $perusahaan,
                $sektor,
                $blokAwal,
                $blokAkhir,
                $tglAwal,
                $tglAkhirEksklusif,
                $apartemen,
                $statusAjb
            );
        }

        return $this->obtainPecahanPenggabungan(
            $perusahaan,
            $sektor,
            $blokAwal,
            $blokAkhir,
            $tglAwal,
            $tglAkhirEksklusif,
            $tampilPenggabungan,
            $apartemen,
            $statusAjb
        );
    }

    private function obtainPecahanPenggabungan(
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif,
        string $tampilPenggabungan,
        string $apartemen,
        string $statusAjb
    ): array {
        $k = $this->namaKolom();
        $jenisFilterSql = $this->buildJenisFilterSql($apartemen, $k['stokJenis']);
        $ajbFilterSql = $this->buildAjbFilterSql($statusAjb);
        $bantu = $this->cteBantu($k, true);
        $kunciIdk = $this->kunciSertipikatIdk();
        $syaratBlok = $this->syaratBlok('hasil_dasar."BLOK"', 'hasil_dasar."NOMOR"');

        $sql = <<<SQL
            WITH {$bantu},
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
                    sertipikat_idk.luas_su_pisah AS "LUAS_SU_PISAH_IDK",
                    sertipikat_idk.luas_su_pisah AS "LUAS_SU_PEMISAHAN",
                    sertipikat_idk.mohon_pisah AS "MOHON_PISAH",
                    sertipikat_idk.tgl_mohon_pisah AS "TGL_MOHON_PISAH",
                    sertipikat_idk.ser_pisah AS "SER_PISAH_IDK",
                    sertipikat_idk.ser_pisah AS "NO_SERTIPIKAT_PEMISAHAN",
                    sertipikat_idk.tgl_ser_pisah AS "TGL_SER_PISAH_IDK",
                    sertipikat_idk.tgl_ser_pisah AS "TGL_SERTIPIKAT_PEMISAHAN",
                    sertipikat_idk.su_pisah AS "SU_PISAH_IDK",
                    sertipikat_idk.su_pisah AS "NO_SU_PEMISAHAN",
                    sertipikat_idk.tgl_su_pisah AS "TGL_SU_PISAH_IDK",
                    sertipikat_idk.tgl_su_pisah AS "TGL_SU_PEMISAHAN",

                    sertipikat.no_sertipikat AS "NO_SERTIPIKAT",
                    sertipikat.no_sertipikat AS "NO_SERTIPIKAT_GABUNGAN",
                    sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT",
                    sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT_GABUNGAN",
                    sertipikat.su_pisah AS "SU_PISAH",
                    sertipikat.su_pisah AS "NO_SU_GABUNGAN",
                    sertipikat.tgl_su_pisah AS "TGL_SU_PISAH",
                    sertipikat.tgl_su_pisah AS "TGL_SU_GABUNGAN",
                    sertipikat.tgl_berlaku AS "TGL_BERLAKU",
                    sertipikat.luas_sup AS "LUAS_SUP",
                    sertipikat.luas_sup AS "LUAS_SU_GABUNGAN",

                    stok.{$k['stokPerusahaan']} AS "KD_PERUSAHAAN",
                    stok.{$k['stokJenis']} AS "KD_JENIS",
                    CAST(:apartemen AS VARCHAR(1)) AS "APARTEMEN",
                    CURRENT_TIMESTAMP AS "TGL_CETAK",
                    sektor_ref.deskripsi AS "NAMA_SEKTOR",
                    ppjb_aktif.user_entry AS "USER_ENTRY",
                    stok.blok AS "BLOK",
                    stok.nomor AS "NOMOR",
                    luas_ref.luas_ppjb AS "LUAS_PPJB",
                    sertipikat.tgl_input_valid AS "TGL_INPUT",
                    CAST(:tampil_penggabungan AS VARCHAR(1))
                        AS "TAMPIL_PENGGABUNGAN",
                    CAST('T' AS VARCHAR(1)) AS "KARTU_SURAT_TANAH"

                FROM public.sr_sertipikat_idk AS sertipikat_idk
                INNER JOIN sertipikat_terpilih AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = {$kunciIdk}
                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

                LEFT JOIN ppjb_aktif
                    ON ppjb_aktif.kunci_stok = stok.kunci_stok
                LEFT JOIN pembeli_nama
                    ON pembeli_nama.kode = ppjb_aktif.ppjb_id
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode
                     = UPPER(BTRIM(COALESCE(CAST(stok.{$k['stokSektor']} AS TEXT), '')))
                LEFT JOIN luas_ref
                    ON luas_ref.kunci = sertipikat.kunci_luas

                WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
                  AND sertipikat.stok_id IS NOT NULL
                  {$jenisFilterSql}
                  {$ajbFilterSql}
            )
            SELECT
                hasil_dasar.*,
                COALESCE(hasil_dasar."LUAS_SU_PISAH_IDK", 0)
                    - COALESCE(hasil_dasar."LUAS_PPJB", 0)
                    AS "SELISIH_LUAS_PEMISAHAN",
                COALESCE(hasil_dasar."LUAS_SUP", 0)
                    - COALESCE(hasil_dasar."LUAS_PPJB", 0)
                    AS "SELISIH_LUAS_GABUNGAN",
                CASE
                    WHEN hasil_dasar."TAMPIL_PENGGABUNGAN" = 'Y'
                    THEN COALESCE(hasil_dasar."LUAS_SUP", 0)
                         - COALESCE(hasil_dasar."LUAS_PPJB", 0)
                    ELSE COALESCE(hasil_dasar."LUAS_SU_PISAH_IDK", 0)
                         - COALESCE(hasil_dasar."LUAS_PPJB", 0)
                END AS "SELISIH_LUAS"
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
                hasil_dasar."NO_SERTIPIKAT" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'awalan_idk' => $this->awalanUnit($perusahaan),
            'perusahaan_langsung' => $perusahaan,
            'tampil_penggabungan' => $tampilPenggabungan,
            'apartemen' => $apartemen,
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

    private function obtainKartuSuratTanah(
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif,
        string $apartemen,
        string $statusAjb
    ): array {
        $k = $this->namaKolom();
        $jenisFilterSql = $this->buildJenisFilterSql($apartemen, $k['stokJenis']);
        $ajbFilterSql = $this->buildAjbFilterSql($statusAjb);
        $bantu = $this->cteBantu($k, false);
        $kunciIdk = $this->kunciSertipikatIdk();
        $syaratBlok = $this->syaratBlok('hasil_dasar."BLOK"', 'hasil_dasar."NOMOR"');

        $sql = <<<SQL
            WITH {$bantu},
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
                sertipikat_idk.luas_su_pisah AS "LUAS_SU_PISAH_IDK",
                sertipikat_idk.luas_su_pisah AS "LUAS_SU_PEMISAHAN",
                sertipikat_idk.mohon_pisah AS "MOHON_PISAH",
                sertipikat_idk.tgl_mohon_pisah AS "TGL_MOHON_PISAH",
                sertipikat_idk.ser_pisah AS "SER_PISAH_IDK",
                sertipikat_idk.ser_pisah AS "NO_SERTIPIKAT_PEMISAHAN",
                sertipikat_idk.tgl_ser_pisah AS "TGL_SER_PISAH_IDK",
                sertipikat_idk.tgl_ser_pisah AS "TGL_SERTIPIKAT_PEMISAHAN",
                sertipikat_idk.su_pisah AS "SU_PISAH_IDK",
                sertipikat_idk.su_pisah AS "NO_SU_PEMISAHAN",
                sertipikat_idk.tgl_su_pisah AS "TGL_SU_PISAH_IDK",
                sertipikat_idk.tgl_su_pisah AS "TGL_SU_PEMISAHAN",

                sertipikat.no_sertipikat AS "NO_SERTIPIKAT",
                sertipikat.no_sertipikat AS "NO_SERTIPIKAT_GABUNGAN",
                sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT",
                sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT_GABUNGAN",
                sertipikat.su_pisah AS "SU_PISAH",
                sertipikat.su_pisah AS "NO_SU_GABUNGAN",
                sertipikat.tgl_su_pisah AS "TGL_SU_PISAH",
                sertipikat.tgl_su_pisah AS "TGL_SU_GABUNGAN",
                sertipikat.tgl_berlaku AS "TGL_BERLAKU",
                sertipikat.luas_sup AS "LUAS_SUP",
                sertipikat.luas_sup AS "LUAS_SU_GABUNGAN",
                sertipikat.atas_nama_pt AS "ATAS_NAMA_PT",

                stok.{$k['stokPerusahaan']} AS "KD_PERUSAHAAN",
                stok.{$k['stokJenis']} AS "KD_JENIS",
                CAST(:apartemen AS VARCHAR(1)) AS "APARTEMEN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                sertipikat.tgl_input_ser_valid AS "TGL_INPUT_SER",
                sertipikat.tgl_input_ser_valid AS "TGL_INPUT",
                sektor_ref.deskripsi AS "NAMA_SEKTOR",
                ppjb_aktif.user_entry AS "USER_ENTRY",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                CAST(NULL AS NUMERIC(18, 2)) AS "LUAS_PPJB",
                CAST(NULL AS NUMERIC(18, 2)) AS "SELISIH_LUAS",
                CAST(NULL AS NUMERIC(18, 2)) AS "SELISIH_LUAS_PEMISAHAN",
                CAST(NULL AS NUMERIC(18, 2)) AS "SELISIH_LUAS_GABUNGAN",
                CAST('T' AS VARCHAR(1)) AS "TAMPIL_PENGGABUNGAN",
                CAST('Y' AS VARCHAR(1)) AS "KARTU_SURAT_TANAH"

            FROM public.sr_sertipikat_idk AS sertipikat_idk
            INNER JOIN sertipikat_terpilih AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = {$kunciIdk}
            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            LEFT JOIN ppjb_aktif
                ON ppjb_aktif.kunci_stok = stok.kunci_stok
            LEFT JOIN pembeli_nama
                ON pembeli_nama.kode = ppjb_aktif.ppjb_id
            LEFT JOIN sektor_ref
                ON sektor_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$k['stokSektor']} AS TEXT), '')))

            WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
              AND sertipikat.stok_id IS NOT NULL
              {$jenisFilterSql}
              {$ajbFilterSql}
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
                hasil_dasar."NO_SERTIPIKAT" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'awalan_idk' => $this->awalanUnit($perusahaan),
            'perusahaan_langsung' => $perusahaan,
            'apartemen' => $apartemen,
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

    private function syaratBlok(string $blok, string $nomor): string
    {
        return <<<SQL
            (
                (
                    UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST({$nomor} AS TEXT), '')))
                    BETWEEN :blok_awal_unit AND :blok_akhir_unit
                )
                OR
                (
                    UPPER(BTRIM(COALESCE(CAST({$blok} AS TEXT), '')))
                    BETWEEN :blok_awal_blok AND :blok_akhir_blok
                )
            )
            SQL;
    }

    private function cteBantu(array $k, bool $pakaiGabung): string
    {
        $tglSumber = $pakaiGabung
            ? "COALESCE(\n"
                . "                        CASE\n"
                . "                            WHEN COALESCE(CAST(s.tgl_input_gabung AS TEXT), '')\n"
                . "                                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'\n"
                . "                            THEN CAST(s.tgl_input_gabung AS TIMESTAMP)\n"
                . "                        END,\n"
                . "                        CASE\n"
                . "                            WHEN COALESCE(CAST(s.tgl_input_ser AS TEXT), '')\n"
                . "                                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'\n"
                . "                            THEN CAST(s.tgl_input_ser AS TIMESTAMP)\n"
                . "                        END\n"
                . "                    )"
            : "CASE\n"
                . "                        WHEN COALESCE(CAST(s.tgl_input_ser AS TEXT), '')\n"
                . "                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'\n"
                . "                        THEN CAST(s.tgl_input_ser AS TIMESTAMP)\n"
                . "                    END";

        return <<<SQL
        sertipikat_terpilih AS (
                SELECT
                    s.*,
                    {$tglSumber} AS tgl_input_valid,
                    CASE
                        WHEN COALESCE(CAST(s.tgl_input_ser AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(s.tgl_input_ser AS TIMESTAMP)
                    END AS tgl_input_ser_valid,
                    UPPER(BTRIM(COALESCE(CAST(s.{$k['sertipikatPerusahaan']} AS TEXT), '')))
                        || '|' || UPPER(BTRIM(COALESCE(CAST(s.blok AS TEXT), '')))
                        || '|' || UPPER(BTRIM(COALESCE(CAST(s.nomor AS TEXT), '')))
                        AS kunci_luas
                FROM public.sr_sertipikat AS s
                WHERE {$tglSumber} >= CAST(:tgl_awal AS DATE)
                  AND {$tglSumber} < CAST(:tgl_akhir_gpt AS DATE)
            ),
            stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE (
                        stok.{$k['stokPerusahaan']} = :perusahaan_langsung
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.{$k['stokPerusahaan']} AS TEXT), '')))
                            = :perusahaan
                      )
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$k['stokSektor']} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, ppjb_id, user_entry
                FROM (
                    SELECT
                        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS ppjb_id,
                        ppjb.user_entry AS user_entry,
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
            luas_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci) kunci, luas_ppjb
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(stok_luas.{$k['stokPerusahaan']} AS TEXT), '')))
                            || '|' || UPPER(BTRIM(COALESCE(
                                CAST(stok_luas.blok AS TEXT), '')))
                            || '|' || UPPER(BTRIM(COALESCE(
                                CAST(stok_luas.nomor AS TEXT), ''))) AS kunci,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                     CAST(ppjb_luas.addendum AS TEXT), 'T'))) = 'Y'
                             AND BTRIM(COALESCE(
                                     CAST(ppjb_luas.jenis_perubahan AS TEXT), '0'))
                                 = '6'
                            THEN ppjb_luas.luas_tanah
                            ELSE stok_luas.luas_tanah
                        END AS luas_ppjb,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                     CAST(ppjb_luas.flag_aktif AS TEXT), ''))) = 'A'
                             AND ppjb_luas.parent_id IS NULL
                            THEN 0
                            ELSE 1
                        END AS urut_aktif,
                        ppjb_luas.tgl_ppjb AS tgl_ppjb,
                        BTRIM(CAST(ppjb_luas.ppjb_id AS TEXT)) AS ppjb_id
                    FROM public.sr_stok AS stok_luas
                    LEFT JOIN public.sr_ppjb AS ppjb_luas
                        ON BTRIM(CAST(ppjb_luas.stok_id AS TEXT))
                         = BTRIM(CAST(stok_luas.stok_id AS TEXT))
                ) AS daftar
                ORDER BY kunci, urut_aktif, tgl_ppjb DESC NULLS LAST, ppjb_id DESC
            ),
            sektor_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(sektor.{$k['sektorKode']} AS TEXT), ''))) AS kode,
                        sektor.deskripsi AS deskripsi,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                     CAST(sektor.{$k['sektorPerusahaan']} AS TEXT), '')))
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
            )
        SQL;
    }

    private function namaKolom(): array
    {
        return [
            'stokPerusahaan' => $this->kolomKode('sr_stok', [
                'kd_perusahaan', 'kd_unit', 'kd_pt',
            ]),
            'stokSektor' => $this->kolomKode('sr_stok', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
            'stokJenis' => $this->kolomKode('sr_stok', [
                'kd_jenis_bgn', 'kd_jenis',
            ]),
            'sektorKode' => $this->kolomKode('sr_sektor', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
            'sektorPerusahaan' => $this->kolomKode('sr_sektor', [
                'kd_perusahaan', 'kd_unit', 'kd_pt',
            ]),
            'sertipikatPerusahaan' => $this->kolomKode('sr_sertipikat', [
                'kd_perusahaan', 'kd_unit', 'kd_pt',
            ]),
        ];
    }

    private function buildJenisFilterSql(string $apartemen, string $kolom): string
    {
        $ekspresi = "UPPER(BTRIM(COALESCE(CAST(stok.{$kolom} AS TEXT), '')))";

        if ($apartemen === 'Y') {
            return "AND {$ekspresi} IN ('APT', 'KTR')";
        }

        return "AND {$ekspresi} NOT IN ('APT', 'KTR')";
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
AND (
                  NOT EXISTS (
                      SELECT 1
                      FROM public.sr_akta AS a
                      WHERE {$kunci}
                          = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM public.sr_akta AS a
                      WHERE {$kunci}
                          = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                        AND a.no_akta IS NULL
                  )
              )
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
                && ($errors === false
                    || ($errors['warning_count'] === 0
                        && $errors['error_count'] === 0));

            if ($valid) {
                if ($addDays !== 0) {
                    $date = $date->modify(($addDays > 0 ? '+' : '') . $addDays . ' day');
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

    private function normalizeAjbStatus($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array(
            $normalized,
            ['SUDAH', 'BELUM', 'SEMUA'],
            true
        ) ? $normalized : 'SEMUA';
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
