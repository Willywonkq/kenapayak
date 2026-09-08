<?php

namespace App\Models\SRIS\Pemesanan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class daftar_sp_sudah_ppjb_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';


    /**
     * Cache metadata kolom hanya di memory selama request berjalan.
     * Tidak menulis ke database dan tidak memakai persistent cache.
     */
    private array $tableColumnsCache = [];

    private function tableColumns(string $table): array
    {
        if (isset($this->tableColumnsCache[$table])) {
            return $this->tableColumnsCache[$table];
        }

        $rows = DB::connection(self::CONNECTION)->select(
            '
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = ?
                  AND table_name = ?
            ',
            [self::SCHEMA, $table]
        );

        $columns = [];

        foreach ($rows as $row) {
            $name = strtolower(trim((string) ($row->column_name ?? '')));

            if ($name !== '') {
                $columns[$name] = true;
            }
        }

        $this->tableColumnsCache[$table] = $columns;

        return $columns;
    }

    private function hasTableColumn(string $table, string $column): bool
    {
        return isset($this->tableColumns($table)[strtolower($column)]);
    }

    /**
     * Membentuk COALESCE dari kolom fisik yang tersedia.
     * Fallback dan urutan kandidat tetap sama seperti akses to_jsonb() sebelumnya.
     */
    private function directTextExpression(
        string $table,
        string $alias,
        array $columns,
        string $default = '',
        bool $trim = false,
        bool $upper = false
    ): string {
        $parts = [];

        foreach ($columns as $column) {
            if (!$this->hasTableColumn($table, $column)) {
                continue;
            }

            $value = "CAST({$alias}.{$column} AS text)";

            if ($trim) {
                $value = "BTRIM({$value})";
            }

            $parts[] = "NULLIF({$value}, '')";
        }

        $escapedDefault = str_replace("'", "''", $default);

        if (!count($parts)) {
            $expression = "'{$escapedDefault}'";
        } else {
            $expression = 'COALESCE('
                . implode(', ', $parts)
                . ", '{$escapedDefault}')";
        }

        if ($upper) {
            return "UPPER(BTRIM({$expression}))";
        }

        return $expression;
    }

    private function pgTextArray(array $values): string
    {
        $escaped = array_map(static function ($value) {
            $value = (string) $value;
            $value = str_replace(['\\\\', '"'], ['\\\\\\\\', '\\\\"'], $value);

            return '"' . $value . '"';
        }, $values);

        return '{' . implode(',', $escaped) . '}';
    }

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

    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = strtoupper(trim((string) $kdPerusahaan));

        $stokKode = $this->directTextExpression(
            'sr_stok',
            'stok',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            '',
            true,
            true
        );

        $stokPerusahaan = $this->directTextExpression(
            'sr_stok',
            'stok',
            ['kd_perusahaan', 'kd_unit', 'kd_pt'],
            '',
            true,
            true
        );

        $sektorKode = $this->directTextExpression(
            'sr_sektor',
            'sektor',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            '',
            true,
            true
        );

        $sektorDeskripsi = $this->directTextExpression(
            'sr_sektor',
            'sektor',
            ['deskripsi'],
            '',
            true,
            false
        );

        $sektorPerusahaan = $this->directTextExpression(
            'sr_sektor',
            'sektor',
            ['kd_perusahaan'],
            '',
            true,
            true
        );

        $sektorAktif = $this->directTextExpression(
            'sr_sektor',
            'sektor',
            ['flag_aktif'],
            'A',
            true,
            true
        );

        $lokasiKode = $this->directTextExpression(
            'sr_lokasi',
            'lokasi',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor'],
            '',
            true,
            true
        );

        $lokasiDeskripsi = $this->directTextExpression(
            'sr_lokasi',
            'lokasi',
            ['deskripsi'],
            '',
            true,
            false
        );

        $lokasiPerusahaan = $this->directTextExpression(
            'sr_lokasi',
            'lokasi',
            ['kd_perusahaan'],
            '',
            true,
            true
        );

        $lokasiAktif = $this->directTextExpression(
            'sr_lokasi',
            'lokasi',
            ['flag_aktif'],
            'A',
            true,
            true
        );

        $sql = <<<SQL
            WITH stok_terpilih AS MATERIALIZED (
                SELECT DISTINCT
                    {$stokKode} AS kode,
                    {$stokPerusahaan} AS kd_perusahaan
                FROM public.sr_stok AS stok
                WHERE {$stokKode} <> ''
                  AND {$stokPerusahaan} <> ''
                  AND (
                        {$stokPerusahaan} = :kd_perusahaan
                        OR :kd_perusahaan = '*'
                        OR :kd_perusahaan = ''
                  )
            ),

            master_sektor AS MATERIALIZED (
                SELECT
                    {$sektorKode} AS kode,
                    {$sektorDeskripsi} AS deskripsi,
                    {$sektorPerusahaan} AS kd_perusahaan,
                    {$sektorAktif} AS flag_aktif,
                    2 AS source_priority
                FROM public.sr_sektor AS sektor

                UNION ALL

                SELECT
                    {$lokasiKode} AS kode,
                    {$lokasiDeskripsi} AS deskripsi,
                    {$lokasiPerusahaan} AS kd_perusahaan,
                    {$lokasiAktif} AS flag_aktif,
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
                                WHEN master_sektor.kd_perusahaan = stok_terpilih.kd_perusahaan
                                THEN 0
                                WHEN master_sektor.kd_perusahaan = ''
                                THEN 1
                                ELSE 2
                            END,
                            CASE WHEN master_sektor.flag_aktif <> 'T' THEN 0 ELSE 1 END,
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

    public function obtainLokasi()
    {
        return collect(self::LOKASI_LIST)
            ->map(static function (array $lokasi): object {
                return (object) $lokasi;
            });
    }

    public function obtainJenis()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_jenis_bangunan as jenis')
            ->selectRaw("
                BTRIM(CAST(jenis.flag_laporan AS text)) AS \"FLAG_LAPORAN\",

                CASE BTRIM(CAST(jenis.flag_laporan AS text))
                    WHEN '1' THEN 'Rumah'
                    WHEN '2' THEN 'Kavling'
                    WHEN '3' THEN 'Rukan'
                    WHEN '4' THEN 'Apartemen'
                    WHEN '5' THEN 'Kantor'
                    ELSE BTRIM(CAST(jenis.deskripsi AS text))
                END AS \"DESKRIPSI\"
            ")
            ->whereRaw("
                BTRIM(
                    COALESCE(CAST(jenis.flag_laporan AS text), '')
                ) IN ('1', '2', '3', '4', '5')
            ")
            ->distinct()
            ->orderByRaw('BTRIM(CAST(jenis.flag_laporan AS text)) ASC')
            ->get();
    }

    private function normalizeOption($value, string $default = '*'): string
    {
        $value = strtoupper(trim((string) ($value ?? '')));

        if ($value === '') {
            return $default;
        }

        $allLabels = [
            'SEMUA',
            'SEMUA SEKTOR',
            'SEMUA LOKASI',
            'SEMUA JENIS',
            'SEMUA DATA',
            'ALL',
        ];

        if (in_array($value, $allLabels, true)) {
            return '*';
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
            // Format datepicker di layar: MM/DD/YYYY.
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[1], (int) $m[2]);
        }

        $time = strtotime($value);

        if ($time !== false) {
            return date('Y-m-d', $time);
        }

        return $default ?: date('Y-m-d');
    }

    private function numericValue($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $value = str_replace(',', '', trim((string) $value));

        if ($value === '' || !is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
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

        /*
         * Modal sektor mengirim KD_SEKTOR. Jika sudah berupa kode, tidak perlu
         * scan sr_sektor + sr_lokasi lagi sebelum query laporan dijalankan.
         */
        if (preg_match('/^[A-Z0-9_-]{1,30}$/', $sektor)) {
            return $this->normalizeCodes([$canonicalSektor, $sektor]);
        }

        $sql = <<<'SQL'
            WITH sektor_normalized AS MATERIALIZED (
                SELECT
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) AS deskripsi,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) AS kd_proyek,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) AS kd_sektor,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) AS kd_cluster,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) AS kd_lokasi,
                    UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) AS kd_lv2
                FROM public.sr_sektor AS s

                UNION ALL

                SELECT
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'deskripsi', ''))) AS deskripsi,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_proyek', ''))) AS kd_proyek,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_sektor', ''))) AS kd_sektor,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_cluster', ''))) AS kd_cluster,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lokasi', ''))) AS kd_lokasi,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lv2', ''))) AS kd_lv2
                FROM public.sr_lokasi AS l
            )
            SELECT DISTINCT kode.value
            FROM sektor_normalized AS s
            CROSS JOIN LATERAL (
                VALUES
                    (s.kd_proyek),
                    (s.kd_sektor),
                    (s.kd_cluster),
                    (s.kd_lokasi),
                    (s.kd_lv2)
            ) AS kode(value)
            WHERE ? IN (
                    s.deskripsi,
                    s.kd_proyek,
                    s.kd_sektor,
                    s.kd_cluster,
                    s.kd_lokasi,
                    s.kd_lv2
                  )
              AND kode.value <> ''
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($sql, [$sektor]);
        $values = [$canonicalSektor, $sektor];

        foreach ($rows as $row) {
            $values[] = $row->value ?? '';
        }

        return $this->normalizeCodes($values);
    }

    private function resolveLokasiValues(string $lokasi): array
    {
        if ($lokasi === '*' || $lokasi === '') {
            return ['*'];
        }

        /*
         * Select lokasi mengirim kode KD_LOKASI. Hindari scan master bila
         * nilainya sudah jelas berupa kode.
         */
        if (preg_match('/^[A-Z0-9_-]{1,30}$/', $lokasi)) {
            return [$lokasi];
        }

        $sql = <<<'SQL'
            WITH lokasi_normalized AS MATERIALIZED (
                SELECT
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'deskripsi', ''))) AS deskripsi,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lokasi', ''))) AS kd_lokasi,
                    UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lv2', ''))) AS kd_lv2
                FROM public.sr_lokasi AS l
            )
            SELECT DISTINCT kode.value
            FROM lokasi_normalized AS l
            CROSS JOIN LATERAL (
                VALUES
                    (l.kd_lokasi),
                    (l.kd_lv2)
            ) AS kode(value)
            WHERE ? IN (l.deskripsi, l.kd_lokasi, l.kd_lv2)
              AND kode.value <> ''
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($sql, [$lokasi]);

        $values = [$lokasi];

        foreach ($rows as $row) {
            $values[] = $row->value ?? '';
        }

        return $this->normalizeCodes($values);
    }

    public function obtainSPSudahPPJB($request)
    {
        $tglAwal = $this->normalizeDate($request->tgl_awal ?? null);
        $tglAkhir = $this->normalizeDate($request->tgl_akhir ?? null, $tglAwal);

        $jenis = $this->normalizeOption($request->jenis ?? '*');
        $lokasi = $this->normalizeOption($request->lokasi ?? '*');
        $sektor = $this->normalizeOption($request->sektor ?? '*');

        $defaultPerusahaan = session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA';
        $perusahaan = $this->normalizeOption($request->perusahaan ?? $defaultPerusahaan, $defaultPerusahaan);

        $persen = $this->numericValue($request->persen ?? 100);
        if ($persen < 0) {
            $persen = 0;
        }

        $schema = self::SCHEMA;

        /*
         * Ekspresi stok dibuat dari kolom fisik yang benar-benar tersedia.
         * Ini menghindari to_jsonb(stok) berulang pada setiap row sr_stok.
         */
        /*
         * Urutan kandidat kolom mendahulukan kd_jenis, kd_tipe, dan kd_model.
         *
         * Query desktop menyambung TIPE memakai STOK.KD_JENIS dan STOK.KD_TIPE,
         * serta mencari MODEL memakai STOK.KD_MODEL. Versi sebelumnya di sini
         * mendahulukan varian kd_*_bgn, sehingga bila kolom itu ada dan berisi
         * nilai yang berbeda, INNER JOIN ke sr_tipe gagal dan barisnya hilang
         * dari laporan. Kolom kd_*_bgn tetap dipakai sebagai cadangan ketika
         * kolom utamanya kosong.
         */
        $stokJenisRaw = $this->directTextExpression(
            'sr_stok', 'stok', ['kd_jenis', 'kd_jenis_bgn'], '', false, false
        );
        $stokTipeRaw = $this->directTextExpression(
            'sr_stok', 'stok', ['kd_tipe', 'kd_tipe_bgn'], '', false, false
        );
        $stokModelRaw = $this->directTextExpression(
            'sr_stok', 'stok', ['kd_model', 'kd_model_bgn'], '000', false, false
        );
        $stokLokasiRaw = $this->directTextExpression(
            'sr_stok', 'stok', ['kd_lokasi', 'kd_lv2'], '', false, false
        );
        $stokSektorRaw = $this->directTextExpression(
            'sr_stok', 'stok', ['kd_sektor', 'kd_proyek', 'kd_cluster'], '', false, false
        );

        $stokLokasiFilter = "UPPER(BTRIM({$stokLokasiRaw}))";
        $stokSektorFilter = "UPPER(BTRIM(COALESCE(NULLIF({$stokSektorRaw}, ''), NULLIF({$stokLokasiRaw}, ''), '')))";

        $lokasiValues = $this->resolveLokasiValues($lokasi);
        $sektorValues = $this->resolveSektorValues($sektor, $perusahaan);

        $where = [];
        $bindings = [];

        $where[] = "ppjb.tgl_ppjb >= ?::date";
        $bindings[] = $tglAwal;

        $where[] = "ppjb.tgl_ppjb < (?::date + INTERVAL '1 day')";
        $bindings[] = $tglAkhir;

        $where[] = "UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS text), ''))) = ?";
        $bindings[] = $perusahaan;

        $where[] = "UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS text), ''))) = 'A'";

        $where[] = "(
            UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS text), ''))) = 'A'
            OR ppjb.tgl_batal > ?::date
        )";
        $bindings[] = $tglAkhir;

        $where[] = "NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS text), '')), '') IS NULL";
        $where[] = "NULLIF(BTRIM(COALESCE(CAST(stok.blok AS text), '')), '') IS NOT NULL";
        $where[] = "NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS text), '')), '') IS NOT NULL";

        /*
         * Filter jenis dipasang setelah kandidat PPJB + stok dipersempit.
         * Dengan begitu PostgreSQL tidak perlu menjalankan join tabel referensi
         * terhadap seluruh histori PPJB.
         */
        $jenisBindings = [];

        if ($jenis !== '*') {
            $jenisWhere = "UPPER(BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan AS text), ''))) = ?";
            $jenisBindings[] = $jenis;
        } else {
            /*
             * Desktop memakai ( JENIS_BANGUNAN.FLAG_LAPORAN = :jenis or :jenis = '*' ),
             * jadi pilihan Semua tidak membatasi apa pun. Versi sebelumnya di sini
             * membatasi flag_laporan ke '1'..'5' sehingga jenis bangunan dengan kode
             * lain, kosong, atau NULL ikut hilang dari laporan.
             */
            $jenisWhere = "TRUE";
        }

        if (!(count($lokasiValues) === 1 && $lokasiValues[0] === '*')) {
            $where[] = $stokLokasiFilter . " IN (" . $this->placeholderList($lokasiValues) . ")";
            array_push($bindings, ...$lokasiValues);
        }

        if (!(count($sektorValues) === 1 && $sektorValues[0] === '*')) {
            $where[] = $stokSektorFilter . " IN (" . $this->placeholderList($sektorValues) . ")";
            array_push($bindings, ...$sektorValues);
        }

        $whereSql = implode("\n                AND ", $where);
        $bindings = array_merge($bindings, $jenisBindings);

        /*
         * Master referensi dinormalisasi sekali per query.
         * Versi lama membangun to_jsonb(sr_sektor/sr_lokasi/sr_model)
         * berulang di LATERAL untuk setiap row hasil PPJB.
         */
        $modelKode = $this->directTextExpression(
            'sr_model', 'master_model', ['kd_model', 'kd_model_bgn'], '', false, false
        );

        $sektorKode = $this->directTextExpression(
            'sr_sektor',
            'master_sektor',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            '',
            true,
            true
        );
        $sektorPerusahaan = $this->directTextExpression(
            'sr_sektor', 'master_sektor', ['kd_perusahaan'], '', true, true
        );
        $sektorDeskripsi = $this->directTextExpression(
            'sr_sektor', 'master_sektor', ['deskripsi'], '', true, false
        );
        $sektorAktif = $this->directTextExpression(
            'sr_sektor', 'master_sektor', ['flag_aktif'], 'A', true, true
        );

        $lokasiMasterKode = $this->directTextExpression(
            'sr_lokasi',
            'master_lokasi',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor'],
            '',
            true,
            true
        );
        $lokasiMasterPerusahaan = $this->directTextExpression(
            'sr_lokasi', 'master_lokasi', ['kd_perusahaan'], '', true, true
        );
        $lokasiMasterDeskripsi = $this->directTextExpression(
            'sr_lokasi', 'master_lokasi', ['deskripsi'], '', true, false
        );
        $lokasiMasterAktif = $this->directTextExpression(
            'sr_lokasi', 'master_lokasi', ['flag_aktif'], 'A', true, true
        );

        /*
         * Query utama sengaja tidak menghitung ANGSURAN di dalam CTE besar.
         * Nilai JML_BAYAR, PROSENTASE, dan TGL_CAPAI dihitung terpisah oleh
         * hydratePembayaranColumns() agar sr_angsuran tidak membuat report timeout.
         */
        $sql = <<<SQL
            WITH model_master AS MATERIALIZED (
                SELECT
                    {$modelKode} AS kode,
                    BTRIM(CAST(master_model.deskripsi AS text)) AS deskripsi
                FROM {$schema}.sr_model AS master_model
            ),

            sektor_master AS MATERIALIZED (
                SELECT
                    {$sektorKode} AS kode,
                    {$sektorPerusahaan} AS kd_perusahaan,
                    {$sektorDeskripsi} AS deskripsi,
                    {$sektorAktif} AS flag_aktif,
                    2 AS source_priority
                FROM {$schema}.sr_sektor AS master_sektor

                UNION ALL

                SELECT
                    {$lokasiMasterKode} AS kode,
                    {$lokasiMasterPerusahaan} AS kd_perusahaan,
                    {$lokasiMasterDeskripsi} AS deskripsi,
                    {$lokasiMasterAktif} AS flag_aktif,
                    1 AS source_priority
                FROM {$schema}.sr_lokasi AS master_lokasi
            ),

            lokasi_master AS MATERIALIZED (
                SELECT
                    {$lokasiMasterKode} AS kode,
                    {$lokasiMasterDeskripsi} AS deskripsi
                FROM {$schema}.sr_lokasi AS master_lokasi
            ),

            filtered_ppjb AS MATERIALIZED (
                SELECT
                    ppjb.ppjb_id,
                    BTRIM(CAST(ppjb.ppjb_id AS text)) AS ppjb_id_text,
                    ppjb.tgl_ppjb,
                    ppjb.user_entry,
                    ppjb.no_ppjb,
                    ppjb.harga_jual,
                    ppjb.kd_sales,
                    ppjb.kd_agen,

                    stok.stok_id,
                    stok.blok,
                    stok.nomor,
                    stok.luas_tanah AS stok_luas_tanah,
                    stok.luas_bangunan AS stok_luas_bangunan,
                    stok.kd_perusahaan,

                    UPPER(BTRIM({$stokJenisRaw})) AS stok_kd_jenis,

                    UPPER(BTRIM({$stokTipeRaw})) AS stok_kd_tipe,

                    {$stokModelRaw} AS stok_kd_model,

                    {$stokLokasiRaw} AS stok_kd_lokasi,

                    {$stokSektorRaw} AS stok_kd_sektor

                FROM {$schema}.sr_ppjb AS ppjb
                INNER JOIN {$schema}.sr_stok AS stok
                    ON stok.stok_id = ppjb.stok_id
                WHERE {$whereSql}
            ),

            candidate_ppjb AS (
                SELECT DISTINCT ON (fp.ppjb_id)
                    fp.ppjb_id,
                    fp.ppjb_id_text,
                    fp.tgl_ppjb,
                    fp.user_entry,
                    fp.no_ppjb,
                    fp.harga_jual,
                    fp.kd_sales,
                    fp.kd_agen,

                    fp.stok_id,
                    fp.blok,
                    fp.nomor,
                    fp.stok_luas_tanah,
                    fp.stok_luas_bangunan,
                    fp.kd_perusahaan,
                    fp.stok_kd_model,
                    fp.stok_kd_lokasi,
                    fp.stok_kd_sektor,

                    tipe.deskripsi AS tipe_deskripsi,
                    tipe.luas_tanah AS tipe_luas_tanah,
                    tipe.luas_bangunan AS tipe_luas_bangunan,

                    jenis_bangunan.flag_laporan,
                    jenis_bangunan.deskripsi AS jenis_bgn,

                    sales.kd_sales AS sales_kd_sales,
                    sales.deskripsi AS sales_nama,
                    agen.nama_agen AS agen_nama

                FROM filtered_ppjb AS fp

                INNER JOIN {$schema}.sr_tipe AS tipe
                    ON UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) =
                       fp.stok_kd_jenis
                   AND UPPER(BTRIM(CAST(tipe.kd_tipe AS text))) =
                       fp.stok_kd_tipe

                INNER JOIN {$schema}.sr_jenis_bangunan AS jenis_bangunan
                    ON UPPER(BTRIM(CAST(jenis_bangunan.kd_jenis AS text))) =
                       UPPER(BTRIM(CAST(tipe.kd_jenis AS text)))

                LEFT JOIN {$schema}.sr_sales AS sales
                    ON UPPER(BTRIM(CAST(sales.kd_sales AS text))) =
                       UPPER(BTRIM(CAST(fp.kd_sales AS text)))

                LEFT JOIN {$schema}.sr_agen AS agen
                    ON UPPER(BTRIM(CAST(agen.kd_agen AS text))) =
                       UPPER(BTRIM(CAST(fp.kd_agen AS text)))

                WHERE {$jenisWhere}

                ORDER BY
                    fp.ppjb_id,
                    fp.tgl_ppjb DESC NULLS LAST,
                    fp.no_ppjb DESC NULLS LAST
            ),

            base_ppjb AS (
                SELECT DISTINCT
                    ppjb_id,
                    ppjb_id_text,
                    NULLIF(
                        REGEXP_REPLACE(
                            COALESCE(CAST(ppjb_id AS text), ''),
                            '[^0-9]',
                            '',
                            'g'
                        ),
                        ''
                    ) AS ppjb_id_digits
                FROM candidate_ppjb
                WHERE ppjb_id IS NOT NULL
            ),

            /*
             * Satu PPJB dicari memakai ID aslinya, dan hanya bila berbeda juga
             * memakai versi angkanya saja. Karena kedua nilai itu dijamin
             * berbeda, satu baris jadwal maupun angsuran mustahil cocok dua
             * kali untuk PPJB yang sama, sehingga penjumlahan di bawah tidak
             * perlu lagi memakai DISTINCT.
             */
            ppjb_lookup AS (
                SELECT
                    bp.ppjb_id,
                    CAST(bp.ppjb_id AS text) AS lookup_id
                FROM base_ppjb AS bp

                UNION ALL

                SELECT
                    bp.ppjb_id,
                    bp.ppjb_id_digits AS lookup_id
                FROM base_ppjb AS bp
                WHERE bp.ppjb_id_digits IS NOT NULL
                  AND bp.ppjb_id_digits <> CAST(bp.ppjb_id AS text)
            ),

            jadwal_source AS (
                /*
                 * DISTINCT dihapus.
                 *
                 * Sebelumnya baris jadwal dengan jadwal_id kosong atau berulang
                 * dan nilai yang kebetulan sama ikut tergabung menjadi satu,
                 * sehingga HARGA_SETELAH_PPJB menjadi lebih kecil daripada
                 * SUM(JUMLAH) pada query desktop.
                 */
                SELECT
                    pl.ppjb_id,
                    jadwal_angsuran.kd_transaksi,
                    jadwal_angsuran.jumlah
                FROM ppjb_lookup AS pl
                INNER JOIN {$schema}.sr_jadwal_angsuran AS jadwal_angsuran
                    ON CAST(jadwal_angsuran.ppjb_id AS text) = pl.lookup_id
            ),

            jadwal_by_ppjb AS (
                SELECT
                    js.ppjb_id,
                    COALESCE(SUM(js.jumlah), 0) AS extra_harga
                FROM jadwal_source AS js
                INNER JOIN {$schema}.sr_kode_transaksi AS kode_transaksi
                    ON UPPER(BTRIM(CAST(kode_transaksi.kd_transaksi AS text))) =
                       UPPER(BTRIM(CAST(js.kd_transaksi AS text)))
                WHERE (
                        (
                            UPPER(BTRIM(COALESCE(CAST(kode_transaksi.flag_hitung AS text), ''))) = 'Y'
                            AND UPPER(BTRIM(COALESCE(CAST(kode_transaksi.flag_pajak AS text), ''))) = 'Y'
                        )
                        OR UPPER(BTRIM(CAST(kode_transaksi.kd_transaksi AS text))) = 'DCB'
                      )
                  AND UPPER(BTRIM(CAST(js.kd_transaksi AS text))) NOT IN ('ANG', 'UMK')
                GROUP BY js.ppjb_id
            ),

            pembeli_ppjb_match AS (
                /*
                 * Pencarian pembeli ikut memakai ppjb_lookup, sama seperti
                 * angsuran dan jadwal. Sebelumnya hanya dicocokkan dengan
                 * ppjb_id apa adanya, sehingga PPJB yang di sr_pembeli_ppjb
                 * tersimpan dengan format angka saja tidak pernah ketemu dan
                 * kolom Nama Pembeli selalu berisi '-'.
                 */
                SELECT
                    pl.ppjb_id,
                    pp.nasabah_id,

                    /*
                     * Database baru tidak lagi menjamin NASABAH_ID bertipe numeric.
                     * Simpan dan bandingkan sebagai text agar ID alfanumerik tetap utuh.
                     * Tidak ada karakter yang dibuang sehingga data pembeli tidak berubah.
                     */
                    NULLIF(
                        UPPER(BTRIM(CAST(pp.nasabah_id AS text))),
                        ''
                    ) AS nasabah_id_text,

                    COALESCE(
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama'), ''),
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama_nasabah'), ''),
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama_pembeli'), ''),
                        ''
                    ) AS nama_pp
                FROM ppjb_lookup AS pl
                INNER JOIN {$schema}.sr_pembeli_ppjb AS pp
                    ON CAST(pp.ppjb_id AS text) = pl.lookup_id
                WHERE COALESCE(NULLIF(UPPER(BTRIM(to_jsonb(pp) ->> 'flag_aktif')), ''), 'Y') IN ('A', 'Y')
            ),

            pembeli_ppjb AS (
                SELECT
                    pm.ppjb_id,
                    STRING_AGG(
                        DISTINCT UPPER(
                            COALESCE(
                                NULLIF(BTRIM(CAST(nasabah.nama AS text)), ''),
                                NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_nasabah'), ''),
                                NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_pembeli'), ''),
                                NULLIF(BTRIM(pm.nama_pp), ''),
                                '-'
                            )
                        ),
                        ', '
                        ORDER BY UPPER(
                            COALESCE(
                                NULLIF(BTRIM(CAST(nasabah.nama AS text)), ''),
                                NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_nasabah'), ''),
                                NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_pembeli'), ''),
                                NULLIF(BTRIM(pm.nama_pp), ''),
                                '-'
                            )
                        )
                    ) AS nama_pembeli
                FROM pembeli_ppjb_match AS pm
                LEFT JOIN {$schema}.sr_nasabah AS nasabah
                    ON pm.nasabah_id_text =
                       NULLIF(
                           UPPER(BTRIM(CAST(nasabah.nasabah_id AS text))),
                           ''
                       )
                GROUP BY pm.ppjb_id
            )

            SELECT
                cp.tgl_ppjb AS "TGL_PPJB",
                cp.user_entry AS "USER_ENTRY",
                cp.no_ppjb AS "NO_PPJB",

                BTRIM(CAST(cp.blok AS text)) || '/' || BTRIM(CAST(cp.nomor AS text)) AS "BLOK_NOMOR",

                cp.tipe_deskripsi AS "TIPE_DESKRIPSI",
                cp.tipe_deskripsi AS "DESKRIPSI",
                cp.tipe_luas_tanah AS "TIPE_LUAS_TANAH",
                cp.tipe_luas_bangunan AS "TIPE_LUAS_BANGUNAN",

                cp.stok_luas_tanah AS "STOK_LUAS_TANAH",
                cp.stok_luas_bangunan AS "STOK_LUAS_BANGUNAN",

                COALESCE(cp.stok_luas_tanah, cp.tipe_luas_tanah) AS "LUAS_TANAH_REPORT",
                COALESCE(cp.stok_luas_bangunan, cp.tipe_luas_bangunan) AS "LUAS_BANGUNAN_REPORT",

                model.deskripsi AS "MODEL",

                cp.harga_jual AS "HARGA_JUAL",

                (
                    COALESCE(cp.harga_jual, 0)
                    + COALESCE(jadwal.extra_harga, 0)
                ) AS "HARGA_SETELAH_PPJB",

                COALESCE(
                    NULLIF(pembeli_ppjb.nama_pembeli, '-'),
                    '-'
                ) AS "NASABAH_NAMA",

                BTRIM(CAST(cp.sales_kd_sales AS text)) AS "KD_SALES",
                BTRIM(CAST(cp.sales_nama AS text)) AS "NAMA_SALES",
                BTRIM(CAST(cp.agen_nama AS text)) AS "NAMA_AGEN",

                BTRIM(CAST(cp.kd_perusahaan AS text)) AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",

                BTRIM(CAST(cp.flag_laporan AS text)) AS "FLAG_LAPORAN",
                BTRIM(CAST(cp.jenis_bgn AS text)) AS "JENIS_BGN",

                0::numeric AS "JML_BAYAR",
                0::numeric AS "PROSENTASE",
                NULL::date AS "TGL_CAPAI",

                COALESCE(
                    NULLIF(BTRIM(CAST(sektor.deskripsi AS text)), ''),
                    CASE UPPER(BTRIM(COALESCE(CAST(cp.stok_kd_sektor AS text), '')))
                        WHEN 'CLA' THEN 'CHELIA RESIDENCE'
                        WHEN 'EMC' THEN 'EMERALD COMMERCIAL'
                        ELSE UPPER(BTRIM(COALESCE(CAST(cp.stok_kd_sektor AS text), '')))
                    END
                ) AS "NAMA_SEKTOR",
                BTRIM(CAST(lokasi.deskripsi AS text)) AS "NAMA_LOKASI",

                cp.ppjb_id AS "PPJB_ID_INTERNAL"

            FROM candidate_ppjb AS cp

            LEFT JOIN jadwal_by_ppjb AS jadwal
                ON jadwal.ppjb_id = cp.ppjb_id

            LEFT JOIN pembeli_ppjb AS pembeli_ppjb
                ON pembeli_ppjb.ppjb_id = cp.ppjb_id

            LEFT JOIN LATERAL (
                SELECT mm.deskripsi
                FROM model_master AS mm
                WHERE mm.kode = COALESCE(NULLIF(cp.stok_kd_model, ''), '000')
                LIMIT 1
            ) AS model ON TRUE

            LEFT JOIN LATERAL (
                SELECT s.deskripsi
                FROM sektor_master AS s
                WHERE s.kode = UPPER(BTRIM(COALESCE(CAST(cp.stok_kd_sektor AS text), '')))
                  AND s.deskripsi <> ''
                ORDER BY
                    CASE
                        WHEN s.kd_perusahaan = UPPER(BTRIM(COALESCE(CAST(cp.kd_perusahaan AS text), '')))
                        THEN 0
                        WHEN s.kd_perusahaan = ''
                        THEN 1
                        ELSE 2
                    END,
                    CASE WHEN s.flag_aktif <> 'T' THEN 0 ELSE 1 END,
                    s.source_priority,
                    s.deskripsi
                LIMIT 1
            ) AS sektor ON TRUE

            LEFT JOIN LATERAL (
                SELECT lm.deskripsi
                FROM lokasi_master AS lm
                WHERE lm.kode = UPPER(BTRIM(COALESCE(CAST(cp.stok_kd_lokasi AS text), '')))
                LIMIT 1
            ) AS lokasi ON TRUE

            ORDER BY
                cp.tgl_ppjb,
                cp.no_ppjb,
                BTRIM(CAST(cp.blok AS text)) || '/' || BTRIM(CAST(cp.nomor AS text))
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);

        $this->hydratePembayaranColumns($rows, $persen);

        return $rows;
    }

    private function hydratePembayaranColumns(array &$rows, float $minimalPersen): void
    {
        if (count($rows) < 1) {
            return;
        }

        $lookupPairs = [];
        $seen = [];

        foreach ($rows as $row) {
            $ppjbId = strtoupper(trim((string) ($row->PPJB_ID_INTERNAL ?? '')));

            if ($ppjbId === '') {
                continue;
            }

            if (!isset($seen[$ppjbId . '|EXACT'])) {
                $lookupPairs[] = [$ppjbId, $ppjbId];
                $seen[$ppjbId . '|EXACT'] = true;
            }

            $digits = preg_replace('/[^0-9]/', '', $ppjbId);

            if (
                $digits !== ''
                && $digits !== $ppjbId
                && !isset($seen[$ppjbId . '|DIGIT'])
            ) {
                $lookupPairs[] = [$ppjbId, $digits];
                $seen[$ppjbId . '|DIGIT'] = true;
            }
        }

        if (count($lookupPairs) < 1) {
            /*
             * Sebelumnya seluruh baris dibuang di sini. Query desktop tidak
             * pernah membuang baris hanya karena PPJB_ID tidak dapat dibaca;
             * ISNULL(...) membuat JML_BAYAR menjadi 0 dan baris tetap tunduk
             * pada HAVING persentase. Perilaku itu yang ditiru di sini.
             */
            $this->applyPembayaranToRows($rows, [], $minimalPersen);

            return;
        }

        $idKeys = [];
        $lookupIds = [];

        foreach ($lookupPairs as $pair) {
            $idKeys[] = $pair[0];
            $lookupIds[] = $pair[1];
        }

        /*
         * Dua parameter array jauh lebih ringan diparse PostgreSQL dibanding
         * ratusan/ribuan placeholder VALUES (?, ?).
         */
        $bindings = [
            $this->pgTextArray($idKeys),
            $this->pgTextArray($lookupIds),
        ];

        $schema = self::SCHEMA;

        /*
         * Sama seperti query desktop:
         * JML_BAYAR = SUM(ANGSURAN.JUMLAH_BAYAR)
         * dengan KODE_TRANSAKSI yang FLAG_HITUNG='Y' dan FLAG_PAJAK='Y', atau DCB,
         * serta ANGSURAN.FLAG_AKTIF='A'.
         *
         * Dipisahkan dari query utama agar sr_angsuran hanya diproses untuk
         * daftar PPJB yang sudah lolos filter utama. Optimasi ini tidak
         * membutuhkan pembuatan index atau perubahan skema database.
         */
        $sql = <<<SQL
            WITH lookup(id_key, ppjb_lookup) AS (
                SELECT *
                FROM unnest(?::text[], ?::text[])
                    AS requested(id_key, ppjb_lookup)
            ),

            sumber AS (
                /*
                 * DISTINCT dihapus.
                 *
                 * Query desktop memakai SUM(JUMLAH_BAYAR) atas seluruh baris
                 * ANGSURAN. Dengan DISTINCT, dua pembayaran yang kebetulan
                 * sama persis (PPJB, kode transaksi, jumlah, dan tanggal
                 * kuitansi yang sama) tergabung menjadi satu sehingga
                 * JML_BAYAR di web lebih kecil daripada desktop. Hal ini
                 * terjadi juga ketika angsuran_id kosong pada data hasil
                 * migrasi.
                 *
                 * DISTINCT juga tidak diperlukan untuk mencegah satu baris
                 * angsuran terhitung dua kali: daftar lookup hanya memuat
                 * versi angka ketika nilainya berbeda dari ID aslinya,
                 * sehingga satu baris angsuran mustahil cocok dua kali untuk
                 * PPJB yang sama.
                 */
                SELECT
                    lookup.id_key,
                    angsuran.kd_transaksi,
                    COALESCE(angsuran.jumlah_bayar, 0) AS jumlah_bayar,
                    angsuran.tgl_kuitansi,
                    COALESCE(NULLIF(UPPER(BTRIM(CAST(angsuran.flag_aktif AS text))), ''), 'A') AS flag_aktif_norm
                FROM lookup
                INNER JOIN {$schema}.sr_angsuran AS angsuran
                    ON CAST(angsuran.ppjb_id AS text) = lookup.ppjb_lookup
            ),

            hasil AS (
                SELECT
                    sumber.id_key,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN (
                                        (
                                            UPPER(BTRIM(COALESCE(CAST(kode_transaksi.flag_hitung AS text), ''))) = 'Y'
                                            AND UPPER(BTRIM(COALESCE(CAST(kode_transaksi.flag_pajak AS text), ''))) = 'Y'
                                        )
                                        OR UPPER(BTRIM(CAST(kode_transaksi.kd_transaksi AS text))) = 'DCB'
                                     )
                                 AND sumber.flag_aktif_norm = 'A'
                                THEN sumber.jumlah_bayar
                                ELSE 0
                            END
                        ),
                        0
                    ) AS jml_bayar,

                    MAX(sumber.tgl_kuitansi) AS tgl_capai

                FROM sumber
                LEFT JOIN {$schema}.sr_kode_transaksi AS kode_transaksi
                    ON UPPER(BTRIM(CAST(kode_transaksi.kd_transaksi AS text))) =
                       UPPER(BTRIM(CAST(sumber.kd_transaksi AS text)))
                GROUP BY sumber.id_key
            )

            SELECT
                id_key,
                jml_bayar,
                tgl_capai
            FROM hasil
        SQL;

        $bayarRows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $bayarByPpjb = [];

        foreach ($bayarRows as $bayar) {
            $key = strtoupper(trim((string) ($bayar->id_key ?? '')));

            $bayarByPpjb[$key] = [
                'jml_bayar' => (float) ($bayar->jml_bayar ?? 0),
                'tgl_capai' => $bayar->tgl_capai ?? null,
            ];
        }

        $this->applyPembayaranToRows($rows, $bayarByPpjb, $minimalPersen);
    }

    /**
     * Isi JML_BAYAR, PROSENTASE, dan TGL_CAPAI lalu terapkan batas persentase.
     *
     * Penyaringan di sini meniru klausa HAVING pada query desktop, yaitu
     * ( JML_BAYAR / HARGA_SETELAH_PPJB ) * 100 >= :persen. Tidak ada baris
     * yang dibuang karena alasan lain.
     */
    private function applyPembayaranToRows(array &$rows, array $bayarByPpjb, float $minimalPersen): void
    {
        $filteredRows = [];

        foreach ($rows as $row) {
            $ppjbId = strtoupper(trim((string) ($row->PPJB_ID_INTERNAL ?? '')));

            $jmlBayar = $bayarByPpjb[$ppjbId]['jml_bayar'] ?? 0.0;
            $tglCapai = $bayarByPpjb[$ppjbId]['tgl_capai'] ?? null;
            $hargaSetelahPpjb = $this->numericValue($row->HARGA_SETELAH_PPJB ?? 0);

            $prosentaseRaw = $hargaSetelahPpjb > 0
                ? ($jmlBayar / $hargaSetelahPpjb) * 100
                : 0.0;

            /*
             * Filter tetap memakai nilai asli seperti HAVING desktop.
             * Tampilan dikunci maksimal 100 agar tidak muncul 100.1122...
             */
            if (($prosentaseRaw + 0.000001) < $minimalPersen) {
                unset($row->PPJB_ID_INTERNAL);
                continue;
            }

            $row->JML_BAYAR = $jmlBayar;
            $row->PROSENTASE = min(100, max(0, $prosentaseRaw));
            $row->TGL_CAPAI = $tglCapai;

            unset($row->PPJB_ID_INTERNAL);

            $filteredRows[] = $row;
        }

        $rows = $filteredRows;
    }
}