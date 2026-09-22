<?php

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class dftr_pengambilan_surat_m extends Model
{
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    public $timestamps = false;

    public function obtainSektor(string $kdPerusahaan): array
    {
        $perusahaan = $this->normalizeText($kdPerusahaan);

        if ($perusahaan === '') {
            return [];
        }

        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $adaUnit = $this->adaKolom('sr_sektor', 'kd_perusahaan');

        $kolomUnit = $adaUnit
            ? "BTRIM(COALESCE(CAST(sektor.kd_perusahaan AS TEXT), ''))"
            : 'CAST(NULL AS TEXT)';

        $syaratUnit = $adaUnit
            ? "AND (
                    sektor.kd_perusahaan = :perusahaan_langsung
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.kd_perusahaan AS TEXT), '')))
                        = :perusahaan
                 )"
            : '';

        $sql = <<<SQL
            SELECT
                BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), ''))
                    AS "KD_SEKTOR",
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), ''))
                    AS "DESKRIPSI",
                {$kolomUnit} AS "KD_PERUSAHAAN"
            FROM public.sr_sektor AS sektor
            WHERE (
                    sektor.flag_aktif = 'A'
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
                  )
              {$syaratUnit}
            ORDER BY sektor.deskripsi, sektor.{$sektorKode}
        SQL;

        $bindings = $adaUnit
            ? ['perusahaan' => $perusahaan, 'perusahaan_langsung' => $perusahaan]
            : [];

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    public function obtainDaftarPengambilanSurat(array $filters): array
    {
        $mode = strtolower(trim((string) ($filters['mode'] ?? 'biasa')));

        if ($mode === 'pengambilan_pt') {
            return $this->obtainPengambilanSertipikatPt($filters);
        }

        if ($mode !== 'biasa') {
            throw new InvalidArgumentException('Mode laporan tidak dikenal.');
        }

        return $this->obtainRekapSuratBiasa($filters);
    }

    private function obtainRekapSuratBiasa(array $filters): array
    {
        $perusahaan = $this->normalizeText($filters['perusahaan'] ?? '');
        $sektor = $this->normalizeText($filters['sektor'] ?? '*');
        $blokAwal = $this->normalizeText($filters['blok_awal'] ?? 'A');
        $blokAkhir = $this->normalizeText($filters['blok_akhir'] ?? 'ZZ');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
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

        [$stokPerusahaan, $stokSektor, $sektorKode] = $this->namaKolom();

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        if (!$pakaiUnik) {
            $this->pastikanKeluargaAda('sr_pengambilan', $awalan);
        }

        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                    ON sertipikat_unik.angka
                     = BTRIM(CAST(pengambilan.sertipikat_id AS TEXT))"
            : '';
        $kunciPengambilan = $this->kunciSertipikat('pengambilan', $pakaiUnik);

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        [$syaratTanggal, $bindingTanggal] = $this->syaratTanggalDokumen($filters);

        $sql = <<<SQL
            WITH {$cteSertipikatUnik}stok_terpilih AS (
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
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            pengambilan_terpilih AS (
                SELECT
                    pengambilan.*,
                    {$kunciPengambilan} AS kunci_sertipikat
                FROM public.sr_pengambilan AS pengambilan
                {$joinSertipikatUnik}
                WHERE {$syaratTanggal}
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    ppjb.nm_notaris AS nm_notaris,
                    ppjb.kd_bank    AS kd_bank
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
                    BTRIM(COALESCE(CAST(nasabah.nama AS TEXT), '')) AS nama
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
            sektor_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(sektor.{$sektorKode} AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(sektor.deskripsi AS TEXT), '')) AS deskripsi,
                        {$urutanSektorUnit} AS urutan_unit,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
                            THEN 0 ELSE 1
                        END AS urutan_aktif,
                        sektor.ctid AS urutan_fisik
                    FROM public.sr_sektor AS sektor
                    {$saringSektorUnit}
                ) AS daftar
                ORDER BY kode, urutan_unit, urutan_aktif, urutan_fisik
            ),
            bank_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, nama
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(bank.kd_bank AS TEXT), '')) AS kode,
                        BTRIM(COALESCE(CAST(bank.nama AS TEXT), '')) AS nama,
                        bank.ctid AS urutan_fisik
                    FROM public.sr_bank AS bank
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            akta_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_ppjb) kunci_ppjb, tgl_akta
                FROM (
                    SELECT
                        BTRIM(CAST(akta.ppjb_id AS TEXT)) AS kunci_ppjb,
                        akta.tgl_akta AS tgl_akta,
                        akta.ctid AS urutan_fisik
                    FROM public.sr_akta AS akta
                ) AS daftar
                ORDER BY kunci_ppjb, tgl_akta DESC NULLS LAST, urutan_fisik
            ),
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                                                  AS "BLOK_NOMOR",
                    nasabah.nama                  AS "NAMA",

                    pengambilan.tgl_input_imb     AS "TGL_INPUT_IMB",
                    pengambilan.tgl_input_ser     AS "TGL_INPUT_SER",
                    pengambilan.tgl_input_akta    AS "TGL_INPUT_AKTA",
                    pengambilan.tgl_input_shm     AS "TGL_INPUT_SHM",
                    pengambilan.tgl_input_ph      AS "TGL_INPUT_PH",
                    pengambilan.tgl_input_ppjb    AS "TGL_INPUT_PPJB",
                    pengambilan.tgl_ambil_imb     AS "TGL_AMBIL_IMB",
                    pengambilan.tgl_ambil_ser     AS "TGL_AMBIL_SER",
                    pengambilan.tgl_ambil_akta    AS "TGL_AMBIL_AKTA",
                    pengambilan.tgl_ambil_shm     AS "TGL_AMBIL_SHM",
                    pengambilan.tgl_ambil_ph      AS "TGL_AMBIL_PH",
                    pengambilan.tgl_ambil_ppjb    AS "TGL_AMBIL_PPJB",

                    BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), ''))
                                                  AS "KD_PERUSAHAAN",
                    sektor_ref.deskripsi          AS "NAMA_SEKTOR",
                    BTRIM(COALESCE(CAST(ppjb.nm_notaris AS TEXT), ''))
                                                  AS "NM_NOTARIS",
                    bank_ref.nama                 AS "NM_BANK",
                    akta_ref.tgl_akta             AS "TGL_AJB",

                    stok.blok                     AS "BLOK",
                    stok.nomor                    AS "NOMOR",
                    stok.stok_id                  AS "STOK_ID",
                    sertipikat.sertipikat_id      AS "SERTIPIKAT_ID",
                    CURRENT_TIMESTAMP             AS "TGL_CETAK"

                FROM pengambilan_terpilih AS pengambilan

                INNER JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = pengambilan.kunci_sertipikat

                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

                INNER JOIN ppjb_aktif AS ppjb
                    ON ppjb.kunci_stok = stok.kunci_stok

                INNER JOIN pembeli_nasabah AS nasabah
                    ON nasabah.kunci_ppjb = ppjb.kunci_ppjb

                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))
                LEFT JOIN bank_ref
                    ON bank_ref.kode
                     = BTRIM(COALESCE(CAST(ppjb.kd_bank AS TEXT), ''))
                LEFT JOIN akta_ref
                    ON akta_ref.kunci_ppjb = ppjb.kunci_ppjb

                WHERE sertipikat.stok_id IS NOT NULL
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            WHERE (
                    (
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                            >= :blok_awal_unit
                        AND
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                            <= :blok_akhir_unit
                    )
                    OR
                    (
                        hasil_dasar."BLOK" >= :blok_awal_blok
                        AND hasil_dasar."BLOK" <= :blok_akhir_blok
                    )
                  )
            ORDER BY
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."NAMA" ASC
        SQL;

        $bindings = array_merge([
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
        ], $bindingTanggal);

        if ($adaSektorUnit) {
            $bindings['perusahaan_sektor'] = $perusahaan;
            $bindings['perusahaan_sektor_saring'] = $perusahaan;
        }

        if (!$pakaiUnik) {
            $bindings['awalan_sertipikat'] = $awalan;
        }

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    private function syaratTanggalDokumen(array $filters): array
    {
        $kategori = [
            'imb'        => ['tgl_input_imb',  'tgl_terima_imb_awal',        'tgl_terima_imb_akhir'],
            'sertipikat' => ['tgl_input_ser',  'tgl_terima_sertipikat_awal', 'tgl_terima_sertipikat_akhir'],
            'ajb'        => ['tgl_input_akta', 'tgl_terima_ajb_awal',        'tgl_terima_ajb_akhir'],
            'shm'        => ['tgl_input_shm',  'tgl_terima_shm_awal',        'tgl_terima_shm_akhir'],
            'ph'         => ['tgl_input_ph',   'tgl_terima_ph_awal',         'tgl_terima_ph_akhir'],
            'ppjb'       => ['tgl_input_ppjb', 'tgl_terima_ppjb_awal',       'tgl_terima_ppjb_akhir'],
        ];

        $terisi = [];

        foreach ($kategori as $kode => [$kolom, $kotakAwal, $kotakAkhir]) {
            $awal = trim((string) ($filters[$kotakAwal] ?? ''));
            $akhir = trim((string) ($filters[$kotakAkhir] ?? ''));

            if ($awal !== '' && $akhir !== '') {
                $terisi[] = [
                    'kode' => $kode,
                    'kolom' => $kolom,
                    'awal' => $this->normalizeDate($awal),
                    'akhir' => $this->normalizeDate($akhir, 1),
                ];
            }
        }

        if ($terisi === []) {
            return ['TRUE', []];
        }

        $semuaKolom = array_column($kategori, 0);
        $potongan = [];

        $satu = $terisi[0];
        $bindings = [
            'tgl_satu_awal' => $satu['awal'],
            'tgl_satu_akhir' => $satu['akhir'],
        ];

        foreach ($semuaKolom as $kolom) {
            $potongan[] = "(pengambilan.{$kolom}"
                . " >= CAST(:tgl_satu_awal AS TIMESTAMP)"
                . " AND pengambilan.{$kolom}"
                . " < CAST(:tgl_satu_akhir AS TIMESTAMP))";
        }

        return ['(' . implode(' OR ', $potongan) . ')', $bindings];
    }

    private function obtainPengambilanSertipikatPt(array $filters): array
    {
        $perusahaan = $this->normalizeText($filters['perusahaan'] ?? '');
        $sektor = $this->normalizeText($filters['sektor'] ?? '*');
        $blokAwal = $this->normalizeText($filters['blok_awal'] ?? 'A');
        $blokAkhir = $this->normalizeText($filters['blok_akhir'] ?? 'ZZ');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
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

        $tglAwal = $this->normalizeDate($filters['tgl_ambil_awal'] ?? date('Y-m-d'));
        $tglAkhir = $this->normalizeDate(
            $filters['tgl_ambil_akhir'] ?? date('Y-m-d'),
            1
        );

        [$stokPerusahaan, $stokSektor, $sektorKode] = $this->namaKolom();

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                        ON sertipikat_unik.angka
                         = BTRIM(CAST(idk.sertipikat_id AS TEXT))"
            : '';
        $kunciIdk = $this->kunciSertipikat('idk', $pakaiUnik);

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        $urutPpjb = $this->urutanPpjbId('ppjb.ppjb_id');

        $sql = <<<SQL
            WITH {$cteSertipikatUnik}stok_terpilih AS (
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
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            idk_terpilih AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_sertipikat)
                    kunci_sertipikat, nama_pt, ser_pisah, tgl_ser_pisah,
                    su_pisah, tgl_su_pisah, luas_su_pisah
                FROM (
                    SELECT
                        {$kunciIdk} AS kunci_sertipikat,
                        idk.nama_pt, idk.ser_pisah, idk.tgl_ser_pisah,
                        idk.su_pisah, idk.tgl_su_pisah, idk.luas_su_pisah,
                        idk.ctid AS urutan_fisik
                    FROM public.sr_sertipikat_idk AS idk
                    {$joinSertipikatUnik}
                ) AS daftar
                ORDER BY kunci_sertipikat, urutan_fisik
            ),
            ppjb_induk AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok) kunci_stok, kunci_ppjb
                FROM (
                    SELECT
                        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                        ppjb.tgl_ppjb AS tgl_ppjb,
                        {$urutPpjb} AS urut_id
                    FROM public.sr_ppjb AS ppjb
                    WHERE (
                            ppjb.flag_aktif = 'A'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                          )
                      AND ppjb.parent_id IS NULL
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST,
                         urut_id DESC NULLS LAST
            ),
            pembeli_gabung AS MATERIALIZED (
                SELECT kunci_ppjb, STRING_AGG(nama, ', ' ORDER BY urutan_fisik) AS nama
                FROM (
                    SELECT
                        BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                        BTRIM(COALESCE(CAST(nasabah.nama AS TEXT), '')) AS nama,
                        pembeli_ppjb.ctid AS urutan_fisik
                    FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                    INNER JOIN public.sr_nasabah AS nasabah
                        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                         = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
                    WHERE (
                            pembeli_ppjb.flag_aktif = 'Y'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
                          )
                ) AS daftar
                GROUP BY kunci_ppjb
            ),
            sektor_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(sektor.{$sektorKode} AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(sektor.deskripsi AS TEXT), '')) AS deskripsi,
                        {$urutanSektorUnit} AS urutan_unit,
                        CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
                            THEN 0 ELSE 1
                        END AS urutan_aktif,
                        sektor.ctid AS urutan_fisik
                    FROM public.sr_sektor AS sektor
                    {$saringSektorUnit}
                ) AS daftar
                ORDER BY kode, urutan_unit, urutan_aktif, urutan_fisik
            ),
            user_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_user) kunci_user, nama_user
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(pengguna.user_name AS TEXT), ''))) AS kunci_user,
                        COALESCE(
                            NULLIF(BTRIM(COALESCE(
                                CAST(pengguna.alias AS TEXT), '')), ''),
                            BTRIM(COALESCE(CAST(pengguna.user_name AS TEXT), ''))
                        ) AS nama_user,
                        pengguna.ctid AS urutan_fisik
                    FROM public.sr_users_app AS pengguna
                ) AS daftar
                ORDER BY kunci_user, urutan_fisik
            ),
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                                              AS "BLOK_NOMOR",
                    pembeli_gabung.nama       AS "NAMA_PEMILIK",
                    idk.nama_pt               AS "NAMA_PT",

                    sertipikat.tgl_ambil_legal AS "TGL_AMBIL_LEGAL",
                    user_ref.nama_user         AS "USER_AMBIL_LEGAL",

                    idk.ser_pisah             AS "NO_SERTIPIKAT_PEMISAHAN",
                    idk.tgl_ser_pisah         AS "TGL_SERTIPIKAT_PEMISAHAN",
                    idk.su_pisah              AS "NO_SU_PEMISAHAN",
                    idk.tgl_su_pisah          AS "TGL_SU_PEMISAHAN",
                    idk.luas_su_pisah         AS "LUAS_SU_PEMISAHAN",

                    sertipikat.no_sertipikat  AS "NO_SERTIPIKAT",
                    sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT",
                    sertipikat.su_pisah       AS "SU_PISAH",
                    sertipikat.tgl_su_pisah   AS "TGL_SU_PISAH",
                    sertipikat.luas_sup       AS "LUAS_SUP",
                    sertipikat.tgl_berlaku    AS "TGL_BERLAKU",

                    BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), ''))
                                              AS "KD_PERUSAHAAN",
                    sektor_ref.deskripsi      AS "NAMA_SEKTOR",
                    stok.blok                 AS "BLOK",
                    stok.nomor                AS "NOMOR",
                    stok.stok_id              AS "STOK_ID",
                    sertipikat.sertipikat_id  AS "SERTIPIKAT_ID",
                    CURRENT_TIMESTAMP         AS "TGL_CETAK"

                FROM public.sr_sertipikat AS sertipikat

                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

                LEFT JOIN idk_terpilih AS idk
                    ON idk.kunci_sertipikat
                     = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))

                LEFT JOIN ppjb_induk
                    ON ppjb_induk.kunci_stok = stok.kunci_stok
                LEFT JOIN pembeli_gabung
                    ON pembeli_gabung.kunci_ppjb = ppjb_induk.kunci_ppjb

                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))
                LEFT JOIN user_ref
                    ON user_ref.kunci_user = UPPER(BTRIM(COALESCE(
                           CAST(sertipikat.user_ambil_legal AS TEXT), '')))

                WHERE sertipikat.stok_id IS NOT NULL
                  AND UPPER(BTRIM(COALESCE(
                        CAST(sertipikat.flag_ambil_legal AS TEXT), 'T'))) = 'Y'
                  AND sertipikat.tgl_ambil_legal
                        >= CAST(:tgl_ambil_awal AS TIMESTAMP)
                  AND sertipikat.tgl_ambil_legal
                        < CAST(:tgl_ambil_akhir AS TIMESTAMP)
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            WHERE (
                    (
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                            >= :blok_awal_unit
                        AND
                        BTRIM(COALESCE(CAST(hasil_dasar."BLOK" AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(hasil_dasar."NOMOR" AS TEXT), ''))
                            <= :blok_akhir_unit
                    )
                    OR
                    (
                        hasil_dasar."BLOK" >= :blok_awal_blok
                        AND hasil_dasar."BLOK" <= :blok_akhir_blok
                    )
                  )
            ORDER BY
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."SERTIPIKAT_ID" ASC
        SQL;

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'tgl_ambil_awal' => $tglAwal,
            'tgl_ambil_akhir' => $tglAkhir,
        ];

        if ($adaSektorUnit) {
            $bindings['perusahaan_sektor'] = $perusahaan;
            $bindings['perusahaan_sektor_saring'] = $perusahaan;
        }

        if (!$pakaiUnik) {
            $bindings['awalan_sertipikat'] = $awalan;
        }

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    private function namaKolom(): array
    {
        return [
            $this->kolomKode('sr_stok', ['kd_perusahaan', 'kd_unit', 'kd_pt']),
            $this->kolomKode('sr_stok', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
            $this->kolomKode('sr_sektor', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
        ];
    }

    private function sektorUnit(): array
    {
        $ada = $this->adaKolom('sr_sektor', 'kd_perusahaan');

        return [
            $ada,
            $ada
                ? "CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                CAST(sektor.kd_perusahaan AS TEXT), '')))
                                = :perusahaan_sektor
                            THEN 0 ELSE 1
                        END"
                : '0',
            $ada
                ? "WHERE UPPER(BTRIM(COALESCE(
                              CAST(sektor.kd_perusahaan AS TEXT), '')))
                              = :perusahaan_sektor_saring
                       OR sektor.kd_perusahaan IS NULL"
                : '',
        ];
    }

    private function urutanPpjbId(string $kolom): string
    {
        return <<<SQL
        CASE
                            WHEN REGEXP_REPLACE(BTRIM(CAST({$kolom} AS TEXT)),
                                                '^[^0-9]+', '') ~ '^[0-9]+$'
                            THEN REGEXP_REPLACE(BTRIM(CAST({$kolom} AS TEXT)),
                                                '^[^0-9]+', '')::numeric
                            ELSE NULL
                        END
        SQL;
    }

    private function kunciSertipikat(string $alias, bool $pakaiUnik): string
    {
        $awalan = $pakaiUnik ? 'sertipikat_unik.awalan' : ':awalan_sertipikat';

        return <<<SQL
        CASE
                        WHEN BTRIM(CAST({$alias}.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                        THEN BTRIM(CAST({$alias}.sertipikat_id AS TEXT))
                        ELSE {$awalan}
                             || BTRIM(CAST({$alias}.sertipikat_id AS TEXT))
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

    private function urutanNomor(string $kolom): string
    {
        return <<<SQL
        CASE
                    WHEN BTRIM(COALESCE(CAST({$kolom} AS TEXT), '')) <> ''
                     AND BTRIM(CAST({$kolom} AS TEXT)) ~ '^[0-9]+$'
                    THEN 0 ELSE 1
                END ASC,
                CASE
                    WHEN BTRIM(COALESCE(CAST({$kolom} AS TEXT), '')) <> ''
                     AND BTRIM(CAST({$kolom} AS TEXT)) ~ '^[0-9]+$'
                    THEN LPAD(BTRIM(CAST({$kolom} AS TEXT)), 50, '0')
                    ELSE ''
                END ASC
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

    private function kolomKode(string $tabel, array $kandidat): string
    {
        static $ingatan = [];

        $kunci = $tabel . '|' . implode(',', $kandidat);

        if (isset($ingatan[$kunci])) {
            return $ingatan[$kunci];
        }

        foreach ($kandidat as $kolom) {
            if ($this->adaKolom($tabel, $kolom)) {
                return $ingatan[$kunci] = $kolom;
            }
        }

        return $ingatan[$kunci] = $kandidat[0];
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
            'Format tanggal tidak valid: ' . $text
            . '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function pastikanKeluargaAda(string $tabel, string $awalan): void
    {
        static $ingatan = [];

        $kunci = $tabel . '|' . $awalan;

        if (isset($ingatan[$kunci])) {
            if ($ingatan[$kunci] === false) {
                $this->tolakKeluargaKosong($tabel, $awalan);
            }

            return;
        }

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
            )
            SELECT angka_sertipikat.awalan AS awalan, COUNT(*) AS jumlah
            FROM public.{$tabel} AS sumber
            INNER JOIN angka_sertipikat
                ON angka_sertipikat.angka
                 = BTRIM(CAST(sumber.sertipikat_id AS TEXT))
            WHERE angka_sertipikat.banyak_keluarga = 1
              AND BTRIM(CAST(sumber.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
            GROUP BY 1
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql);

        $jumlahPerKeluarga = [];

        foreach ($baris as $item) {
            $jumlahPerKeluarga[(string) $item->awalan] = (int) $item->jumlah;
        }

        if ($jumlahPerKeluarga === []) {
            $ingatan[$kunci] = true;

            return;
        }

        $ada = ($jumlahPerKeluarga[$awalan] ?? 0) > 0;
        $ingatan[$kunci] = $ada;

        if (!$ada) {
            $this->tolakKeluargaKosong($tabel, $awalan);
        }
    }

    private function tolakKeluargaKosong(string $tabel, string $awalan): void
    {
        throw new RuntimeException(
            'Data ' . strtoupper(str_replace('sr_', '', $tabel))
            . ' untuk unit ini belum termigrasi. Tabel ' . $tabel
            . ' tidak memuat satu pun baris berawalan ' . $awalan
            . ', sehingga laporannya tidak bisa disusun tanpa mengarang.'
            . ' Laporan sengaja dikosongkan daripada menampilkan baris'
            . ' milik unit lain. Silakan teruskan ke tim migrasi.'
        );
    }

}
