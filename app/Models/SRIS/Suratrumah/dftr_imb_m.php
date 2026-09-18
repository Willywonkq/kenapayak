<?php

// MODEL POSTGRESQL V1 - DAFTAR REKAPITULASI IMB

// MODEL VERSION POSTGRES-WEB-SRIS-V1-20260918
// Sumber query: aplikasi desktop SRIS / SQL Server, dialihkan ke PostgreSQL.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class dftr_imb_m extends Model
{
    use HasFactory;

    /**
     * Koneksi PostgreSQL yang sudah ada pada config/database.php.
     * Tabel hasil migrasi memakai awalan sr_ pada schema public.
     */
    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Parameter "lokasi" pada query desktop sebenarnya memfilter KD_SEKTOR:
     *     STOK.KD_SEKTOR = :lokasi
     * Jadi lookup di web tetap memakai master sektor, sama seperti
     * model SQL Server sebelumnya.
     */
    public function obtainLokasi($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $sektorKode = $this->kolomKode('sr_sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        $syaratPerusahaan = $this->adaKolom('sr_sektor', 'kd_perusahaan')
            ? "AND (
                    sektor.kd_perusahaan = :kd_perusahaan_langsung
                    OR UPPER(BTRIM(COALESCE(
                           CAST(sektor.kd_perusahaan AS TEXT), '')))
                        = :kd_perusahaan
                 )"
            : '';

        $kolomPerusahaan = $this->adaKolom('sr_sektor', 'kd_perusahaan')
            ? 'sektor.kd_perusahaan'
            : "CAST(NULL AS TEXT)";

        $sql = <<<SQL
            SELECT
                sektor.{$sektorKode} AS "KD_LOKASI",
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

        $bindings = ['kd_perusahaan' => $kdPerusahaan];

        if ($syaratPerusahaan !== '') {
            $bindings['kd_perusahaan_langsung'] = $kdPerusahaan;
        } else {
            /*
             * Tanpa kolom kd_perusahaan, parameternya tidak terpakai.
             * Dihapus supaya PostgreSQL tidak menolak parameter berlebih.
             */
            $bindings = [];
        }

        return collect(
            DB::connection(self::CONNECTION)->select($sql, $bindings)
        );
    }

    public function obtainDaftarRekapitulasiImb($request): array
    {
        $perusahaan = $this->normalizeText(
            $request->perusahaan
            ?? session('kd_unit')
            ?? session('kd_perusahaan')
            ?? ''
        );

        $lokasi = $this->normalizeText($request->lokasi ?? '*');
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

        if ($blokAwal === '') {
            $blokAwal = 'A';
        }

        if ($blokAkhir === '' || $blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
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

        /*
         * Penyusunan ulang kunci sertipikat. Lihat keterangan panjang
         * pada awalanUnit().
         */
        $awalan = $this->awalanUnit($perusahaan);
        $pakaiUnik = $awalan === '';

        if (!$pakaiUnik) {
            $this->pastikanKeluargaAda('sr_imb', $awalan);
        }

        $cteSertipikatUnik = $pakaiUnik ? $this->cteSertipikatUnik() : '';
        $joinSertipikatUnik = $pakaiUnik
            ? "LEFT JOIN sertipikat_unik
                    ON sertipikat_unik.angka
                     = BTRIM(CAST(imb.sertipikat_id AS TEXT))"
            : '';
        $kunciImb = $this->kunciSertipikat('imb', $pakaiUnik);

        /*
         * Syarat STOK.BLOK = SERTIPIKAT.BLOK dan STOK.NOMOR =
         * SERTIPIKAT.NOMOR. Lihat keterangan panjang pada
         * syaratBlokNomor().
         */
        $joinBlokNomor = $this->syaratBlokNomor();

        /*
         * Master sektor pada sebagian hasil migrasi tidak membawa
         * kd_perusahaan. Kalau begitu, pencocokan sektor cukup memakai
         * kodenya saja dan bagian unit dilewati, persis seperti cabang
         * "SEKTOR.KD_PERUSAHAAN IS NULL" pada query desktop.
         */
        $adaSektorUnit = $this->adaKolom('sr_sektor', 'kd_perusahaan');

        $urutanSektorUnit = $adaSektorUnit
            ? "CASE
                            WHEN UPPER(BTRIM(COALESCE(
                                CAST(sektor.kd_perusahaan AS TEXT), '')))
                                = :perusahaan_sektor
                            THEN 0 ELSE 1
                        END"
            : '0';

        $saringSektorUnit = $adaSektorUnit
            ? "WHERE UPPER(BTRIM(COALESCE(
                              CAST(sektor.kd_perusahaan AS TEXT), '')))
                              = :perusahaan_sektor_saring
                       OR sektor.kd_perusahaan IS NULL"
            : '';

        /*
         * Saringan blok sengaja dipasang setelah penggabungan tabel, di
         * belakang hasil_dasar. Bentuknya memakai UPPER, BTRIM, dan
         * penyambungan teks, sehingga perencana query tidak punya
         * statistik untuk menaksirnya dan menduga sr_stok hanya berisi
         * satu baris padahal ribuan; dugaan itu membuat PostgreSQL
         * memilih nested loop yang lambat. Karena stok disambung dengan
         * INNER JOIN, menyaring sebelum atau sesudah penggabungan sama
         * saja hasilnya.
         *
         * Kondisi blok kedua dipertahankan seperti model SQL Server yang
         * sudah dipakai di web, yaitu BLOK_AWAL sampai BLOK_AKHIR, bukan
         * bentuk aslinya di desktop yang memakai BLOK_AKHIR dua kali.
         */
        $sql = <<<SQL
            WITH {$cteSertipikatUnik}stok_terpilih AS (
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
                        UPPER(BTRIM(COALESCE(
                            CAST(stok.{$stokSektor} AS TEXT), '')))
                            = :lokasi_filter
                        OR :lokasi_semua = '*'
                      )
                  AND stok.blok IS NOT NULL
                  AND stok.nomor IS NOT NULL
            ),
            imb_terpilih AS (
                SELECT
                    imb.*,
                    {$kunciImb} AS kunci_sertipikat
                FROM public.sr_imb AS imb
                {$joinSertipikatUnik}
                /*
                 * Batas atas dibuat eksklusif, mengikuti model SQL Server
                 * yang sudah dipakai di web, supaya baris pada tanggal
                 * akhir tetap ikut walaupun TGL_INPUT menyimpan jam.
                 */
                WHERE imb.tgl_input >= CAST(:tgl_awal AS TIMESTAMP)
                  AND imb.tgl_input < CAST(:tgl_akhir_eksklusif AS TIMESTAMP)
            ),
            ppjb_aktif AS MATERIALIZED (
                /*
                 * PPJB aktif dikumpulkan lebih dulu menjadi tabel bantu
                 * berkunci sederhana. Kalau sr_ppjb, sr_pembeli_ppjb, dan
                 * sr_nasabah disambung langsung memakai BTRIM(CAST(...)),
                 * perencana query tidak punya statistik untuk ekspresi
                 * semacam itu dan taksirannya meleset jauh.
                 */
                SELECT
                    BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
                    BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    ppjb.user_entry AS user_entry
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
                 * Satu baris per pembeli aktif, bukan digabung menjadi
                 * satu teks, karena laporan desktop memang menampilkan
                 * satu baris untuk tiap pembeli.
                 */
                SELECT
                    BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                    nasabah.nama     AS nama,
                    nasabah.telp_rmh AS telp_rmh,
                    nasabah.no_hp    AS no_hp,
                    nasabah.telp_ktr AS telp_ktr
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
            lokasi_ref AS MATERIALIZED (
                /*
                 * Pengganti subquery NAMA_LOKASI. Subquery berkorelasi
                 * dijalankan sekali untuk tiap baris keluaran, sedangkan
                 * tabel bantu ini cukup sekali lalu disambung.
                 *
                 * DISTINCT ON memakai urutan fisik baris, meniru SQL
                 * Server yang mengambil baris pertama yang ditemukannya
                 * ketika ada lebih dari satu baris berkode sama.
                 */
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
                 * Pengganti subquery NAMA_SEKTOR.
                 *
                 * Desktop memakai TOP (1) dengan urutan: sektor milik
                 * unit yang sama didahulukan, baru yang kd_perusahaan-nya
                 * kosong, lalu yang bertanda aktif. Karena laporan ini
                 * selalu untuk satu unit, tabel bantunya langsung disusun
                 * untuk unit itu saja sehingga cukup satu baris per kode.
                 *
                 * Disusun begini supaya tidak ada kode yang cocok dua kali
                 * lalu menggandakan baris laporan.
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
            hasil_dasar AS MATERIALIZED (
                SELECT
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
                        || COALESCE(CAST(stok.nomor AS TEXT), '')
                                            AS "BLOK_NOMOR",
                    nasabah.nama            AS "NAMA",
                    stok.luas_tanah         AS "LUAS_TANAH",
                    stok.luas_bangunan      AS "LUAS_BANGUNAN_STOK",
                    stok.jalan              AS "JALAN",

                    imb.no_pimb             AS "NO_PIMB",
                    imb.tgl_pimb            AS "TGL_PIMB",
                    imb.no_imb              AS "NO_IMB",
                    imb.tgl_imb             AS "TGL_IMB",
                    imb.luas_bangunan       AS "LUAS_BANGUNAN",
                    imb.no_ipb              AS "NO_IPB",
                    imb.tgl_ipb             AS "TGL_IPB",

                    nasabah.telp_rmh        AS "TELP_RMH",
                    nasabah.no_hp           AS "NO_HP",
                    nasabah.telp_ktr        AS "TELP_KTR",

                    stok.{$stokPerusahaan}  AS "KD_PERUSAHAAN",
                    CURRENT_TIMESTAMP       AS "TGL_CETAK",
                    ppjb.user_entry         AS "USER_ENTRY",

                    lokasi_ref.deskripsi    AS "NAMA_LOKASI",
                    sektor_ref.deskripsi    AS "NAMA_SEKTOR",

                    stok.blok  AS "BLOK",
                    stok.nomor AS "NOMOR"

                FROM imb_terpilih AS imb

                INNER JOIN public.sr_sertipikat AS sertipikat
                    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT))
                     = imb.kunci_sertipikat

                INNER JOIN stok_terpilih AS stok
                    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
                    {$joinBlokNomor}

                INNER JOIN ppjb_aktif AS ppjb
                    ON ppjb.kunci_stok = stok.kunci_stok

                INNER JOIN pembeli_nasabah AS nasabah
                    ON nasabah.kunci_ppjb = ppjb.kunci_ppjb

                LEFT JOIN lokasi_ref
                    ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokLokasi} AS TEXT), '')))
                LEFT JOIN sektor_ref
                    ON sektor_ref.kode = UPPER(BTRIM(COALESCE(
                           CAST(stok.{$stokSektor} AS TEXT), '')))

                WHERE sertipikat.stok_id IS NOT NULL
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
                        hasil_dasar."BLOK" >= :blok_awal_blok
                        AND hasil_dasar."BLOK" <= :blok_akhir_blok
                    )
                  )
            /*
             * NULLS FIRST dipasang khusus untuk NAMA_SEKTOR karena SQL
             * Server menaruh NULL paling awal pada urutan menaik,
             * sedangkan PostgreSQL menaruhnya paling akhir. Tanpa ini
             * baris yang sektornya tidak ketemu akan pindah tempat.
             */
            ORDER BY
                hasil_dasar."NAMA_SEKTOR" ASC NULLS FIRST,
                hasil_dasar."BLOK" ASC,
                {$this->urutanNomor('hasil_dasar."NOMOR"')},
                hasil_dasar."NOMOR" ASC
        SQL;

        $bindings = [
            'perusahaan' => $perusahaan,
            'perusahaan_langsung' => $perusahaan,
            'lokasi_filter' => $lokasi,
            'lokasi_semua' => $lokasi,
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

    /**
     * Syarat tambahan STOK.BLOK = SERTIPIKAT.BLOK dan
     * STOK.NOMOR = SERTIPIKAT.NOMOR milik query desktop.
     *
     * Syarat ini SEBENARNYA BERLEBIH. Pasangan stok dan sertipikat sudah
     * ditentukan sepenuhnya oleh STOK_ID, jadi syarat blok dan nomor
     * hanya lapis pemeriksaan tambahan. Karena itu ia hanya bisa
     * MEMBUANG baris, tidak pernah bisa menambah baris yang keliru.
     *
     * Perbandingannya dibuat sedikit lebih tahan banting daripada query
     * desktop, dengan dua pelonggaran yang aman:
     *
     * 1. Blok dibandingkan tanpa peduli besar kecil huruf.
     * 2. Nomor dibandingkan sebagai angka bila kedua sisinya seluruhnya
     *    angka, sehingga 053 dan 53 dianggap sama.
     *
     * Pelonggaran kedua itu penjagaan terhadap satu jenis kerusakan yang
     * sudah berkali-kali terbukti pada hasil migrasi ini, yaitu kolom
     * yang sempat dijadikan angka sehingga nol di depannya hilang. Kalau
     * itu sampai terjadi pada kolom nomor, syarat aslinya akan
     * menghabiskan SELURUH baris dan laporannya kosong sama sekali.
     *
     * Diukur pada database sekarang, kerusakan itu TIDAK terjadi: seluruh
     * 9.628 pasangan stok dan sertipikat pada unit SBKS cocok persis.
     * Jadi pelonggaran ini tidak mengubah hasil apa pun hari ini, dan
     * hanya berjaga untuk kemungkinan itu muncul di kemudian hari.
     */
    private function syaratBlokNomor(): string
    {
        if (
            !$this->adaKolom('sr_sertipikat', 'blok')
            || !$this->adaKolom('sr_sertipikat', 'nomor')
        ) {
            return '';
        }

        return <<<SQL
        AND UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                     = UPPER(BTRIM(COALESCE(CAST(sertipikat.blok AS TEXT), '')))
                   AND (
                        BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                            = BTRIM(COALESCE(CAST(sertipikat.nomor AS TEXT), ''))
                        OR (
                             BTRIM(CAST(stok.nomor AS TEXT)) ~ '^[0-9]+$'
                             AND BTRIM(CAST(sertipikat.nomor AS TEXT)) ~ '^[0-9]+$'
                             AND BTRIM(CAST(stok.nomor AS TEXT))::numeric
                               = BTRIM(CAST(sertipikat.nomor AS TEXT))::numeric
                           )
                       )
        SQL;
    }
}
