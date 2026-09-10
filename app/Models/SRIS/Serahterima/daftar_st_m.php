<?php

namespace App\Models\SRIS\Serahterima;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class daftar_st_m extends Model
{
    // V8: basis V4 yang sudah cepat; nasabah dibuat LEFT JOIN agar unit tetap tampil
    // walaupun referensi nama pembeli hasil migrasi tidak ditemukan.
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    private function normalizeText($value, string $default = ''): string
    {
        $value = strtoupper(trim((string) $value));
        return $value === '' ? $default : $value;
    }

    private function normalizeAllFilter($value, string $default = '*'): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '' || in_array($value, [
            '*', 'SEMUA', 'ALL', 'S', '0', '-', 'NULL'
        ], true)) {
            return $default;
        }

        return $value;
    }

    private function normalizeCompany($value, string $default = 'DTSA'): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '' || in_array($value, ['*', 'SEMUA', 'ALL'], true)) {
            return $default;
        }

        if (strpos($value, 'DUTA SUMARA') !== false || strpos($value, 'DTSA') !== false) {
            return 'DTSA';
        }

        if (strpos($value, 'CGTK') !== false) {
            return 'CGTK';
        }

        return $value;
    }

    private function normalizeYesNoAll($value, string $default = '*'): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '' || in_array($value, [
            '*', 'SEMUA', 'ALL', 'S', '0', '-', 'NULL'
        ], true)) {
            return $default;
        }

        if (in_array($value, ['Y', 'YA', 'YES', 'TRUE', '1', 'SUDAH'], true)) {
            return 'Y';
        }

        if (in_array($value, ['T', 'TIDAK', 'NO', 'FALSE', '2', 'BELUM'], true)) {
            return 'T';
        }

        return $default;
    }

    private function normalizeActive($value, string $default = 'A'): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '' || in_array($value, ['A', 'AKTIF', 'ACTIVE', 'Y', 'YA'], true)) {
            return 'A';
        }

        if (in_array($value, ['B', 'BATAL', 'CANCEL', 'CANCELED', 'CANCELLED', 'T'], true)) {
            return 'B';
        }

        if (in_array($value, ['*', 'SEMUA', 'ALL', 'S'], true)) {
            return '*';
        }

        return $default;
    }

    private function normalizeAllDateFlag($value, string $default = 'T'): string
    {
        $value = strtoupper(trim((string) $value));

        if (in_array($value, ['Y', 'YA', 'YES', 'TRUE', '1', 'ON', 'ALL', 'SEMUA'], true)) {
            return 'Y';
        }

        if (in_array($value, ['T', 'N', 'NO', 'FALSE', '0', 'OFF'], true)) {
            return 'T';
        }

        return $default;
    }

    /**
     * Membaca tipe kolom dari katalog PostgreSQL. Query ini hanya SELECT dan
     * memungkinkan join ID memakai operator "=" tanpa CAST ketika tipenya sama.
     */
    private function columnMetadata(): array
    {
        static $metadata;

        if ($metadata !== null) {
            return $metadata;
        }

        $rows = DB::connection(self::CONNECTION)->select(
            <<<'SQL'
                SELECT table_name, column_name, udt_name
                FROM information_schema.columns
                WHERE table_schema = :schema
                  AND table_name IN (
                      'sr_serah_terima',
                      'sr_ppjb',
                      'sr_stok',
                      'sr_tipe',
                      'sr_jenis_bangunan',
                      'sr_pembeli_ppjb',
                      'sr_nasabah'
                  )
            SQL,
            ['schema' => self::SCHEMA]
        );

        $metadata = [];

        foreach ($rows as $row) {
            $metadata[$row->table_name][$row->column_name] = $row->udt_name;
        }

        return $metadata;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $identifier)) {
            throw new \InvalidArgumentException('Nama kolom tidak valid.');
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function firstExistingColumn(
        array $metadata,
        string $table,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (isset($metadata[$table][$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Menghasilkan COALESCE dari kolom yang memang ada pada schema aktif.
     * Ini menggantikan to_jsonb(row)->>'kolom' yang sangat mahal.
     */
    private function normalizedColumnExpression(
        array $metadata,
        string $table,
        string $alias,
        array $candidates
    ): string {
        $expressions = [];

        foreach ($candidates as $candidate) {
            if (isset($metadata[$table][$candidate])) {
                $expressions[] = sprintf(
                    "NULLIF(CAST(%s.%s AS TEXT), '')",
                    $alias,
                    $this->quoteIdentifier($candidate)
                );
            }
        }

        if ($expressions === []) {
            return "''";
        }

        $expressions[] = "''";

        return 'UPPER(BTRIM(COALESCE('
            . implode(', ', $expressions)
            . ')))';
    }

    /**
     * Perbandingan langsung dipakai untuk numeric/uuid dengan tipe sama.
     * Jika schema lama menyimpan salah satu ID sebagai teks, perilaku lama
     * (trim + cast ke text) tetap digunakan agar hasil tidak berubah.
     */
    private function idJoinExpression(
        array $metadata,
        string $leftTable,
        string $leftAlias,
        string $leftColumn,
        string $rightTable,
        string $rightAlias,
        string $rightColumn
    ): string {
        $leftType = $metadata[$leftTable][$leftColumn] ?? null;
        $rightType = $metadata[$rightTable][$rightColumn] ?? null;
        $directTypes = ['int2', 'int4', 'int8', 'numeric', 'uuid'];

        $left = $leftAlias . '.' . $this->quoteIdentifier($leftColumn);
        $right = $rightAlias . '.' . $this->quoteIdentifier($rightColumn);

        if (
            $leftType !== null
            && $leftType === $rightType
            && in_array($leftType, $directTypes, true)
        ) {
            return $left . ' = ' . $right;
        }

        return sprintf(
            'BTRIM(CAST(%s AS TEXT)) = BTRIM(CAST(%s AS TEXT))',
            $left,
            $right
        );
    }

    private function safeTimestampExpression(
        array $metadata,
        array $candidates
    ): string {
        $expressions = [];

        foreach ($candidates as [$table, $alias, $column]) {
            if (!isset($metadata[$table][$column])) {
                continue;
            }

            $field = $alias . '.' . $this->quoteIdentifier($column);
            $expressions[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '') "
                . "~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}' "
                . 'THEN CAST(%s AS TIMESTAMP) END',
                $field,
                $field
            );
        }

        return $expressions === []
            ? 'NULL::TIMESTAMP'
            : 'COALESCE(' . implode(', ', $expressions) . ')';
    }

    private function safeIntegerExpression(
        array $metadata,
        array $candidates,
        bool $defaultZero = false
    ): string {
        $expressions = [];

        foreach ($candidates as [$table, $alias, $column]) {
            if (!isset($metadata[$table][$column])) {
                continue;
            }

            $field = $alias . '.' . $this->quoteIdentifier($column);
            $expressions[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '') "
                . "~ '^-{0,1}[0-9]+([.][0-9]+){0,1}$' "
                . 'THEN CAST(CAST(%s AS NUMERIC) AS INTEGER) END',
                $field,
                $field
            );
        }

        if ($defaultZero) {
            $expressions[] = '0';
        }

        return $expressions === []
            ? 'NULL::INTEGER'
            : 'COALESCE(' . implode(', ', $expressions) . ')';
    }

    /**
     * Master lokasi dan sektor dibaca satu kali. Pemilihan deskripsi meniru
     * ORDER BY ... LIMIT 1 pada model lama, tetapi tidak diulang per baris ST.
     */
    private function referenceMaps(string $company): array
    {
        $lokasiRows = DB::connection(self::CONNECTION)->select(
            <<<'SQL'
                SELECT DISTINCT ON (kode)
                    kode,
                    deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_proyek', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_cluster', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_sektor', ''),
                            ''
                        ))) AS kode,
                        BTRIM(CAST(l.deskripsi AS TEXT)) AS deskripsi
                    FROM public.sr_lokasi AS l
                ) AS lokasi
                WHERE kode <> ''
                ORDER BY kode, deskripsi
            SQL
        );

        $sektorRows = DB::connection(self::CONNECTION)->select(
            <<<'SQL'
                SELECT kode, kd_perusahaan, deskripsi
                FROM (
                    SELECT
                        UPPER(BTRIM(COALESCE(
                            NULLIF(to_jsonb(s) ->> 'kd_sektor', ''),
                            NULLIF(to_jsonb(s) ->> 'kd_proyek', ''),
                            NULLIF(to_jsonb(s) ->> 'kd_cluster', ''),
                            NULLIF(to_jsonb(s) ->> 'kd_lokasi', ''),
                            NULLIF(to_jsonb(s) ->> 'kd_lv2', ''),
                            ''
                        ))) AS kode,
                        UPPER(BTRIM(COALESCE(
                            to_jsonb(s) ->> 'kd_perusahaan',
                            ''
                        ))) AS kd_perusahaan,
                        BTRIM(CAST(s.deskripsi AS TEXT)) AS deskripsi,
                        ROW_NUMBER() OVER (
                            PARTITION BY UPPER(BTRIM(COALESCE(
                                NULLIF(to_jsonb(s) ->> 'kd_sektor', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_proyek', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_cluster', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_lokasi', ''),
                                NULLIF(to_jsonb(s) ->> 'kd_lv2', ''),
                                ''
                            )))
                            ORDER BY
                                CASE
                                    WHEN UPPER(BTRIM(COALESCE(
                                        to_jsonb(s) ->> 'kd_perusahaan',
                                        ''
                                    ))) = :company
                                    THEN 0
                                    ELSE 1
                                END,
                                BTRIM(CAST(s.deskripsi AS TEXT))
                        ) AS urutan
                    FROM public.sr_sektor AS s
                ) AS sektor
                WHERE kode <> ''
                  AND urutan = 1
            SQL,
            ['company' => $company]
        );

        $lokasi = [];
        foreach ($lokasiRows as $row) {
            if ($row->kode !== '' && !array_key_exists($row->kode, $lokasi)) {
                $lokasi[$row->kode] = (string) $row->deskripsi;
            }
        }

        $sektor = [];
        foreach ($sektorRows as $row) {
            if ($row->kode !== '' && !array_key_exists($row->kode, $sektor)) {
                $sektor[$row->kode] = (string) $row->deskripsi;
            }
        }

        return ['lokasi' => $lokasi, 'sektor' => $sektor];
    }

    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeCompany($kdPerusahaan, 'DTSA');

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

    public function obtainJenis()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_jenis_bangunan as jenis_bangunan')
            ->selectRaw('
                BTRIM(CAST(jenis_bangunan.flag_laporan AS TEXT))
                    AS "FLAG_LAPORAN",
                BTRIM(CAST(jenis_bangunan.deskripsi AS TEXT))
                    AS "DESKRIPSI"
            ')
            ->whereNotNull('jenis_bangunan.flag_laporan')
            ->groupByRaw('
                BTRIM(CAST(jenis_bangunan.flag_laporan AS TEXT)),
                BTRIM(CAST(jenis_bangunan.deskripsi AS TEXT))
            ')
            ->orderByRaw(
                'BTRIM(CAST(jenis_bangunan.deskripsi AS TEXT)) ASC'
            )
            ->get();
    }

    public function obtainSerahTerima($request)
    {
        $tglAwal = $request->tgl_awal
            ?? $request->tgl_surat_awal
            ?? date('Y-m-d');
        $tglAkhir = $request->tgl_akhir
            ?? $request->tgl_surat_akhir
            ?? date('Y-m-d');
        $tglStAwal = $request->tgl_st_awal
            ?? $request->tgl_realisasi_awal
            ?? date('Y-m-d');
        $tglStAkhir = $request->tgl_st_akhir
            ?? $request->tgl_realisasi_akhir
            ?? date('Y-m-d');

        $tglAll = $this->normalizeAllDateFlag($request->tgl_all ?? 'T', 'T');
        $tglStAll = $this->normalizeAllDateFlag(
            $request->tgl_st_all ?? $request->tgl_realisasi_all ?? 'T',
            'T'
        );

        $sektor = $this->normalizeAllFilter($request->sektor ?? '*', '*');
        $sektor = [
            'CHELIA RESIDENCE' => 'CLA',
            'EMERALD COMMERCIAL' => 'EMC',
        ][$sektor] ?? $sektor;

        $jenis = $this->normalizeAllFilter($request->jenis ?? '*', '*');
        $blokAwal = $this->normalizeText($request->blok_awal ?? 'A', 'A');
        $blokAkhir = $this->normalizeText($request->blok_akhir ?? 'ZZ', 'ZZ');

        if ($blokAkhir === 'Z') {
            $blokAkhir = 'ZZ';
        }

        $perusahaan = $this->normalizeCompany(
            $request->perusahaan
                ?? session('kd_unit')
                ?? session('kd_perusahaan')
                ?? session('KD_PERUSAHAAN')
                ?? 'DTSA',
            'DTSA'
        );

        $aktif = $this->normalizeActive(
            $request->aktif ?? $request->sts_aktif ?? 'A',
            'A'
        );
        $stsDtp = $this->normalizeYesNoAll(
            $request->sts_dtp ?? $request->ikut_ppn_dtp ?? '*',
            '*'
        );
        $statusRealisasi = $this->normalizeYesNoAll(
            $request->status_realisasi
                ?? $request->sts_realisasi
                ?? $request->realisasi
                ?? '*',
            '*'
        );

        /*
         * Nilai dropdown sektor pada halaman dapat berupa DESKRIPSI, misalnya
         * "VIOLA RESIDENCE", sedangkan sr_stok menyimpan KODE sektor/lokasi.
         * Model lama menerjemahkan deskripsi itu ke kode sebelum memfilter.
         */
        $references = $this->referenceMaps($perusahaan);
        $sektorCandidates = [$sektor];

        if ($sektor !== '*') {
            foreach (['lokasi', 'sektor'] as $referenceType) {
                foreach ($references[$referenceType] as $kode => $deskripsi) {
                    if (strtoupper(trim((string) $deskripsi)) === $sektor) {
                        $sektorCandidates[] = strtoupper(trim((string) $kode));
                    }
                }
            }
        }

        $sektorCandidates = array_values(array_unique(array_filter(
            $sektorCandidates,
            static fn ($value) => $value !== ''
        )));

        $cacheParameters = [
            $tglAwal,
            $tglAkhir,
            $tglAll,
            $tglStAwal,
            $tglStAkhir,
            $tglStAll,
            $sektorCandidates,
            $jenis,
            $blokAwal,
            $blokAkhir,
            $perusahaan,
            $aktif,
            $stsDtp,
            $statusRealisasi,
        ];
        $cacheKey = 'sris:daftar-st:minimal-fix:v8:'
            . sha1(json_encode($cacheParameters));
        $cacheSeconds = max(
            0,
            (int) config('sris.daftar_st_cache_seconds', 60)
        );

        if ($cacheSeconds > 0) {
            try {
                $cached = Cache::store('file')->get($cacheKey);

                if (is_array($cached)) {
                    $waktuCetak = DB::connection(self::CONNECTION)
                        ->selectOne('SELECT CURRENT_TIMESTAMP AS waktu_cetak')
                        ->waktu_cetak;

                    foreach ($cached as $row) {
                        $row->TGL_CETAK = $waktuCetak;
                    }

                    return $cached;
                }
            } catch (\Throwable $ignored) {
                // Cache hanya akselerator; laporan tetap dijalankan.
            }
        }

        $metadata = $this->columnMetadata();

        $requiredColumn = function (
            string $table,
            array $candidates
        ) use ($metadata): string {
            foreach ($candidates as $candidate) {
                if (isset($metadata[$table][$candidate])) {
                    return $candidate;
                }
            }

            throw new \RuntimeException(sprintf(
                'Kolom wajib tidak ditemukan pada %s. Kandidat: %s',
                $table,
                implode(', ', $candidates)
            ));
        };

        $field = function (string $alias, string $column): string {
            return $alias . '.' . $this->quoteIdentifier($column);
        };

        $normalizedField = function (
            string $alias,
            string $column
        ) use ($field): string {
            return 'UPPER(BTRIM(COALESCE(CAST('
                . $field($alias, $column)
                . " AS TEXT), '')))";
        };

        $textExpression = function (
            string $table,
            string $alias,
            array $candidates,
            string $default = '',
            bool $uppercase = false
        ) use ($metadata, $field): string {
            $parts = [];

            foreach ($candidates as $candidate) {
                if (!isset($metadata[$table][$candidate])) {
                    continue;
                }

                $parts[] = sprintf(
                    "NULLIF(BTRIM(CAST(%s AS TEXT)), '')",
                    $field($alias, $candidate)
                );
            }

            $parts[] = "'" . str_replace("'", "''", $default) . "'";
            $expression = 'COALESCE(' . implode(', ', $parts) . ')';

            return $uppercase ? 'UPPER(' . $expression . ')' : $expression;
        };

        $stokKdJenisColumn = $requiredColumn(
            'sr_stok',
            ['kd_jenis_bgn', 'kd_jenis']
        );
        $stokKdTipeColumn = $requiredColumn(
            'sr_stok',
            ['kd_tipe_bgn', 'kd_tipe']
        );
        $stokPerusahaanColumn = $requiredColumn(
            'sr_stok',
            ['kd_perusahaan', 'kd_unit', 'kd_pt']
        );
        $stokSektorColumn = $requiredColumn(
            'sr_stok',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2']
        );
        $stokLokasiColumn = $requiredColumn(
            'sr_stok',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor']
        );
        $stokBlokColumn = $requiredColumn('sr_stok', ['blok']);
        $stokNomorColumn = $requiredColumn('sr_stok', ['nomor']);
        $stokFlagAktifColumn = $requiredColumn('sr_stok', ['flag_aktif']);
        $ppjbFlagAktifColumn = $requiredColumn('sr_ppjb', ['flag_aktif']);
        $ppjbParentColumn = $requiredColumn('sr_ppjb', ['parent_id']);
        $ppjbFlagDtpColumn = $requiredColumn('sr_ppjb', ['flag_dtp']);
        $pembeliFlagAktifColumn = $requiredColumn(
            'sr_pembeli_ppjb',
            ['flag_aktif']
        );
        $jenisFlagColumn = $requiredColumn(
            'sr_jenis_bangunan',
            ['flag_laporan']
        );

        $joinSerahTerimaPpjb = $this->idJoinExpression(
            $metadata,
            'sr_serah_terima',
            'serah_terima',
            'ppjb_id',
            'sr_ppjb',
            'ppjb',
            'ppjb_id'
        );
        $joinStokPpjb = $this->idJoinExpression(
            $metadata,
            'sr_stok',
            'stok',
            'stok_id',
            'sr_ppjb',
            'ppjb',
            'stok_id'
        );
        $joinPembeliPpjb = $this->idJoinExpression(
            $metadata,
            'sr_pembeli_ppjb',
            'pembeli_ppjb',
            'ppjb_id',
            'sr_ppjb',
            'ppjb',
            'ppjb_id'
        );

        /*
         * Pertahankan perilaku model lama untuk NASABAH_ID. Pada beberapa
         * hasil migrasi, satu sisi bertipe teks dan sisi lain numerik.
         */
        $nasabahIdType = $metadata['sr_nasabah']['nasabah_id'] ?? null;
        $pembeliNasabahIdType =
            $metadata['sr_pembeli_ppjb']['nasabah_id'] ?? null;

        /*
         * Sambungan ke sr_nasabah dibuat bertingkat.
         *
         * Cabang lama selalu membuang karakter bukan angka dari nasabah_id
         * milik sr_pembeli_ppjb sebelum dibandingkan. Ketika kedua kolom
         * bertipe teks dan isinya memuat huruf, misalnya N1, pembuangan itu
         * menyisakan 1 sehingga tidak pernah cocok dengan N1 di sr_nasabah,
         * dan seluruh kolom Nama Pembeli menjadi tanda hubung.
         *
         * Urutannya sekarang: tipe sama dibandingkan langsung, sisi nasabah
         * bertipe angka dibandingkan sesudah karakter bukan angka dibuang,
         * dan selebihnya dibandingkan sebagai teks yang sudah dibuang
         * spasinya. Cabang terakhir inilah yang sebelumnya tidak ada.
         */
        if (
            $nasabahIdType !== null
            && $nasabahIdType === $pembeliNasabahIdType
        ) {
            $joinNasabah =
                'pembeli_ppjb."nasabah_id" = nasabah."nasabah_id"';
        } elseif (
            in_array(
                $nasabahIdType,
                ['int2', 'int4', 'int8', 'numeric'],
                true
            )
        ) {
            $joinNasabah = <<<'SQL'
                CAST(
                    NULLIF(
                        REGEXP_REPLACE(
                            COALESCE(CAST(pembeli_ppjb.nasabah_id AS TEXT), ''),
                            '[^0-9]',
                            '',
                            'g'
                        ),
                        ''
                    ) AS NUMERIC
                ) = nasabah.nasabah_id
            SQL;
        } else {
            $joinNasabah = 'BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))'
                . ' = BTRIM(CAST(nasabah.nasabah_id AS TEXT))';
        }

        $joinTipeJenis = $this->idJoinExpression(
            $metadata,
            'sr_tipe',
            'tipe',
            'kd_jenis',
            'sr_stok',
            'stok',
            $stokKdJenisColumn
        );
        $joinTipeKode = $this->idJoinExpression(
            $metadata,
            'sr_tipe',
            'tipe',
            'kd_tipe',
            'sr_stok',
            'stok',
            $stokKdTipeColumn
        );
        $joinJenisBangunan = $this->idJoinExpression(
            $metadata,
            'sr_jenis_bangunan',
            'jenis_bangunan',
            'kd_jenis',
            'sr_tipe',
            'tipe',
            'kd_jenis'
        );

        $stokBlok = $normalizedField('stok', $stokBlokColumn);
        $stokNomor = $normalizedField('stok', $stokNomorColumn);
        $stokBlokNomor = 'UPPER('
            . "BTRIM(COALESCE(CAST(stok.{$this->quoteIdentifier($stokBlokColumn)} AS TEXT), ''))"
            . " || '/' || "
            . "BTRIM(COALESCE(CAST(stok.{$this->quoteIdentifier($stokNomorColumn)} AS TEXT), ''))"
            . ')';
        $stokPerusahaan = $normalizedField('stok', $stokPerusahaanColumn);
        $stokSektor = $normalizedField('stok', $stokSektorColumn);
        $stokLokasi = $normalizedField('stok', $stokLokasiColumn);
        $stokFlagAktif = $normalizedField('stok', $stokFlagAktifColumn);
        $ppjbFlagAktif = $normalizedField('ppjb', $ppjbFlagAktifColumn);
        $pembeliFlagAktif = $normalizedField(
            'pembeli_ppjb',
            $pembeliFlagAktifColumn
        );
        $jenisFlag = $normalizedField(
            'jenis_bangunan',
            $jenisFlagColumn
        );
        $jenisDeskripsi = $normalizedField(
            'jenis_bangunan',
            'deskripsi'
        );
        $ppjbFlagDtp = $normalizedField('ppjb', $ppjbFlagDtpColumn);

        $nama = $textExpression(
            'sr_nasabah',
            'nasabah',
            ['nama', 'nama_nasabah', 'nama_pembeli', 'nama_lengkap'],
            '-',
            true
        );
        $telpRumah = $textExpression(
            'sr_nasabah',
            'nasabah',
            ['telp_rmh', 'no_telp', 'telp'],
            ''
        );
        $kodeAlamat = $textExpression(
            'sr_nasabah',
            'nasabah',
            ['kd_alamt_surat', 'kd_alamat_surat'],
            ''
        );

        $alamatRumah = $textExpression(
            'sr_nasabah', 'nasabah', ['alamat_rmh'], ''
        );
        $alamatKantor = $textExpression(
            'sr_nasabah', 'nasabah', ['alamat_ktr'], ''
        );
        $alamatSurat = $textExpression(
            'sr_nasabah', 'nasabah', ['alamat_srt'], ''
        );
        $alamatKtp = $textExpression(
            'sr_nasabah', 'nasabah', ['alamat_ktp'], ''
        );

        $kotaRumah = $textExpression(
            'sr_nasabah', 'nasabah', ['kota_rmh'], ''
        );
        $kodePosRumah = $textExpression(
            'sr_nasabah', 'nasabah', ['kode_pos_rmh'], ''
        );
        $kotaKantor = $textExpression(
            'sr_nasabah', 'nasabah', ['kota_ktr'], ''
        );
        $kodePosKantor = $textExpression(
            'sr_nasabah', 'nasabah', ['kode_pos_ktr'], ''
        );
        $kotaSurat = $textExpression(
            'sr_nasabah', 'nasabah', ['kota_srt'], ''
        );
        $kodePosSurat = $textExpression(
            'sr_nasabah', 'nasabah', ['kode_pos_srt'], ''
        );
        $kotaKtp = $textExpression(
            'sr_nasabah', 'nasabah', ['kota_ktp'], ''
        );
        $kodePosKtp = $textExpression(
            'sr_nasabah', 'nasabah', ['kode_pos_ktp'], ''
        );

        $alamat = sprintf(
            "CASE %s WHEN '1' THEN %s WHEN '2' THEN %s "
            . "WHEN '3' THEN %s ELSE %s END",
            $kodeAlamat,
            $alamatRumah,
            $alamatKantor,
            $alamatSurat,
            $alamatKtp
        );
        $kota = sprintf(
            "CASE %s WHEN '1' THEN BTRIM(%s || '  ' || %s) "
            . "WHEN '2' THEN BTRIM(%s || '  ' || %s) "
            . "WHEN '3' THEN BTRIM(%s || '  ' || %s) "
            . "ELSE BTRIM(%s || '  ' || %s) END",
            $kodeAlamat,
            $kotaRumah,
            $kodePosRumah,
            $kotaKantor,
            $kodePosKantor,
            $kotaSurat,
            $kodePosSurat,
            $kotaKtp,
            $kodePosKtp
        );

        $tglRencanaSt = $this->safeTimestampExpression(
            $metadata,
            [['sr_stok', 'stok', 'tgl_rencana_st']]
        );
        $tglRencanaSb = $this->safeTimestampExpression(
            $metadata,
            [
                ['sr_ppjb', 'ppjb', 'tgl_rencana_sb'],
                ['sr_stok', 'stok', 'tgl_rencana_sb'],
                ['sr_tipe', 'tipe', 'tgl_rencana_sb'],
            ]
        );
        $waktuAdd = $this->safeIntegerExpression(
            $metadata,
            [
                ['sr_ppjb', 'ppjb', 'waktu_add'],
                ['sr_stok', 'stok', 'waktu_add'],
                ['sr_tipe', 'tipe', 'waktu_add'],
                ['sr_jenis_bangunan', 'jenis_bangunan', 'waktu_add'],
            ]
        );
        $waktu = $this->safeIntegerExpression(
            $metadata,
            [
                ['sr_ppjb', 'ppjb', 'waktu'],
                ['sr_stok', 'stok', 'waktu'],
                ['sr_tipe', 'tipe', 'waktu'],
                ['sr_jenis_bangunan', 'jenis_bangunan', 'waktu'],
            ],
            true
        );

        $tglRencana = 'COALESCE('
            . $tglRencanaSt . ', '
            . $tglRencanaSb . ', '
            . 'ppjb.tgl_ppjb + ('
            . 'COALESCE(' . $waktuAdd . ', ' . $waktu . ', 0)'
            . " * INTERVAL '1 month'))";

        $where = [
            $stokFlagAktif . " = 'A'",
            $ppjbFlagAktif . " = 'A'",
            $pembeliFlagAktif . " = 'Y'",
            '(' . $stokPerusahaan . ' = :perusahaan'
                . ' OR ' . $stokPerusahaan . " = '')",
            $stokBlok . " <> ''",
            $stokNomor . " <> ''",
            'NULLIF(BTRIM(CAST('
                . $field('ppjb', $ppjbParentColumn)
                . " AS TEXT)), '') IS NULL",
            '(('
                . $stokBlokNomor . ' >= :blok_nomor_awal'
                . ' AND ' . $stokBlokNomor . ' <= :blok_nomor_akhir)'
                . ' OR (' . $stokBlok . ' >= :blok_awal'
                . ' AND ' . $stokBlok . ' <= :blok_akhir))',
        ];

        $bindings = [
            'perusahaan' => $perusahaan,
            'blok_nomor_awal' => $blokAwal,
            'blok_nomor_akhir' => $blokAkhir,
            'blok_awal' => $blokAwal,
            'blok_akhir' => $blokAkhir,
        ];

        /*
         * Penyaring tanggal pada query desktop hanya membandingkan kolomnya
         * dengan rentang, tanpa jalur cadangan apa pun:
         *
         *     ( ( SERAH_TERIMA.TGL_SURAT >= :awal AND <= :akhir ) OR :all = 'Y' )
         *
         * Baris yang tanggalnya kosong ikut terbuang, karena perbandingan
         * dengan NULL tidak pernah bernilai benar.
         *
         * Model sebelumnya menambahkan jalur cadangan memakai tanggal
         * rencana ketika tanggalnya kosong. Jalur itu tidak ada di desktop
         * dan membuat laporan web kelebihan baris, terutama pada penyaring
         * Tgl Realisasi karena serah terima yang belum terealisasi ikut
         * tertarik masuk. Jalur cadangan itu dihapus.
         */
        if ($tglAll !== 'Y') {
            $where[] = 'serah_terima.tgl_surat >= CAST(:tgl_awal_surat AS DATE)'
                . " AND serah_terima.tgl_surat < CAST(:tgl_akhir_surat AS DATE) + INTERVAL '1 day'";
            $bindings['tgl_awal_surat'] = $tglAwal;
            $bindings['tgl_akhir_surat'] = $tglAkhir;
        }

        if ($tglStAll !== 'Y') {
            $where[] = 'serah_terima.tgl_serah_terima >= CAST(:tgl_st_awal_realisasi AS DATE)'
                . " AND serah_terima.tgl_serah_terima < CAST(:tgl_st_akhir_realisasi AS DATE) + INTERVAL '1 day'";
            $bindings['tgl_st_awal_realisasi'] = $tglStAwal;
            $bindings['tgl_st_akhir_realisasi'] = $tglStAkhir;
        }

        if ($sektor !== '*') {
            $sektorPlaceholders = [];
            $lokasiPlaceholders = [];

            foreach ($sektorCandidates as $index => $candidate) {
                $sektorKey = 'sektor_candidate_' . $index;
                $lokasiKey = 'lokasi_candidate_' . $index;
                $sektorPlaceholders[] = ':' . $sektorKey;
                $lokasiPlaceholders[] = ':' . $lokasiKey;
                $bindings[$sektorKey] = $candidate;
                $bindings[$lokasiKey] = $candidate;
            }

            $where[] = '(' . $stokSektor . ' IN ('
                . implode(', ', $sektorPlaceholders)
                . ') OR ' . $stokLokasi . ' IN ('
                . implode(', ', $lokasiPlaceholders)
                . '))';
        }

        if ($jenis !== '*') {
            if ($jenis === 'KAVLING') {
                $where[] = $jenisFlag . " = '2'";
            } elseif ($jenis === 'NON_KAVLING') {
                $where[] = $jenisFlag . " <> '2'";
            } else {
                $where[] = '(' . $jenisFlag . ' = :jenis_flag'
                    . ' OR ' . $jenisDeskripsi . ' = :jenis_deskripsi)';
                $bindings['jenis_flag'] = $jenis;
                $bindings['jenis_deskripsi'] = $jenis;
            }
        }

        $statusAktif = "COALESCE(NULLIF(UPPER(BTRIM(CAST(serah_terima.flag_aktif AS TEXT))), ''), 'A')";

        /*
         * Query desktop hanya menuliskan ISNULL(SERAH_TERIMA.FLAG_AKTIF,'A')
         * yang dibandingkan dengan pilihan Aktif atau Batal. Tidak ada syarat
         * tambahan mengenai TGL_BATAL.
         *
         * Model sebelumnya menambahkan tgl_batal IS NULL pada pilihan Aktif,
         * sehingga baris yang flag_aktif-nya masih A tetapi tgl_batal-nya
         * terisi ikut terbuang, padahal desktop tetap menampilkannya.
         * Nilai Y dan T juga tidak dikenal desktop, jadi ikut dihapus supaya
         * kedua laporan menyaring dengan syarat yang sama persis.
         */
        if ($aktif === 'A') {
            $where[] = $statusAktif . " = 'A'";
        } elseif ($aktif === 'B') {
            /*
             * Pada sr_serah_terima nilai flag_aktif hanya A dan T, tidak ada
             * B. Nilai yang dikirim desktop untuk pilihan Batal karena itu
             * adalah T, sedangkan B dipakai sebagian data lama. Keduanya
             * diterima supaya pilihan Batal tidak menghasilkan daftar kosong.
             */
            $where[] = $statusAktif . " IN ('B', 'T')";
        } elseif ($aktif !== '*') {
            $where[] = $statusAktif . ' = :aktif_lain';
            $bindings['aktif_lain'] = $aktif;
        }

        if ($stsDtp !== '*') {
            $where[] = "COALESCE(NULLIF(" . $ppjbFlagDtp . ", ''), 'T') = :sts_dtp";
            $bindings['sts_dtp'] = $stsDtp;
        }

        if ($statusRealisasi === 'Y') {
            $where[] = 'serah_terima.tgl_serah_terima IS NOT NULL';
        } elseif ($statusRealisasi === 'T') {
            $where[] = 'serah_terima.tgl_serah_terima IS NULL';
        }

        $sql = <<<'SQL'
            SELECT
                __BLOK_NOMOR__ AS "BLOK_NOMOR",
                __NAMA__ AS "NAMA",
                BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')) AS "NO_PPJB",
                ppjb.tgl_ppjb AS "TGL_PPJB",
                __TGL_RENCANA__ AS "TGL_RENCANA_SS",
                ppjb.user_entry AS "USER_ENTRY",
                COALESCE(
                    NULLIF(BTRIM(CAST(serah_terima.no_surat AS TEXT)), ''),
                    ''
                ) AS "NO_SURAT",
                serah_terima.tgl_surat AS "TGL_SURAT",
                serah_terima.tgl_serah_terima AS "TGL_SERAH_TERIMA",
                COALESCE(
                    NULLIF(BTRIM(CAST(serah_terima.no_telp AS TEXT)), ''),
                    '-'
                ) AS "NO_TELP",
                __STOK_KD_JENIS__ AS "KD_JENIS",
                __STOK_PERUSAHAAN__ AS "KD_PERUSAHAAN",
                __JENIS_FLAG__ AS "FLAG_LAPORAN",
                BTRIM(COALESCE(CAST(jenis_bangunan.deskripsi AS TEXT), ''))
                    AS "DESKRIPSI_JENIS",
                NULLIF(__TELP_RUMAH__, '') AS "TELP_RMH",
                __ALAMAT__ AS "ALAMAT",
                __KOTA__ AS "KOTA",
                '' AS "NAMA_LOKASI",
                '' AS "NAMA_SEKTOR",
                __STOK_LOKASI__ AS "__KD_LOKASI",
                __STOK_SEKTOR__ AS "__KD_SEKTOR",
                __STATUS_AKTIF__ AS "STATUS_AKTIF",
                serah_terima.tgl_batal AS "TGL_BATAL",
                serah_terima.tgl_entry AS "TGL_ENTRY_ST",
                serah_terima.user_entry AS "USER_ENTRY_ST",
                CURRENT_TIMESTAMP AS "TGL_CETAK"

            FROM public.sr_serah_terima AS serah_terima

            INNER JOIN public.sr_ppjb AS ppjb
                ON __JOIN_SERAH_TERIMA_PPJB__

            INNER JOIN public.sr_stok AS stok
                ON __JOIN_STOK_PPJB__

            /*
             * Query desktop menyambung TIPE dan JENIS_BANGUNAN dengan join
             * lama (koma di FROM), yang berarti INNER JOIN. Itu aman di SQL
             * Server karena setiap pasangan KD_JENIS + KD_TIPE pada STOK
             * pasti ada di TIPE.
             *
             * Di PostgreSQL pasangan itu belum lengkap, sudah terukur 1292
             * dari 3036 pasangan yang dipakai sr_stok, sehingga INNER JOIN
             * menghapus unitnya dari laporan padahal desktop menampilkannya.
             * Karena itu di sini memakai LEFT JOIN. Unitnya tetap tampil,
             * hanya kolom jenis bangunan yang kosong.
             */
            LEFT JOIN public.sr_tipe AS tipe
                ON __JOIN_TIPE_JENIS__
                AND __JOIN_TIPE_KODE__

            LEFT JOIN public.sr_jenis_bangunan AS jenis_bangunan
                ON __JOIN_JENIS_BANGUNAN__

            INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
                ON __JOIN_PEMBELI_PPJB__

            LEFT JOIN public.sr_nasabah AS nasabah
                ON __JOIN_NASABAH__

            WHERE __WHERE__

            /*
             * Desktop mengurutkan berdasarkan FLAG_LAPORAN lalu BLOK_NOMOR.
             * Unit yang tipenya belum ada di sr_tipe tidak punya FLAG_LAPORAN,
             * dan nilai kosong akan naik ke paling atas sehingga seluruh nomor
             * urut bergeser dan sulit dibandingkan dengan desktop. Karena itu
             * baris tanpa FLAG_LAPORAN sengaja ditaruh paling belakang.
             */
            ORDER BY
                CASE WHEN __JENIS_FLAG__ = '' THEN 1 ELSE 0 END ASC,
                "FLAG_LAPORAN" ASC,
                "BLOK_NOMOR" ASC
        SQL;

        $sql = strtr($sql, [
            '__BLOK_NOMOR__' => $stokBlokNomor,
            '__NAMA__' => $nama,
            '__TGL_RENCANA__' => $tglRencana,
            '__STOK_KD_JENIS__' => $normalizedField(
                'stok',
                $stokKdJenisColumn
            ),
            '__STOK_PERUSAHAAN__' => $stokPerusahaan,
            '__JENIS_FLAG__' => $jenisFlag,
            '__TELP_RUMAH__' => $telpRumah,
            '__ALAMAT__' => $alamat,
            '__KOTA__' => $kota,
            '__STOK_LOKASI__' => $stokLokasi,
            '__STOK_SEKTOR__' => $stokSektor,
            '__STATUS_AKTIF__' => $statusAktif,
            '__JOIN_SERAH_TERIMA_PPJB__' => $joinSerahTerimaPpjb,
            '__JOIN_STOK_PPJB__' => $joinStokPpjb,
            '__JOIN_TIPE_JENIS__' => $joinTipeJenis,
            '__JOIN_TIPE_KODE__' => $joinTipeKode,
            '__JOIN_JENIS_BANGUNAN__' => $joinJenisBangunan,
            '__JOIN_PEMBELI_PPJB__' => $joinPembeliPpjb,
            '__JOIN_NASABAH__' => $joinNasabah,
            '__WHERE__' => implode("\n                AND ", $where),
        ]);

        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);

        foreach ($rows as $row) {
            $kdLokasi = (string) ($row->__KD_LOKASI ?? '');
            $kdSektor = (string) ($row->__KD_SEKTOR ?? '');

            $row->NAMA_LOKASI = $references['lokasi'][$kdLokasi] ?? '';
            $row->NAMA_SEKTOR = $references['sektor'][$kdSektor]
                ?? [
                    'CLA' => 'CHELIA RESIDENCE',
                    'EMC' => 'EMERALD COMMERCIAL',
                ][$kdSektor]
                ?? $kdSektor;

            unset($row->__KD_LOKASI, $row->__KD_SEKTOR);
        }

        if ($cacheSeconds > 0) {
            try {
                Cache::store('file')->put($cacheKey, $rows, $cacheSeconds);
            } catch (\Throwable $ignored) {
                // Hasil laporan tetap dikembalikan walaupun cache gagal.
            }
        }

        return $rows;
    }
}