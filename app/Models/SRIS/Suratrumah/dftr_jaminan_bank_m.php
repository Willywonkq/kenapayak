<?php

// MODEL POSTGRESQL V1 - REKAP JAMINAN BANK

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260917
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_jaminan_bank_m extends Model
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
     * Jenis Jaminan mengikuti pilihan tetap pada aplikasi desktop SRIS.
     * Urutan dropdown desktop:
     * Semua, IMB, Akta Jual Beli, Sertipikat, PPJB, Peralihan Hak.
     *
     * Nilai "Semua" dikirim sebagai "*" karena query desktop memakai:
     *   (JAMINAN.JENIS_JAMINAN = :jaminan OR :jaminan = '*')
     */
    private const JENIS_JAMINAN_VALID = [
        '*',
        'IMB',
        'AKTA JUAL BELI',
        'SERTIPIKAT',
        'PPJB',
        'PERALIHAN HAK',
    ];

    public function obtainRekapJaminanBank($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );

        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ');
        $sektor = $this->normalizeText($request->sektor ?? '*');
        $jaminan = $this->normalizeJenisJaminan($request->jenis_jaminan ?? '*');
        $statusAjb = $this->normalizeAjbStatus($request->status_ajb ?? 'SEMUA');

        $tglAwalBank = $this->normalizeDateNullable($request->tgl_awal_bank ?? null);
        $tglAkhirBank = $this->normalizeDateNullable($request->tgl_akhir_bank ?? null);

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '') {
            $blokAkhir = 'ZZ';
        }

        if ($sektor === '') {
            $sektor = '*';
        }

        if ($jaminan === '') {
            $jaminan = '*';
        }

        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $stokLokasi = $this->kolomKode('sr_stok', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster',
        ]);
        $lokasiKode = $this->kolomKode('sr_lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $ajbFilterSql = $this->buildAjbFilterSql($statusAjb);
        $tanggalFilterSql = $this->buildTanggalFilterSql($tglAwalBank, $tglAkhirBank);

        /*
         * Cara menyambungkan sr_jaminan ke sr_sertipikat ditentukan dari
         * bentuk kolomnya. Lihat keterangan panjang pada kunciSertipikatJaminan().
         */
        $pakaiUnit = $this->adaKolom('sr_jaminan', 'kd_perusahaan');
        $cteAwalanUnit = $pakaiUnit ? $this->cteAwalanUnit() : '';
        $joinAwalanUnit = $pakaiUnit
            ? "INNER JOIN awalan_unit
                    ON awalan_unit.kode_unit = UPPER(BTRIM(COALESCE(
                           CAST(jaminan.kd_perusahaan AS TEXT), '')))"
            : '';
        $cteSertipikatUnik = $pakaiUnit ? '' : $this->cteSertipikatUnik();
        $joinSertipikatUnik = $pakaiUnit
            ? ''
            : "LEFT JOIN sertipikat_unik
                    ON sertipikat_unik.angka
                     = BTRIM(CAST(jaminan.sertipikat_id AS TEXT))";
        $kunciJaminan = $this->kunciSertipikatJaminan($pakaiUnit);

        /*
         * CATATAN PENTING:
         * Kondisi blok di bawah SENGAJA mempertahankan query desktop:
         *
         *   OR (STOK.BLOK >= :BLOK_AKHIR AND STOK.BLOK <= :BLOK_AKHIR)
         *
         * Secara logika bagian kedua hanya sama dengan BLOK_AKHIR. Belum
         * "dibetulkan" ke BLOK_AWAL...BLOK_AKHIR agar hasilnya tetap
         * mengikuti perilaku desktop.
         *
         * Saringan blok dipasang setelah penggabungan tabel, di belakang
         * hasil_dasar. Bentuknya memakai UPPER, BTRIM, dan penyambungan
         * teks, sehingga perencana query tidak punya statistik untuk
         * menaksirnya dan menduga sr_stok hanya berisi 1 baris padahal
         * ribuan; dugaan itu membuat PostgreSQL memilih nested loop.
         * Karena stok disambung dengan INNER JOIN, menyaring sebelum atau
         * sesudah penggabungan sama saja hasilnya.
         */
        $sql = <<<SQL
            WITH {$cteAwalanUnit}{$cteSertipikatUnik}stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                /*
                 * Cabang pertama membandingkan kolomnya apa adanya. Hasilnya
                 * sama persis dengan cabang kedua, karena parameternya sudah
                 * dibuat huruf besar tanpa spasi oleh normalizeText. Gunanya
                 * memberi perencana query sebuah perbandingan kolom biasa
                 * yang ada statistiknya.
                 */
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
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :sektor_filter
                        OR :sektor_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            jaminan_terpilih AS (
                SELECT
                    jaminan.*,
                    {$kunciJaminan} AS kunci_sertipikat
                FROM public.sr_jaminan AS jaminan
                {$joinAwalanUnit}
                {$joinSertipikatUnik}
                WHERE jaminan.no_jaminan IS NOT NULL
                  AND jaminan.no_lunas IS NULL
                  AND jaminan.no_batal IS NULL
                  AND (
                        UPPER(BTRIM(COALESCE(
                            CAST(jaminan.jenis_jaminan AS TEXT), '')))
                            = :jaminan_filter
                        OR :jaminan_semua = '*'
                      )
                  {$tanggalFilterSql}
            ),
            lokasi_ref AS MATERIALIZED (
                /*
                 * Pengganti subquery NAMA_LOKASI. Subquery berkorelasi
                 * dijalankan sekali untuk tiap baris keluaran, sedangkan
                 * tabel bantu ini cukup sekali lalu disambung.
                 *
                 * DISTINCT ON memakai urutan fisik baris, meniru SQL Server
                 * yang mengambil baris pertama yang ditemukannya ketika ada
                 * lebih dari satu baris berkode sama.
                 */
                SELECT DISTINCT ON (kode)
                    kode, deskripsi
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
                /* Pengganti subquery NAMA_SEKTOR, cara yang sama. */
                SELECT DISTINCT ON (kode)
                    kode, deskripsi
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
            ppjb_aktif AS MATERIALIZED (
                /*
                 * PPJB aktif dikumpulkan lebih dulu menjadi tabel bantu
                 * berkunci sederhana. Sebelumnya sr_ppjb, sr_pembeli_ppjb,
                 * dan sr_nasabah disambung langsung memakai
                 * BTRIM(CAST(...)), dan perencana query tidak punya
                 * statistik untuk ekspresi semacam itu sehingga menaksir
                 * hasil gabungannya 24 miliar baris lalu memilih merge join
                 * berlapis. Dengan dikumpulkan dulu, sambungannya menjadi
                 * perbandingan kolom biasa antar tabel bantu yang kecil.
                 */
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
                FROM public.sr_ppjb AS ppjb
                WHERE (
                        ppjb.flag_aktif = 'A'
                        OR UPPER(BTRIM(COALESCE(
                               CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
                      )
                  AND ppjb.parent_id IS NULL
            ),
            pembeli_nasabah AS MATERIALIZED (
                /*
                 * Satu baris per pembeli aktif, bukan digabung menjadi satu
                 * teks, karena laporan desktop memang menampilkan satu baris
                 * untuk tiap pembeli.
                 */
                SELECT
                    BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    nasabah.nama AS nama
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
            plafond_kpr AS MATERIALIZED (
                /*
                 * Pengganti subquery PLAFOND_KPR. ISNULL(FLAG_KPR, 'T') = 'Y'
                 * pada desktop berarti hanya baris bertanda Y yang dijumlah,
                 * dan baris tanpa tanda tidak ikut.
                 */
                SELECT
                    BTRIM(CAST(jadwal.ppjb_id AS TEXT)) AS kunci_ppjb,
                    SUM(jadwal.jumlah) AS jumlah
                FROM public.sr_jadwal_angsuran AS jadwal
                WHERE UPPER(BTRIM(COALESCE(
                          CAST(jadwal.flag_kpr AS TEXT), 'T'))) = 'Y'
                GROUP BY 1
            ),
            hasil_dasar AS MATERIALIZED (

            SELECT
                BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                    || COALESCE(CAST(stok.nomor AS TEXT), '') AS "BLOK_NOMOR",
                nasabah.nama AS "NAMA",
                jaminan.pengajuan AS "PENGAJUAN",
                jaminan.tgl_pengajuan AS "TGL_PENGAJUAN",
                jaminan.no_jaminan AS "NO_JAMINAN",
                jaminan.tgl_jaminan AS "TGL_JAMINAN",
                jaminan.nama_bank AS "NAMA_BANK",
                jaminan.alamat_bank AS "ALAMAT_BANK",
                jaminan.jenis_jaminan AS "JENIS_JAMINAN",
                jaminan.nama_ambil AS "NAMA_AMBIL",
                jaminan.tgl_ambil AS "TGL_AMBIL",
                jaminan.no_lunas AS "NO_LUNAS",
                jaminan.tgl_lunas AS "TGL_LUNAS",
                jaminan.no_batal AS "NO_BATAL",
                jaminan.tgl_batal AS "TGL_BATAL",
                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                lokasi_ref.deskripsi AS "NAMA_LOKASI",
                sektor_ref.deskripsi AS "NAMA_SEKTOR",
                COALESCE(plafond_kpr.jumlah, 0) AS "PLAFOND_KPR",

                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR"

            FROM jaminan_terpilih AS jaminan

            INNER JOIN public.sr_sertipikat AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = jaminan.kunci_sertipikat

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            INNER JOIN ppjb_aktif AS ppjb
                ON ppjb.kunci_stok = stok.kunci_stok

            INNER JOIN pembeli_nasabah AS nasabah
                ON nasabah.kunci_ppjb = ppjb.kunci_ppjb

            LEFT JOIN lokasi_ref
                ON lokasi_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))
            LEFT JOIN sektor_ref
                ON sektor_ref.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
            LEFT JOIN plafond_kpr
                ON plafond_kpr.kunci_ppjb = ppjb.kunci_ppjb

            WHERE sertipikat.stok_id IS NOT NULL
              {$ajbFilterSql}
            )

            SELECT hasil_dasar.*
            FROM hasil_dasar
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
                        hasil_dasar."BLOK" >= :blok_akhir_blok_awal
                        AND hasil_dasar."BLOK" <= :blok_akhir_blok_akhir
                    )
                  )
            ORDER BY
                hasil_dasar."BLOK",
                hasil_dasar."NOMOR",
                hasil_dasar."NAMA",
                hasil_dasar."TGL_JAMINAN",
                hasil_dasar."NO_JAMINAN"
        SQL;

        $bindings = [
            'blok_awal_unit' => $blokAwal,
            'blok_akhir_unit' => $blokAkhir,

            // Dipertahankan persis seperti query desktop: keduanya BLOK_AKHIR.
            'blok_akhir_blok_awal' => $blokAkhir,
            'blok_akhir_blok_akhir' => $blokAkhir,

            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
            'jaminan_filter' => $jaminan,
            'jaminan_semua' => $jaminan,
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
        ];

        if ($tglAwalBank !== null && $tglAkhirBank !== null) {
            $bindings['tgl_awal_bank'] = $tglAwalBank;
            $bindings['tgl_akhir_bank'] = $tglAkhirBank;
        }

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    /**
     * Menyusun ulang SERTIPIKAT_ID milik sr_jaminan agar bisa disamakan
     * dengan sr_sertipikat.sertipikat_id.
     *
     * sr_sertipikat menyimpan teks lengkap seperti DBPSA-26099. Beberapa
     * tabel hasil migrasi menyimpan kunci yang sama sebagai numeric sehingga
     * awalannya terbuang; itu sudah terjadi pada sr_akta, sr_pengambilan,
     * sr_biaya_ajb, sr_peralihan, dan sr_sertipikat_idk.
     *
     * Awalannya TIDAK BOLEH ditebak. Ada dua awalan yang dipakai bersamaan,
     * DBPSA- dan DBPSS-, dan hampir seluruh angka muncul pada keduanya;
     * diukur pada database DTSA, 19.465 dari 23.308 baris sr_sertipikat_idk
     * angkanya ada di kedua keluarga. Salah pilih berarti data satu unit
     * menempel ke unit lain, dan itu tidak kelihatan di layar.
     *
     * Karena itu dipakai tiga cara berjenjang, dari yang paling pasti:
     *
     * 1. Nilainya masih membawa awalan sendiri -> dipakai apa adanya.
     *    Ini yang berlaku bila kolomnya ternyata bertipe teks.
     *
     * 2. sr_jaminan punya KD_PERUSAHAAN -> awalannya diambil dari unit itu
     *    lewat peta unit ke awalan yang dibaca dari sr_stok. Pasti benar,
     *    karena tiap unit hanya memakai satu awalan. Cara ini sudah terbukti
     *    pada fitur Rekap Estimasi Biaya AJB.
     *
     * 3. Tidak ada keduanya -> hanya angka yang menunjuk ke TEPAT SATU
     *    sertipikat yang dipakai. Barisnya bisa berkurang, tetapi yang
     *    tampil dijamin tidak nyasar ke unit lain. Uji kelayakan tanggal
     *    sengaja tidak dipakai di sini: jaminan bank terbit bertahun-tahun
     *    setelah sertipikatnya, sehingga kedua keluarga sama-sama terlihat
     *    masuk akal dan ujinya tidak memisahkan apa pun.
     *
     * Pemeriksaan skema pada berkas diagnostiknya akan menunjukkan cara
     * mana yang sebenarnya berlaku pada database ini.
     */
    private function kunciSertipikatJaminan(bool $pakaiUnit): string
    {
        $awalan = $pakaiUnit
            ? 'awalan_unit.awalan'
            : 'sertipikat_unik.awalan';

        return <<<SQL
            CASE
                WHEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
                ELSE {$awalan}
                     || BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
            END
            SQL;
    }

    /**
     * Peta unit ke awalan kunci, dibaca dari STOK_ID pada sr_stok.
     *
     * Diukur pada database DTSA: kedua puluh enam kode perusahaan
     * masing-masing hanya memakai satu awalan, misalnya DTSA dan SBKS
     * memakai DBPSA- sedangkan SSPG dan SPCK memakai DBPSS-. DISTINCT ON
     * mengambil yang terbanyak supaya tetap satu baris per unit seandainya
     * suatu saat ada unit yang datanya bercampur.
     */
    private function cteAwalanUnit(): string
    {
        return <<<SQL
        awalan_unit AS MATERIALIZED (
                SELECT DISTINCT ON (kode_unit) kode_unit, awalan
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), '')))
                            AS kode_unit,
                        REGEXP_REPLACE(BTRIM(CAST(stok_id AS TEXT)), '[0-9]+$', '')
                            AS awalan,
                        COUNT(*) AS jumlah
                    FROM public.sr_stok
                    WHERE stok_id IS NOT NULL
                    GROUP BY 1, 2
                ) AS daftar
                WHERE kode_unit <> ''
                ORDER BY kode_unit, jumlah DESC, awalan
            ),
            
        SQL;
    }

    /**
     * Angka yang menunjuk ke tepat satu sertipikat, beserta awalannya.
     *
     * Dipakai hanya bila sr_jaminan tidak membawa penanda unit. Angka yang
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
     * SEMUA: tanpa filter AKTA.
     * BELUM: persis query desktop -> tidak ada AKTA dengan NO_AKTA terisi.
     * SUDAH: persis query desktop -> ada AKTA dengan NO_AKTA terisi.
     *
     * NOT IN pada query desktop diganti NOT EXISTS supaya tetap benar bila
     * subquerynya berisi NULL; pada NOT IN satu NULL saja membuat seluruh
     * hasilnya kosong.
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
     * Query desktop mengizinkan kedua tanggal NULL. Pada web:
     * - kedua tanggal diisi -> filter tanggal aktif;
     * - keduanya kosong -> tidak memakai filter tanggal.
     *
     * Batas atas dipertahankan inklusif seperti query desktop, bukan
     * diubah menjadi tanggal akhir + 1 hari.
     */
    private function buildTanggalFilterSql(?string $tglAwalBank, ?string $tglAkhirBank): string
    {
        if ($tglAwalBank === null || $tglAkhirBank === null) {
            return '';
        }

        return <<<SQL
AND jaminan.tgl_jaminan >= CAST(:tgl_awal_bank AS DATE)
                  AND jaminan.tgl_jaminan <= CAST(:tgl_akhir_bank AS DATE)
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
    private function normalizeDateNullable($value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $formats = ['!Y-m-d', '!Ymd', '!d/m/Y', '!m/d/Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $text);
            $errors = DateTimeImmutable::getLastErrors();
            $valid = $date !== false
                && ($errors === false
                    || ($errors['warning_count'] === 0
                        && $errors['error_count'] === 0));

            if ($valid) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException(
            'Format tanggal tidak valid: ' . $text . '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizeJenisJaminan($value): string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '' || $normalized === 'SEMUA') {
            return '*';
        }

        return in_array($normalized, self::JENIS_JAMINAN_VALID, true)
            ? $normalized
            : '*';
    }

    private function normalizeAjbStatus($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['SUDAH', 'BELUM', 'SEMUA'], true)
            ? $normalized
            : 'SEMUA';
    }
}
