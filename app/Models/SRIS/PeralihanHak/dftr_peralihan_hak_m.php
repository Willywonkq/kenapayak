<?php

// MODEL POSTGRESQL V1 - DAFTAR/REKAP PERALIHAN HAK

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260916
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\PeralihanHak;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_peralihan_hak_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Master cluster, sama dengan fitur Rekap Estimasi Biaya AJB.
     *
     * Kode perusahaan yang kosong ikut ditampilkan. Pada hasil migrasi
     * sebagian baris master tidak membawa kode perusahaan, sedangkan desktop
     * tetap memakai sektornya lewat STOK.
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
        $sektorPerusahaan = $this->kolomKode('sr_sektor', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);

        $sql = <<<SQL
            SELECT
                UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                    AS "KD_CLUSTER",
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
     * Entry utama data laporan.
     *
     * Query desktop untuk seluruh kombinasi Status Entry dan Status Approve
     * isinya sama persis; yang membedakan hanya nilai kedua parameternya.
     * Karena itu di sini cukup satu query dengan dua parameter tersebut.
     */
    public function obtainRekapPeralihanHak($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );
        $cluster = $this->normalizeText($request->cluster ?? '*');
        $stsEntry = $this->normalizeStatus($request->sts_entry ?? '*');
        $stsApprove = $this->normalizeStatus($request->sts_approve ?? '*');

        if ($perusahaan === '') {
            throw new RuntimeException('Kode perusahaan/unit tidak tersedia.');
        }

        if ($cluster === '') {
            $cluster = '*';
        }

        return $this->obtainPeralihan(
            $perusahaan,
            $cluster,
            $stsEntry,
            $stsApprove,
            $this->normalizeDate($request->tgl_awal ?? date('Y-m-d')),
            $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'), 1)
        );
    }

    /**
     * Query laporan.
     *
     * Penyesuaian terhadap query desktop dijelaskan pada komentar di dalam
     * SQL.
     *
     * Padanan dialek yang dipakai: ISNULL -> COALESCE, + -> ||,
     * GETDATE() -> CURRENT_TIMESTAMP, SELECT TOP (1) -> DISTINCT ON,
     * OUTER APPLY -> ekspresi CASE di dalam CTE, ISDATE() -> kawal regex,
     * NOT LIKE '%[^0-9]%' -> ~ '^[0-9]+$',
     * RIGHT(REPLICATE('0',50)+x,50) -> LPAD(x,50,'0').
     */
    private function obtainPeralihan(
        string $perusahaan,
        string $cluster,
        string $stsEntry,
        string $stsApprove,
        string $tglAwal,
        string $tglAkhirEksklusif
    ): array {
        $stokPerusahaan = $this->kolomKode('sr_stok', [
            'kd_perusahaan', 'kd_unit', 'kd_pt',
        ]);
        $stokSektor = $this->kolomKode('sr_stok', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        /*
         * Pada hasil migrasi kolom jenis dan tipe bangunan di sr_stok bernama
         * kd_jenis_bgn dan kd_tipe_bgn, sedangkan di sr_tipe tetap kd_jenis
         * dan kd_tipe. Sudah terbukti pada fitur Rekap Estimasi Biaya AJB.
         */
        $stokJenis = $this->kolomKode('sr_stok', ['kd_jenis_bgn', 'kd_jenis']);
        $stokTipe = $this->kolomKode('sr_stok', ['kd_tipe_bgn', 'kd_tipe']);

        $sql = <<<SQL
            WITH peralihan_terpilih AS (
                /*
                 * Peralihan disaring tanggal lebih dulu supaya yang dijoin
                 * tinggal sedikit. Kunci pada database ini dibandingkan lewat
                 * BTRIM dan CAST, dan perbandingan semacam itu tidak bisa
                 * memakai index, sehingga menyempitkan lebih dulu jauh lebih
                 * murah daripada menyaring di belakang.
                 *
                 * Ekspresi CASE di sini padanan OUTER APPLY + ISDATE()
                 * desktop: pada database legacy kolom tanggal dapat berisi
                 * nilai yang tidak valid.
                 *
                 * Batas atas dibuat eksklusif (tanggal akhir + 1 hari) agar
                 * baris yang jamnya bukan 00:00 pada tanggal akhir tetap
                 * ikut. Query asli memakai <= tanggal akhir, sehingga baris
                 * seperti itu terlewat.
                 */
                SELECT
                    peralihan.*,
                    CASE
                        WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                             ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                        THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                    END AS tgl_peralihan_valid,
                    BTRIM(CAST(peralihan.peralihan_id AS TEXT)) AS kunci_peralihan,
                    BTRIM(CAST(peralihan.ppjb_id AS TEXT)) AS kunci_ppjb
                FROM public.sr_peralihan AS peralihan
                WHERE CASE
                          WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                      END >= CAST(:tgl_awal AS DATE)
                  AND CASE
                          WHEN COALESCE(CAST(peralihan.tgl_peralihan AS TEXT), '')
                               ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                          THEN CAST(peralihan.tgl_peralihan AS TIMESTAMP)
                      END < CAST(:tgl_akhir_eksklusif AS DATE)
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(peralihan.flag_entry AS TEXT), 'T')))
                            = :sts_entry_filter
                        OR :sts_entry_semua = '*'
                      )
            ),
            stok_terpilih AS (
                SELECT
                    stok.*,
                    BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
                FROM public.sr_stok AS stok
                WHERE UPPER(BTRIM(COALESCE(CAST(stok.{$stokPerusahaan} AS TEXT), '')))
                        = :perusahaan
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :cluster_filter
                        OR :cluster_semua = '*'
                      )
            ),
            angsuran_bph AS MATERIALIZED (
                /*
                 * Pengganti subquery TGL_KUITANSI_BPH.
                 *
                 * Query desktop memakai SELECT TOP (1) tanpa ORDER BY, jadi
                 * barisnya dipilih sekenanya: yang pertama ditemukan saat
                 * tabel dibaca berurutan, yaitu yang letak fisiknya paling
                 * awal. DISTINCT ON di sini juga mengurutkan lewat ctid
                 * supaya baris yang terpilih sama persis.
                 *
                 * Bentuk tabel bantu juga jauh lebih ringan. Subquery
                 * berkorelasi dijalankan sekali untuk setiap baris hasil,
                 * dan sr_angsuran termasuk tabel besar.
                 */
                SELECT DISTINCT ON (kode) kode, tgl_kuitansi
                FROM (
                    SELECT
                        BTRIM(CAST(x.ppjb_id AS TEXT)) AS kode,
                        x.tgl_kuitansi AS tgl_kuitansi,
                        x.ctid AS urutan_fisik
                    FROM public.sr_angsuran AS x
                    WHERE UPPER(BTRIM(COALESCE(CAST(x.kd_transaksi AS TEXT), '')))
                          = 'BPH'
                ) AS daftar
                ORDER BY kode, urutan_fisik
            )

            SELECT
                UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                    || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                    AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",

                stok.luas_tanah AS "LUAS_TANAH",
                ppjb.luas_bangunan AS "LUAS_BANGUNAN",
                stok.luas_semi_gross AS "LUAS_SEMI_GROSS",
                tipe.deskripsi AS "TIPE_BANGUNAN",
                sektor.deskripsi AS "NAMA_CLUSTER",

                peralihan.peralihan_id AS "PERALIHAN_ID",
                peralihan.tgl_peralihan_valid AS "TGL_PERALIHAN",
                peralihan.notaris AS "NOTARIS",
                peralihan.tgl_notaris AS "TGL_NOTARIS",
                peralihan.no_kuitansi AS "NO_KUITANSI",
                peralihan.tgl_kuitansi AS "TGL_KUITANSI",
                peralihan.jml_kuitansi AS "JML_KUITANSI",
                peralihan.harga_pasar AS "HARGA_PASAR",
                peralihan.nm_agen AS "NM_AGEN",
                peralihan.nm_sales AS "NM_SALES",
                peralihan.no_telp AS "NO_TELP",

                nasabah_lama.nama AS "PEMBELI_LAMA",
                nasabah_baru.nama AS "PEMBELI_BARU",

                CASE
                    WHEN UPPER(BTRIM(COALESCE(CAST(peralihan.flag_entry AS TEXT), 'T')))
                         = 'Y'
                    THEN 'Sudah Entry'
                    ELSE 'Belum Entry'
                END AS "STS_PEMBELI_BARU",

                angsuran_bph.tgl_kuitansi AS "TGL_KUITANSI_BPH",

                ppjb.dpp AS "HARGA_EXCLUDE_PPN",
                ppjb.harga_jual AS "HARGA_INCLUDE_PPN",

                stok.{$stokSektor} AS "KD_SEKTOR",
                stok.{$stokPerusahaan} AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK"

            /*
             * Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi
             * antar tabel tidak berubah, termasuk PEMBELI_LAMA dan
             * PEMBELI_BARU yang tetap dipasangkan lewat PERALIHAN_ID.
             */
            FROM peralihan_terpilih AS peralihan

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT)) = peralihan.kunci_ppjb

            INNER JOIN public.sr_pembeli_lama AS pembeli_lama
                ON BTRIM(CAST(pembeli_lama.peralihan_id AS TEXT))
                 = peralihan.kunci_peralihan

            INNER JOIN public.sr_pembeli_baru AS pembeli_baru
                ON BTRIM(CAST(pembeli_baru.peralihan_id AS TEXT))
                 = peralihan.kunci_peralihan

            INNER JOIN stok_terpilih AS stok
                ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

            /*
             * Desktop memakai INNER JOIN ke NASABAH lewat subquery
             * SELECT TOP (1). Di sini dipakai LEFT JOIN langsung ke
             * sr_nasabah, karena nasabah_id adalah kuncinya sehingga satu
             * baris saja yang cocok, dan pasangannya yang belum ikut
             * tersalin tidak ikut menghapus barisnya.
             */
            LEFT JOIN public.sr_nasabah AS nasabah_lama
                ON BTRIM(CAST(nasabah_lama.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli_lama.nasabah_id AS TEXT))

            LEFT JOIN public.sr_nasabah AS nasabah_baru
                ON BTRIM(CAST(nasabah_baru.nasabah_id AS TEXT))
                 = BTRIM(CAST(pembeli_baru.nasabah_id AS TEXT))

            /*
             * Desktop memakai INNER JOIN ke TIPE dan SEKTOR. Di PostgreSQL
             * pasangan KD_JENIS dengan KD_TIPE belum lengkap, sudah terukur
             * pada model Serah Terima sebanyak 1.292 dari 3.036 pasangan
             * yang dipakai sr_stok. INNER JOIN akan menghapus unit yang di
             * desktop tetap tampil, sehingga di sini memakai LEFT JOIN.
             * Unitnya tetap ada dan hanya kolom tipe atau clusternya kosong.
             */
            LEFT JOIN public.sr_tipe AS tipe
                ON BTRIM(CAST(tipe.kd_jenis AS TEXT))
                 = BTRIM(CAST(stok.{$stokJenis} AS TEXT))
               AND BTRIM(CAST(tipe.kd_tipe AS TEXT))
                 = BTRIM(CAST(stok.{$stokTipe} AS TEXT))

            LEFT JOIN public.sr_sektor AS sektor
                ON UPPER(BTRIM(COALESCE(CAST(sektor.{$sektorKode} AS TEXT), '')))
                 = UPPER(BTRIM(COALESCE(CAST(stok.{$stokSektor} AS TEXT), '')))

            LEFT JOIN angsuran_bph
                ON angsuran_bph.kode = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

            /*
             * Status approval. Bentuk ISNULL((SELECT COUNT(*) ...), 0) > 0
             * pada query asli ditulis ulang menjadi EXISTS dengan arti yang
             * sama persis: COUNT(*) pada subquery skalar tidak pernah NULL,
             * dan "lebih dari nol" sama dengan "ada barisnya".
             */
            WHERE (
                    (
                        'T' = :sts_approve_belum
                        AND EXISTS (
                            SELECT 1
                            FROM public.sr_approval AS a
                            WHERE BTRIM(CAST(a.parent_id AS TEXT))
                                = peralihan.kunci_peralihan
                              AND BTRIM(CAST(a.jenis AS TEXT)) = '6'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve1 AS TEXT), 'T'))) = 'T'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve2 AS TEXT), 'T'))) = 'T'
                        )
                    )
                    OR
                    (
                        'Y' = :sts_approve_sudah
                        AND EXISTS (
                            SELECT 1
                            FROM public.sr_approval AS a
                            WHERE BTRIM(CAST(a.parent_id AS TEXT))
                                = peralihan.kunci_peralihan
                              AND BTRIM(CAST(a.jenis AS TEXT)) = '6'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve1 AS TEXT), 'T'))) = 'Y'
                              AND UPPER(BTRIM(COALESCE(
                                      CAST(a.approve2 AS TEXT), 'T'))) = 'Y'
                        )
                    )
                    OR
                    (
                        '*' = :sts_approve_semua
                    )
                  )

            /*
             * Query asli tidak memiliki ORDER BY. Urutan ditambahkan supaya
             * baris dapat dikelompokkan per cluster pada laporan, persis
             * seperti tampilan desktop.
             */
            ORDER BY
                BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')) ASC,
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
                peralihan.tgl_peralihan_valid ASC,
                peralihan.peralihan_id ASC
        SQL;

        return DB::connection(self::CONNECTION)->select($sql, [
            'sts_entry_filter' => $stsEntry,
            'sts_entry_semua' => $stsEntry,
            'tgl_awal' => $tglAwal,
            'tgl_akhir_eksklusif' => $tglAkhirEksklusif,
            'cluster_filter' => $cluster,
            'cluster_semua' => $cluster,
            'perusahaan' => $perusahaan,
            'sts_approve_belum' => $stsApprove,
            'sts_approve_sudah' => $stsApprove,
            'sts_approve_semua' => $stsApprove,
        ]);
    }

    /**
     * Label status untuk header laporan.
     */
    public function getStatusEntryLabel(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'Y' => 'Sudah Entry',
            'T' => 'Belum Entry',
            default => 'Semua',
        };
    }

    public function getStatusApproveLabel(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'Y' => 'Sudah',
            'T' => 'Belum',
            default => 'Semua',
        };
    }

    /**
     * Status hanya mengenal tiga nilai: 'Y', 'T', dan '*' untuk semua.
     */
    private function normalizeStatus($value): string
    {
        $status = strtoupper(trim((string) $value));

        return in_array($status, ['Y', 'T'], true) ? $status : '*';
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
