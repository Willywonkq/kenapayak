<?php

// MODEL POSTGRESQL V1 - DAFTAR PENGAJUAN SERTIPIKAT BALIK NAMA

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260917
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\Suratrumah;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class dftr_pengajuan_balik_nama_m extends Model
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

    /**
     * Query laporan Daftar Pengajuan Sertipikat Balik Nama.
     *
     * Catatan:
     * - Logika utama mempertahankan query desktop.
     * - Tanggal akhir dibuat eksklusif H+1 agar seluruh transaksi pada tanggal
     *   akhir tetap terambil walaupun AKTA.TGL_INPUT memiliki komponen waktu.
     *
     * Padanan dialek yang dipakai: ISNULL -> COALESCE, + -> ||,
     * GETDATE() -> CURRENT_TIMESTAMP, SELECT TOP (1) -> DISTINCT ON,
     * OUTER APPLY -> tabel bantu yang disambung LEFT JOIN,
     * NOT LIKE '%[^0-9]%' -> ~ '^[0-9]+$',
     * RIGHT(REPLICATE('0',50)+x,50) -> LPAD(x,50,'0').
     *
     * Fungsi F_GET_PEMBELI() milik SQL Server tidak ada di PostgreSQL.
     * Penggantinya tabel bantu yang menggabungkan nama seluruh pembeli aktif
     * pada satu PPJB, sama seperti pada fitur Rekap Estimasi Biaya AJB.
     */
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

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '') {
            $blokAkhir = 'Z';
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

        /*
         * Pada hasil migrasi kolom jenis bangunan di sr_stok bernama
         * kd_jenis_bgn, bukan kd_jenis seperti pada query desktop. Sudah
         * terbukti pada fitur Rekap Estimasi Biaya AJB.
         */
        $stokJenis = $this->kolomKode('sr_stok', ['kd_jenis_bgn', 'kd_jenis']);

        $kunciAkta = $this->kunciSertipikat('akta.sertipikat_id');
        $kunciIdk = $this->kunciSertipikatIdk();
        $syaratBlok = $this->syaratBlok('hasil_dasar."BLOK"', 'hasil_dasar."NOMOR"');

        $sql = <<<SQL
            WITH akta_terpilih AS (
                /*
                 * Akta disaring tanggal lebih dulu supaya yang dijoin tinggal
                 * sedikit. Kunci pada database ini dibandingkan lewat BTRIM
                 * dan CAST, dan perbandingan semacam itu tidak bisa memakai
                 * index, sehingga menyempitkan lebih dulu jauh lebih murah.
                 */
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
                /*
                 * Cabang pertama membandingkan kolomnya apa adanya. Hasilnya
                 * sama persis dengan cabang kedua, karena parameternya sudah
                 * dibuat huruf besar tanpa spasi oleh normalizeText, sehingga
                 * baris yang cocok pada cabang pertama pasti cocok juga pada
                 * cabang kedua. Gunanya bukan menyaring, melainkan memberi
                 * perencana query sebuah perbandingan kolom biasa yang ada
                 * statistiknya. Tanpa itu jumlah baris sr_stok ditaksir 1
                 * padahal ribuan, dan PostgreSQL memilih nested loop yang
                 * membaca sr_sertipikat berulang-ulang.
                 */
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
                /*
                 * Pengganti OUTER APPLY ... SELECT TOP (1) PPJB. Urutannya
                 * dipertahankan: PPJB terbaru lebih dulu.
                 */
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
                /*
                 * Pengganti F_GET_PEMBELI(PPJB_ID) milik SQL Server. Nama
                 * seluruh pembeli aktif pada satu PPJB digabung.
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
                 * Pengganti OUTER APPLY ... SELECT TOP (1) SEKTOR. Urutannya
                 * dipertahankan: yang kode perusahaannya cocok lebih dulu,
                 * lalu yang berstatus aktif.
                 */
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
            'awalan_idk' => $this->awalanSertipikatIdk(),
            'perusahaan_langsung' => $perusahaan,
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,
            'blok_akhir_blok_min' => $blokAkhir,
            'blok_akhir_blok_max' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_gpt' => $tglAkhirEksklusif,
            'perusahaan' => $perusahaan,
            'perusahaan_sektor' => $perusahaan,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
        ]);
    }

    /**
     * Menyusun ulang SERTIPIKAT_ID agar bisa disamakan dengan
     * sr_sertipikat.sertipikat_id.
     *
     * sr_sertipikat menyimpan teks lengkap seperti DBPSA-18784, sedangkan
     * sr_akta bertipe numeric sehingga awalannya terbuang dan hanya
     * menyisakan 18784. Awalannya tidak boleh sekadar dibuang dari sisi
     * sertipikat, karena ada dua awalan yang dipakai bersamaan, DBPSA- dan
     * DBPSS-, dan setiap angka muncul pada keduanya.
     *
     * Awalan yang benar diambil dari PPJB_ID pada baris akta itu sendiri,
     * karena kolom itu selamat sebagai teks lengkap. Cara yang sama sudah
     * terbukti pada fitur Daftar Akta Jual Beli.
     */
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
    /**
     * Syarat rentang blok, dipasang setelah penggabungan tabel.
     *
     * Dulu syarat ini berada di dalam stok_terpilih. Bentuknya memakai
     * UPPER, BTRIM, dan penyambungan teks, sehingga perencana query tidak
     * punya statistik apa pun untuk menaksirnya dan menduga sr_stok hanya
     * berisi 1 baris padahal ribuan. Dugaan itu membuat PostgreSQL memilih
     * nested loop dan membaca sr_sertipikat berulang kali; pada database
     * uji berisi 6.000 baris, satu laporan memakan 66 detik.
     *
     * Hasilnya tidak berubah karena stok disambung dengan INNER JOIN, jadi
     * menyaring sebelum atau sesudah penggabungan sama saja. Yang berubah
     * hanya taksiran perencana, dan waktunya turun menjadi di bawah satu
     * detik.
     *
     * Cabang kedua memakai BLOK_AKHIR untuk kedua sisinya, mengikuti query
     * desktop apa adanya.
     */
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
     * Tiga cara yang dipakai model lain tidak bisa dipakai di sini:
     * sr_sertipikat_idk tidak membawa KD_PERUSAHAAN seperti sr_biaya_ajb,
     * kolom SERTIPIKAT_IDK ternyata nomor sertipikat induk dan bukan kunci
     * berawalan seperti AKTA.PPJB_ID, dan uji kelayakan tanggal memberi
     * hasil yang sama untuk kedua awalan, 99,0 persen lawan 99,3 persen.
     *
     * Penentunya memakai isi datanya sendiri. Kedua tabel menyimpan data
     * pemisahan yang sama dari dua sisi, sehingga pasangan yang benar isinya
     * sama dan pasangan yang salah tidak. Diukur pada database DTSA:
     *
     *     DBPSA-   23.304 pasangan   13.293 SU_PISAH sama   2.167 beda
     *     DBPSS-   19.468 pasangan        0 SU_PISAH sama  12.000 beda
     *
     * DBPSS- tidak pernah sama sekalipun pada NO_SERTIPIKAT, TGL_SU_PISAH,
     * TGL_SERTIPIKAT, maupun TGL_INPUT, jadi kecocokan angkanya hanyalah
     * tabrakan. Dihitung baris per baris, 16.619 baris hanya cocok DBPSA-
     * dan tidak satu baris pun yang hanya cocok DBPSS-.
     *
     * Awalannya tidak ditulis mati di sini, melainkan dihitung dari data
     * sehingga tetap benar bila suatu saat sumbernya berubah. Hasilnya
     * diingat supaya query penentu ini hanya jalan sekali.
     */
    private function awalanSertipikatIdk(): string
    {
        static $awalan = null;

        if ($awalan !== null) {
            return $awalan;
        }

        /*
         * Kedua sisi dibuat berkunci "angka" lebih dulu supaya syarat join
         * hanya menyangkut dua tabel dan bisa memakai hash join, sama
         * seperti penentu awalan pada fitur Daftar Peralihan Hak.
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

    /**
     * Memilih nama kolom kode yang benar-benar ada pada tabel hasil migrasi.
     * Hanya dipakai untuk kolom kode, karena penamaannya berbeda-beda antar
     * unit. Sama seperti pada model lain yang sudah dimigrasi.
     */
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

    /**
     * Menormalisasi tanggal request menjadi format Y-m-d.
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
            'Format tanggal tidak valid: ' . $text .
            '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }
}
