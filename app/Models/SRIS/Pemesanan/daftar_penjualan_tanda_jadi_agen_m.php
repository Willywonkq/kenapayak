<?php

namespace App\Models\SRIS\Pemesanan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class daftar_penjualan_tanda_jadi_agen_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    private const LOKASI_LIST = [
        ['KD_LOKASI' => 'BB',    'DESKRIPSI' => 'Bulevar Barat'],
        ['KD_LOKASI' => 'BG',    'DESKRIPSI' => 'Bukit Gading Villa'],
        ['KD_LOKASI' => 'GB',    'DESKRIPSI' => 'Graha Bulevar'],
        ['KD_LOKASI' => 'GBT',   'DESKRIPSI' => 'Graha Bulevar Timur'],
        ['KD_LOKASI' => 'GER',   'DESKRIPSI' => 'Gading Eight Residence'],
        ['KD_LOKASI' => 'GK',    'DESKRIPSI' => 'Gading Kusuma'],
        ['KD_LOKASI' => 'GK2',   'DESKRIPSI' => 'Gading Kusuma SPN'],
        ['KD_LOKASI' => 'GM',    'DESKRIPSI' => 'Royal Gading Mansion'],
        ['KD_LOKASI' => 'GN',    'DESKRIPSI' => 'Gading Nirwana'],
        ['KD_LOKASI' => 'GO',    'DESKRIPSI' => 'Grand Orchard'],
        ['KD_LOKASI' => 'GOS',   'DESKRIPSI' => 'Orchard Square'],
        ['KD_LOKASI' => 'GP',    'DESKRIPSI' => 'Gading Park View'],
        ['KD_LOKASI' => 'GP2',   'DESKRIPSI' => 'Gading Park View SKG'],
        ['KD_LOKASI' => 'GPE',   'DESKRIPSI' => 'Graha SKG PE'],
        ['KD_LOKASI' => 'GPV',   'DESKRIPSI' => 'Graha Park View'],
        ['KD_LOKASI' => 'GR',    'DESKRIPSI' => 'Gading Riviera'],
        ['KD_LOKASI' => 'GRB',   'DESKRIPSI' => 'Graha SKG RB'],
        ['KD_LOKASI' => 'GT',    'DESKRIPSI' => 'Kelapa Gading Timur'],
        ['KD_LOKASI' => 'KG',    'DESKRIPSI' => 'Summarecon Kelapa Gading'],
        ['KD_LOKASI' => 'MS',    'DESKRIPSI' => 'Menara Satu'],
        ['KD_LOKASI' => 'NB',    'DESKRIPSI' => 'New Batavia'],
        ['KD_LOKASI' => 'NIAS',  'DESKRIPSI' => 'Graha SKG GN'],
        ['KD_LOKASI' => 'RF',    'DESKRIPSI' => 'Riviera Garden'],
        ['KD_LOKASI' => 'RGC',   'DESKRIPSI' => 'The Riviera Garden Commercial'],
        ['KD_LOKASI' => 'RGS',   'DESKRIPSI' => 'Royal Gading Square'],
        ['KD_LOKASI' => 'RP',    'DESKRIPSI' => 'Riviera Plaza'],
        ['KD_LOKASI' => 'SB',    'DESKRIPSI' => 'Summarecon Bekasi'],
        ['KD_LOKASI' => 'SBD',   'DESKRIPSI' => 'Summarecon Bandung'],
        ['KD_LOKASI' => 'SBGR',  'DESKRIPSI' => 'Summarecon Bogor'],
        ['KD_LOKASI' => 'SCG',   'DESKRIPSI' => 'Summarecon Crown Gading'],
        ['KD_LOKASI' => 'SCR',   'DESKRIPSI' => 'Summagung Commercial'],
        ['KD_LOKASI' => 'SEKAR', 'DESKRIPSI' => 'Summarecon Emerald Karawang'],
        ['KD_LOKASI' => 'SHR',   'DESKRIPSI' => 'Sherwood'],
        ['KD_LOKASI' => 'SKG',   'DESKRIPSI' => 'Graha SKG'],
        ['KD_LOKASI' => 'SMM',   'DESKRIPSI' => 'Summarecon Mutiara Makassar'],
        ['KD_LOKASI' => 'SRMY',  'DESKRIPSI' => 'Srimaya Residence'],
        ['KD_LOKASI' => 'STGR',  'DESKRIPSI' => 'Summarecon Tangerang'],
        ['KD_LOKASI' => 'TGN',   'DESKRIPSI' => 'The Nirwana Garden'],
        ['KD_LOKASI' => 'TK',    'DESKRIPSI' => 'The Kew Garden Residence'],
        ['KD_LOKASI' => 'TKC',   'DESKRIPSI' => 'The Kensington'],
        ['KD_LOKASI' => 'TKR',   'DESKRIPSI' => 'The Kensington Royal Suites'],
        ['KD_LOKASI' => 'TNM',   'DESKRIPSI' => 'Titanium'],
        ['KD_LOKASI' => 'TOSB',  'DESKRIPSI' => 'The Orchard Summarecon Bekasi'],
        ['KD_LOKASI' => 'TS',    'DESKRIPSI' => 'The Summit'],
        ['KD_LOKASI' => 'TSL',   'DESKRIPSI' => 'The Springlake'],
        ['KD_LOKASI' => 'WG',    'DESKRIPSI' => 'Wisma Gading Permai'],
    ];

    /**
     * Laporan ini harus memuat seluruh unit seperti desktop, karena baris TOTAL
     * PENJUALAN dihitung dari jumlah baris yang dikirim ke tampilan. Batas bawaan
     * 700 baris membuat total di web ikut terpotong tanpa peringatan apa pun,
     * jadi bawaannya sekarang tanpa batas.
     *
     * Batas hanya berlaku bila request memang mengirim limit/page_limit/per_page.
     */
    private const DEFAULT_VIEW_LIMIT = 0;
    private const MAX_VIEW_LIMIT = 5000;

    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = strtoupper(trim((string) $kdPerusahaan));

        /*
         * Kode sektor wajib berasal dari sr_stok karena nilai inilah yang
         * dipakai kembali oleh filter laporan. sr_sektor dan sr_lokasi hanya
         * melengkapi deskripsi seperti pada model Daftar Unit ST.
         */
        $sql = <<<'SQL'
            WITH
            stok_sektor AS (
                SELECT
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(stok) ->> 'kd_sektor', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_lv2', ''),
                                ''
                            )
                        )
                    ) AS kode,
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(stok) ->> 'kd_perusahaan', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_unit', ''),
                                NULLIF(to_jsonb(stok) ->> 'kd_pt', ''),
                                ''
                            )
                        )
                    ) AS kd_perusahaan
                FROM public.sr_stok AS stok
            ),
            stok_terpilih AS (
                SELECT DISTINCT kode, kd_perusahaan
                FROM stok_sektor
                WHERE kode <> ''
                    AND kd_perusahaan <> ''
                    AND (
                        kd_perusahaan = :kd_perusahaan
                        OR :kd_perusahaan = '*'
                        OR :kd_perusahaan = ''
                    )
            ),
            master_sektor AS (
                SELECT
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(sektor) ->> 'kd_sektor', ''),
                                NULLIF(to_jsonb(sektor) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(sektor) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(sektor) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(sektor) ->> 'kd_lv2', ''),
                                ''
                            )
                        )
                    ) AS kode,
                    BTRIM(COALESCE(to_jsonb(sektor) ->> 'deskripsi', ''))
                        AS deskripsi,
                    UPPER(
                        BTRIM(
                            COALESCE(
                                to_jsonb(sektor) ->> 'kd_perusahaan',
                                ''
                            )
                        )
                    ) AS kd_perusahaan,
                    COALESCE(
                        NULLIF(
                            UPPER(BTRIM(to_jsonb(sektor) ->> 'flag_aktif')),
                            ''
                        ),
                        'A'
                    ) AS flag_aktif,
                    2 AS source_priority
                FROM public.sr_sektor AS sektor

                UNION ALL

                SELECT
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(lokasi) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(lokasi) ->> 'kd_lv2', ''),
                                NULLIF(to_jsonb(lokasi) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(lokasi) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(lokasi) ->> 'kd_sektor', ''),
                                ''
                            )
                        )
                    ) AS kode,
                    BTRIM(COALESCE(to_jsonb(lokasi) ->> 'deskripsi', ''))
                        AS deskripsi,
                    UPPER(
                        BTRIM(
                            COALESCE(
                                to_jsonb(lokasi) ->> 'kd_perusahaan',
                                ''
                            )
                        )
                    ) AS kd_perusahaan,
                    COALESCE(
                        NULLIF(
                            UPPER(BTRIM(to_jsonb(lokasi) ->> 'flag_aktif')),
                            ''
                        ),
                        'A'
                    ) AS flag_aktif,
                    1 AS source_priority
                FROM public.sr_lokasi AS lokasi
            ),
            pilihan_master AS (
                SELECT
                    stok_terpilih.kode,
                    stok_terpilih.kd_perusahaan,
                    master_sektor.deskripsi,
                    ROW_NUMBER() OVER (
                        PARTITION BY
                            stok_terpilih.kd_perusahaan,
                            stok_terpilih.kode
                        ORDER BY
                            CASE
                                WHEN master_sektor.kd_perusahaan
                                    = stok_terpilih.kd_perusahaan
                                THEN 0
                                WHEN master_sektor.kd_perusahaan = ''
                                THEN 1
                                ELSE 2
                            END,
                            CASE
                                WHEN master_sektor.flag_aktif <> 'T'
                                THEN 0
                                ELSE 1
                            END,
                            master_sektor.source_priority,
                            master_sektor.deskripsi
                    ) AS urutan
                FROM stok_terpilih
                LEFT JOIN master_sektor
                    ON master_sektor.kode = stok_terpilih.kode
                    AND master_sektor.deskripsi <> ''
            )
            SELECT
                kode AS "KD_SEKTOR",
                COALESCE(
                    NULLIF(deskripsi, ''),
                    CASE kode
                        WHEN 'CLA' THEN 'CHELIA RESIDENCE'
                        WHEN 'EMC' THEN 'EMERALD COMMERCIAL'
                        ELSE kode
                    END
                ) AS "DESKRIPSI",
                kd_perusahaan AS "KD_PERUSAHAAN"
            FROM pilihan_master
            WHERE urutan = 1
            ORDER BY "DESKRIPSI" ASC, "KD_SEKTOR" ASC
        SQL;

        return collect(DB::connection(self::CONNECTION)->select($sql, [
            'kd_perusahaan' => $kdPerusahaan,
        ]));
    }

    public function obtainTipeBayar()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_tipe_bayar as tipe_bayar')
            ->selectRaw('
                BTRIM(CAST(tipe_bayar.tipe_bayar AS text)) AS "TIPE_BAYAR",
                BTRIM(CAST(tipe_bayar.nama AS text)) AS "NAMA"
            ')
            ->orderByRaw('BTRIM(CAST(tipe_bayar.tipe_bayar AS text)) ASC')
            ->get();
    }

    public function obtainAgen()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_agen as agen')
            ->selectRaw('
                BTRIM(CAST(agen.kd_agen AS text)) AS "KD_AGEN",
                BTRIM(CAST(agen.kd_group AS text)) AS "KD_GROUP",
                BTRIM(CAST(agen.nama_agen AS text)) AS "NAMA_AGEN",
                BTRIM(COALESCE(CAST(agen.nama_pt AS text), \'\')) AS "NAMA_PT",
                BTRIM(COALESCE(CAST(agen.npwp AS text), \'\')) AS "NPWP",
                BTRIM(COALESCE(CAST(agen.nama_pj AS text), \'\')) AS "NAMA_PJ"
            ')
            ->whereRaw("UPPER(BTRIM(COALESCE(CAST(agen.flag_aktif AS text), ''))) = 'A'")
            ->whereNotNull('agen.kd_agen')
            ->whereNotNull('agen.nama_agen')
            ->whereRaw("BTRIM(CAST(agen.kd_agen AS text)) <> ''")
            ->whereRaw("BTRIM(CAST(agen.nama_agen AS text)) <> ''")
            ->orderByRaw('BTRIM(CAST(agen.nama_agen AS text)) ASC')
            ->get();
    }

    public function obtainLokasi()
    {
        return collect(self::LOKASI_LIST)
            ->map(static function (array $lokasi): object {
                return (object) $lokasi;
            });
    }

    public function obtainPenjualanTandaJadiAgen($request)
    {
        $totalStartedAt = microtime(true);
        $resolverStartedAt = microtime(true);

        $tglAwal = $this->normalizeDate($request->tgl_awal ?? null);
        $tglAkhir = $this->normalizeDate($request->tgl_akhir ?? null, $tglAwal);

        $agen = $this->normalizeAgen($request->agen ?? '*****');
        $lokasi = $this->normalizeOption($request->lokasi ?? '*');
        $sektor = $this->normalizeOption($request->sektor ?? '*');
        $tipeBayar = $this->normalizeOption($request->tipe_bayar ?? '*');

        $defaultPerusahaan = session('kd_unit')
            ?? session('kd_perusahaan')
            ?? 'DTSA';

        $perusahaan = $this->normalizeOption(
            $request->perusahaan ?? $defaultPerusahaan,
            $defaultPerusahaan
        );

        $viewLimit = $this->resolveViewLimit($request);
        $lokasiValues = $this->resolveLokasiValues($lokasi);
        $sektorValues = $this->resolveSektorValues($sektor, $perusahaan);
        $resolverMs = $this->elapsedMilliseconds($resolverStartedAt);

        $rawBindings = [];
        $normalizedBindings = [];
        $rawWhere = [];
        $normalizedWhere = [];

        $rawWhere[] = "um.tgl_uang_muka >= ?::date";
        $rawBindings[] = $tglAwal;

        $rawWhere[] = "um.tgl_uang_muka < (?::date + INTERVAL '1 day')";
        $rawBindings[] = $tglAkhir;

        $rawWhere[] = "UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = ?";
        $rawBindings[] = $perusahaan;

        $rawWhere[] = "COALESCE(NULLIF(UPPER(BTRIM(CAST(um.flag_aktif AS text))), ''), 'A') = 'A'";
        $rawWhere[] = "NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL";

        if ($agen !== '*****' && $agen !== '*') {
            $rawWhere[] = "UPPER(BTRIM(COALESCE(CAST(um.kd_agen AS text), ''))) = ?";
            $rawBindings[] = $agen;
        }

        if ($tipeBayar !== '*') {
            $rawWhere[] = "UPPER(BTRIM(COALESCE(CAST(um.tipe_bayar_angs AS text), ''))) = ?";
            $rawBindings[] = $tipeBayar;
        }

        if (!(count($lokasiValues) === 1 && $lokasiValues[0] === '*')) {
            $normalizedWhere[] = "UPPER(BTRIM(COALESCE(CAST(raw.kd_lokasi AS text), ''))) IN (" . $this->placeholderList($lokasiValues) . ")";
            array_push($normalizedBindings, ...$lokasiValues);
        }

        if (!(count($sektorValues) === 1 && $sektorValues[0] === '*')) {
            $normalizedWhere[] = "UPPER(BTRIM(COALESCE(CAST(raw.kd_sektor AS text), ''))) IN (" . $this->placeholderList($sektorValues) . ")";
            array_push($normalizedBindings, ...$sektorValues);
        }

        $rawWhereSql = implode("\n                    AND ", $rawWhere);
        $normalizedWhereSql = count($normalizedWhere) > 0
            ? implode("\n                    AND ", $normalizedWhere)
            : 'TRUE';
        $bindings = array_merge($rawBindings, $normalizedBindings);
        $limitSql = '';

        if ($viewLimit > 0) {
            $limitSql = "\n                LIMIT ?";
            $bindings[] = $viewLimit;
        }

        $schema = self::SCHEMA;

        /*
         * Catatan penting:
         * Query SQL Server asli yang diberikan tidak memasang filter NOT EXISTS PPJB.
         * Karena itu model ini juga tidak membuang unit yang sudah punya PPJB,
         * agar daftar barisnya mengikuti laporan desktop.
         */
        $sql = <<<SQL
            WITH raw_base AS (
                /*
                 * Ambil kolom transaksi/stok dan bentuk kode JSON satu kali.
                 * Pada versi sebelumnya to_jsonb(stok) dihitung berulang di SELECT,
                 * filter, dan JOIN untuk baris yang sama.
                 */
                SELECT
                    um.uang_muka_id,
                    BTRIM(CAST(um.uang_muka_id AS text)) AS uang_muka_id_text,
                    NULLIF(
                        REGEXP_REPLACE(COALESCE(CAST(um.uang_muka_id AS text), ''), '[^0-9]', '', 'g'),
                        ''
                    ) AS uang_muka_id_digits,

                    um.no_uang_muka,
                    um.tgl_uang_muka,
                    um.tipe_bayar_angs,
                    um.harga_rumah,
                    um.harga_jual,
                    um.kd_agen,
                    um.kd_sales,

                    stok.stok_id,
                    BTRIM(CAST(stok.blok AS text)) AS blok,
                    BTRIM(CAST(stok.nomor AS text)) AS nomor,
                    UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))
                        || '/'
                        || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))
                        AS stok_blok,

                    stok.jalan,
                    COALESCE(NULLIF(stok_payload.data ->> 'kd_jenis_bgn', ''), NULLIF(stok_payload.data ->> 'kd_jenis', ''), '') AS kd_jenis,
                    COALESCE(NULLIF(stok_payload.data ->> 'kd_tipe_bgn', ''), NULLIF(stok_payload.data ->> 'kd_tipe', ''), '') AS kd_tipe,
                    COALESCE(NULLIF(stok_payload.data ->> 'kd_model_bgn', ''), NULLIF(stok_payload.data ->> 'kd_model', ''), '') AS kd_model,
                    COALESCE(NULLIF(stok_payload.data ->> 'kd_lokasi', ''), NULLIF(stok_payload.data ->> 'kd_lv2', ''), '') AS kd_lokasi,
                    COALESCE(NULLIF(stok_payload.data ->> 'kd_sektor', ''), NULLIF(stok_payload.data ->> 'kd_proyek', ''), NULLIF(stok_payload.data ->> 'kd_cluster', ''), NULLIF(stok_payload.data ->> 'kd_lokasi', ''), NULLIF(stok_payload.data ->> 'kd_lv2', ''), '') AS kd_sektor,
                    stok.kd_perusahaan,
                    stok.luas_tanah,
                    stok.luas_bangunan

                FROM {$schema}.sr_uang_muka AS um

                INNER JOIN {$schema}.sr_stok AS stok
                    ON stok.stok_id = um.stok_id

                CROSS JOIN LATERAL (
                    SELECT to_jsonb(stok) AS data
                    OFFSET 0
                ) AS stok_payload

                WHERE {$rawWhereSql}
            ),

            tipe_jenis_lookup AS (
                /*
                 * Normalisasi tabel referensi satu kali agar ekspresi UPPER/BTRIM
                 * tidak dihitung ulang untuk setiap pasangan transaksi.
                 * Tidak memakai DISTINCT supaya kardinalitas tetap sama dengan JOIN lama.
                 *
                 * sr_jenis_bangunan disambung memakai LEFT JOIN. Di SQL Server tabel
                 * JENIS_BANGUNAN lengkap sehingga INNER JOIN aman, sedangkan di
                 * PostgreSQL masih ada kd_jenis yang belum tersalin. Dengan INNER JOIN
                 * baris tipe tersebut hilang dari lookup dan ikut menghilangkan unit.
                 */
                SELECT
                    UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) AS kd_jenis,
                    UPPER(BTRIM(CAST(tipe.kd_tipe AS text))) AS kd_tipe,
                    tipe.deskripsi AS tipe_bangunan,
                    tipe.listrik,
                    jenis_bangunan.deskripsi AS jenis_bangunan
                FROM {$schema}.sr_tipe AS tipe
                LEFT JOIN {$schema}.sr_jenis_bangunan AS jenis_bangunan
                    ON UPPER(BTRIM(CAST(jenis_bangunan.kd_jenis AS text)))
                     = UPPER(BTRIM(CAST(tipe.kd_jenis AS text)))
            ),

            base AS (
                SELECT
                    raw.*,
                    tipe.tipe_bangunan,
                    tipe.listrik,
                    tipe.jenis_bangunan
                FROM raw_base AS raw
                /*
                 * Query desktop menyambung TIPE dengan join lama (koma di FROM),
                 * yang berarti INNER JOIN. Itu aman di SQL Server karena setiap
                 * pasangan KD_JENIS + KD_TIPE pada STOK pasti ada di TIPE.
                 *
                 * Di PostgreSQL pasangan tersebut belum lengkap: sebagian sr_stok
                 * kd_tipe-nya kosong atau kodenya belum ada di sr_tipe. INNER JOIN
                 * membuat unit tersebut lenyap dari laporan, padahal desktop tetap
                 * menampilkannya. Karena itu di sini memakai LEFT JOIN: unitnya tetap
                 * tampil, hanya kolom Tipe/Jenis/Listrik yang kosong.
                 */
                LEFT JOIN tipe_jenis_lookup AS tipe
                    ON tipe.kd_jenis =
                       UPPER(BTRIM(COALESCE(CAST(raw.kd_jenis AS text), '')))
                   AND tipe.kd_tipe =
                       UPPER(BTRIM(COALESCE(CAST(raw.kd_tipe AS text), '')))
                WHERE {$normalizedWhereSql}
                ORDER BY
                    UPPER(BTRIM(COALESCE(CAST(raw.blok AS text), ''))),
                    LPAD(COALESCE(NULLIF(REGEXP_REPLACE(COALESCE(CAST(raw.nomor AS text), ''), '[^0-9]', '', 'g'), ''), '0'), 20, '0'),
                    UPPER(BTRIM(COALESCE(CAST(raw.nomor AS text), ''))),
                    raw.tgl_uang_muka,
                    BTRIM(CAST(raw.no_uang_muka AS text))
                {$limitSql}
            ),

            base_ids AS (
                SELECT DISTINCT
                    uang_muka_id,
                    uang_muka_id_text,
                    uang_muka_id_digits
                FROM base
            ),

            biaya_by_um AS (
                SELECT
                    b.uang_muka_id,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN biaya.balance = -1
                                THEN COALESCE(bdp.jumlah, 0)
                                ELSE 0
                            END
                        ),
                        0
                    ) AS discount,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN biaya.balance = 1
                                 AND UPPER(BTRIM(COALESCE(CAST(bdp.kd_biaya AS text), ''))) <> 'PPN'
                                THEN COALESCE(bdp.jumlah, 0)
                                ELSE 0
                            END
                        ),
                        0
                    ) AS biaya,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(BTRIM(COALESCE(CAST(bdp.kd_biaya AS text), ''))) = 'PPN'
                                THEN COALESCE(bdp.jumlah, 0)
                                ELSE 0
                            END
                        ),
                        0
                    ) AS ppn,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(BTRIM(COALESCE(CAST(bdp.kd_biaya AS text), ''))) = 'BPL'
                                THEN COALESCE(bdp.jumlah, 0)
                                ELSE 0
                            END
                        ),
                        0
                    ) AS pls

                FROM base_ids AS b
                INNER JOIN {$schema}.sr_biaya_dp AS bdp
                    ON bdp.uang_muka_id = b.uang_muka_id
                LEFT JOIN {$schema}.sr_biaya AS biaya
                    ON UPPER(BTRIM(CAST(biaya.kd_biaya AS text))) = UPPER(BTRIM(CAST(bdp.kd_biaya AS text)))
                GROUP BY b.uang_muka_id
            ),

            pembeli_dp_raw AS (
                SELECT
                    b.uang_muka_id,
                    CASE
                        WHEN COALESCE(to_jsonb(pd) ->> 'urut', '') ~ '^[0-9]+$'
                        THEN CAST(to_jsonb(pd) ->> 'urut' AS integer)
                        ELSE 999
                    END AS urut,
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(CAST(nasabah.nama AS text), ''),
                                NULLIF(to_jsonb(nasabah) ->> 'nama_nasabah', ''),
                                NULLIF(to_jsonb(nasabah) ->> 'nama_pembeli', ''),
                                NULLIF(to_jsonb(pd) ->> 'nama', ''),
                                NULLIF(to_jsonb(pd) ->> 'nama_nasabah', ''),
                                NULLIF(to_jsonb(pd) ->> 'nama_pembeli', ''),
                                ''
                            )
                        )
                    ) AS nama_pembeli
                FROM base_ids AS b
                INNER JOIN {$schema}.sr_pembeli_dp AS pd
                    ON pd.uang_muka_id = b.uang_muka_id
                LEFT JOIN {$schema}.sr_nasabah AS nasabah
                    ON NULLIF(
                        UPPER(BTRIM(CAST(pd.nasabah_id AS text))),
                        ''
                    ) = NULLIF(
                        UPPER(BTRIM(CAST(nasabah.nasabah_id AS text))),
                        ''
                    )
                WHERE COALESCE(NULLIF(UPPER(BTRIM(to_jsonb(pd) ->> 'flag_nama_dp')), ''), 'Y') = 'Y'
            ),

            pembeli_dp_unik AS (
                SELECT
                    uang_muka_id,
                    nama_pembeli,
                    MIN(urut) AS urut
                FROM pembeli_dp_raw
                WHERE NULLIF(BTRIM(nama_pembeli), '') IS NOT NULL
                GROUP BY uang_muka_id, nama_pembeli
            ),

            pembeli_dp AS (
                SELECT
                    uang_muka_id,
                    STRING_AGG(nama_pembeli, ', ' ORDER BY urut, nama_pembeli) AS nasabah_nama
                FROM pembeli_dp_unik
                GROUP BY uang_muka_id
            ),

            /*
             * Ketiga lookup berikut menggantikan LEFT JOIN LATERAL yang sebelumnya
             * dijalankan kembali untuk setiap baris laporan. Hasilnya tetap sama:
             * satu deskripsi paling awal menurut aturan ORDER BY lama, tetapi setiap
             * tabel referensi cukup dipindai satu kali untuk seluruh request.
             */
            lokasi_lookup AS (
                SELECT
                    kode,
                    MIN(deskripsi) AS deskripsi
                FROM (
                    SELECT
                        UPPER(
                            BTRIM(
                                COALESCE(
                                    NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                                    NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                                    ''
                                )
                            )
                        ) AS kode,
                        BTRIM(CAST(l.deskripsi AS text)) AS deskripsi
                    FROM {$schema}.sr_lokasi AS l
                ) AS lokasi_normalized
                WHERE kode <> ''
                GROUP BY kode
            ),

            base_company AS (
                SELECT DISTINCT
                    UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS text), ''))) AS kd_perusahaan
                FROM base
            ),

            sektor_candidates AS (
                SELECT
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(s) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_sektor', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_lv2', ''),
                                ''
                            )
                        )
                    ) AS kode,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_perusahaan', ''))) AS kd_perusahaan,
                    BTRIM(CAST(s.deskripsi AS text)) AS deskripsi,
                    2 AS source_priority
                FROM {$schema}.sr_sektor AS s
                WHERE COALESCE(
                        NULLIF(UPPER(BTRIM(to_jsonb(s) ->> 'flag_aktif')), ''),
                        'A'
                      ) <> 'T'

                UNION ALL

                SELECT
                    UPPER(
                        BTRIM(
                            COALESCE(
                                NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                                NULLIF(to_jsonb(l) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(l) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(l) ->> 'kd_sektor', ''),
                                ''
                            )
                        )
                    ) AS kode,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_perusahaan', ''))) AS kd_perusahaan,
                    BTRIM(CAST(l.deskripsi AS text)) AS deskripsi,
                    1 AS source_priority
                FROM {$schema}.sr_lokasi AS l
                WHERE COALESCE(
                        NULLIF(UPPER(BTRIM(to_jsonb(l) ->> 'flag_aktif')), ''),
                        'A'
                      ) <> 'T'
            ),

            sektor_lookup AS (
                SELECT
                    perusahaan.kd_perusahaan,
                    sektor.kode,
                    (
                        ARRAY_AGG(
                            sektor.deskripsi
                            ORDER BY
                                CASE
                                    WHEN sektor.kd_perusahaan = perusahaan.kd_perusahaan
                                    THEN 0
                                    WHEN sektor.kd_perusahaan = ''
                                    THEN 1
                                    ELSE 2
                                END,
                                sektor.source_priority,
                                sektor.deskripsi
                        )
                    )[1] AS deskripsi
                FROM base_company AS perusahaan
                CROSS JOIN sektor_candidates AS sektor
                WHERE sektor.kode <> ''
                GROUP BY perusahaan.kd_perusahaan, sektor.kode
            ),

            model_lookup AS (
                SELECT
                    kode,
                    MIN(deskripsi) AS deskripsi
                FROM (
                    SELECT
                        UPPER(
                            BTRIM(
                                COALESCE(
                                    NULLIF(to_jsonb(m) ->> 'kd_model_bgn', ''),
                                    NULLIF(to_jsonb(m) ->> 'kd_model', ''),
                                    ''
                                )
                            )
                        ) AS kode,
                        BTRIM(CAST(m.deskripsi AS text)) AS deskripsi
                    FROM {$schema}.sr_model AS m
                ) AS model_normalized
                WHERE kode <> ''
                GROUP BY kode
            )

            SELECT
                b.stok_blok AS "STOK_BLOK",
                b.jalan AS "JALAN",
                BTRIM(CAST(b.kd_jenis AS text)) AS "KD_JENIS",

                COALESCE(lokasi_ref.deskripsi, '') AS "NAMA_LOKASI",
                COALESCE(
                    NULLIF(sektor_ref.deskripsi, ''),
                    CASE UPPER(BTRIM(COALESCE(CAST(b.kd_sektor AS text), '')))
                        WHEN 'CLA' THEN 'CHELIA RESIDENCE'
                        WHEN 'EMC' THEN 'EMERALD COMMERCIAL'
                        ELSE UPPER(BTRIM(COALESCE(CAST(b.kd_sektor AS text), '')))
                    END
                ) AS "NAMA_SEKTOR",
                BTRIM(COALESCE(CAST(b.jenis_bangunan AS text), '')) AS "JENIS_BANGUNAN",
                BTRIM(COALESCE(CAST(b.tipe_bangunan AS text), '')) AS "TIPE_BANGUNAN",
                COALESCE(model_ref.deskripsi, '') AS "MODEL",

                b.luas_tanah AS "LUAS_TANAH",
                b.luas_bangunan AS "LUAS_BANGUNAN",
                b.listrik AS "LISTRIK",

                BTRIM(CAST(b.no_uang_muka AS text)) AS "NO_UANG_MUKA",
                b.tgl_uang_muka AS "TGL_UANG_MUKA",

                COALESCE(NULLIF(pembeli_dp.nasabah_nama, ''), '-') AS "NASABAH_NAMA",
                BTRIM(COALESCE(CAST(tipe_bayar_ref.nama AS text), '')) AS "TIPE_PEMBAYARAN",

                COALESCE(b.harga_rumah, 0) AS "HARGA_RUMAH",
                COALESCE(biaya.discount, 0) AS "DISCOUNT",
                COALESCE(biaya.biaya, 0) AS "BIAYA",
                COALESCE(biaya.ppn, 0) AS "PPN",
                COALESCE(biaya.pls, 0) AS "PLS",
                COALESCE(b.harga_jual, 0) AS "HARGA_JUAL",

                BTRIM(COALESCE(CAST(b.kd_agen AS text), '')) AS "KD_AGEN",
                BTRIM(COALESCE(CAST(agen_ref.nama_agen AS text), '')) AS "NAMA_AGEN",

                BTRIM(COALESCE(CAST(b.kd_sales AS text), '')) AS "KD_SALES",
                BTRIM(COALESCE(CAST(sales_ref.deskripsi AS text), '')) AS "NAMA_SALES",

                BTRIM(COALESCE(CAST(b.kd_perusahaan AS text), '')) AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK"

            FROM base AS b

            LEFT JOIN biaya_by_um AS biaya
                ON biaya.uang_muka_id = b.uang_muka_id

            LEFT JOIN pembeli_dp AS pembeli_dp
                ON pembeli_dp.uang_muka_id = b.uang_muka_id

            LEFT JOIN lokasi_lookup AS lokasi_ref
                ON lokasi_ref.kode =
                   UPPER(BTRIM(COALESCE(CAST(b.kd_lokasi AS text), '')))

            LEFT JOIN sektor_lookup AS sektor_ref
                ON sektor_ref.kode =
                   UPPER(BTRIM(COALESCE(CAST(b.kd_sektor AS text), '')))
               AND sektor_ref.kd_perusahaan =
                   UPPER(BTRIM(COALESCE(CAST(b.kd_perusahaan AS text), '')))

            LEFT JOIN model_lookup AS model_ref
                ON model_ref.kode =
                   UPPER(BTRIM(COALESCE(CAST(b.kd_model AS text), '')))

            LEFT JOIN {$schema}.sr_tipe_bayar AS tipe_bayar_ref
                ON UPPER(BTRIM(CAST(tipe_bayar_ref.tipe_bayar AS text))) = UPPER(BTRIM(COALESCE(CAST(b.tipe_bayar_angs AS text), '')))

            LEFT JOIN {$schema}.sr_agen AS agen_ref
                ON UPPER(BTRIM(CAST(agen_ref.kd_agen AS text))) = UPPER(BTRIM(COALESCE(CAST(b.kd_agen AS text), '')))

            LEFT JOIN {$schema}.sr_sales AS sales_ref
                ON UPPER(BTRIM(CAST(sales_ref.kd_sales AS text))) = UPPER(BTRIM(COALESCE(CAST(b.kd_sales AS text), '')))

            ORDER BY
                UPPER(BTRIM(COALESCE(CAST(b.blok AS text), ''))),
                LPAD(COALESCE(NULLIF(REGEXP_REPLACE(COALESCE(CAST(b.nomor AS text), ''), '[^0-9]', '', 'g'), ''), '0'), 20, '0'),
                UPPER(BTRIM(COALESCE(CAST(b.nomor AS text), ''))),
                b.tgl_uang_muka,
                BTRIM(CAST(b.no_uang_muka AS text))
        SQL;

        $postgresStartedAt = microtime(true);
        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $postgresMs = $this->elapsedMilliseconds($postgresStartedAt);

        /*
         * Fallback sementara untuk kasus data sr_nasabah PostgreSQL belum lengkap.
         * Desktop masih membaca SQL Server sehingga nama pembeli bisa muncul,
         * sedangkan PostgreSQL hanya bisa menampilkan nama yang sudah termigrasi.
         * Jika koneksi SQL Server lama tersedia di config/database.php, method ini
         * akan mengisi nama yang masih kosong berdasarkan NO_UANG_MUKA.
         */
        $sqlServerTiming = $this->hydrateMissingBuyerNamesFromSqlServer($rows);

        /*
         * Pertahankan seluruh unit yang memenuhi filter transaksi, meskipun
         * nama pembelinya belum ditemukan di PostgreSQL maupun SQL Server.
         * Query utama sudah memakai LEFT JOIN dan fallback '-' sehingga blok
         * seperti GA/001, GA/003, dan GA/005 tidak boleh dibuang hanya karena
         * data nama pembeli belum lengkap.
         */

        $timing = [
            'resolver_ms' => $resolverMs,
            'postgres_ms' => $postgresMs,
            'sqlserver_ms' => $sqlServerTiming['duration_ms'],
            'sqlserver_status' => $sqlServerTiming['status'],
            'sqlserver_requested_names' => $sqlServerTiming['requested_names'],
            'result_rows' => count($rows),
            'total_ms' => $this->elapsedMilliseconds($totalStartedAt),
        ];

        $this->publishTimingHeaders($timing);
        Log::info('Timing get_summary penjualan tanda jadi agen', $timing);

        return $rows;
    }

    private function hydrateMissingBuyerNamesFromSqlServer(array &$rows): array
    {
        $startedAt = microtime(true);
        $timing = [
            'duration_ms' => 0,
            'status' => 'not_needed',
            'requested_names' => 0,
        ];

        if (count($rows) < 1) {
            return $timing;
        }

        $connectionName = $this->resolveSqlServerConnectionName();

        if ($connectionName === null) {
            $timing['status'] = 'connection_not_configured';
            $timing['duration_ms'] = $this->elapsedMilliseconds($startedAt);

            return $timing;
        }

        $noUangMukaList = [];

        foreach ($rows as $row) {
            $nama = $this->normalizeOption($row->NASABAH_NAMA ?? '', '');

            if ($nama !== '' && $nama !== '-') {
                continue;
            }

            $noUangMuka = strtoupper(trim((string) ($row->NO_UANG_MUKA ?? '')));

            if ($noUangMuka !== '') {
                $noUangMukaList[$noUangMuka] = true;
            }
        }

        $noUangMukaList = array_keys($noUangMukaList);
        $timing['requested_names'] = count($noUangMukaList);

        if (count($noUangMukaList) < 1) {
            $timing['duration_ms'] = $this->elapsedMilliseconds($startedAt);

            return $timing;
        }

        $namaByNoUangMuka = [];
        $timing['status'] = 'executed';

        try {
            foreach (array_chunk($noUangMukaList, 300) as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $matchedNoUangMuka = [];

                $sql = <<<SQLSRV
                    SELECT
                        UPPER(LTRIM(RTRIM(CAST(NO_UANG_MUKA AS VARCHAR(100))))) AS no_uang_muka,
                        UPPER(LTRIM(RTRIM(COALESCE(dbo.F_GET_PEMBELI_DP(UANG_MUKA_ID), '')))) AS nama_pembeli
                    FROM UANG_MUKA WITH (NOLOCK)
                    WHERE NO_UANG_MUKA IN ($placeholders)
                SQLSRV;

                $sqlServerRows = DB::connection($connectionName)->select($sql, $chunk);

                foreach ($sqlServerRows as $sqlServerRow) {
                    $noUangMuka = strtoupper(trim((string) ($sqlServerRow->no_uang_muka ?? '')));
                    $namaPembeli = strtoupper(trim((string) ($sqlServerRow->nama_pembeli ?? '')));

                    if ($noUangMuka !== '') {
                        $matchedNoUangMuka[$noUangMuka] = true;
                    }

                    if ($noUangMuka !== '' && $namaPembeli !== '' && $namaPembeli !== '-') {
                        $namaByNoUangMuka[$noUangMuka] = $namaPembeli;
                    }
                }

                /*
                 * Jalur langsung di atas dapat memakai indeks bawaan NO_UANG_MUKA.
                 * Untuk data lama yang mungkin mempunyai spasi/format tidak normal,
                 * ulangi hanya kode yang belum cocok dengan aturan lama. Dengan cara
                 * ini hasil tetap kompatibel, tetapi full scan normalisasi tidak
                 * menjadi jalur utama setiap request.
                 */
                $unmatchedChunk = array_values(array_filter(
                    $chunk,
                    static fn ($item) => !isset($matchedNoUangMuka[strtoupper(trim((string) $item))])
                ));

                if (count($unmatchedChunk) > 0) {
                    $timing['status'] = 'executed_with_normalized_retry';
                    $retryPlaceholders = implode(',', array_fill(0, count($unmatchedChunk), '?'));

                    $retrySql = <<<SQLSRV
                        SELECT
                            UPPER(LTRIM(RTRIM(CAST(NO_UANG_MUKA AS VARCHAR(100))))) AS no_uang_muka,
                            UPPER(LTRIM(RTRIM(COALESCE(dbo.F_GET_PEMBELI_DP(UANG_MUKA_ID), '')))) AS nama_pembeli
                        FROM UANG_MUKA WITH (NOLOCK)
                        WHERE UPPER(LTRIM(RTRIM(CAST(NO_UANG_MUKA AS VARCHAR(100)))))
                              IN ($retryPlaceholders)
                    SQLSRV;

                    $retryRows = DB::connection($connectionName)
                        ->select($retrySql, $unmatchedChunk);

                    foreach ($retryRows as $retryRow) {
                        $noUangMuka = strtoupper(trim((string) ($retryRow->no_uang_muka ?? '')));
                        $namaPembeli = strtoupper(trim((string) ($retryRow->nama_pembeli ?? '')));

                        if ($noUangMuka !== '' && $namaPembeli !== '' && $namaPembeli !== '-') {
                            $namaByNoUangMuka[$noUangMuka] = $namaPembeli;
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            /*
             * Fallback ini tidak boleh membuat laporan gagal. Jika koneksi SQL Server
             * belum tersedia di environment web, laporan tetap memakai data PostgreSQL.
             */
            $timing['status'] = 'failed_' . class_basename($exception);
            $timing['duration_ms'] = $this->elapsedMilliseconds($startedAt);

            return $timing;
        }

        if (count($namaByNoUangMuka) < 1) {
            $timing['status'] = 'no_names_found';
            $timing['duration_ms'] = $this->elapsedMilliseconds($startedAt);

            return $timing;
        }

        foreach ($rows as $row) {
            $nama = $this->normalizeOption($row->NASABAH_NAMA ?? '', '');

            if ($nama !== '' && $nama !== '-') {
                continue;
            }

            $noUangMuka = strtoupper(trim((string) ($row->NO_UANG_MUKA ?? '')));

            if (isset($namaByNoUangMuka[$noUangMuka])) {
                $row->NASABAH_NAMA = $namaByNoUangMuka[$noUangMuka];
            }
        }

        $timing['duration_ms'] = $this->elapsedMilliseconds($startedAt);

        return $timing;
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function publishTimingHeaders(array $timing): void
    {
        if (headers_sent()) {
            return;
        }

        $serverTiming = implode(', ', [
            'summary-resolver;dur=' . $timing['resolver_ms'],
            'summary-postgres;dur=' . $timing['postgres_ms'],
            'summary-sqlserver;dur=' . $timing['sqlserver_ms'],
            'summary-total;dur=' . $timing['total_ms'],
        ]);

        header('Server-Timing: ' . $serverTiming);
        header(
            'X-Summary-Timing: resolver=' . $timing['resolver_ms']
            . '; postgres=' . $timing['postgres_ms']
            . '; sqlserver=' . $timing['sqlserver_ms']
            . '; sqlserver_status=' . $timing['sqlserver_status']
            . '; missing_names=' . $timing['sqlserver_requested_names']
            . '; rows=' . $timing['result_rows']
            . '; total=' . $timing['total_ms']
        );
    }

    private function resolveSqlServerConnectionName(): ?string
    {
        $candidates = [
            'sqlsrv',
            'sqlserver',
            'sqlsrv_sris',
            'sris_sqlsrv',
            'sqlsrv_old',
        ];

        foreach ($candidates as $candidate) {
            if (config('database.connections.' . $candidate) !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeOption($value, string $default = '*'): string
    {
        $value = strtoupper(trim((string) ($value ?? '')));

        if ($value === '') {
            return $default;
        }

        $allLabels = [
            'SEMUA',
            'SEMUA AGEN',
            'SEMUA SEKTOR',
            'SEMUA LOKASI',
            'SEMUA TIPE BAYAR',
            'HARAP PILIH TIPE PEMBAYARAN',
            'PILIH TIPE PEMBAYARAN',
        ];

        if (in_array($value, $allLabels, true)) {
            return '*';
        }

        return $value;
    }

    private function normalizeAgen($value): string
    {
        $value = $this->normalizeOption($value, '*****');

        if ($value === '*') {
            return '*****';
        }

        return $value;
    }

    private function normalizeDate($value, ?string $default = null): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return $default ?: date('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
            // Format datepicker di web terlihat MM/DD/YYYY.
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[1], (int) $m[2]);
        }

        $time = strtotime($value);

        if ($time !== false) {
            return date('Y-m-d', $time);
        }

        return $default ?: date('Y-m-d');
    }

    private function isExportRequest($request): bool
    {
        foreach (['excel', 'export', 'export_excel', 'download', 'is_excel', 'full_export', 'all_data'] as $key) {
            if (!isset($request->{$key})) {
                continue;
            }

            $value = strtoupper(trim((string) $request->{$key}));

            if (in_array($value, ['1', 'Y', 'YES', 'TRUE', 'EXCEL', 'EXPORT'], true)) {
                return true;
            }
        }

        if (is_object($request) && method_exists($request, 'path')) {
            $path = strtolower((string) $request->path());

            if (str_contains($path, 'excel') || str_contains($path, 'export')) {
                return true;
            }
        }

        return false;
    }

    private function resolveViewLimit($request): int
    {
        if ($this->isExportRequest($request)) {
            return 0;
        }

        $rawLimit = $request->limit
            ?? $request->page_limit
            ?? $request->per_page
            ?? self::DEFAULT_VIEW_LIMIT;

        $rawLimitText = strtoupper(trim((string) $rawLimit));

        if (in_array($rawLimitText, ['0', '*', 'ALL', 'FULL', 'SEMUA'], true)) {
            return 0;
        }

        $limit = (int) $rawLimit;

        if ($limit < 1) {
            $limit = self::DEFAULT_VIEW_LIMIT;
        }

        return min($limit, self::MAX_VIEW_LIMIT);
    }

    private function placeholderList(array $items): string
    {
        return implode(',', array_fill(0, count($items), '?'));
    }

    private function normalizeCodes(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $item = strtoupper(trim((string) $item));

            if ($item !== '') {
                $result[$item] = true;
            }
        }

        return array_keys($result);
    }

    private function resolveLokasiValues(string $lokasi): array
    {
        if ($lokasi === '*' || $lokasi === '') {
            return ['*'];
        }

        $sql = <<<'SQL'
            SELECT DISTINCT kode.value
            FROM public.sr_lokasi AS lokasi
            CROSS JOIN LATERAL (
                VALUES
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lokasi', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lv2', ''))))
            ) AS kode(value)
            WHERE (
                   UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'deskripsi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lokasi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lv2', ''))) = ?
            )
              AND kode.value <> ''
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($sql, [
            $lokasi,
            $lokasi,
            $lokasi,
        ]);

        $values = [$lokasi];

        foreach ($rows as $row) {
            $values[] = $row->value ?? '';
        }

        return $this->normalizeCodes($values);
    }

    private function resolveSektorValues(string $sektor, string $perusahaan): array
    {
        if ($sektor === '*' || $sektor === '') {
            return ['*'];
        }

        $fallbackByDescription = [
            'CHELIA RESIDENCE' => 'CLA',
            'EMERALD COMMERCIAL' => 'EMC',
        ];
        $canonicalSektor = $fallbackByDescription[$sektor] ?? $sektor;

        $sql = <<<'SQL'
            SELECT DISTINCT kode.value
            FROM public.sr_sektor AS sektor
            CROSS JOIN LATERAL (
                VALUES
                    (UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_proyek', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_sektor', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_cluster', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_lokasi', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_lv2', ''))))
            ) AS kode(value)
            WHERE (
                   UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'deskripsi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_proyek', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_sektor', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_cluster', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_lokasi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_lv2', ''))) = ?
            )
              AND (
                    UPPER(BTRIM(COALESCE(to_jsonb(sektor) ->> 'kd_perusahaan', ''))) = ?
                    OR COALESCE(to_jsonb(sektor) ->> 'kd_perusahaan', '') = ''
                    OR ? = '*'
              )
              AND kode.value <> ''

            UNION

            SELECT DISTINCT kode.value
            FROM public.sr_lokasi AS lokasi
            CROSS JOIN LATERAL (
                VALUES
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_proyek', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_sektor', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_cluster', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lokasi', '')))),
                    (UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lv2', ''))))
            ) AS kode(value)
            WHERE (
                   UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'deskripsi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_proyek', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_sektor', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_cluster', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lokasi', ''))) = ?
                OR UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_lv2', ''))) = ?
            )
              AND (
                    UPPER(BTRIM(COALESCE(to_jsonb(lokasi) ->> 'kd_perusahaan', ''))) = ?
                    OR COALESCE(to_jsonb(lokasi) ->> 'kd_perusahaan', '') = ''
                    OR ? = '*'
              )
              AND kode.value <> ''
        SQL;

        $bindings = [
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $perusahaan,
            $perusahaan,
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $sektor,
            $perusahaan,
            $perusahaan,
        ];

        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $values = [$canonicalSektor, $sektor];

        foreach ($rows as $row) {
            $values[] = $row->value ?? '';
        }

        return $this->normalizeCodes($values);
    }
}