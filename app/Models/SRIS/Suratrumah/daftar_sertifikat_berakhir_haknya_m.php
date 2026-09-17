<?php

// MODEL POSTGRESQL V1 - DAFTAR SERTIPIKAT YANG BERAKHIR HAKNYA

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260917
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\Suratrumah;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class daftar_sertifikat_berakhir_haknya_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Mengambil sektor aktif berdasarkan unit/perusahaan yang sedang dipilih.
     */
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

    /**
     * Laporan Daftar Sertipikat yang Berakhir Haknya.
     *
     * Pemetaan pilihan desktop:
     * - Semua AJB  : tanpa filter AKTA.
     * - Belum AJB  : tidak memiliki AKTA dengan NO_AKTA terisi.
     * - Sudah AJB  : memiliki AKTA dengan NO_AKTA terisi.
     * - Apartemen  : KD_JENIS APT/KTR; sektor tidak dipakai, sedangkan pilihan
     *                 Semua/Sudah/Belum AJB tetap diterapkan.
     * - Nonapartemen: KD_JENIS selain APT/KTR.
     *
     * Checkbox tampil_penggabungan hanya mengatur kolom pada view. Query tetap
     * mengembalikan semua kolom yang diperlukan agar perubahan tampilan tidak
     * perlu meminta ulang data.
     */
    public function obtainDaftarSertipikatBerakhirHaknya($request): array
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
        $apartemen = $this->normalizeYesNo($request->apartemen ?? 'T');
        $statusAjb = $this->normalizeAjbStatus(
            $request->status_ajb ?? 'SEMUA'
        );

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        $sektor = $sektor === '' ? '*' : $sektor;
        $blokAwal = $blokAwal === '' ? 'A' : $blokAwal;
        $blokAkhir = ($blokAkhir === '' || $blokAkhir === 'Z')
            ? 'ZZ'
            : $blokAkhir;

        return $this->runReportQuery(
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

    private function runReportQuery(
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

        $sektorFilterSql = $this->buildSektorFilterSql($apartemen, $k['stokSektor']);
        $jenisFilterSql = $this->buildJenisFilterSql($apartemen, $k['stokJenis']);
        $ajbFilterSql = $this->buildAjbFilterSql($statusAjb);

        $kunciIdk = $this->kunciSertipikatIdk();
        $syaratBlok = $this->syaratBlok(
            'hasil_dasar."BLOK"',
            'hasil_dasar."NOMOR"'
        );

        $sql = <<<SQL
            WITH stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                /*
                 * Cabang pertama membandingkan kolomnya apa adanya. Hasilnya
                 * sama persis dengan cabang kedua, karena parameternya sudah
                 * dibuat huruf besar tanpa spasi oleh normalizeText. Gunanya
                 * memberi perencana query sebuah perbandingan kolom biasa yang
                 * ada statistiknya, supaya jumlah barisnya tidak ditaksir 1
                 * padahal ribuan.
                 */
                WHERE (
                        stok.{$k['stokPerusahaan']} = :perusahaan_langsung
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.{$k['stokPerusahaan']} AS TEXT), '')))
                            = :perusahaan
                      )
                  AND (
                        stok.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
                  {$sektorFilterSql}
            ),
            sertipikat_terpilih AS (
                /*
                 * Padanan OUTER APPLY TANGGAL_REF pada query desktop.
                 * Tanggal dibuat aman karena pada database legacy kolom
                 * tanggal dapat berisi teks yang bukan tanggal; ini padanan
                 * ISDATE() desktop. Sertipikat disaring tanggal lebih dulu
                 * supaya yang dijoin tinggal sedikit.
                 */
                SELECT
                    s.*,
                    CASE
                        WHEN COALESCE(CAST(s.tgl_berlaku AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(s.tgl_berlaku AS TIMESTAMP)
                    END AS tgl_berlaku_valid
                FROM public.sr_sertipikat AS s
                WHERE s.stok_id IS NOT NULL
                  AND CASE
                          WHEN COALESCE(CAST(s.tgl_berlaku AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(s.tgl_berlaku AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(s.tgl_berlaku AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(s.tgl_berlaku AS TIMESTAMP)
                      END < CAST(:tgl_akhir_eksklusif AS DATE)
            ),
            ppjb_aktif AS MATERIALIZED (
                /*
                 * Pengganti OUTER APPLY ... SELECT TOP (1) PPJB.
                 * Urutannya dipertahankan: PPJB terbaru lebih dulu.
                 */
                SELECT DISTINCT ON (kunci_stok)
                    kunci_stok, ppjb_id, user_entry
                FROM (
                    SELECT
                        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS ppjb_id,
                        ppjb.user_entry AS user_entry,
                        ppjb.tgl_ppjb AS tgl_ppjb
                    FROM public.sr_ppjb AS ppjb
                    WHERE UPPER(BTRIM(COALESCE(
                              CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                      AND ppjb.parent_id IS NULL
                ) AS daftar
                ORDER BY kunci_stok, tgl_ppjb DESC NULLS LAST, ppjb_id DESC
            ),
            pembeli_nama AS MATERIALIZED (
                /*
                 * Pengganti F_GET_PEMBELI. Seluruh pembeli aktif pada satu
                 * PPJB digabung menjadi satu teks, sama seperti keluaran
                 * fungsi desktopnya.
                 */
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
                /*
                 * Pengganti OUTER APPLY ... SELECT TOP (1) SEKTOR.
                 * Urutannya dipertahankan: sektor milik unit yang sama lebih
                 * dulu, lalu yang bertanda aktif.
                 */
                SELECT DISTINCT ON (kode)
                    kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            CAST(sektor.{$k['sektorKode']} AS TEXT), ''))) AS kode,
                        BTRIM(COALESCE(
                            CAST(sektor.deskripsi AS TEXT), '')) AS deskripsi,
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
                sertipikat_idk.luas_su_pisah AS "LUAS_SU_PISAH",
                sertipikat_idk.mohon_pisah AS "MOHON_PISAH",
                sertipikat_idk.tgl_mohon_pisah AS "TGL_MOHON_PISAH",
                sertipikat_idk.ser_pisah AS "SER_PISAH",
                sertipikat_idk.tgl_ser_pisah AS "TGL_SER_PISAH",
                sertipikat_idk.su_pisah AS "SU_PISAH_IDK",
                sertipikat_idk.tgl_su_pisah AS "TGL_SU_PISAH_IDK",

                sertipikat.no_sertipikat AS "NO_SERTIPIKAT",
                sertipikat.tgl_sertipikat AS "TGL_SERTIPIKAT",
                sertipikat.su_pisah AS "SU_PISAH",
                sertipikat.tgl_su_pisah AS "TGL_SU_PISAH",
                sertipikat.tgl_berlaku_valid AS "TGL_BERLAKU",
                sertipikat.luas_sup AS "LUAS_SUP",

                stok.{$k['stokPerusahaan']} AS "KD_PERUSAHAAN",
                stok.{$k['stokJenis']} AS "KD_JENIS",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                ppjb_aktif.user_entry AS "USER_ENTRY",
                sektor_ref.deskripsi AS "NAMA_SEKTOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                sertipikat.sertipikat_id AS "SERTIPIKAT_ID"

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

            WHERE TRUE
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

        $bindings = [
            'awalan_idk' => $this->awalanSertipikatIdk(),
            'perusahaan_langsung' => $perusahaan,
            'perusahaan' => $perusahaan,
            'perusahaan_sektor' => $perusahaan,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_awal_blok' => $blokAwal,
            'blok_akhir_blok' => $blokAkhir,
        ];

        /*
         * Query apartemen desktop tidak memakai sektor. Binding sektor hanya
         * dikirim bila penampungnya memang ada di dalam query, karena PDO
         * menolak parameter yang tidak terpakai.
         */
        if ($apartemen !== 'Y') {
            $bindings['sektor_filter'] = $sektor;
            $bindings['sektor_semua'] = $sektor;
        }

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    /**
     * Menyusun ulang SERTIPIKAT_ID milik sr_sertipikat_idk agar bisa
     * disamakan dengan sr_sertipikat.sertipikat_id.
     *
     * sr_sertipikat menyimpan teks lengkap seperti DBPSA-21857, sedangkan
     * sr_sertipikat_idk bertipe numeric sehingga awalannya terbuang dan
     * hanya menyisakan 21857. Tanpa disusun ulang, sambungan kedua tabel
     * menghasilkan 0 baris dan laporannya selalu kosong.
     *
     * Bila nilainya ternyata sudah membawa awalan sendiri, nilainya dipakai
     * apa adanya, sehingga tetap benar bila kolomnya suatu saat diperbaiki
     * menjadi teks.
     */
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

    /**
     * Menentukan awalan sr_sertipikat_idk dari datanya sendiri.
     *
     * Awalannya tidak boleh sekadar dibuang dari sisi sr_sertipikat, karena
     * ada dua awalan yang dipakai bersamaan, DBPSA- dan DBPSS-, dan hampir
     * seluruh angka muncul pada keduanya. Diukur pada database DTSA, 19.465
     * dari 23.308 baris idk angkanya ada di kedua keluarga.
     *
     * Penentunya memakai isi datanya sendiri. Kedua tabel menyimpan data
     * pemisahan yang sama dari dua sisi, sehingga pasangan yang benar isinya
     * sama dan pasangan yang salah tidak. Diukur pada database DTSA, DBPSS-
     * tidak pernah sama satu baris pun pada SU_PISAH, NO_SERTIPIKAT,
     * TGL_SU_PISAH, TGL_SERTIPIKAT, maupun TGL_INPUT, sedangkan DBPSA- sama
     * pada 13.293 baris. Dihitung baris per baris, 16.619 baris hanya cocok
     * DBPSA- dan tidak satu baris pun yang hanya cocok DBPSS-.
     *
     * Cara yang sama dipakai pada fitur Daftar Sertipikat Pecahan.
     */
    private function awalanSertipikatIdk(): string
    {
        static $awalan = null;

        if ($awalan !== null) {
            return $awalan;
        }

        /*
         * Kedua sisi dibuat berkunci "angka" lebih dulu supaya syarat join
         * hanya menyangkut dua tabel dan bisa memakai hash join.
         */
        $sql = <<<SQL
            SELECT
                ser.awalan AS awalan,
                COUNT(*) AS cocok
            FROM (
                SELECT
                    BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
                    UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) AS no_ser,
                    UPPER(BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), ''))) AS su_pisah
                FROM public.sr_sertipikat_idk AS i
                WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
                  AND (
                        BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')) <> ''
                        OR BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), '')) <> ''
                      )
            ) AS idk
            INNER JOIN (
                SELECT
                    REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '')
                        AS awalan,
                    REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '')
                        AS angka,
                    UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), ''))) AS no_ser,
                    UPPER(BTRIM(COALESCE(CAST(s.su_pisah AS TEXT), ''))) AS su_pisah
                FROM public.sr_sertipikat AS s
                WHERE s.sertipikat_id IS NOT NULL
            ) AS ser
                ON ser.angka = idk.angka
            WHERE (idk.no_ser <> '' AND idk.no_ser = ser.no_ser)
               OR (idk.su_pisah <> '' AND idk.su_pisah = ser.su_pisah)
            GROUP BY 1
            ORDER BY 2 DESC, 1 ASC
            LIMIT 1
        SQL;

        $baris = DB::connection(self::CONNECTION)->select($sql);
        $awalan = $baris ? (string) $baris[0]->awalan : '';

        return $awalan;
    }

    /**
     * Syarat rentang blok, dipasang setelah penggabungan tabel.
     *
     * Bentuknya memakai UPPER, BTRIM, dan penyambungan teks, sehingga
     * perencana query tidak punya statistik apa pun untuk menaksirnya dan
     * menduga sr_stok hanya berisi 1 baris padahal ribuan. Dugaan itu membuat
     * PostgreSQL memilih nested loop yang membaca sr_sertipikat berulang kali.
     *
     * Hasilnya tidak berubah karena stok disambung dengan INNER JOIN, jadi
     * menyaring sebelum atau sesudah penggabungan sama saja.
     */
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

    private function namaKolom(): array
    {
        return [
            'stokPerusahaan' => $this->kolomKode('sr_stok', [
                'kd_perusahaan', 'kd_unit', 'kd_pt',
            ]),
            'stokSektor' => $this->kolomKode('sr_stok', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
            /*
             * Pada hasil migrasi kolom jenis bangunan di sr_stok bernama
             * kd_jenis_bgn, bukan kd_jenis seperti pada query desktop.
             */
            'stokJenis' => $this->kolomKode('sr_stok', [
                'kd_jenis_bgn', 'kd_jenis',
            ]),
            'sektorKode' => $this->kolomKode('sr_sektor', [
                'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
            ]),
            'sektorPerusahaan' => $this->kolomKode('sr_sektor', [
                'kd_perusahaan', 'kd_unit', 'kd_pt',
            ]),
        ];
    }

    private function buildSektorFilterSql(string $apartemen, string $kolom): string
    {
        if ($apartemen === 'Y') {
            return '';
        }

        return <<<SQL
AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$kolom} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
SQL;
    }

    private function buildJenisFilterSql(string $apartemen, string $kolom): string
    {
        $ekspresi = "UPPER(BTRIM(COALESCE(CAST(stok.{$kolom} AS TEXT), '')))";

        if ($apartemen === 'Y') {
            return "AND {$ekspresi} IN ('APT', 'KTR')";
        }

        return "AND {$ekspresi} NOT IN ('APT', 'KTR')";
    }

    /**
     * Filter AJB mengikuti tabel AKTA dari query desktop.
     * SUDAH = NO_AKTA terisi.
     * BELUM = belum ada baris AKTA atau NO_AKTA masih NULL.
     * SEMUA = tanpa filter AKTA.
     *
     * SERTIPIKAT_ID pada sr_akta bertipe numeric sehingga awalannya terbuang,
     * sedangkan sr_sertipikat menyimpan teks lengkap berawalan. Awalan yang
     * benar diambil dari PPJB_ID pada baris akta itu sendiri, sama seperti
     * pada fitur Daftar Akta Jual Beli.
     */
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

        /* Setara dengan NOT IN pada query desktop, tetapi aman bila
           subquerynya berisi NULL. */
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

    /**
     * Memilih nama kolom kode yang benar-benar ada pada tabel hasil migrasi.
     * Hanya dipakai untuk kolom kode, karena penamaannya berbeda-beda antar
     * unit. Sama seperti pada model lain yang sudah dimigrasi.
     */
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

    /**
     * PostgreSQL memakai format tanggal ISO, bukan gaya CONVERT 112 milik
     * SQL Server.
     */
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

    private function normalizeAjbStatus($value): string
    {
        $normalized = $this->normalizeText($value);

        return in_array(
            $normalized,
            ['SUDAH', 'BELUM', 'SEMUA'],
            true
        ) ? $normalized : 'SEMUA';
    }

    private function normalizeYesNo($value): string
    {
        $normalized = $this->normalizeText($value);

        return in_array(
            $normalized,
            ['Y', '1', 'TRUE', 'ON'],
            true
        ) ? 'Y' : 'T';
    }
}
