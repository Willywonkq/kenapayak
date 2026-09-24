<?php

namespace App\Models\SRIS\AktaJualBeli;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class rekap_ajb_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    public function obtainLokasi($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $lokasiKode = $this->kolomKode('sr_lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);

        $sql = <<<SQL
            SELECT DISTINCT ON (UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), ''))))
                UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), '')))
                    AS "KD_LOKASI",
                BTRIM(COALESCE(CAST(lokasi.deskripsi AS TEXT), '')) AS "DESKRIPSI"
            FROM public.sr_lokasi AS lokasi
            WHERE UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), ''))) <> ''
            ORDER BY
                UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), ''))),
                BTRIM(COALESCE(CAST(lokasi.deskripsi AS TEXT), ''))
        SQL;

        return collect(
            DB::connection(self::CONNECTION)->select($sql)
        );
    }

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
            $this->normalizeDate($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    private function obtainSudahAdaAkta(
        string $perusahaan,
        string $lokasi,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
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

        $stokTerpilih = $this->cteStokTerpilih(
            $stokPerusahaan,
            $stokLokasi,
            $stokSektor
        );
        $tabelBantu = $this->cteTabelBantu($lokasiKode);
        $ppjbTerpilih = $this->ctePpjbTerpilih(
            'EXISTS ('
            . ' SELECT 1 FROM akta_terpilih AS a'
            . ' WHERE BTRIM(CAST(a.ppjb_id AS TEXT))'
            . ' = BTRIM(CAST(ppjb.ppjb_id AS TEXT)))'
        );
        $pembeliTerpilih = $this->ctePembeliTerpilih();

        $sql = <<<SQL
            WITH akta_terpilih AS MATERIALIZED (
                SELECT
                    akta.*,
                    CASE
                        WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(akta.tgl_akta AS TIMESTAMP)
                    END AS tgl_akta_valid
                FROM public.sr_akta AS akta
                WHERE CASE
                          WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(akta.tgl_akta AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(akta.tgl_akta AS TIMESTAMP)
                      END < CAST(:tgl_akhir_gpt AS DATE)
            ),
            {$stokTerpilih},
            {$ppjbTerpilih},
            {$pembeliTerpilih},
            {$tabelBantu}

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                nasabah.nama AS "NAMA",

                stok.luas_tanah AS "LUAS_TANAH",
                stok.luas_bangunan AS "LUAS_BANGUNAN",

                akta.no_notaris AS "NO_NOTARIS",
                akta.tgl_notaris AS "TGL_NOTARIS",
                akta.notaris AS "NOTARIS",
                akta.no_akta AS "NO_AKTA",
                akta.tgl_akta_valid AS "TGL_AKTA",
                akta.ttd_akta AS "TTD_AKTA",
                akta.harga AS "HARGA",
                akta.harga_njop AS "HARGA_NJOP",

                sertipikat_unit.no_sertipikat AS "NO_SERTIPIKAT",
                sertipikat_unit.tgl_sertipikat AS "TGL_SERTIPIKAT",
                sertipikat_unit.luas_sup AS "LUAS_SUP",

                nasabah.telp_rmh AS "TELP_RMH",
                nasabah.fax_rmh AS "FAX_RMH",
                nasabah.telp_ktr AS "TELP_KTR",
                nasabah.fax_ktr AS "FAX_KTR",
                nasabah.no_hp AS "NO_HP",
                nasabah.alamat_rmh AS "ALAMAT_RMH",
                nasabah.kota_rmh AS "KOTA_RMH",
                nasabah.kode_pos_rmh AS "KODE_POS_RMH",

                ppjb.no_ppjb AS "NO_PPJB",
                ppjb.tgl_ppjb AS "TGL_PPJB",
                ppjb.harga_jual AS "HARGA_JUAL",

                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                ppjb.user_entry AS "USER_ENTRY",

                lokasi_unik.deskripsi AS "NAMA_LOKASI",
                tipe_bayar_unik.nama AS "TIPE_BAYAR",
                bank_perjanjian.nama AS "BANK",
                agen_unik.nama_agen AS "NM_AGEN",
                sales_unik.deskripsi AS "NM_SALES",

                CAST('T' AS VARCHAR(1)) AS "BELUM_TTD_AKTA"

            FROM akta_terpilih AS akta

            INNER JOIN ppjb_terpilih AS ppjb
                ON ppjb.kunci_ppjb = BTRIM(CAST(akta.ppjb_id AS TEXT))

            INNER JOIN pembeli_terpilih AS pembeli_ppjb
                ON pembeli_ppjb.kunci_ppjb = ppjb.kunci_ppjb

            LEFT JOIN public.sr_nasabah AS nasabah
                ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = ppjb.kunci_stok

            LEFT JOIN sertipikat_unit
                ON sertipikat_unit.kode = stok.kunci_stok
            LEFT JOIN lokasi_unik
                ON lokasi_unik.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))
            LEFT JOIN tipe_bayar_unik
                ON tipe_bayar_unik.kode
                 = BTRIM(COALESCE(CAST(ppjb.tipe_bayar AS TEXT), ''))
            LEFT JOIN bank_perjanjian
                ON bank_perjanjian.kode
                 = BTRIM(COALESCE(CAST(ppjb.perjanjian_bank_id AS TEXT), ''))
            LEFT JOIN agen_unik
                ON agen_unik.kode
                 = BTRIM(COALESCE(CAST(ppjb.kd_agen AS TEXT), ''))
            LEFT JOIN sales_unik
                ON sales_unik.kode
                 = BTRIM(COALESCE(CAST(ppjb.kd_sales AS TEXT), ''))

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
                stok.nomor ASC,
                akta.no_akta ASC
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

    private function obtainBelumTtdAkta(
        string $perusahaan,
        string $lokasi,
        string $sektor,
        string $blokAwal,
        string $blokAkhir
    ): array {
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

        $stokTerpilih = $this->cteStokTerpilih(
            $stokPerusahaan,
            $stokLokasi,
            $stokSektor
        );
        $tabelBantu = $this->cteTabelBantu($lokasiKode);
        $aktaRingkas = $this->cteAktaRingkas();
        $ppjbTerpilih = $this->ctePpjbTerpilih(
            'EXISTS ('
            . ' SELECT 1 FROM stok_terpilih AS s'
            . ' WHERE s.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT)))'
        );
        $pembeliTerpilih = $this->ctePembeliTerpilih();

        $sql = <<<SQL
            WITH {$stokTerpilih},
            {$ppjbTerpilih},
            {$pembeliTerpilih},
            {$aktaRingkas},
            {$tabelBantu}

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                nasabah.nama AS "NAMA",

                stok.luas_tanah AS "LUAS_TANAH",
                stok.luas_bangunan AS "LUAS_BANGUNAN",

                CAST(NULL AS VARCHAR(50)) AS "NO_NOTARIS",
                CAST(NULL AS TIMESTAMP) AS "TGL_NOTARIS",
                CAST(NULL AS VARCHAR(100)) AS "NOTARIS",
                CAST(NULL AS VARCHAR(50)) AS "NO_AKTA",
                CAST(NULL AS TIMESTAMP) AS "TGL_AKTA",
                CAST(NULL AS VARCHAR(100)) AS "TTD_AKTA",
                CAST(NULL AS NUMERIC(18, 2)) AS "HARGA",
                CAST(NULL AS NUMERIC(18, 2)) AS "HARGA_NJOP",

                sertipikat_unit.no_sertipikat AS "NO_SERTIPIKAT",
                sertipikat_unit.tgl_sertipikat AS "TGL_SERTIPIKAT",
                sertipikat_unit.luas_sup AS "LUAS_SUP",

                nasabah.telp_rmh AS "TELP_RMH",
                nasabah.fax_rmh AS "FAX_RMH",
                nasabah.telp_ktr AS "TELP_KTR",
                nasabah.fax_ktr AS "FAX_KTR",
                nasabah.no_hp AS "NO_HP",
                nasabah.alamat_rmh AS "ALAMAT_RMH",
                nasabah.kota_rmh AS "KOTA_RMH",
                nasabah.kode_pos_rmh AS "KODE_POS_RMH",

                ppjb.no_ppjb AS "NO_PPJB",
                ppjb.tgl_ppjb AS "TGL_PPJB",
                ppjb.harga_jual AS "HARGA_JUAL",

                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                ppjb.user_entry AS "USER_ENTRY",

                lokasi_unik.deskripsi AS "NAMA_LOKASI",
                CAST(NULL AS VARCHAR(100)) AS "TIPE_BAYAR",
                CAST(NULL AS VARCHAR(100)) AS "BANK",
                agen_unik.nama_agen AS "NM_AGEN",
                sales_unik.deskripsi AS "NM_SALES",

                CAST('Y' AS VARCHAR(1)) AS "BELUM_TTD_AKTA"

            FROM ppjb_terpilih AS ppjb

            INNER JOIN pembeli_terpilih AS pembeli_ppjb
                ON pembeli_ppjb.kunci_ppjb = ppjb.kunci_ppjb

            LEFT JOIN public.sr_nasabah AS nasabah
                ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = ppjb.kunci_stok

            LEFT JOIN akta_ringkas
                ON akta_ringkas.kunci_ppjb = ppjb.kunci_ppjb

            LEFT JOIN sertipikat_unit
                ON sertipikat_unit.kode = stok.kunci_stok
            LEFT JOIN lokasi_unik
                ON lokasi_unik.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))
            LEFT JOIN agen_unik
                ON agen_unik.kode
                 = BTRIM(COALESCE(CAST(ppjb.kd_agen AS TEXT), ''))
            LEFT JOIN sales_unik
                ON sales_unik.kode
                 = BTRIM(COALESCE(CAST(ppjb.kd_sales AS TEXT), ''))

            WHERE (
                    akta_ringkas.kunci_ppjb IS NULL
                    OR akta_ringkas.jumlah_tanpa_nomor > 0
                  )

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
                stok.nomor ASC,
                ppjb.no_ppjb ASC
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

    private function cteStokTerpilih(
        string $stokPerusahaan,
        string $stokLokasi,
        string $stokSektor
    ): string {
        return <<<SQL
        stok_terpilih AS MATERIALIZED (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), '')))
                        = 'A'
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
                  AND UPPER(BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                        = :perusahaan
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))
                            = :lokasi_filter
                        OR :lokasi_semua = '*'
                      )
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
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
            )
        SQL;
    }

    private function ctePpjbTerpilih(string $syaratKaitan): string
    {
        return <<<SQL
        ppjb_terpilih AS MATERIALIZED (
                SELECT
                    ppjb.*,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_ppjb AS ppjb
                WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), '')))
                        = 'A'
                  AND ppjb.parent_id IS NULL
                  AND {$syaratKaitan}
            )
        SQL;
    }

    private function ctePembeliTerpilih(): string
    {
        return <<<SQL
        pembeli_terpilih AS MATERIALIZED (
                SELECT
                    pembeli.*,
                    BTRIM(CAST(pembeli.ppjb_id AS TEXT)) AS kunci_ppjb
                FROM public.sr_pembeli_ppjb AS pembeli
                WHERE UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), '')))
                        = 'Y'
                  AND EXISTS (
                        SELECT 1
                        FROM ppjb_terpilih AS p
                        WHERE p.kunci_ppjb
                            = BTRIM(CAST(pembeli.ppjb_id AS TEXT))
                      )
            )
        SQL;
    }

    private function cteAktaRingkas(): string
    {
        return <<<SQL
        akta_ringkas AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(a.ppjb_id AS TEXT)) AS kunci_ppjb,
                    COUNT(*) AS jumlah_akta,
                    COUNT(*) FILTER (WHERE a.no_akta IS NULL)
                        AS jumlah_tanpa_nomor
                FROM public.sr_akta AS a
                GROUP BY BTRIM(CAST(a.ppjb_id AS TEXT))
            )
        SQL;
    }

    private function cteTabelBantu(string $lokasiKode): string
    {
        return <<<SQL
        sertipikat_unit AS MATERIALIZED (
                SELECT DISTINCT ON (kode)
                    kode, no_sertipikat, tgl_sertipikat, luas_sup
                FROM (
                    SELECT
                        BTRIM(CAST(x.stok_id AS TEXT)) AS kode,
                        x.no_sertipikat AS no_sertipikat,
                        x.tgl_sertipikat AS tgl_sertipikat,
                        x.luas_sup AS luas_sup,
                        x.ctid AS urutan_fisik
                    FROM public.sr_sertipikat AS x
                    WHERE EXISTS (
                        SELECT 1
                        FROM stok_terpilih AS s
                        WHERE s.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
                    )
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            lokasi_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), '')))
                            AS kode,
                        lokasi.deskripsi AS deskripsi,
                        lokasi.ctid AS urutan_fisik
                    FROM public.sr_lokasi AS lokasi
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            tipe_bayar_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, nama
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(a.tipe_bayar AS TEXT), '')) AS kode,
                        a.nama AS nama,
                        a.ctid AS urutan_fisik
                    FROM public.sr_tipe_bayar AS a
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            bank_perjanjian AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, nama
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(b.perjanjian_bank_id AS TEXT), ''))
                            AS kode,
                        a.nama AS nama,
                        b.ctid AS urutan_fisik
                    FROM public.sr_perjanjian_bank AS b
                    INNER JOIN public.sr_bank AS a
                        ON BTRIM(CAST(a.kd_bank AS TEXT))
                         = BTRIM(CAST(b.kd_bank AS TEXT))
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            agen_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, nama_agen
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(a.kd_agen AS TEXT), '')) AS kode,
                        a.nama_agen AS nama_agen,
                        a.ctid AS urutan_fisik
                    FROM public.sr_agen AS a
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            sales_unik AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        BTRIM(COALESCE(CAST(a.kd_sales AS TEXT), '')) AS kode,
                        a.deskripsi AS deskripsi,
                        a.ctid AS urutan_fisik
                    FROM public.sr_sales AS a
                ) AS daftar
                ORDER BY kode, urutan_fisik
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
