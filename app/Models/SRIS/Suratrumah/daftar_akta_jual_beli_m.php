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
            SELECT DISTINCT ON (UPPER(BTRIM(COALESCE(lokasi.{$lokasiKode}, ''))))
                UPPER(BTRIM(COALESCE(lokasi.{$lokasiKode}, ''))) AS "KD_LOKASI",
                BTRIM(COALESCE(lokasi.deskripsi, '')) AS "DESKRIPSI"
            FROM public.sr_lokasi AS lokasi
            WHERE UPPER(BTRIM(COALESCE(lokasi.{$lokasiKode}, ''))) <> ''
            ORDER BY
                UPPER(BTRIM(COALESCE(lokasi.{$lokasiKode}, ''))),
                BTRIM(COALESCE(lokasi.deskripsi, ''))
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
                UPPER(BTRIM(COALESCE(sektor.{$sektorKode}, ''))) AS "KD_SEKTOR",
                BTRIM(COALESCE(sektor.deskripsi, '')) AS "DESKRIPSI",
                UPPER(BTRIM(COALESCE(sektor.{$sektorPerusahaan}, '')))
                    AS "KD_PERUSAHAAN"
            FROM public.sr_sektor AS sektor
            WHERE UPPER(BTRIM(COALESCE(sektor.flag_aktif, ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(sektor.{$sektorKode}, ''))) <> ''
              AND (
                    UPPER(BTRIM(COALESCE(sektor.{$sektorPerusahaan}, '')))
                        = :kd_perusahaan
                    OR UPPER(BTRIM(COALESCE(sektor.{$sektorPerusahaan}, '')))
                        = ''
                  )
            ORDER BY
                BTRIM(COALESCE(sektor.deskripsi, '')),
                UPPER(BTRIM(COALESCE(sektor.{$sektorKode}, '')))
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

        $sql = <<<SQL
            SELECT
                UPPER(BTRIM(COALESCE(stok.blok, ''))) || '/'
                    || UPPER(BTRIM(COALESCE(stok.nomor, ''))) AS "BLOK_NOMOR",
                stok.blok AS "BLOK",
                stok.nomor AS "NOMOR",
                nasabah.nama AS "NAMA",

                stok.luas_tanah AS "LUAS_TANAH",
                stok.luas_bangunan AS "LUAS_BANGUNAN",

                akta.no_notaris AS "NO_NOTARIS",
                akta.tgl_notaris AS "TGL_NOTARIS",
                akta.notaris AS "NOTARIS",
                akta.no_akta AS "NO_AKTA",
                tgl_ref.tgl_akta_valid AS "TGL_AKTA",
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

                (
                    SELECT lokasi.deskripsi
                    FROM public.sr_lokasi AS lokasi
                    WHERE UPPER(BTRIM(COALESCE(lokasi.{$lokasiKode}, '')))
                        = UPPER(BTRIM(COALESCE(stok.{$stokLokasi}, '')))
                    LIMIT 1
                ) AS "NAMA_LOKASI",
                (
                    SELECT sektor.deskripsi
                    FROM public.sr_sektor AS sektor
                    WHERE UPPER(BTRIM(COALESCE(sektor.{$sektorKode}, '')))
                        = UPPER(BTRIM(COALESCE(stok.{$stokSektor}, '')))
                    LIMIT 1
                ) AS "NAMA_SEKTOR",
                (
                    SELECT angsuran.tgl_kuitansi
                    FROM public.sr_angsuran AS angsuran
                    WHERE BTRIM(CAST(angsuran.ppjb_id AS TEXT))
                        = BTRIM(CAST(ppjb.ppjb_id AS TEXT))
                      AND UPPER(BTRIM(COALESCE(angsuran.kd_transaksi, '')))
                        = 'BBN'
                    LIMIT 1
                ) AS "TGL_KUITANSI_BBN"

            FROM public.sr_akta AS akta

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

            /*
             * SERTIPIKAT_ID ditulis berbeda di kedua tabel karena migrasi
             * tidak utuh. sr_sertipikat menyimpan teks berawalan seperti
             * DBPSA-18784, sedangkan sr_akta terlanjur dibuat bertipe
             * numeric sehingga awalannya terbuang dan hanya menyisakan
             * 18784. Diukur pada database DTSA: cocok apa adanya 0 baris,
             * cocok setelah awalan dibuang 17.407 dari 17.407 baris. Karena
             * itu awalan dibuang lebih dulu di kedua sisi.
             *
             * Hanya kolom ini yang diperlakukan begitu. PPJB_ID, STOK_ID,
             * dan NASABAH_ID sudah sama bentuknya di semua tabel, jadi
             * dibandingkan apa adanya.
             */
            INNER JOIN public.sr_sertipikat AS sertipikat
                ON REGEXP_REPLACE(
                       BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )
                 = REGEXP_REPLACE(
                       BTRIM(CAST(akta.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )

            LEFT JOIN public.sr_pengambilan AS pengambilan
                ON REGEXP_REPLACE(
                       BTRIM(CAST(pengambilan.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )
                 = REGEXP_REPLACE(
                       BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)),
                       '^[^0-9]+', ''
                   )

            INNER JOIN public.sr_stok AS stok
                ON BTRIM(CAST(stok.stok_id AS TEXT))
                 = BTRIM(CAST(sertipikat.stok_id AS TEXT))

            /*
             * Pada database legacy kolom tanggal dapat berisi karakter
             * kosong/tidak valid, sehingga tanggalnya dikonversi secara
             * aman lebih dulu. Ini padanan OUTER APPLY + ISDATE() desktop.
             */
            LEFT JOIN LATERAL (
                SELECT CASE
                           WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                                ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                           THEN CAST(akta.tgl_akta AS TIMESTAMP)
                       END AS tgl_akta_valid
            ) AS tgl_ref ON TRUE

            WHERE UPPER(BTRIM(COALESCE(stok.flag_aktif, ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(ppjb.flag_aktif, ''))) = 'A'
              AND UPPER(BTRIM(COALESCE(pembeli_ppjb.flag_aktif, ''))) = 'Y'
              AND ppjb.parent_id IS NULL
              AND (
                    (
                        UPPER(BTRIM(COALESCE(stok.blok, ''))) || '/'
                        || UPPER(BTRIM(COALESCE(stok.nomor, '')))
                        BETWEEN :blok_awal_unit AND :blok_akhir_unit
                    )
                    OR
                    (
                        UPPER(BTRIM(COALESCE(stok.blok, '')))
                        BETWEEN :blok_awal_blok AND :blok_akhir_blok
                    )
                  )
              AND tgl_ref.tgl_akta_valid >= CAST(:tgl_awal AS DATE)
              AND tgl_ref.tgl_akta_valid < CAST(:tgl_akhir AS DATE)
              AND UPPER(BTRIM(COALESCE(stok.{$stokPerusahaan}, '')))
                    = :perusahaan
              AND (
                    UPPER(BTRIM(COALESCE(stok.{$stokLokasi}, '')))
                        = :lokasi_filter
                    OR :lokasi_semua = '*'
                  )
              AND (
                    UPPER(BTRIM(COALESCE(stok.{$stokSektor}, '')))
                        = :sektor_filter
                    OR :sektor_semua = '*'
                  )
              AND stok.blok IS NOT NULL
              AND stok.nomor IS NOT NULL
              AND sertipikat.stok_id IS NOT NULL

            ORDER BY
                UPPER(BTRIM(COALESCE(stok.{$stokSektor}, ''))) ASC,
                UPPER(BTRIM(COALESCE(stok.blok, ''))) ASC,
                CASE
                    WHEN BTRIM(COALESCE(stok.nomor, '')) ~ '^[0-9]+$'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN BTRIM(COALESCE(stok.nomor, '')) ~ '^[0-9]+$'
                    THEN LPAD(BTRIM(stok.nomor), 50, '0')
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
