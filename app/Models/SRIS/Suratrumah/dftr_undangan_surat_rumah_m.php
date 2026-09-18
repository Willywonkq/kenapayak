<?php

// MODEL POSTGRESQL V1 - DAFTAR UNDANGAN SURAT RUMAH

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260918
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.
//
// Penyesuaian khusus PostgreSQL:
// - OUTER APPLY dan subquery TOP (1) diganti tabel bantu ber-DISTINCT ON;
// - F_GET_PEMBELI diganti STRING_AGG, karena fungsi itu milik SQL Server;
// - PPJB_ID dan SERTIPIKAT_ID disusun ulang awalannya bila perlu, lihat
//   kunciSertipikat() dan kunciPpjb().

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_undangan_surat_rumah_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Tabel dan kolom penanda untuk tiap jenis report.
     * Dikumpulkan di satu tempat supaya keenam jenisnya tidak perlu
     * ditulis berulang di banyak method.
     */
    private const SUMBER_JENIS = [
        '1' => ['tabel' => 'sr_undangan_ppjb', 'pakai_jenis_surat' => true],
        '2' => ['tabel' => 'sr_undangan_ppjb', 'pakai_jenis_surat' => true],
        '3' => ['tabel' => 'sr_undangan_st',   'pakai_jenis_surat' => false],
        '4' => ['tabel' => 'sr_undangan_ajb',  'pakai_jenis_surat' => false],
        '5' => ['tabel' => 'sr_undangan_skb',  'pakai_jenis_surat' => false],
        '6' => ['tabel' => 'sr_perpanjangan_sertipikat', 'pakai_jenis_surat' => false],
    ];

    /**
     * Master sektor atau cluster.
     */
    public function obtainCluster($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $adaUnit = $this->adaKolom('sr_sektor', 'kd_perusahaan');
        $kolomUnit = $adaUnit ? 'sektor.kd_perusahaan' : 'CAST(NULL AS TEXT)';
        $syaratUnit = $adaUnit
            ? "AND (
                    sektor.kd_perusahaan = :kd_perusahaan_langsung
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.kd_perusahaan AS TEXT), '')))
                        = :kd_perusahaan
                 )"
            : '';

        $sql = <<<SQL
            SELECT
                sektor.{$sektorKode} AS "KD_CLUSTER",
                sektor.{$sektorKode} AS "KD_SEKTOR",
                sektor.deskripsi     AS "DESKRIPSI",
                {$kolomUnit}         AS "KD_PERUSAHAAN"
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
            ? [
                'kd_perusahaan' => $kdPerusahaan,
                'kd_perusahaan_langsung' => $kdPerusahaan,
              ]
            : [];

        return collect(
            DB::connection(self::CONNECTION)->select($sql, $bindings)
        );
    }

    /**
     * Lookup blok dan nomor beserta pembelinya, untuk modal pencarian.
     */
    public function obtainBlok($kdPerusahaan): array
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return [];
        }

        [$stokPerusahaan, $stokSektor, $stokLokasi, $lokasiKode, $sektorKode]
            = $this->namaKolom();

        $sql = <<<SQL
            WITH lokasi_ref AS MATERIALIZED (
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
                        sektor.ctid AS urutan_fisik
                    FROM public.sr_sektor AS sektor
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            tipe_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci) kunci, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
                            || '|' ||
                        UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
                            AS kunci,
                        BTRIM(COALESCE(
                            CAST(tipe.deskripsi AS TEXT), '')) AS deskripsi,
                        tipe.ctid AS urutan_fisik
                    FROM public.sr_tipe AS tipe
                ) AS daftar
                ORDER BY kunci, urutan_fisik
            )
            SELECT
                BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                    || COALESCE(CAST(stok.nomor AS TEXT), '') AS "BLOK_NOMOR",
                ppjb.ppjb_id            AS "PPJB_ID",
                nasabah.nama            AS "NAMA_PEMBELI",
                ppjb.no_ppjb            AS "NO_PPJB",
                ppjb.tgl_ppjb           AS "TGL_PPJB",
                tipe_ref.deskripsi      AS "TIPE",
                lokasi_ref.deskripsi    AS "LOKASI",
                stok.stok_id            AS "STOK_ID",
                stok.no_virtual_acc     AS "NO_VIRTUAL_ACC",
                stok.blok               AS "BLOK",
                stok.nomor              AS "NOMOR",
                sektor_ref.deskripsi    AS "NM_CLUSTER"
            FROM public.sr_ppjb AS ppjb
            INNER JOIN public.sr_stok AS stok
                ON BTRIM(CAST(stok.stok_id AS TEXT))
                 = BTRIM(CAST(ppjb.stok_id AS TEXT))
            INNER JOIN public.sr_pembeli_ppjb AS pembeli
                ON BTRIM(CAST(pembeli.ppjb_id AS TEXT))
                 = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
            INNER JOIN public.sr_nasabah AS nasabah
                ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
            LEFT JOIN lokasi_ref
                ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                       CAST(stok.{$stokLokasi} AS TEXT), '')))
            LEFT JOIN sektor_ref
                ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                       CAST(stok.{$stokSektor} AS TEXT), '')))
            LEFT JOIN tipe_ref
                ON tipe_ref.kunci =
                   UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS TEXT), '')))
                   || '|' ||
                   UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS TEXT), '')))
            WHERE (
                    pembeli.flag_aktif = 'Y'
                    OR UPPER(BTRIM(COALESCE(
                           CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
                  )
              AND (
                    ppjb.flag_aktif = 'A'
                    OR UPPER(BTRIM(COALESCE(
                           CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                  )
              AND ppjb.parent_id IS NULL
              AND stok.parent_id IS NULL
              AND (
                    stok.{$stokPerusahaan} = :perusahaan_langsung
                    OR UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                        = :perusahaan
                  )
              AND stok.blok IS NOT NULL
              AND stok.nomor IS NOT NULL
            ORDER BY
                stok.blok ASC,
                {$this->urutanNomor('stok.nomor')},
                stok.nomor ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'perusahaan' => $kdPerusahaan,
            'perusahaan_langsung' => $kdPerusahaan,
        ]);
    }

    /**
     * Entry utama data report.
     */
    public function obtainDaftarSuratUndangan($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );

        $sektor = $this->normalizeText($request->cluster ?? '*');
        $blokAwal = $this->normalizeBlok($request->blok_awal ?? '*');
        $blokAkhir = $this->normalizeBlok($request->blok_akhir ?? '*');
        $jenis = $this->normalizeJenis($request->jenis_report ?? '1');
        $belumDiundang = $this->normalizeYesNo($request->belum_diundang ?? 'T');

        $tglAwal = $this->normalizeDate($request->tgl_awal ?? date('Y-m-d'));
        $tglAkhir = $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'), 1);

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($blokAwal === '') {
            $blokAwal = '*';
        }

        if ($blokAkhir === '') {
            $blokAkhir = '*';
        }

        if ($belumDiundang === 'Y') {
            return $this->obtainBelumDiundang(
                $perusahaan, $sektor, $blokAwal, $blokAkhir,
                $tglAwal, $tglAkhir, $jenis
            );
        }

        return $this->obtainUndangan(
            $perusahaan, $sektor, $blokAwal, $blokAkhir,
            $tglAwal, $tglAkhir, $jenis
        );
    }

    /**
     * Report undangan yang sudah dibuat.
     *
     * Keenam jenisnya memakai sumber tabel berbeda tetapi kerangka yang
     * sama, jadi kerangkanya disusun sekali dan bagian yang berbeda saja
     * yang ditukar. Ini menjaga penyaring blok, sektor, unit, dan tanggal
     * tetap satu tulisan sehingga tidak bisa berbeda tanpa sengaja.
     */
    private function obtainUndangan(
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhir,
        string $jenis
    ): array {
        [$stokPerusahaan, $stokSektor, $stokLokasi, $lokasiKode, $sektorKode]
            = $this->namaKolom();

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        $sumber = self::SUMBER_JENIS[$jenis];
        $tabel = $sumber['tabel'];

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'blok_awal_semua' => $blokAwal,
            'blok_akhir_semua' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir' => $tglAkhir,
        ];

        if ($sumber['pakai_jenis_surat']) {
            $bindings['jenis_surat'] = $jenis;
        }

        /*
         * Awalan sertipikat HANYA dipakai jenis 6, karena hanya
         * perpanjangan sertipikat yang bertumpu pada SERTIPIKAT_ID.
         * Mengikatnya pada jenis lain membuat PostgreSQL menolak
         * querynya, sebab parameternya tidak ada di dalam teks query.
         */
        if ($jenis === '6' && !$pakaiUnik) {
            $bindings['awalan_sertipikat'] = $awalan;
        }

        if ($adaSektorUnit) {
            $bindings['perusahaan_sektor'] = $perusahaan;
            $bindings['perusahaan_sektor_saring'] = $perusahaan;
        }

        $syaratJenisSurat = $sumber['pakai_jenis_surat']
            ? "AND BTRIM(COALESCE(CAST(surat.jenis_surat AS TEXT), ''))
                    = :jenis_surat"
            : '';

        /*
         * Jenis 6 bertumpu pada sertipikat, bukan pada PPJB. Karena
         * sr_perpanjangan_sertipikat menyimpan SERTIPIKAT_ID yang mungkin
         * kehilangan awalannya, kuncinya disusun ulang lebih dulu.
         */
        if ($jenis === '6') {
            $cteUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
            $joinUnik = $pakaiUnik
                ? "LEFT JOIN sertipikat_unik
                            ON sertipikat_unik.angka
                             = BTRIM(CAST(surat.sertipikat_id AS TEXT))"
                : '';
            $kunci = $this->kunciSertipikat('surat', $pakaiUnik);

            $suratTerpilih = <<<SQL
            surat_terpilih AS (
                SELECT
                    surat.*,
                    {$kunci} AS kunci_sertipikat
                FROM public.{$tabel} AS surat
                {$joinUnik}
                WHERE surat.tgl_surat >= CAST(:tgl_awal AS TIMESTAMP)
                  AND surat.tgl_surat <  CAST(:tgl_akhir AS TIMESTAMP)
            ),
            SQL;

            $sambungSurat = <<<SQL
            INNER JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = surat.kunci_sertipikat
                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
                LEFT JOIN ppjb_induk
                    ON ppjb_induk.kunci_stok = stok.kunci_stok
                LEFT JOIN pembeli_nasabah AS nasabah
                    ON nasabah.kunci_ppjb = ppjb_induk.kunci_ppjb
            SQL;
        } else {
            $cteUnik = '';
            $joinUnik = '';

            $suratTerpilih = <<<SQL
            surat_terpilih AS (
                SELECT
                    surat.*,
                    BTRIM(CAST(surat.ppjb_id AS TEXT)) AS kunci_ppjb
                FROM public.{$tabel} AS surat
                WHERE surat.tgl_surat >= CAST(:tgl_awal AS TIMESTAMP)
                  AND surat.tgl_surat <  CAST(:tgl_akhir AS TIMESTAMP)
                  {$syaratJenisSurat}
            ),
            SQL;

            $sambungSurat = <<<SQL
            INNER JOIN ppjb_aktif AS ppjb
                    ON ppjb.kunci_ppjb = surat.kunci_ppjb
                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = ppjb.kunci_stok
                INNER JOIN pembeli_nasabah AS nasabah
                    ON nasabah.kunci_ppjb = ppjb.kunci_ppjb
            SQL;
        }

        $kolomJenis = $this->kolomKeluaranJenis($jenis);
        $alamat = $this->kolomAlamatSurat();

        $sql = <<<SQL
            WITH {$cteUnik}stok_terpilih AS (
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
                               CAST(stok.flag_aktif AS TEXT), 'T'))) = 'A'
                      )
                  AND (
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    ppjb.no_ppjb, ppjb.tgl_ppjb, ppjb.harga_jual,
                    ppjb.tgl_tanda_tangan, ppjb.tgl_ttd_notaris
                FROM public.sr_ppjb AS ppjb
                WHERE (
                        ppjb.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND ppjb.parent_id IS NULL
            ),
            ppjb_induk AS MATERIALIZED (
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, kunci_ppjb, no_ppjb, tgl_ppjb, harga_jual,
                    tgl_tanda_tangan, tgl_ttd_notaris
                FROM (
                    SELECT
                        ppjb_aktif.*,
                        {$this->urutanPpjbId('ppjb_aktif.kunci_ppjb')} AS urut_id
                    FROM ppjb_aktif
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST,
                         urut_id DESC NULLS LAST
            ),
            pembeli_nasabah AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(pembeli.ppjb_id AS TEXT)) AS kunci_ppjb,
                    nasabah.*
                FROM public.sr_pembeli_ppjb AS pembeli
                INNER JOIN public.sr_nasabah AS nasabah
                    ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                     = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
                WHERE (
                        pembeli.flag_aktif = 'Y'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
                      )
            ),
            {$this->cteRujukan($lokasiKode, $sektorKode, $urutanSektorUnit, $saringSektorUnit)}
            {$suratTerpilih}
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || COALESCE(CAST(stok.nomor AS TEXT), '')
                                                AS "BLOK_NOMOR",
                    COALESCE(nasabah.nama, '')  AS "NASABAH_NAMA",
                    {$alamat}
                    nasabah.telp_rmh            AS "TELP_RMH",
                    nasabah.no_hp               AS "NO_HP",

                    {$kolomJenis}

                    BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), ''))
                                                AS "KD_PERUSAHAAN",
                    stok.{$stokLokasi}          AS "KD_LOKASI",
                    stok.{$stokSektor}          AS "KD_SEKTOR",
                    lokasi_ref.deskripsi        AS "NAMA_LOKASI",
                    sektor_ref.deskripsi        AS "NAMA_SEKTOR",
                    CURRENT_TIMESTAMP           AS "TGL_CETAK",

                    stok.blok  AS "BLOK",
                    stok.nomor AS "NOMOR"

                FROM surat_terpilih AS surat

                {$sambungSurat}

                LEFT JOIN lokasi_ref
                    ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokLokasi} AS TEXT), '')))
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))
                LEFT JOIN jenis_ref
                    ON jenis_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.kd_jenis AS TEXT), '')))
                LEFT JOIN tipe_ref
                    ON tipe_ref.kunci =
                       UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS TEXT), '')))
                       || '|' ||
                       UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS TEXT), '')))

                WHERE stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            {$this->syaratBlok()}
            ORDER BY
                hasil_dasar."NAMA_SEKTOR" ASC NULLS FIRST,
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."URUT" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    /**
     * Report "Belum Diundang".
     *
     * Berangkat dari PPJB, bukan dari surat, lalu membuang yang sudah
     * punya surat pada rentang tanggal yang diminta.
     */
    private function obtainBelumDiundang(
        string $perusahaan,
        string $sektor,
        string $blokAwal,
        string $blokAkhir,
        string $tglAwal,
        string $tglAkhir,
        string $jenis
    ): array {
        [$stokPerusahaan, $stokSektor, $stokLokasi, $lokasiKode, $sektorKode]
            = $this->namaKolom();

        [$adaSektorUnit, $urutanSektorUnit, $saringSektorUnit]
            = $this->sektorUnit();

        $sumber = self::SUMBER_JENIS[$jenis];
        $tabel = $sumber['tabel'];

        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
            'blok_awal_semua' => $blokAwal,
            'blok_akhir_semua' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir' => $tglAkhir,
        ];

        if ($sumber['pakai_jenis_surat']) {
            $bindings['jenis_surat'] = $jenis;
        }

        if ($adaSektorUnit) {
            $bindings['perusahaan_sektor'] = $perusahaan;
            $bindings['perusahaan_sektor_saring'] = $perusahaan;
        }

        /*
         * Daftar yang SUDAH diundang dikumpulkan lebih dulu menjadi tabel
         * bantu, supaya NOT EXISTS berkorelasi pada desktop berubah
         * menjadi satu kali baca.
         *
         * Jenis 6 berkunci stok, karena perpanjangan sertipikat menempel
         * pada sertipikat, bukan pada PPJB.
         */
        if ($jenis === '6') {
            $cteUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
            $joinUnik = $pakaiUnik
                ? "LEFT JOIN sertipikat_unik
                            ON sertipikat_unik.angka
                             = BTRIM(CAST(surat.sertipikat_id AS TEXT))"
                : '';
            $kunci = $this->kunciSertipikat('surat', $pakaiUnik);

            if (!$pakaiUnik) {
                $bindings['awalan_sertipikat'] = $awalan;
            }

            $sudahDiundang = <<<SQL
            sudah_diundang AS MATERIALIZED (
                SELECT DISTINCT
                    BTRIM(CAST(sertipikat.stok_id AS TEXT)) AS kunci
                FROM public.{$tabel} AS surat
                {$joinUnik}
                INNER JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = {$kunci}
                WHERE surat.tgl_surat >= CAST(:tgl_awal AS TIMESTAMP)
                  AND surat.tgl_surat <  CAST(:tgl_akhir AS TIMESTAMP)
            ),
            SQL;
            $syaratBelum = 'ppjb.kunci_stok';
        } else {
            $cteUnik = '';
            $syaratJenisSurat = $sumber['pakai_jenis_surat']
                ? "AND BTRIM(COALESCE(CAST(surat.jenis_surat AS TEXT), ''))
                        = :jenis_surat"
                : '';

            $sudahDiundang = <<<SQL
            sudah_diundang AS MATERIALIZED (
                SELECT DISTINCT BTRIM(CAST(surat.ppjb_id AS TEXT)) AS kunci
                FROM public.{$tabel} AS surat
                WHERE surat.tgl_surat >= CAST(:tgl_awal AS TIMESTAMP)
                  AND surat.tgl_surat <  CAST(:tgl_akhir AS TIMESTAMP)
                  {$syaratJenisSurat}
            ),
            SQL;
            $syaratBelum = 'ppjb.kunci_ppjb';
        }

        $sql = <<<SQL
            WITH {$cteUnik}stok_terpilih AS (
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
                               CAST(stok.flag_aktif AS TEXT), 'T'))) = 'A'
                      )
                  AND (
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.parent_id IS NULL
            ),
            ppjb_aktif AS MATERIALIZED (
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    ppjb.no_ppjb, ppjb.tgl_ppjb,
                    ppjb.tgl_tanda_tangan, ppjb.tgl_ttd_notaris
                FROM public.sr_ppjb AS ppjb
                WHERE (
                        ppjb.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(ppjb.flag_aktif AS TEXT), 'T'))) = 'A'
                      )
                  AND ppjb.parent_id IS NULL
            ),
            pembeli_gabung AS MATERIALIZED (
                /* Pengganti F_GET_PEMBELI milik SQL Server. */
                SELECT kunci_ppjb, STRING_AGG(nama, ', ' ORDER BY urutan_fisik) AS nama
                FROM (
                    SELECT
                        BTRIM(CAST(pembeli.ppjb_id AS TEXT)) AS kunci_ppjb,
                        BTRIM(COALESCE(CAST(nasabah.nama AS TEXT), '')) AS nama,
                        pembeli.ctid AS urutan_fisik
                    FROM public.sr_pembeli_ppjb AS pembeli
                    INNER JOIN public.sr_nasabah AS nasabah
                        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                         = BTRIM(CAST(pembeli.nasabah_id AS TEXT))
                    WHERE (
                            pembeli.flag_aktif = 'Y'
                            OR UPPER(BTRIM(COALESCE(
                                   CAST(pembeli.flag_aktif AS TEXT), ''))) = 'Y'
                          )
                ) AS daftar
                GROUP BY kunci_ppjb
            ),
            {$this->cteRujukan($lokasiKode, $sektorKode, $urutanSektorUnit, $saringSektorUnit)}
            {$sudahDiundang}
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || COALESCE(CAST(stok.nomor AS TEXT), '')
                                                AS "BLOK_NOMOR",
                    pembeli_gabung.nama         AS "NAMA_PEMBELI",
                    BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), ''))
                                                AS "KD_PERUSAHAAN",
                    stok.{$stokSektor}          AS "KD_SEKTOR",
                    sektor_ref.deskripsi        AS "NAMA_SEKTOR",
                    stok.kd_jenis               AS "KD_JENIS",
                    jenis_ref.deskripsi         AS "NAMA_JENIS_BANGUNAN",
                    stok.kd_tipe                AS "KD_TIPE",
                    tipe_ref.deskripsi          AS "NAMA_TIPE",
                    stok.luas_tanah             AS "LUAS_TANAH",
                    stok.luas_bangunan          AS "LUAS_BANGUNAN",
                    stok.luas_semi_gross        AS "LUAS_SEMI_GROSS",
                    tipe_ref.listrik            AS "LISTRIK",
                    stok.kd_mata_uang           AS "KD_MATA_UANG",
                    ppjb.no_ppjb                AS "NO_PPJB",
                    ppjb.tgl_ppjb               AS "TGL_PPJB",
                    ppjb.tgl_tanda_tangan       AS "TGL_TANDA_TANGAN",
                    ppjb.tgl_ttd_notaris        AS "TGL_TTD_NOTARIS",
                    jenis_ref.flag_laporan      AS "FLAG_LAPORAN",
                    CURRENT_TIMESTAMP           AS "TGL_CETAK",

                    stok.blok  AS "BLOK",
                    stok.nomor AS "NOMOR"

                FROM ppjb_aktif AS ppjb

                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = ppjb.kunci_stok

                LEFT JOIN pembeli_gabung
                    ON pembeli_gabung.kunci_ppjb = ppjb.kunci_ppjb
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))
                LEFT JOIN lokasi_ref
                    ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokLokasi} AS TEXT), '')))
                INNER JOIN jenis_ref
                    ON jenis_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.kd_jenis AS TEXT), '')))
                INNER JOIN tipe_ref
                    ON tipe_ref.kunci =
                       UPPER(BTRIM(COALESCE(CAST(stok.kd_jenis AS TEXT), '')))
                       || '|' ||
                       UPPER(BTRIM(COALESCE(CAST(stok.kd_tipe AS TEXT), '')))

                WHERE NOT EXISTS (
                        SELECT 1 FROM sudah_diundang
                        WHERE sudah_diundang.kunci = {$syaratBelum}
                      )
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
            {$this->syaratBlok()}
            ORDER BY
                hasil_dasar."KD_SEKTOR" ASC,
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC,
                hasil_dasar."NO_PPJB" ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    /**
     * Tabel bantu rujukan yang dipakai kedua jalur laporan.
     */
    private function cteRujukan(
        string $lokasiKode,
        string $sektorKode,
        string $urutanSektorUnit,
        string $saringSektorUnit
    ): string {
        return <<<SQL
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
            jenis_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kode) kode, deskripsi, flag_laporan
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(jenis.kd_jenis AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(jenis.deskripsi AS TEXT), '')) AS deskripsi,
                        jenis.flag_laporan AS flag_laporan,
                        jenis.ctid AS urutan_fisik
                    FROM public.sr_jenis_bangunan AS jenis
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            tipe_ref AS MATERIALIZED (
                SELECT DISTINCT ON (kunci) kunci, deskripsi, listrik
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))
                            || '|' ||
                        UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))
                            AS kunci,
                        BTRIM(COALESCE(
                            CAST(tipe.deskripsi AS TEXT), '')) AS deskripsi,
                        tipe.listrik AS listrik,
                        tipe.ctid AS urutan_fisik
                    FROM public.sr_tipe AS tipe
                ) AS daftar
                ORDER BY kunci, urutan_fisik
            ),
        SQL;
    }

    /**
     * Saringan blok, dipakai kedua jalur laporan.
     *
     * Cabang ketiga dipertahankan dari query desktop: bila kedua batas
     * bernilai bintang, seluruh blok ikut.
     */
    private function syaratBlok(): string
    {
        return <<<SQL
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
                    OR
                    (
                        :blok_awal_semua = '*'
                        AND :blok_akhir_semua = '*'
                    )
                  )
        SQL;
    }

    /**
     * Susunan CASE untuk alamat, kota, dan kode pos surat, mengikuti
     * KD_ALAMT_SURAT: 1 rumah, 2 kantor, 3 surat, selain itu KTP.
     */
    private function kolomAlamatSurat(): string
    {
        $bagian = [
            'ALAMAT_SRT'   => ['alamat_rmh', 'alamat_ktr', 'alamat_srt', 'alamat_ktp'],
            'KOTA_SRT'     => ['kota_rmh', 'kota_ktr', 'kota_srt', 'kota_ktp'],
            'KODE_POS_SRT' => ['kode_pos_rmh', 'kode_pos_ktr', 'kode_pos_srt', 'kode_pos_ktp'],
        ];

        $potongan = [];

        foreach ($bagian as $alias => [$rmh, $ktr, $srt, $ktp]) {
            $potongan[] = <<<SQL
            CASE BTRIM(COALESCE(CAST(nasabah.kd_alamt_surat AS TEXT), ''))
                        WHEN '1' THEN nasabah.{$rmh}
                        WHEN '2' THEN nasabah.{$ktr}
                        WHEN '3' THEN nasabah.{$srt}
                        ELSE nasabah.{$ktp}
                    END AS "{$alias}",
            SQL;
        }

        return implode("\n                    ", $potongan);
    }

    /**
     * Kolom keluaran yang berbeda-beda menurut jenis report.
     */
    private function kolomKeluaranJenis(string $jenis): string
    {
        $bersama = <<<SQL
        surat.urut        AS "SURAT_KE",
                    surat.urut        AS "URUT",
                    surat.no_surat    AS "NO_SURAT",
                    surat.tgl_surat   AS "TGL_SURAT",
        SQL;

        if ($jenis === '1' || $jenis === '2') {
            return $bersama . <<<SQL

                    surat.tgl_undangan AS "TGL_UNDANGAN",
                    surat.waktu        AS "WAKTU_UNDANGAN",
                    surat.waktu        AS "WAKTU",
                    surat.tempat       AS "TEMPAT_UNDANGAN",
                    surat.tempat       AS "TEMPAT",
                    ppjb.no_ppjb       AS "NO_PPJB",
                    ppjb.tgl_ppjb      AS "TGL_PPJB",
                    ppjb.harga_jual    AS "HARGA_JUAL",
                    stok.luas_tanah    AS "LUAS_TANAH",
                    stok.luas_bangunan AS "LUAS_BANGUNAN",
            SQL;
        }

        if ($jenis === '3') {
            return $bersama . <<<SQL

                    surat.tgl_undangan AS "TGL_UNDANGAN",
                    surat.waktu        AS "WAKTU_UNDANGAN",
                    surat.waktu        AS "WAKTU",
                    surat.tempat       AS "TEMPAT_UNDANGAN",
                    surat.tempat       AS "TEMPAT",
                    ppjb.no_ppjb       AS "NO_PPJB",
                    ppjb.tgl_ppjb      AS "TGL_PPJB",
                    ppjb.tgl_tanda_tangan AS "TGL_TANDA_TANGAN",
                    ppjb.tgl_ttd_notaris  AS "TGL_TTD_NOTARIS",
                    stok.kd_jenis      AS "KD_JENIS",
                    jenis_ref.deskripsi AS "NAMA_JENIS_BANGUNAN",
                    stok.kd_tipe       AS "KD_TIPE",
                    tipe_ref.deskripsi AS "NAMA_TIPE",
                    stok.luas_tanah    AS "LUAS_TANAH",
                    stok.luas_bangunan AS "LUAS_BANGUNAN",
                    stok.luas_semi_gross AS "LUAS_SEMI_GROSS",
                    tipe_ref.listrik   AS "LISTRIK",
                    stok.kd_mata_uang  AS "KD_MATA_UANG",
                    jenis_ref.flag_laporan AS "FLAG_LAPORAN",
            SQL;
        }

        if ($jenis === '4') {
            return $bersama . <<<SQL

                    surat.tgl_diterima  AS "TGL_DITERIMA",
                    surat.nama_penerima AS "NAMA_PENERIMA",
                    ppjb.no_ppjb        AS "NO_PPJB",
                    ppjb.tgl_ppjb       AS "TGL_PPJB",
                    ppjb.harga_jual     AS "HARGA_JUAL",
                    stok.luas_tanah     AS "LUAS_TANAH",
                    stok.luas_bangunan  AS "LUAS_BANGUNAN",
            SQL;
        }

        if ($jenis === '5') {
            return $bersama . <<<SQL

                    ppjb.no_ppjb       AS "NO_PPJB",
                    ppjb.tgl_ppjb      AS "TGL_PPJB",
                    ppjb.harga_jual    AS "HARGA_JUAL",
                    stok.luas_tanah    AS "LUAS_TANAH",
                    stok.luas_bangunan AS "LUAS_BANGUNAN",
            SQL;
        }

        /* Jenis 6, Perpanjangan Sertipikat. */
        return $bersama . <<<SQL

                    sertipikat.no_sertipikat  AS "NO_SERTIPIKAT",
                    sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT",
                    sertipikat.tgl_berlaku    AS "TGL_BERLAKU",
                    ppjb_induk.no_ppjb        AS "NO_PPJB",
                    ppjb_induk.tgl_ppjb       AS "TGL_PPJB",
                    ppjb_induk.tgl_tanda_tangan AS "TGL_TANDA_TANGAN",
                    ppjb_induk.tgl_ttd_notaris  AS "TGL_TTD_NOTARIS",
                    stok.kd_jenis      AS "KD_JENIS",
                    jenis_ref.deskripsi AS "NAMA_JENIS_BANGUNAN",
                    stok.kd_tipe       AS "KD_TIPE",
                    tipe_ref.deskripsi AS "NAMA_TIPE",
                    stok.luas_tanah    AS "LUAS_TANAH",
                    stok.luas_bangunan AS "LUAS_BANGUNAN",
                    stok.luas_semi_gross AS "LUAS_SEMI_GROSS",
                    tipe_ref.listrik   AS "LISTRIK",
                    stok.kd_mata_uang  AS "KD_MATA_UANG",
                    jenis_ref.flag_laporan AS "FLAG_LAPORAN",
        SQL;
    }

    public function getJenisLabel(string $jenis): string
    {
        return match ($jenis) {
            '1' => 'Undangan PPJB (PPSRS)',
            '2' => 'Undangan PPJB (Notaris)',
            '3' => 'Undangan Serah Terima',
            '4' => 'Undangan AJB',
            '5' => 'Undangan SKB',
            '6' => 'Perpanjangan Sertipikat',
            default => 'Tidak Dikenal',
        };
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

    private function normalizeJenis($value): string
    {
        $jenis = trim((string) $value);

        if (!isset(self::SUMBER_JENIS[$jenis])) {
            throw new RuntimeException('Jenis report tidak valid.');
        }

        return $jenis;
    }

    private function normalizeBlok($value): string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? '*' : $value;
    }

    private function normalizeYesNo($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['Y', '1', 'TRUE', 'ON'], true) ? 'Y' : 'T';
    }

    /**
     * Menyusun ulang SERTIPIKAT_ID agar bisa disamakan dengan
     * sr_sertipikat.sertipikat_id.
     *
     * sr_sertipikat menyimpan teks lengkap seperti DBPSA-26099. Beberapa
     * tabel hasil migrasi menyimpan kunci yang sama sebagai numeric
     * sehingga awalannya terbuang; itu sudah terbukti terjadi pada
     * sr_akta, sr_peralihan, sr_jaminan, dan sr_sertipikat_idk.
     *
     * Awalannya TIDAK BOLEH ditebak. Ada dua awalan yang dipakai
     * bersamaan, DBPSA- dan DBPSS-, dan hampir seluruh angka muncul pada
     * keduanya. Salah pilih berarti data satu unit menempel ke unit lain,
     * dan itu tidak kelihatan di layar.
     *
     * Dipakai dua cara berjenjang:
     *
     * 1. Nilainya masih membawa awalan sendiri, dipakai apa adanya.
     *    Ini yang berlaku bila kolomnya ternyata bertipe teks.
     *
     * 2. Awalan diambil dari UNIT YANG DIMINTA di layar, lewat peta unit
     *    ke awalan yang dibaca dari sr_stok. Lihat awalanUnit().
     *
     * 3. Unitnya tidak ada di peta, hanya angka yang menunjuk ke TEPAT
     *    SATU sertipikat yang dipakai. Barisnya bisa berkurang, tetapi
     *    yang tampil dijamin tidak nyasar ke unit lain.
     */
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

    /**
     * Awalan kunci milik satu unit, dibaca dari STOK_ID pada sr_stok.
     *
     * Cara ini lebih tepat daripada memilih satu awalan lewat suara
     * terbanyak seluruh tabel, karena laporan ini memang selalu untuk
     * SATU unit saja. Sudah diukur pada database hasil migrasi bahwa
     * kedua puluh enam kode perusahaan masing-masing hanya memakai satu
     * awalan; misalnya DTSA dan SBKS memakai DBPSA-, sedangkan SSPG dan
     * SPCK memakai DBPSS-.
     *
     * Diambil yang terbanyak supaya tetap satu jawaban seandainya suatu
     * saat ada unit yang datanya bercampur.
     *
     * Hasilnya diingat per unit supaya query penentu ini hanya jalan
     * sekali untuk tiap unit.
     */
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

    /**
     * Angka yang menunjuk ke tepat satu sertipikat, beserta awalannya.
     *
     * Hanya dipakai bila unitnya tidak ada di peta awalan. Angka yang
     * muncul pada dua keluarga sekaligus sengaja tidak diikutkan, karena
     * memilih salah satunya berarti menebak.
     */
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

    /**
     * Pengurutan nomor rumah seperti pada desktop: nomor yang seluruhnya
     * angka didahulukan dan diurutkan sebagai angka, sisanya menyusul.
     *
     * Padanan SQL Server:
     *     NOT LIKE '%[^0-9]%'          -> ~ '^[0-9]+$'
     *     RIGHT(REPLICATE('0',50)+x,50) -> LPAD(x, 50, '0')
     */
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

    /**
     * Memeriksa keberadaan sebuah kolom pada tabel hasil migrasi.
     */
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

    /**
     * Memilih nama kolom kode yang benar-benar ada pada tabel hasil
     * migrasi. Hanya dipakai untuk kolom kode, karena penamaannya
     * berbeda-beda antar tabel. Sama seperti pada model lain yang sudah
     * dimigrasi.
     */
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

    /**
     * PostgreSQL memakai format tanggal ISO, bukan gaya CONVERT 112
     * milik SQL Server.
     */
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

    /**
     * Menolak menampilkan laporan untuk unit yang keluarga awalannya
     * TIDAK ADA pada tabel sumber.
     *
     * Alasannya ditemukan waktu membandingkan hasil migrasi dengan
     * sumbernya. Karena sertipikat_id kehilangan awalan, angkanya saja
     * yang tersisa, dan hampir semua angka dipakai kedua keluarga awalan.
     * Kalau sebuah unit diminta sedangkan tabel sumber tidak memuat satu
     * pun baris dari keluarga awalan unit itu, penyusunan ulang tetap
     * "berhasil" menemukan sertipikat, tetapi seluruh barisnya keliru:
     * baris milik keluarga lain ditarik dan ditampilkan seolah milik unit
     * yang diminta.
     *
     * Itu bukan kemungkinan di atas kertas. Diukur pada sr_imb, keenam
     * unit berawalan DBPSS- akan menampilkan 20.140 baris yang seluruhnya
     * tidak ada dasarnya, karena sr_imb ternyata hanya memuat baris dari
     * SRIS_PUSAT.
     *
     * Laporan kosong masih bisa ditelusuri, sedangkan laporan yang salah
     * tetapi kelihatan wajar tidak. Karena itu di sini dipilih berhenti
     * dengan pesan, bukan menampilkan apa adanya.
     *
     * Pemeriksaannya membaca data, bukan daftar tetap, sehingga begitu
     * migrasinya diperbaiki penjagaan ini membuka sendiri tanpa perlu
     * mengubah kode.
     *
     * Dasar pemeriksaan: baris yang angkanya hanya dipakai SATU keluarga
     * awalan. Baris semacam itu asal-usulnya pasti. Kalau keluarga yang
     * diminta tidak punya satu pun baris pasti sedangkan keluarga lain
     * punya, berarti keluarga itu memang tidak terwakili.
     */
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

        /*
         * Tidak ada satu pun baris yang asal-usulnya pasti berarti
         * pemeriksaan ini tidak punya dasar untuk menyimpulkan apa pun.
         * Dalam keadaan itu laporan dibiarkan jalan, supaya penjagaan ini
         * tidak memblokir database yang isinya memang sedikit.
         */
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
