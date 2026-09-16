<?php

// MODEL POSTGRESQL V1 - DAFTAR AKTA JUAL BELI

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260916
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class daftar_akta_jual_beli_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Master lokasi.
     *
     * Data lokasi memang tersimpan di tabel LOKASI. Query laporan desktop
     * membuktikannya lewat dua baris berikut:
     *
     *     ( SELECT DESKRIPSI FROM LOKASI WHERE KD_LOKASI = STOK.KD_LOKASI )
     *     ( STOK.KD_LOKASI = :lokasi OR :lokasi = '*' )
     *
     * Daftar dibaca langsung dan apa adanya seperti lookup desktop, tanpa
     * disaring lewat STOK.
     */
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
                UPPER(BTRIM(COALESCE(CAST(lokasi.{$lokasiKode} AS TEXT), ''))) AS "KD_LOKASI",
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

    /**
     * Master sektor, mengikuti pola fitur Daftar Sertipikat Pecahan.
     *
     * Kode perusahaan yang kosong ikut ditampilkan. Pada hasil migrasi
     * sebagian baris master tidak membawa kode perusahaan, sedangkan desktop
     * tetap memakai sektornya lewat STOK.
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
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), ''))) AS "KD_SEKTOR",
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
     * Laporan Daftar Akta Jual Beli.
     *
     * Query mengikuti query desktop, dengan penyesuaian berikut:
     *
     * 1. Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi antar
     *    tabel dan seluruh kondisi WHERE tidak berubah.
     * 2. Batas blok bawah pada cabang kedua memakai :blok_awal_blok. Pada
     *    query asli cabang itu tertulis
     *    ( STOK.BLOK >= :BLOK_AKHIR AND STOK.BLOK <= :BLOK_AKHIR ),
     *    sehingga hanya cocok untuk satu blok saja.
     * 3. Batas tanggal atas dibuat eksklusif (tanggal akhir + 1 hari) agar
     *    baris yang jam-nya bukan 00:00 pada tanggal akhir tetap ikut,
     *    sama seperti fitur lain di aplikasi ini.
     * 4. NASABAH disambung dengan LEFT JOIN, bukan INNER JOIN seperti
     *    desktop. Di SQL Server seluruh 38.821 baris punya pasangan,
     *    sedangkan di PostgreSQL hanya 34.560 dari 63.447. INNER JOIN akan
     *    membuang unit yang di desktop tetap tampil; dengan LEFT JOIN
     *    unitnya tetap ada dan hanya kolom nasabahnya yang kosong.
     *
     * Padanan dialek yang dipakai: ISNULL -> COALESCE, + -> ||,
     * GETDATE() -> CURRENT_TIMESTAMP, SELECT TOP (1) -> LIMIT 1,
     * OUTER APPLY -> LEFT JOIN LATERAL ... ON TRUE, ISDATE() -> kawal regex,
     * NOT LIKE '%[^0-9]%' -> ~ '^[0-9]+$',
     * RIGHT(REPLICATE('0',50)+x,50) -> LPAD(x,50,'0').
     */
    public function obtainDaftarAktaJualBeli($request): array
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

        $tglAwal = $this->normalizeDate($request->tgl_awal ?? date('Y-m-d'));
        $tglAkhirEksklusif = $this->normalizeDate(
            $request->tgl_akhir ?? date('Y-m-d'),
            1
        );

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

        /*
         * Nama kolom kode berbeda-beda antar hasil migrasi, sama seperti
         * pada model Serah Terima. Hanya kolom kode yang dicari seperti ini;
         * kolom lainnya ditulis apa adanya.
         */
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
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $kunciAkta = $this->kunciSertipikat('akta.sertipikat_id');

        /*
         * Susunan query sengaja dibuat menyempit lebih dulu.
         *
         * Seluruh kunci pada database ini harus dibandingkan lewat
         * BTRIM(CAST(...)), dan perbandingan semacam itu tidak bisa memakai
         * index. Kalau tabel besar dijoin apa adanya, PostgreSQL membaca
         * habis semuanya berkali-kali. Karena itu akta disaring tanggal
         * lebih dulu dan stok disaring unit, lokasi, sektor, serta blok
         * lebih dulu, sehingga yang dijoin tinggal sedikit.
         *
         * Ketiga kolom yang dulu diambil lewat subquery berkorelasi kini
         * disiapkan sebagai tabel kecil dan disambung dengan LEFT JOIN.
         * Subquery berkorelasi dijalankan sekali untuk setiap baris hasil,
         * dan untuk sr_angsuran yang besar itu berarti membacanya ratusan
         * kali. Sekarang tabel itu dibaca satu kali saja.
         *
         * Ketiga subquery itu memakai LIMIT 1 tanpa ORDER BY, jadi barisnya
         * dipilih sekenanya: yang pertama ditemukan saat tabel dibaca
         * berurutan, yaitu yang letak fisiknya paling awal. Supaya nilai
         * yang tampil tidak berubah sedikit pun, DISTINCT ON di sini juga
         * mengurutkan berdasarkan letak fisik lewat ctid, bukan berdasarkan
         * tanggal. Satu PPJB yang punya lebih dari satu kuitansi BBN tetap
         * menampilkan tanggal yang sama seperti sebelumnya.
         *
         * Susunan kolom keluaran tidak berubah sama sekali.
         */
        $sql = <<<SQL
            WITH akta_terpilih AS (
                SELECT
                    akta.*,
                    CASE
                        WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(akta.tgl_akta AS TIMESTAMP)
                    END AS tgl_akta_valid,
                    {$kunciAkta} AS kunci_sertipikat
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
                      END < CAST(:tgl_akhir AS DATE)
            ),
            stok_terpilih AS (
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
            ),
            lokasi_unik AS (
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
            sektor_unik AS (
                SELECT DISTINCT ON (kode) kode, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                            AS kode,
                        sektor.deskripsi AS deskripsi,
                        sektor.ctid AS urutan_fisik
                    FROM public.sr_sektor AS sektor
                ) AS daftar
                ORDER BY kode, urutan_fisik
            ),
            angsuran_bbn AS (
                SELECT DISTINCT ON (kode) kode, tgl_kuitansi
                FROM (
                    SELECT
                        BTRIM(CAST(angsuran.ppjb_id AS TEXT)) AS kode,
                        angsuran.tgl_kuitansi AS tgl_kuitansi,
                        angsuran.ctid AS urutan_fisik
                    FROM public.sr_angsuran AS angsuran
                    WHERE UPPER(BTRIM(COALESCE(
                              CAST(angsuran.kd_transaksi AS TEXT), '')))
                          = 'BBN'
                ) AS daftar
                ORDER BY kode, urutan_fisik
            )

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) AS "BLOK_NOMOR",
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
                akta.tgl_input AS "TGL_INPUT",
                akta.ttd_akta AS "TTD_AKTA",
                akta.tgl_entry AS "TGL_ENTRY",
                akta.user_entry AS "USER_ENTRY",

                pengambilan.tgl_ambil_akta AS "TGL_AMBIL_AKTA",
                pengambilan.tgl_cetak_akta AS "TGL_CETAK_AKTA",

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

                lokasi_unik.deskripsi AS "NAMA_LOKASI",
                sektor_unik.deskripsi AS "NAMA_SEKTOR",
                angsuran_bbn.tgl_kuitansi AS "TGL_KUITANSI_BBN"

            FROM akta_terpilih AS akta

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
                 = BTRIM(CAST(akta.ppjb_id AS TEXT))

            INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
                ON BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))
                 = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            /*
             * Desktop memakai INNER JOIN ke NASABAH. Di PostgreSQL sebagian
             * pasangannya belum ikut tersalin, sehingga INNER JOIN akan
             * menghapus unit yang di desktop tetap tampil.
             */
            LEFT JOIN public.sr_nasabah AS nasabah
                ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

            INNER JOIN public.sr_sertipikat AS sertipikat
                ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                 = akta.kunci_sertipikat

            /*
             * SERTIPIKAT_ID pada sr_pengambilan juga kehilangan awalannya.
             * Kedua sisi sama-sama dibuang awalannya di sini, dan hasilnya
             * sama dengan menyusun ulang awalan seperti pada join di atas,
             * karena awalan pada sertipikat memang sudah dipastikan benar
             * oleh join tersebut. Bedanya, bentuk ini hanya menyangkut dua
             * tabel sehingga PostgreSQL bisa memakai hash join.
             */
            LEFT JOIN public.sr_pengambilan AS pengambilan
                ON REGEXP_REPLACE(
                       BTRIM(CAST(pengambilan.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )
                 = REGEXP_REPLACE(
                       BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            LEFT JOIN lokasi_unik
                ON lokasi_unik.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokLokasi} AS TEXT), '')))

            LEFT JOIN sektor_unik
                ON sektor_unik.kode
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))

            LEFT JOIN angsuran_bbn
                ON angsuran_bbn.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')))
                    = 'Y'
              AND ppjb.parent_id IS NULL
              AND sertipikat.stok_id IS NOT NULL

            ORDER BY
                UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), ''))) ASC,
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
            'tgl_akhir' => $tglAkhirEksklusif,
            'perusahaan' => $perusahaan,
            'lokasi_filter' => $lokasi,
            'lokasi_semua' => $lokasi,
            'sektor_filter' => $sektor,
            'sektor_semua' => $sektor,
        ]);
    }

    /**
     * Menyusun ulang SERTIPIKAT_ID agar bisa disamakan dengan
     * sr_sertipikat.sertipikat_id.
     *
     * sr_sertipikat menyimpan teks lengkap seperti DBPSA-18784, sedangkan
     * sr_akta dan sr_pengambilan bertipe numeric sehingga awalannya terbuang
     * dan hanya menyisakan 18784.
     *
     * Awalannya tidak boleh sekadar dibuang dari sisi sertipikat. Pada
     * database DTSA ada DUA awalan yang dipakai bersamaan, DBPSA- dan
     * DBPSS-, dan setiap angka muncul pada keduanya. Membuang awalan
     * membuat satu akta menemukan dua sertipikat sekaligus, sehingga
     * barisnya berganda dan sebagiannya menunjuk unit yang salah.
     *
     * Awalan yang benar diambil dari PPJB_ID pada baris akta itu sendiri,
     * karena kolom itu selamat sebagai teks lengkap. Akta dengan PPJB_ID
     * DBPSA-18784 berarti sertipikatnya DBPSA- ditambah angkanya.
     *
     * Bila nilainya ternyata sudah membawa awalan sendiri, nilainya dipakai
     * apa adanya. Bila PPJB_ID tidak berawalan, angkanya juga dipakai apa
     * adanya. Jadi skema yang kuncinya sudah konsisten tidak ikut berubah.
     */
    private function kunciSertipikat(string $kolom): string
    {
        return <<<SQL
            CASE
                WHEN BTRIM(CAST({$kolom} AS TEXT)) !~ '^[0-9]+$'
                THEN BTRIM(CAST({$kolom} AS TEXT))
                WHEN BTRIM(CAST(akta.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
                THEN REGEXP_REPLACE(
                         BTRIM(CAST(akta.ppjb_id AS TEXT)), '[0-9]+\$', ''
                     ) || BTRIM(CAST({$kolom} AS TEXT))
                ELSE BTRIM(CAST({$kolom} AS TEXT))
            END
            SQL;
    }

    /**
     * Memilih nama kolom kode yang benar-benar ada pada tabel hasil migrasi.
     * Hanya dipakai untuk kolom kode, karena penamaannya berbeda-beda antar
     * unit. Sama seperti pada model Serah Terima yang sudah dimigrasi.
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
     * Mendukung nilai HTML date (Y-m-d) dan dua format slash umum.
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
            'Format tanggal tidak valid: ' . $text .
            '. Gunakan format YYYY-MM-DD.'
        );
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }
}
