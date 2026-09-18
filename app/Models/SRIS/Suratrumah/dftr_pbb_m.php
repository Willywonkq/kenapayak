<?php

// MODEL POSTGRESQL V1 - DAFTAR REKAP PBB

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260918
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.
//
// Perbaikan terhadap query desktop yang sudah ada pada model SQL Server
// sebelumnya dan tetap dipertahankan di sini:
// 1. Alias join XPPJB yang salah tulis pada query desktop diperbaiki.
// 2. Campuran comma JOIN dan LEFT JOIN diganti JOIN eksplisit.
// 3. Kondisi blok kedua yang memakai BLOK_AKHIR dua kali diperbaiki
//    menjadi BLOK_AWAL sampai BLOK_AKHIR.
// 4. Mode "Belum Ada PBB" memakai NOT EXISTS agar aman terhadap NULL.
// 5. Tanggal akhir dibuat eksklusif agar seluruh baris pada tanggal
//    akhir tetap ikut ketika TGL_INPUT menyimpan jam.
//
// Penyesuaian khusus PostgreSQL:
// - OUTER APPLY TOP (1) diganti tabel bantu ber-DISTINCT ON, supaya
//   tidak dijalankan ulang untuk tiap baris keluaran;
// - F_GET_PEMBELI diganti STRING_AGG, karena fungsi itu milik SQL Server;
// - SERTIPIKAT_ID disusun ulang awalannya, lihat kunciSertipikat().

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_pbb_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
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
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'Z');

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

        if ($blokAkhir === '') {
            $blokAkhir = 'Z';
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

    /**
     * Tabel bantu yang dipakai bersama oleh ketiga mode laporan.
     *
     * OUTER APPLY TOP (1) milik SQL Server diganti DISTINCT ON. Keduanya
     * sama-sama mengambil satu baris teratas menurut urutan tertentu,
     * bedanya DISTINCT ON disusun sekali untuk seluruh tabel sedangkan
     * OUTER APPLY dijalankan ulang untuk tiap baris keluaran.
     *
     * NULLS LAST dipasang pada TGL_PPJB karena SQL Server menaruh NULL
     * paling akhir pada urutan menurun, sedangkan PostgreSQL menaruhnya
     * paling awal. Tanpa itu, PPJB yang tanggalnya kosong justru akan
     * terpilih sebagai yang terbaru.
     */
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
                /*
                 * Cabang pertama membandingkan kolomnya apa adanya.
                 * Hasilnya sama persis dengan cabang kedua, karena
                 * parameternya sudah dibuat huruf besar tanpa spasi oleh
                 * normalizeText. Gunanya memberi perencana query sebuah
                 * perbandingan kolom biasa yang ada statistiknya.
                 *
                 * FLAG_AKTIF sengaja TIDAK disaring, mengikuti query
                 * desktop untuk laporan PBB yang memang tidak memakainya.
                 */
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
                /*
                 * Desktop memakai TOP (1) dengan urutan: sektor milik unit
                 * yang sama didahulukan, baru yang kd_perusahaan-nya
                 * kosong, lalu yang bertanda aktif. Karena laporan ini
                 * selalu untuk satu unit, tabel bantunya langsung disusun
                 * untuk unit itu saja sehingga cukup satu baris per kode
                 * dan tidak ada baris laporan yang tergandakan.
                 */
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
                /* PPJB aktif terbaru untuk tiap stok, apa pun parent-nya. */
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
                /* PPJB aktif terbaru yang bukan turunan, untuk nama pembeli. */
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
                /*
                 * Pengganti F_GET_PEMBELI milik SQL Server. Fungsi itu
                 * menggabungkan nama seluruh pembeli sebuah PPJB menjadi
                 * satu teks; di sini dikerjakan STRING_AGG.
                 *
                 * Urutannya memakai urutan fisik baris, meniru fungsi
                 * aslinya yang membaca tabel apa adanya.
                 */
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
                /*
                 * Padanan OUTER APPLY XPPJB pada desktop: satu pembeli
                 * teratas beserta data pribadinya, diambil dari PPJB aktif
                 * yang bukan turunan dan paling baru.
                 */
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

    /**
     * Susunan alamat pembeli, meniru penggabungan teks pada desktop:
     * alamat KTP, lalu kota bila ada, lalu kode pos bila ada.
     */
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

    /**
     * Pengurutan PPJB_ID sebagai angka.
     *
     * Pada SQL Server PPJB_ID bertipe angka sehingga ORDER BY PPJB_ID DESC
     * membandingkannya sebagai angka. Pada PostgreSQL kolomnya teks
     * berawalan, dan membandingkan teks memberi urutan berbeda. Jadi
     * bagian angkanya diambil dulu.
     */
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

    /**
     * Mode NORMAL dan mode NAMA_ALAMAT.
     *
     * Keduanya memakai sumber, penyaring, dan pengurutan yang sama persis;
     * yang berbeda hanya kolom mengenai pembeli. Karena itu disatukan agar
     * penyaringnya tidak perlu ditulis dua kali dan tidak bisa berbeda
     * tanpa sengaja.
     */
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

        /*
         * Saringan blok dipasang setelah penggabungan tabel, di belakang
         * hasil_dasar. Bentuknya memakai BTRIM dan penyambungan teks,
         * sehingga perencana query tidak punya statistik untuk
         * menaksirnya dan menduga sr_stok hanya berisi satu baris padahal
         * ribuan. Karena stok disambung dengan INNER JOIN, menyaring
         * sebelum atau sesudah penggabungan sama saja hasilnya.
         */
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

    /**
     * Mode "Belum Ada PBB".
     *
     * Berangkat dari STOK, bukan dari PBB, lalu membuang stok yang sudah
     * punya PBB pada rentang tahun yang diminta. Tgl. Input memang tidak
     * dipakai pada mode ini, karena stok yang belum punya PBB tidak punya
     * baris PBB sama sekali sehingga tidak ada tanggal untuk disaring.
     */
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
                /*
                 * Kunci sertipikat yang SUDAH punya PBB pada rentang tahun
                 * yang diminta. Dikumpulkan lebih dulu supaya NOT EXISTS
                 * berkorelasi pada desktop berubah menjadi satu kali baca.
                 */
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

                /*
                 * CROSS APPLY pada desktop bersifat wajib, jadi stok yang
                 * tidak punya pembeli aktif memang tidak ikut tampil.
                 */
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

    /**
     * Nama kolom kode yang dipakai ketiga mode, dikumpulkan sekali.
     */
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

    /**
     * Master sektor pada sebagian hasil migrasi tidak membawa
     * kd_perusahaan. Kalau begitu, pencocokan sektor cukup memakai
     * kodenya saja, persis seperti cabang "SEKTOR.KD_PERUSAHAAN IS NULL"
     * pada query desktop.
     */
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
}
