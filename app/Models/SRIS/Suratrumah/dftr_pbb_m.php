<?php

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_pbb_m extends Model
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

        $adaUnit = $this->adaKolom('sr_sektor', 'kd_perusahaan');

        $kolomPerusahaan = $adaUnit
            ? 'sektor.kd_perusahaan'
            : 'CAST(NULL AS TEXT)';

        $syaratPerusahaan = $adaUnit
            ? "AND (
                    sektor.kd_perusahaan = :kd_perusahaan_langsung
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.kd_perusahaan AS TEXT), '')))
                        = :kd_perusahaan
                 )"
            : '';

        $sql = <<<SQL
            SELECT
                sektor.{$sektorKode} AS "KD_SEKTOR",
                sektor.deskripsi     AS "DESKRIPSI",
                {$kolomPerusahaan}   AS "KD_PERUSAHAAN"
            FROM public.sr_sektor AS sektor
            WHERE (
                    sektor.flag_aktif = 'A'
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.flag_aktif AS TEXT), ''))) = 'A'
                  )
              {$syaratPerusahaan}
            ORDER BY
                sektor.deskripsi,
                sektor.{$sektorKode}
        SQL;

        $bindings = $adaUnit
            ? [
                'kd_perusahaan' => $kdPerusahaan,
                'kd_perusahaan_langsung' => $kdPerusahaan,
              ]
            : [];

        return collect(
            DB::connection(self::CONNECTION)->select($sql, $bindings)
        );
    }

    public function obtainDaftarRekapPbb($request): array
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

        $tahunAwal = (int) ($request->tahun_awal ?? 2000);
        $tahunAkhir = (int) ($request->tahun_akhir ?? date('Y'));

        $tglAwal = $this->normalizeDate($request->tgl_awal ?? date('Y-m-d'));
        $tglAkhirEksklusif = $this->normalizeDate(
            $request->tgl_akhir ?? date('Y-m-d'),
            1
        );

        $tampilNamaAlamatWp = $this->normalizeYesNo(
            $request->tampil_nama_alamat_wp ?? 'T'
        );

        $belumAdaPbb = $this->normalizeYesNo($request->belum_ada_pbb ?? 'T');

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

        if ($belumAdaPbb === 'Y') {
            return $this->obtainBelumAdaPbb(
                $perusahaan, $sektor, $blokAwal, $blokAkhir,
                $tahunAwal, $tahunAkhir
            );
        }

        return $this->obtainPbb(
            $tampilNamaAlamatWp === 'Y',
            $perusahaan, $sektor, $blokAwal, $blokAkhir,
            $tahunAwal, $tahunAkhir, $tglAwal, $tglAkhirEksklusif
        );
    }

    private function cteBersama(
        string $stokPerusahaan,
        string $stokSektor,
        string $lokasiKode,
        string $sektorKode,
        string $urutanSektorUnit,
        string $saringSektorUnit
    ): string {
        $urutPpjb = $this->urutanPpjbId('ppjb.ppjb_id');
        $urutA = $this->urutanPpjbId('a.ppjb_id');

        return <<<SQL
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
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
            ),
            lokasi_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
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
            ppjb_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, no_ppjb, tgl_ppjb
                FROM (
                    SELECT
                        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                        ppjb.no_ppjb  AS no_ppjb,
                        ppjb.tgl_ppjb AS tgl_ppjb,
                        {$urutPpjb} AS urut_id
                    FROM public.sr_ppjb AS ppjb
                    WHERE (
                            ppjb.flag_aktif = 'A'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                          )
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST,
                         urut_id DESC NULLS LAST
            ),
            ppjb_induk AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, kunci_ppjb
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
                SELECT
                    kunci_ppjb,
                    STRING_AGG(nama, ', ' ORDER BY urutan_fisik) AS nama
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
            pembeli_utama AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, no_ppjb, tgl_ppjb, nama, alamat_ktp,
                    kota_ktp, kode_pos_ktp, no_identitas, npwp, no_hp
                FROM (
                    SELECT
                        BTRIM(CAST(a.stok_id AS TEXT)) AS kunci_stok,
                        a.no_ppjb  AS no_ppjb,
                        a.tgl_ppjb AS tgl_ppjb,
                        c.nama, c.alamat_ktp, c.kota_ktp, c.kode_pos_ktp,
                        c.no_identitas, c.npwp, c.no_hp,
                        {$urutA} AS urut_id,
                        b.ctid AS urutan_fisik
                    FROM public.sr_ppjb AS a
                    INNER JOIN public.sr_pembeli_ppjb AS b
                        ON BTRIM(CAST(a.ppjb_id AS TEXT))
                         = BTRIM(CAST(b.ppjb_id AS TEXT))
                    INNER JOIN public.sr_nasabah AS c
                        ON BTRIM(CAST(b.nasabah_id AS TEXT))
                         = BTRIM(CAST(c.nasabah_id AS TEXT))
                    WHERE (
                            b.flag_aktif = 'Y'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(b.flag_aktif AS TEXT), ''))) = 'Y'
                          )
                      AND (
                            a.flag_aktif = 'A'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(a.flag_aktif AS TEXT), ''))) = 'A'
                          )
                      AND a.parent_id IS NULL
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST,
                         urut_id DESC NULLS LAST, urutan_fisik
            ),
        SQL;
    }

    private function alamatPembeli(string $alias): string
    {
        return <<<SQL
        BTRIM(COALESCE(CAST({$alias}.alamat_ktp AS TEXT), ''))
                    || CASE
                        WHEN NULLIF(BTRIM(COALESCE(
                            CAST({$alias}.kota_ktp AS TEXT), '')), '') IS NULL
                        THEN ''
                        ELSE ' ' || BTRIM(CAST({$alias}.kota_ktp AS TEXT))
                       END
                    || CASE
                        WHEN NULLIF(BTRIM(COALESCE(
                            CAST({$alias}.kode_pos_ktp AS TEXT), '')), '') IS NULL
                        THEN ''
                        ELSE ' ' || BTRIM(CAST({$alias}.kode_pos_ktp AS TEXT))
                       END
        SQL;
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

    private function obtainPbb(
        bool $denganNamaAlamatWp,
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        int $tahunAwal,
        int $tahunAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        [$stokPerusahaan, $stokSektor, $stokLokasi, $lokasiKode, $sektorKode]
            = $this->namaKolom();

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        if (!$pakaiUnik) {
            $this->pastikanKeluargaAda('sr_pbb', $awalan);
        }

        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                    ON sertipikat_unik.angka
                     = BTRIM(CAST(pbb.sertipikat_id AS TEXT))"
            : '';
        $kunciPbb = $this->kunciSertipikat('pbb', $pakaiUnik);

        $cteBersama = $this->cteBersama(
            $stokPerusahaan, $stokSektor, $lokasiKode, $sektorKode,
            $urutanSektorUnit, $saringSektorUnit
        );

        if ($denganNamaAlamatWp) {
            $alamat = $this->alamatPembeli('pembeli_utama');
            $kolomPembeli = <<<SQL
            pembeli_utama.no_ppjb       AS "NO_PPJB",
                    pembeli_utama.tgl_ppjb      AS "TGL_PPJB",
                    pembeli_utama.nama          AS "NAMA_PPJB",
                    pembeli_utama.no_identitas  AS "NO_KTP",
                    pembeli_utama.npwp          AS "NPWP",
                    {$alamat} AS "ALAMAT_PEMBELI",
                    pembeli_utama.no_hp         AS "NO_HP",
                    CAST('NAMA_ALAMAT' AS VARCHAR(20)) AS "MODE_LAPORAN",
            SQL;
            $joinPembeli = 'LEFT JOIN pembeli_utama
                    ON pembeli_utama.kunci_stok = stok.kunci_stok';
        } else {
            $kolomPembeli = <<<SQL
            ppjb_ref.no_ppjb       AS "NO_PPJB",
                    ppjb_ref.tgl_ppjb      AS "TGL_PPJB",
                    pembeli_gabung.nama    AS "NAMA_PPJB",
                    CAST('NORMAL' AS VARCHAR(20)) AS "MODE_LAPORAN",
            SQL;
            $joinPembeli = 'LEFT JOIN ppjb_ref
                    ON ppjb_ref.kunci_stok = stok.kunci_stok
                LEFT JOIN ppjb_induk
                    ON ppjb_induk.kunci_stok = stok.kunci_stok
                LEFT JOIN pembeli_gabung
                    ON pembeli_gabung.kunci_ppjb = ppjb_induk.kunci_ppjb';
        }

        $sql = <<<SQL
            WITH {$cteSertipikatUnik}{$cteBersama}
            pbb_terpilih AS (
                SELECT
                    pbb.*,
                    {$kunciPbb} AS kunci_sertipikat
                FROM public.sr_pbb AS pbb
                {$joinSertipikatUnik}
                WHERE pbb.tahun_pbb >= :tahun_awal
                  AND pbb.tahun_pbb <= :tahun_akhir
                  AND pbb.tgl_input >= CAST(:tgl_awal AS TIMESTAMP)
                  AND pbb.tgl_input < CAST(:tgl_akhir_eksklusif AS TIMESTAMP)
            ),
            hasil_dasar AS MATERIALIZED (
                SELECT
                    pbb.tahun_pbb        AS "TAHUN_PBB",
                    pbb.nop_induk        AS "NOP_INDUK",
                    pbb.nop_pisah        AS "NOP_PISAH",
                    pbb.nama_wp          AS "NAMA_WP",
                    pbb.letak_op         AS "LETAK_OP",
                    pbb.keterangan       AS "KETERANGAN",
                    pbb.luas_bumi        AS "LUAS_BUMI",
                    pbb.luas_bangunan    AS "LUAS_BANGUNAN",
                    pbb.pbb_bayar        AS "PBB_BAYAR",
                    pbb.njop_bumi        AS "NJOP_BUMI",
                    pbb.njop_bangunan    AS "NJOP_BANGUNAN",
                    pbb.jumlah_bumi      AS "JUMLAH_BUMI",
                    pbb.jumlah_bangunan  AS "JUMLAH_BANGUNAN",
                    pbb.njop_dasar       AS "NJOP_DASAR",
                    pbb.njop_tkp         AS "NJOP_TKP",
                    pbb.njop_hitung      AS "NJOP_HITUNG",
                    pbb.njkp             AS "NJKP",
                    pbb.pbb_njkp         AS "PBB_NJKP",
                    pbb.stimulus         AS "STIMULUS",

                    {$kolomPembeli}

                    stok.blok  AS "BLOK",
                    stok.nomor AS "NOMOR",
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                                             AS "BLOK_NOMOR",
                    stok.{$stokSektor}     AS "KD_SEKTOR",
                    stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",

                    lokasi_ref.deskripsi AS "NAMA_LOKASI",
                    sektor_ref.deskripsi AS "NAMA_SEKTOR",

                    CURRENT_TIMESTAMP AS "TGL_CETAK"

                FROM pbb_terpilih AS pbb

                INNER JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = pbb.kunci_sertipikat

                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

                {$joinPembeli}

                LEFT JOIN lokasi_ref
                    ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokLokasi} AS TEXT), '')))
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))
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
                COALESCE(hasil_dasar."NAMA_SEKTOR", '') ASC,
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."TAHUN_PBB" ASC,
                hasil_dasar."NOP_PISAH" ASC
        SQL;

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'tahun_awal' => $tahunAwal,
            'tahun_akhir' => $tahunAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
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

    private function obtainBelumAdaPbb(
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        int $tahunAwal,
        int $tahunAkhir
    ): array {
        [$stokPerusahaan, $stokSektor, $stokLokasi, $lokasiKode, $sektorKode]
            = $this->namaKolom();

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        if (!$pakaiUnik) {
            $this->pastikanKeluargaAda('sr_pbb', $awalan);
        }

        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                        ON sertipikat_unik.angka
                         = BTRIM(CAST(pbb_cek.sertipikat_id AS TEXT))"
            : '';
        $kunciPbbCek = $this->kunciSertipikat('pbb_cek', $pakaiUnik);

        $cteBersama = $this->cteBersama(
            $stokPerusahaan, $stokSektor, $lokasiKode, $sektorKode,
            $urutanSektorUnit, $saringSektorUnit
        );

        $alamat = $this->alamatPembeli('pembeli_utama');

        $sql = <<<SQL
            WITH {$cteSertipikatUnik}{$cteBersama}
            pbb_ada AS MATERIALIZED (
                SELECT DISTINCT {$kunciPbbCek} AS kunci_sertipikat
                FROM public.sr_pbb AS pbb_cek
                {$joinSertipikatUnik}
                WHERE pbb_cek.tahun_pbb >= :tahun_awal
                  AND pbb_cek.tahun_pbb <= :tahun_akhir
            ),
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                                              AS "BLOK_NOMOR",

                    stok.luas_tanah       AS "LUAS_TANAH",
                    stok.luas_bangunan    AS "LUAS_BANGUNAN",

                    pembeli_utama.no_ppjb      AS "NO_PPJB",
                    pembeli_utama.tgl_ppjb     AS "TGL_PPJB",
                    pembeli_utama.nama         AS "NAMA_PPJB",
                    pembeli_utama.no_identitas AS "NO_KTP",
                    pembeli_utama.npwp         AS "NPWP",
                    {$alamat} AS "ALAMAT_PEMBELI",
                    pembeli_utama.no_hp        AS "NO_HP",

                    stok.blok  AS "BLOK",
                    stok.nomor AS "NOMOR",
                    stok.{$stokSektor}     AS "KD_SEKTOR",
                    stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",

                    lokasi_ref.deskripsi AS "NAMA_LOKASI",
                    sektor_ref.deskripsi AS "NAMA_SEKTOR",

                    CURRENT_TIMESTAMP AS "TGL_CETAK",
                    CAST('BELUM_PBB' AS VARCHAR(20)) AS "MODE_LAPORAN"

                FROM stok_terpilih AS stok

                INNER JOIN pembeli_utama
                    ON pembeli_utama.kunci_stok = stok.kunci_stok

                LEFT JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.stok_id AS TEXT)) = stok.kunci_stok

                LEFT JOIN lokasi_ref
                    ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokLokasi} AS TEXT), '')))
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))

                WHERE NOT EXISTS (
                        SELECT 1
                        FROM pbb_ada
                        WHERE pbb_ada.kunci_sertipikat
                            = BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                      )
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
                COALESCE(hasil_dasar."NAMA_SEKTOR", '') ASC,
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC
        SQL;

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'tahun_awal' => $tahunAwal,
            'tahun_akhir' => $tahunAkhir,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
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
            $this->kolomKode('sr_stok', [
                'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster',
            ]),
            $this->kolomKode('sr_lokasi', [
                'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
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

    private function normalizeYesNo($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['Y', '1', 'TRUE', 'ON'], true) ? 'Y' : 'T';
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
