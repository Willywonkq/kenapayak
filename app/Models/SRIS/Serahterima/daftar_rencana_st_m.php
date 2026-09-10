<?php

namespace App\Models\SRIS\Serahterima;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class daftar_rencana_st_m extends Model
{
    // Minimal fix: unit tetap tampil walaupun NASABAH_ID tidak ditemukan.
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    private function reportColumnMetadata(): array
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

    private function reportQuoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $identifier)) {
            throw new \InvalidArgumentException('Nama kolom tidak valid.');
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function reportNormalizedExpression(
        array $metadata,
        string $table,
        string $alias,
        array $candidates
    ): string {
        $parts = [];

        foreach ($candidates as $candidate) {
            if (!isset($metadata[$table][$candidate])) {
                continue;
            }

            /*
             * NULLIF dikenakan sesudah BTRIM. Kolom kode yang bertipe char
             * berisi spasi ketika kosong, dan NULLIF terhadap nilai mentah
             * tidak mengenalinya sebagai kosong, sehingga COALESCE berhenti
             * di kolom itu dan kolom cadangan berikutnya tidak pernah dipakai.
             */
            $parts[] = sprintf(
                "NULLIF(BTRIM(CAST(%s.%s AS TEXT)), '')",
                $alias,
                $this->reportQuoteIdentifier($candidate)
            );
        }

        if ($parts === []) {
            return "''";
        }

        $parts[] = "''";

        return 'UPPER(BTRIM(COALESCE(' . implode(', ', $parts) . ')))';
    }

    private function reportIdJoin(
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
        $left = $leftAlias . '.' . $this->reportQuoteIdentifier($leftColumn);
        $right = $rightAlias . '.' . $this->reportQuoteIdentifier($rightColumn);

        if ($leftType !== null && $leftType === $rightType) {
            return $left . ' = ' . $right;
        }

        return sprintf(
            'BTRIM(CAST(%s AS TEXT)) = BTRIM(CAST(%s AS TEXT))',
            $left,
            $right
        );
    }

    private function reportSafeTimestamp(array $metadata, array $candidates): string
    {
        $parts = [];

        foreach ($candidates as [$table, $alias, $column]) {
            if (!isset($metadata[$table][$column])) {
                continue;
            }

            $field = $alias . '.' . $this->reportQuoteIdentifier($column);
            $type = $metadata[$table][$column];

            if (in_array($type, ['date', 'timestamp', 'timestamptz'], true)) {
                $parts[] = 'CAST(' . $field . ' AS TIMESTAMP)';
                continue;
            }

            $parts[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '') "
                . "~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}' "
                . 'THEN CAST(%s AS TIMESTAMP) END',
                $field,
                $field
            );
        }

        return $parts === []
            ? 'NULL::TIMESTAMP'
            : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function reportSafeInteger(
        array $metadata,
        array $candidates,
        bool $defaultZero = false
    ): string {
        $parts = [];

        foreach ($candidates as [$table, $alias, $column]) {
            if (!isset($metadata[$table][$column])) {
                continue;
            }

            $field = $alias . '.' . $this->reportQuoteIdentifier($column);
            $type = $metadata[$table][$column];

            if (in_array($type, ['int2', 'int4', 'int8'], true)) {
                $parts[] = 'CAST(' . $field . ' AS INTEGER)';
                continue;
            }

            $parts[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '') "
                . "~ '^[+-]?[0-9]+([.][0-9]+)?$' "
                . 'THEN CAST(CAST(%s AS NUMERIC) AS INTEGER) END',
                $field,
                $field
            );
        }

        if ($defaultZero) {
            $parts[] = '0';
        }

        return $parts === []
            ? ($defaultZero ? '0' : 'NULL::INTEGER')
            : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function reportTextExpression(
        array $metadata,
        string $table,
        string $alias,
        array $candidates,
        string $default = '',
        bool $uppercase = false
    ): string {
        $parts = [];

        foreach ($candidates as $candidate) {
            if (!isset($metadata[$table][$candidate])) {
                continue;
            }

            $parts[] = sprintf(
                "NULLIF(BTRIM(CAST(%s.%s AS TEXT)), '')",
                $alias,
                $this->reportQuoteIdentifier($candidate)
            );
        }

        $parts[] = "'" . str_replace("'", "''", $default) . "'";
        $expression = 'COALESCE(' . implode(', ', $parts) . ')';

        return $uppercase ? 'UPPER(' . $expression . ')' : $expression;
    }

    private function resolveRencanaSectorCandidates(string $sektor): array
    {
        if ($sektor === '' || $sektor === '*') {
            return [];
        }

        $candidates = [$sektor];

        if (preg_match('/^([A-Z0-9_-]+)\s*-\s*/', $sektor, $match)) {
            $candidates[] = $match[1];
        }

        $rows = DB::connection(self::CONNECTION)->select(
            <<<'SQL'
                SELECT DISTINCT kode
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
                            to_jsonb(s) ->> 'deskripsi',
                            ''
                        ))) AS deskripsi
                    FROM public.sr_sektor AS s

                    UNION ALL

                    SELECT
                        UPPER(BTRIM(COALESCE(
                            NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_proyek', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_cluster', ''),
                            NULLIF(to_jsonb(l) ->> 'kd_sektor', ''),
                            ''
                        ))) AS kode,
                        UPPER(BTRIM(COALESCE(
                            to_jsonb(l) ->> 'deskripsi',
                            ''
                        ))) AS deskripsi
                    FROM public.sr_lokasi AS l
                ) AS master
                WHERE kode = :sektor OR deskripsi = :sektor
            SQL,
            ['sektor' => $sektor]
        );

        foreach ($rows as $row) {
            $kode = strtoupper(trim((string) ($row->kode ?? '')));

            if ($kode !== '') {
                $candidates[] = $kode;
            }
        }

        return array_values(array_unique($candidates));
    }

    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = strtoupper(trim((string) $kdPerusahaan));

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
        return collect([
            (object) [
                'KODE' => 'KAVLING',
                'DESKRIPSI' => 'Kavling',
            ],
            (object) [
                'KODE' => 'NON_KAVLING',
                'DESKRIPSI' => 'Non Kavling',
            ],
        ]);
    }

    public function obtainRencanaSerahTerima($request)
    {
        $tglAwal = $request->tgl_awal ?? date('Y-m-d');
        $tglAkhir = $request->tgl_akhir ?? date('Y-m-d');

        $sektor = strtoupper(trim((string) ($request->sektor ?? '*')));
        $sektor = [
            'CHELIA RESIDENCE' => 'CLA',
            'EMERALD COMMERCIAL' => 'EMC',
        ][$sektor] ?? $sektor;
        $sektor = $sektor === '' ? '*' : $sektor;

        $jenis = strtoupper(trim((string) ($request->jenis ?? 'NON_KAVLING')));
        $blokAwal = strtoupper(trim((string) ($request->blok_awal ?? 'A')));
        $blokAkhir = strtoupper(trim((string) ($request->blok_akhir ?? 'ZZ')));
        $perusahaan = strtoupper(trim((string) (
            $request->perusahaan
            ?? session('kd_unit')
            ?? 'DTSA'
        )));

        $flagManagement = strtoupper(trim((string) (
            $request->flag_mgmt
            ?? $request->versi_management
            ?? 'T'
        )));
        $tglCutoff = $request->tgl_cutoff ?? $tglAwal;

        $blokAwal = $blokAwal === '' ? 'A' : $blokAwal;
        $blokAkhir = ($blokAkhir === '' || $blokAkhir === 'Z')
            ? 'ZZ'
            : $blokAkhir;

        if (!in_array($jenis, ['KAVLING', 'NON_KAVLING', '*'], true)) {
            $jenis = 'NON_KAVLING';
        }

        if (!in_array($flagManagement, ['Y', 'T'], true)) {
            $flagManagement = 'T';
        }

        $sektorCandidates = $this->resolveRencanaSectorCandidates($sektor);

        $cacheParameters = [
            $tglAwal,
            $tglAkhir,
            $sektorCandidates,
            $jenis,
            $blokAwal,
            $blokAkhir,
            $perusahaan,
            $tglCutoff,
            $flagManagement,
        ];
        $cacheKey = 'sris:daftar-rencana-st:fast:v3:'
            . sha1(json_encode($cacheParameters));
        $cacheSeconds = max(
            0,
            (int) config('sris.daftar_rencana_st_cache_seconds', 60)
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
                // Cache hanya akselerator; kegagalannya tidak menggagalkan laporan.
            }
        }

        $metadata = $this->reportColumnMetadata();

        $stokKdJenis = $this->reportNormalizedExpression(
            $metadata,
            'sr_stok',
            'stok',
            ['kd_jenis', 'kd_jenis_bgn']
        );
        $stokKdTipe = $this->reportNormalizedExpression(
            $metadata,
            'sr_stok',
            'stok',
            ['kd_tipe', 'kd_tipe_bgn']
        );
        $stokPerusahaan = $this->reportNormalizedExpression(
            $metadata,
            'sr_stok',
            'stok',
            ['kd_perusahaan', 'kd_unit', 'kd_pt']
        );
        $stokSektor = $this->reportNormalizedExpression(
            $metadata,
            'sr_stok',
            'stok',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2']
        );
        $stokLokasi = $this->reportNormalizedExpression(
            $metadata,
            'sr_stok',
            'stok',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor']
        );

        $joinStokPpjb = $this->reportIdJoin(
            $metadata,
            'sr_stok',
            'stok',
            'stok_id',
            'sr_ppjb',
            'ppjb',
            'stok_id'
        );
        $joinStPpjb = $this->reportIdJoin(
            $metadata,
            'sr_serah_terima',
            'st',
            'ppjb_id',
            'sr_ppjb',
            'pr',
            'ppjb_id'
        );
        $joinPembeliPpjb = $this->reportIdJoin(
            $metadata,
            'sr_pembeli_ppjb',
            'pembeli_ppjb',
            'ppjb_id',
            'sr_ppjb',
            'pr',
            'ppjb_id'
        );
        $joinBasePembeli = $this->reportIdJoin(
            $metadata,
            'sr_ppjb',
            'base',
            'ppjb_id',
            'sr_pembeli_ppjb',
            'pembeli_aktif',
            'ppjb_id'
        );

        $tipeKdJenis = "UPPER(BTRIM(COALESCE(CAST(tipe.kd_jenis AS TEXT), '')))";
        $tipeKdTipe = "UPPER(BTRIM(COALESCE(CAST(tipe.kd_tipe AS TEXT), '')))";
        $jenisKdJenis = "UPPER(BTRIM(COALESCE(CAST(jenis_bangunan.kd_jenis AS TEXT), '')))";

        $tglRencanaSb = $this->reportSafeTimestamp(
            $metadata,
            [['sr_ppjb', 'ppjb', 'tgl_rencana_sb']]
        );
        $waktuAdd = $this->reportSafeInteger(
            $metadata,
            [['sr_ppjb', 'ppjb', 'waktu_add']]
        );
        /*
         * Kolom waktu berada di sr_ppjb, bukan di sr_tipe.
         *
         * Pada query desktop nama kolomnya ditulis tanpa nama tabel, yaitu
         * ISNULL(TGL_RENCANA_SB, DATEADD(month, ISNULL(waktu, 0), TGL_PPJB)),
         * sehingga sempat dikira milik TIPE. SQL Server menolak nama kolom
         * yang ada di lebih dari satu tabel pada FROM, dan query itu berjalan
         * normal, jadi hanya satu tabel yang memilikinya. sr_tipe terbukti
         * tidak punya kolom waktu sama sekali, sedangkan sr_ppjb punya waktu,
         * waktu_add, dan waktu_ppn_dtp. Jadi yang dimaksud adalah PPJB.WAKTU,
         * satu tabel dengan TGL_RENCANA_SB dan waktu_add di rumus yang sama.
         *
         * Ketika masih diarahkan ke sr_tipe, kolomnya tidak ditemukan dan
         * reportSafeInteger memakai nilai bawaan nol. Akibatnya tanggal
         * Rencana Serah Terima jatuh menjadi sama dengan tanggal PPJB, dan
         * karena rumus yang sama dipakai sebagai penyaring rentang tanggal,
         * kumpulan baris yang terambil pun berbeda dengan desktop.
         */
        $waktu = $this->reportSafeInteger(
            $metadata,
            [['sr_ppjb', 'ppjb', 'waktu']],
            true
        );

        $tglRencanaTampil = 'COALESCE('
            . $tglRencanaSb
            . ', ppjb.tgl_ppjb + (COALESCE('
            . $waktuAdd . ', ' . $waktu . ', 0) * INTERVAL \'1 month\'))';
        $tglRencanaFilter = 'COALESCE('
            . $tglRencanaSb
            . ', ppjb.tgl_ppjb + (COALESCE('
            . $waktu . ', 0) * INTERVAL \'1 month\'))';

        $nama = $this->reportTextExpression(
            $metadata,
            'sr_nasabah',
            'nasabah',
            ['nama', 'nama_nasabah', 'nama_pembeli'],
            '-',
            true
        );
        $telpRumah = $this->reportTextExpression(
            $metadata,
            'sr_nasabah',
            'nasabah',
            ['telp_rmh', 'no_telp'],
            ''
        );
        $kodeAlamat = $this->reportTextExpression(
            $metadata,
            'sr_nasabah',
            'nasabah',
            ['kd_alamt_surat', 'kd_alamat_surat'],
            ''
        );
        $alamatRumah = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['alamat_rmh'], ''
        );
        $alamatKantor = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['alamat_ktr'], ''
        );
        $alamatSurat = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['alamat_srt'], ''
        );
        $alamatKtp = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['alamat_ktp'], ''
        );
        $kotaRumah = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kota_rmh'], ''
        );
        $kodePosRumah = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kode_pos_rmh'], ''
        );
        $kotaKantor = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kota_ktr'], ''
        );
        $kodePosKantor = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kode_pos_ktr'], ''
        );
        $kotaSurat = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kota_srt'], ''
        );
        $kodePosSurat = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kode_pos_srt'], ''
        );
        $kotaKtp = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kota_ktp'], ''
        );
        $kodePosKtp = $this->reportTextExpression(
            $metadata, 'sr_nasabah', 'nasabah', ['kode_pos_ktp'], ''
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

        $nasabahIdType = $metadata['sr_nasabah']['nasabah_id'] ?? null;
        $pembeliNasabahIdType = $metadata['sr_pembeli_ppjb']['nasabah_id'] ?? null;

        if (
            $nasabahIdType !== null
            && $nasabahIdType === $pembeliNasabahIdType
        ) {
            $joinNasabah = 'pembeli_aktif.nasabah_id = nasabah.nasabah_id';
        } elseif (in_array($nasabahIdType, ['int2', 'int4', 'int8', 'numeric'], true)) {
            $joinNasabah = <<<'SQL'
                CAST(
                    NULLIF(
                        REGEXP_REPLACE(
                            COALESCE(CAST(pembeli_aktif.nasabah_id AS TEXT), ''),
                            '[^0-9]',
                            '',
                            'g'
                        ),
                        ''
                    ) AS NUMERIC
                ) = nasabah.nasabah_id
            SQL;
        } else {
            $joinNasabah = 'BTRIM(CAST(pembeli_aktif.nasabah_id AS TEXT)) '
                . '= BTRIM(CAST(nasabah.nasabah_id AS TEXT))';
        }

        $baseWhere = [
            "UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'",
            "UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'",
            $stokPerusahaan . ' = :perusahaan',
            "NULLIF(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')), '') IS NOT NULL",
            "NULLIF(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')), '') IS NOT NULL",
            "NULLIF(BTRIM(COALESCE(CAST(ppjb.parent_id AS TEXT), '')), '') IS NULL",
            "((UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/' || "
                . "UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) "
                . "BETWEEN :blok_awal AND :blok_akhir) OR "
                . "(UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) "
                . "BETWEEN :blok_awal AND :blok_akhir))",
        ];

        $bindings = [
            'tgl_awal' => $tglAwal,
            'tgl_akhir' => $tglAkhir,
            'blok_awal' => $blokAwal,
            'blok_akhir' => $blokAkhir,
            'perusahaan' => $perusahaan,
            'tgl_cutoff' => $tglCutoff,
            'flag_mgmt' => $flagManagement,
        ];

        if ($sektor !== '*') {
            $sectorPlaceholders = [];
            $locationPlaceholders = [];

            foreach ($sektorCandidates as $index => $candidate) {
                $sectorKey = 'sektor_' . $index;
                $locationKey = 'lokasi_' . $index;
                $sectorPlaceholders[] = ':' . $sectorKey;
                $locationPlaceholders[] = ':' . $locationKey;
                $bindings[$sectorKey] = $candidate;
                $bindings[$locationKey] = $candidate;
            }

            $baseWhere[] = '(' . $stokSektor . ' IN ('
                . implode(', ', $sectorPlaceholders)
                . ') OR ' . $stokLokasi . ' IN ('
                . implode(', ', $locationPlaceholders)
                . '))';
        }

        if ($jenis === 'KAVLING') {
            $baseWhere[] = "BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan AS TEXT), '')) = '2'";
        } elseif ($jenis === 'NON_KAVLING') {
            $baseWhere[] = "BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan AS TEXT), '')) <> '2'";
        }

        $sql = <<<'SQL'
            WITH
            prm AS (
                SELECT
                    CAST(:tgl_cutoff AS DATE) AS tgl_cutoff,
                    UPPER(BTRIM(CAST(:flag_mgmt AS TEXT))) AS flag_mgmt
            ),

            base_raw AS (
                SELECT
                    ppjb.ppjb_id,
                    UPPER(
                        BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))
                        || '/'
                        || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                    ) AS blok_nomor,
                    UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) AS blok,
                    UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) AS nomor,
                    __STOK_KD_JENIS__ AS kd_jenis,
                    __STOK_KD_TIPE__ AS kd_tipe,
                    __STOK_PERUSAHAAN__ AS kd_perusahaan,
                    __STOK_SEKTOR__ AS kd_sektor,
                    __STOK_LOKASI__ AS kd_lokasi,
                    BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')) AS no_ppjb,
                    ppjb.tgl_ppjb,
                    ppjb.user_entry,
                    BTRIM(COALESCE(CAST(jenis_bangunan.flag_laporan AS TEXT), ''))
                        AS flag_laporan,
                    BTRIM(COALESCE(CAST(jenis_bangunan.deskripsi AS TEXT), ''))
                        AS deskripsi_jenis,
                    __TGL_RENCANA_TAMPIL__ AS tgl_rencana_tampil,
                    __TGL_RENCANA_FILTER__ AS tgl_rencana_filter
                FROM public.sr_ppjb AS ppjb
                INNER JOIN public.sr_stok AS stok
                    ON __JOIN_STOK_PPJB__
                /*
                 * Query desktop menyambung TIPE dan JENIS_BANGUNAN dengan join
                 * lama (koma di FROM), yang berarti INNER JOIN. Itu aman di SQL
                 * Server karena setiap pasangan KD_JENIS + KD_TIPE pada STOK
                 * pasti ada di TIPE.
                 *
                 * Di PostgreSQL pasangan itu belum lengkap, sehingga INNER JOIN
                 * membuat unitnya lenyap dari laporan padahal desktop tetap
                 * menampilkannya. Karena itu di sini memakai LEFT JOIN. Unitnya
                 * tetap tampil, hanya kolom yang berasal dari tipe yang kosong.
                 *
                 * Akibat lain yang perlu diketahui: flag_laporan ikut kosong,
                 * dan penyaring Non Kavling memakai flag_laporan <> '2',
                 * sehingga unit tanpa tipe masuk ke Non Kavling.
                 */
                LEFT JOIN public.sr_tipe AS tipe
                    ON __TIPE_KD_JENIS__ = __STOK_KD_JENIS__
                    AND __TIPE_KD_TIPE__ = __STOK_KD_TIPE__
                LEFT JOIN public.sr_jenis_bangunan AS jenis_bangunan
                    ON __JENIS_KD_JENIS__ = __TIPE_KD_JENIS__
                WHERE __BASE_WHERE__
            ),

            base AS MATERIALIZED (
                SELECT *
                FROM base_raw
                WHERE tgl_rencana_filter >= CAST(:tgl_awal AS DATE)
                  AND tgl_rencana_filter
                        < CAST(:tgl_akhir AS DATE) + INTERVAL '1 day'
            ),

            ppjb_relevan AS MATERIALIZED (
                SELECT DISTINCT ppjb_id
                FROM base
            ),

            st_relevan AS MATERIALIZED (
                SELECT
                    st.ctid AS row_pos,
                    st.ppjb_id,
                    st.no_surat,
                    st.tgl_surat,
                    st.tgl_serah_terima
                FROM public.sr_serah_terima AS st
                INNER JOIN ppjb_relevan AS pr
                    ON __JOIN_ST_PPJB__
                WHERE COALESCE(
                        NULLIF(
                            UPPER(BTRIM(CAST(st.flag_aktif AS TEXT))),
                            ''
                        ),
                        'A'
                    ) <> 'T'
                  AND st.tgl_batal IS NULL
            ),

            surat_st AS (
                SELECT DISTINCT ON (ppjb_id)
                    ppjb_id,
                    COALESCE(
                        NULLIF(BTRIM(CAST(no_surat AS TEXT)), ''),
                        'T'
                    ) AS no_surat,
                    tgl_surat
                FROM st_relevan
                ORDER BY ppjb_id, row_pos
            ),

            realisasi_st AS (
                SELECT DISTINCT ON (ppjb_id)
                    ppjb_id,
                    tgl_serah_terima
                FROM st_relevan
                WHERE tgl_serah_terima IS NOT NULL
                ORDER BY ppjb_id, row_pos
            ),

            pembeli_aktif AS MATERIALIZED (
                SELECT
                    pembeli_ppjb.ppjb_id,
                    pembeli_ppjb.nasabah_id
                FROM public.sr_pembeli_ppjb AS pembeli_ppjb
                INNER JOIN ppjb_relevan AS pr
                    ON __JOIN_PEMBELI_PPJB__
                WHERE UPPER(
                    BTRIM(
                        COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')
                    )
                ) = 'Y'
            ),

            lokasi_kode AS (
                SELECT DISTINCT kd_lokasi
                FROM base
                WHERE kd_lokasi <> ''
            ),

            lokasi_ref AS (
                SELECT DISTINCT ON (lokasi_kode.kd_lokasi)
                    lokasi_kode.kd_lokasi,
                    BTRIM(CAST(l.deskripsi AS TEXT)) AS deskripsi
                FROM lokasi_kode
                INNER JOIN public.sr_lokasi AS l
                    ON UPPER(BTRIM(COALESCE(
                        NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_proyek', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_cluster', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_sektor', ''),
                        ''
                    ))) = lokasi_kode.kd_lokasi
                ORDER BY lokasi_kode.kd_lokasi, l.ctid
            ),

            sektor_kode AS (
                SELECT DISTINCT kd_perusahaan, kd_sektor
                FROM base
                WHERE kd_sektor <> ''
            ),

            master_sektor AS (
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
                    BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', '')) AS deskripsi,
                    COALESCE(
                        NULLIF(
                            UPPER(BTRIM(to_jsonb(s) ->> 'flag_aktif')),
                            ''
                        ),
                        'A'
                    ) AS flag_aktif,
                    2 AS source_priority
                FROM public.sr_sektor AS s

                UNION ALL

                SELECT
                    UPPER(BTRIM(COALESCE(
                        NULLIF(to_jsonb(l) ->> 'kd_lokasi', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_lv2', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_proyek', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_cluster', ''),
                        NULLIF(to_jsonb(l) ->> 'kd_sektor', ''),
                        ''
                    ))) AS kode,
                    UPPER(BTRIM(COALESCE(
                        to_jsonb(l) ->> 'kd_perusahaan',
                        ''
                    ))) AS kd_perusahaan,
                    BTRIM(COALESCE(to_jsonb(l) ->> 'deskripsi', '')) AS deskripsi,
                    COALESCE(
                        NULLIF(
                            UPPER(BTRIM(to_jsonb(l) ->> 'flag_aktif')),
                            ''
                        ),
                        'A'
                    ) AS flag_aktif,
                    1 AS source_priority
                FROM public.sr_lokasi AS l
            ),

            sektor_ref AS (
                SELECT kd_perusahaan, kd_sektor, deskripsi
                FROM (
                    SELECT
                        sektor_kode.kd_perusahaan,
                        sektor_kode.kd_sektor,
                        master_sektor.deskripsi,
                        ROW_NUMBER() OVER (
                            PARTITION BY
                                sektor_kode.kd_perusahaan,
                                sektor_kode.kd_sektor
                            ORDER BY
                                CASE
                                    WHEN master_sektor.kd_perusahaan
                                        = sektor_kode.kd_perusahaan
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
                    FROM sektor_kode
                    INNER JOIN master_sektor
                        ON master_sektor.kode = sektor_kode.kd_sektor
                       AND master_sektor.deskripsi <> ''
                ) AS pilihan
                WHERE urutan = 1
            )

            SELECT
                base.blok_nomor AS "BLOK_NOMOR",
                __NAMA__ AS "NAMA",
                base.no_ppjb AS "NO_PPJB",
                base.tgl_ppjb AS "TGL_PPJB",
                base.tgl_rencana_tampil AS "TGL_RENCANA_SS",
                base.user_entry AS "USER_ENTRY",
                base.kd_jenis AS "KD_JENIS",
                base.kd_perusahaan AS "KD_PERUSAHAAN",
                base.flag_laporan AS "FLAG_LAPORAN",
                base.deskripsi_jenis AS "DESKRIPSI_JENIS",
                NULLIF(__TELP_RUMAH__, '') AS "TELP_RMH",
                __ALAMAT__ AS "ALAMAT",
                __KOTA__ AS "KOTA",
                COALESCE(
                    NULLIF(BTRIM(CAST(surat_st.no_surat AS TEXT)), ''),
                    ''
                ) AS "NO_SURAT",
                surat_st.tgl_surat AS "TGL_SURAT",
                realisasi_st.tgl_serah_terima AS "TGL_SERAH_TERIMA",
                COALESCE(lokasi_ref.deskripsi, '') AS "NAMA_LOKASI",
                COALESCE(
                    NULLIF(sektor_ref.deskripsi, ''),
                    CASE base.kd_sektor
                        WHEN 'CLA' THEN 'CHELIA RESIDENCE'
                        WHEN 'EMC' THEN 'EMERALD COMMERCIAL'
                        ELSE base.kd_sektor
                    END
                ) AS "NAMA_SEKTOR",
                CURRENT_TIMESTAMP AS "TGL_CETAK"
            FROM base
            INNER JOIN pembeli_aktif
                ON __JOIN_BASE_PEMBELI__
            LEFT JOIN public.sr_nasabah AS nasabah
                ON __JOIN_NASABAH__
            LEFT JOIN surat_st
                ON surat_st.ppjb_id = base.ppjb_id
            LEFT JOIN realisasi_st
                ON realisasi_st.ppjb_id = base.ppjb_id
            LEFT JOIN lokasi_ref
                ON lokasi_ref.kd_lokasi = base.kd_lokasi
            LEFT JOIN sektor_ref
                ON sektor_ref.kd_perusahaan = base.kd_perusahaan
               AND sektor_ref.kd_sektor = base.kd_sektor
            CROSS JOIN prm
            WHERE (
                (
                    COALESCE(
                        realisasi_st.tgl_serah_terima,
                        TIMESTAMP '2999-01-01 00:00:00'
                    ) > prm.tgl_cutoff
                    AND prm.flag_mgmt = 'Y'
                )
                OR prm.flag_mgmt = 'T'
            )
            /*
             * Desktop mengurutkan berdasarkan FLAG_LAPORAN lalu BLOK_NOMOR.
             * Unit yang tipenya belum ada di sr_tipe tidak punya FLAG_LAPORAN,
             * dan nilai kosong akan naik ke paling atas sehingga seluruh nomor
             * urut bergeser dan sulit dibandingkan dengan desktop. Karena itu
             * baris tanpa FLAG_LAPORAN sengaja ditaruh paling belakang. Urutan
             * baris yang FLAG_LAPORAN-nya ada tetap sama persis dengan desktop,
             * dan unit yang tipenya belum lengkap mudah dikenali di bagian
             * bawah laporan karena kolom jenisnya kosong.
             */
            ORDER BY
                CASE WHEN base.flag_laporan = '' THEN 1 ELSE 0 END ASC,
                "FLAG_LAPORAN" ASC,
                "BLOK_NOMOR" ASC
        SQL;

        $sql = strtr($sql, [
            '__STOK_KD_JENIS__' => $stokKdJenis,
            '__STOK_KD_TIPE__' => $stokKdTipe,
            '__STOK_PERUSAHAAN__' => $stokPerusahaan,
            '__STOK_SEKTOR__' => $stokSektor,
            '__STOK_LOKASI__' => $stokLokasi,
            '__TGL_RENCANA_TAMPIL__' => $tglRencanaTampil,
            '__TGL_RENCANA_FILTER__' => $tglRencanaFilter,
            '__JOIN_STOK_PPJB__' => $joinStokPpjb,
            '__JOIN_ST_PPJB__' => $joinStPpjb,
            '__JOIN_PEMBELI_PPJB__' => $joinPembeliPpjb,
            '__JOIN_BASE_PEMBELI__' => $joinBasePembeli,
            '__JOIN_NASABAH__' => $joinNasabah,
            '__TIPE_KD_JENIS__' => $tipeKdJenis,
            '__TIPE_KD_TIPE__' => $tipeKdTipe,
            '__JENIS_KD_JENIS__' => $jenisKdJenis,
            '__BASE_WHERE__' => implode("\n                    AND ", $baseWhere),
            '__NAMA__' => $nama,
            '__TELP_RUMAH__' => $telpRumah,
            '__ALAMAT__' => $alamat,
            '__KOTA__' => $kota,
        ]);

        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);

        if ($cacheSeconds > 0) {
            try {
                Cache::store('file')->put($cacheKey, $rows, $cacheSeconds);
            } catch (\Throwable $ignored) {
                // Hasil laporan tetap dikembalikan walaupun cache gagal.
            }
        }

        return $rows;
    }

    private function hydrateSerahTerimaColumns(array &$rows, string $perusahaan): void
    {
        if (count($rows) < 1) {
            return;
        }

        $blokNomors = [];

        foreach ($rows as $row) {
            $blokNomor = $this->normalizeText($row->BLOK_NOMOR ?? '');

            if ($blokNomor !== '') {
                $blokNomors[$blokNomor] = true;
            }
        }

        $blokNomors = array_keys($blokNomors);

        if (count($blokNomors) < 1) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($blokNomors), '?'));

        $sqlBast = <<<SQL
            SELECT
                UPPER(
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))
                    || '/'
                    || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                ) AS blok_nomor,

                UPPER(BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), '')))
                    AS no_ppjb,

                COALESCE(
                    NULLIF(BTRIM(CAST(st.no_surat AS TEXT)), ''),
                    'T'
                ) AS no_surat,

                st.tgl_surat,
                st.tgl_serah_terima,
                st.tgl_entry,
                st.serah_terima_id

            FROM public.sr_serah_terima AS st

            INNER JOIN public.sr_ppjb AS ppjb
                ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
                = BTRIM(CAST(st.ppjb_id AS TEXT))

            INNER JOIN public.sr_stok AS stok
                ON BTRIM(CAST(stok.stok_id AS TEXT))
                = BTRIM(CAST(ppjb.stok_id AS TEXT))

            WHERE UPPER(
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))
                    || '/'
                    || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                ) IN ($placeholders)

                AND (
                    UPPER(
                        BTRIM(
                            COALESCE(
                                to_jsonb(stok) ->> 'kd_perusahaan',
                                ''
                            )
                        )
                    ) = ?
                    OR ? = '*'
                )

                AND COALESCE(
                    NULLIF(
                        UPPER(BTRIM(CAST(st.flag_aktif AS TEXT))),
                        ''
                    ),
                    'A'
                ) <> 'T'

                AND NULLIF(
                    BTRIM(COALESCE(CAST(st.tgl_batal AS TEXT), '')),
                    ''
                ) IS NULL

            ORDER BY
                UPPER(
                    BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))
                    || '/'
                    || BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))
                ) ASC,
                UPPER(BTRIM(COALESCE(CAST(ppjb.no_ppjb AS TEXT), ''))) ASC,
                CASE WHEN st.tgl_serah_terima IS NULL THEN 1 ELSE 0 END ASC,
                st.tgl_serah_terima DESC NULLS LAST,
                st.tgl_surat DESC NULLS LAST,
                st.tgl_entry DESC NULLS LAST,
                BTRIM(CAST(st.serah_terima_id AS TEXT)) DESC
        SQL;

        $bindings = array_merge(
            $blokNomors,
            [$perusahaan, $perusahaan]
        );

        $bastRows = DB::connection(self::CONNECTION)->select(
            $sqlBast,
            $bindings
        );

        $byBlokAndPpjb = [];
        $byBlok = [];

        foreach ($bastRows as $bast) {
            $blokNomor = $this->normalizeText($bast->blok_nomor ?? '');
            $noPpjb = $this->normalizeText($bast->no_ppjb ?? '');

            if ($blokNomor === '') {
                continue;
            }

            if (!isset($byBlok[$blokNomor])) {
                $byBlok[$blokNomor] = $bast;
            }

            $exactKey = $blokNomor . '|' . $noPpjb;

            if ($noPpjb !== '' && !isset($byBlokAndPpjb[$exactKey])) {
                $byBlokAndPpjb[$exactKey] = $bast;
            }
        }

        foreach ($rows as $row) {
            $blokNomor = $this->normalizeText($row->BLOK_NOMOR ?? '');
            $noPpjb = $this->normalizeText($row->NO_PPJB ?? '');

            if ($blokNomor === '') {
                continue;
            }

            $exactKey = $blokNomor . '|' . $noPpjb;

            $bast = $byBlokAndPpjb[$exactKey]
                ?? $byBlok[$blokNomor]
                ?? null;

            if (!$bast) {
                continue;
            }

            /*
             * Sengaja ditimpa, bukan hanya diisi jika kosong.
             * Tujuannya agar record PPJB historis yang salah/lebih lama
             * tidak mempertahankan nilai kosong atau nilai yang tidak
             * representatif.
             */
            $row->NO_SURAT = $bast->no_surat ?? '';
            $row->TGL_SURAT = $bast->tgl_surat ?? null;
            $row->TGL_SERAH_TERIMA = $bast->tgl_serah_terima ?? null;
        }
    }

    private function sortRowsByBlokNomor(array &$rows): void
    {
        usort($rows, function ($left, $right) {
            [$leftBlok, $leftNomor] = $this->splitBlokNomor(
                $left->BLOK_NOMOR ?? ''
            );

            [$rightBlok, $rightNomor] = $this->splitBlokNomor(
                $right->BLOK_NOMOR ?? ''
            );

            $blokCompare = strcmp($leftBlok, $rightBlok);

            if ($blokCompare !== 0) {
                return $blokCompare;
            }

            $leftNumber = $this->numericPart($leftNomor);
            $rightNumber = $this->numericPart($rightNomor);

            if ($leftNumber !== $rightNumber) {
                return $leftNumber <=> $rightNumber;
            }

            return strcmp($leftNomor, $rightNomor);
        });
    }

    private function splitBlokNomor($blokNomor): array
    {
        $parts = explode('/', $this->normalizeText($blokNomor), 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function numericPart(string $value): int
    {
        if (preg_match('/\d+/', $value, $match)) {
            return (int) $match[0];
        }

        return 0;
    }

    private function normalizeText($value): string
    {
        return strtoupper(trim((string) $value));
    }
}