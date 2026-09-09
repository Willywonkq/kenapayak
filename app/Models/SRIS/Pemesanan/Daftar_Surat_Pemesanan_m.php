<?php 

namespace App\Models\SRIS\Pemesanan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Daftar_Surat_Pemesanan_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';


    /**
     * Metadata kolom disimpan hanya di memory object selama request berjalan.
     * Tidak menggunakan Laravel Cache dan tidak menulis apa pun ke database.
     */
    private array $columnMap = [];

    private function tableColumns(string $table): array
    {
        if (isset($this->columnMap[$table])) {
            return $this->columnMap[$table];
        }

        $rows = DB::connection(self::CONNECTION)->select(
            '
                SELECT column_name, data_type
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
                $columns[$name] = strtolower(trim((string) ($row->data_type ?? '')));
            }
        }

        $this->columnMap[$table] = $columns;

        return $columns;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return isset($this->tableColumns($table)[strtolower($column)]);
    }

    /**
     * Apakah kolom bertipe angka pada database yang sedang dipakai.
     *
     * Tipe kolom kunci berbeda antar hasil migrasi. Contohnya
     * sr_uang_muka.uang_muka_id bertipe varchar sementara
     * sr_biaya_dp.uang_muka_id bertipe numeric, dan PostgreSQL menolak
     * perbandingan langsung antara keduanya.
     */
    private function isNumericColumn(string $table, string $column): bool
    {
        $type = $this->tableColumns($table)[strtolower($column)] ?? '';

        return in_array($type, [
            'numeric', 'decimal', 'integer', 'bigint', 'smallint',
            'real', 'double precision',
        ], true);
    }

    /**
     * Ekspresi kunci join yang seragam untuk satu kolom.
     *
     * Ketika salah satu sisi bertipe angka, kedua sisi disamakan ke ranah
     * numeric supaya nol di depan tidak membuat nilai yang sebenarnya sama
     * menjadi tidak cocok. Selain itu keduanya disamakan sebagai teks.
     */
    private function idKeyExpr(string $table, string $alias, string $column, bool $asNumeric): string
    {
        $qualified = $alias === '' ? $column : "{$alias}.{$column}";

        if (!$asNumeric) {
            return "BTRIM(CAST({$qualified} AS text))";
        }

        if ($this->isNumericColumn($table, $column)) {
            return $qualified;
        }

        return "(CASE WHEN BTRIM(CAST({$qualified} AS text)) ~ '^[0-9]+$'"
            . " THEN CAST(BTRIM(CAST({$qualified} AS text)) AS numeric) END)";
    }

    /**
     * Perbandingan kolom kunci sebuah tabel dengan kolom hasil CTE yang sudah
     * menyediakan bentuk teks maupun numeric sekaligus.
     */
    private function idJoinPrepared(
        string $table,
        string $alias,
        string $column,
        string $numericColumn,
        string $textColumn
    ): string {
        if ($this->isNumericColumn($table, $column)) {
            return "{$alias}.{$column} = {$numericColumn}";
        }

        /*
         * Kolomnya dibiarkan mentah. PostgreSQL menerima perbandingan
         * character varying dengan text, dan dengan begitu index pada kolom
         * tersebut tetap dapat dipakai.
         */
        return "{$alias}.{$column} = {$textColumn}";
    }

    /**
     * Syarat pendamping idJoinPrepared() agar sisi CTE yang tidak dapat
     * dipetakan tidak ikut terbawa.
     */
    private function idJoinPreparedGuard(
        string $table,
        string $column,
        string $numericColumn,
        string $textColumn
    ): string {
        if ($this->isNumericColumn($table, $column)) {
            return "{$numericColumn} IS NOT NULL";
        }

        return "NULLIF({$textColumn}, '') IS NOT NULL";
    }

    /**
     * Bentuk perbandingan kolom kunci dengan daftar nilai dari PHP.
     *
     * Kolomnya sengaja dibiarkan mentah dan justru daftar nilainya yang
     * disesuaikan tipenya. Bila kolom yang dibungkus ekspresi, PostgreSQL
     * tidak dapat memakai index dan tabel sebesar sr_angsuran maupun
     * sr_pembeli_dp akan dipindai seluruhnya.
     */
    private function idAnyArray(string $table, string $alias, string $column, string $placeholder): string
    {
        $tipe = $this->isNumericColumn($table, $column) ? 'numeric' : 'text';

        return "{$alias}.{$column} = ANY({$placeholder}::{$tipe}[])";
    }

    /**
     * Daftar nilai untuk idAnyArray(). Ketika kolomnya bertipe angka, hanya
     * nilai yang benar-benar berupa angka yang dikirim supaya cast tidak gagal.
     */
    private function pgArrayForColumn(string $table, string $column, array $values): string
    {
        if ($this->isNumericColumn($table, $column)) {
            $values = array_values(array_filter($values, static function ($value): bool {
                return preg_match('/^[0-9]+$/', trim((string) $value)) === 1;
            }));

            return '{' . implode(',', array_map(static fn ($v) => trim((string) $v), $values)) . '}';
        }

        return $this->pgTextArray($values);
    }

    /**
     * Bentuk perbandingan dua kolom kunci antar tabel dengan tipe apa pun.
     */
    private function idJoin(
        string $tableA,
        string $aliasA,
        string $columnA,
        string $tableB,
        string $aliasB,
        string $columnB
    ): string {
        $asNumeric = $this->isNumericColumn($tableA, $columnA)
            || $this->isNumericColumn($tableB, $columnB);

        return $this->idKeyExpr($tableA, $aliasA, $columnA, $asNumeric)
            . ' = '
            . $this->idKeyExpr($tableB, $aliasB, $columnB, $asNumeric);
    }

    /**
     * Membuat COALESCE dari kolom fisik yang benar-benar tersedia.
     * Hasilnya sama dengan fallback to_jsonb lama, tetapi PostgreSQL tidak
     * perlu mengubah seluruh row menjadi JSONB pada setiap evaluasi filter.
     */
    private function directTextExpr(
        string $table,
        string $alias,
        array $columns,
        bool $upper = true
    ): string {
        $parts = [];

        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                $parts[] = "NULLIF(BTRIM(CAST({$alias}.{$column} AS text)), '')";
            }
        }

        if (!count($parts)) {
            return "''";
        }

        $expr = count($parts) === 1
            ? $parts[0]
            : 'COALESCE(' . implode(', ', $parts) . ", '')";

        return $upper
            ? "UPPER(BTRIM(COALESCE({$expr}, '')))"
            : "BTRIM(COALESCE({$expr}, ''))";
    }

    private function stokSektorExpr(string $alias = 'stok'): string
    {
        return $this->directTextExpr(
            'sr_stok',
            $alias,
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            true
        );
    }

    private function stokLokasiExpr(string $alias = 'stok'): string
    {
        return $this->directTextExpr(
            'sr_stok',
            $alias,
            ['kd_lokasi', 'kd_lv2'],
            true
        );
    }

    private function stokPerusahaanExpr(string $alias = 'stok'): string
    {
        return $this->directTextExpr(
            'sr_stok',
            $alias,
            ['kd_perusahaan', 'kd_unit', 'kd_pt'],
            true
        );
    }

    /**
     * Ekspresi hydrate pembayaran menggunakan kolom fisik bila tersedia.
     * Urutan fallback tetap: jumlah_bayar -> jumlah -> nilai_bayar -> nominal.
     */
    private function angsuranNumericExpr(string $alias = 'angsuran'): string
    {
        $parts = [];

        foreach (['jumlah_bayar', 'jumlah', 'nilai_bayar', 'nominal'] as $column) {
            if (!$this->hasColumn('sr_angsuran', $column)) {
                continue;
            }

            $parts[] = "
                CASE
                    WHEN REPLACE(
                        COALESCE(CAST({$alias}.{$column} AS text), ''),
                        ',',
                        ''
                    ) ~ '^[+-]{0,1}[0-9]+([.][0-9]+){0,1}$'
                    THEN CAST(
                        REPLACE(CAST({$alias}.{$column} AS text), ',', '')
                        AS numeric
                    )
                END
            ";
        }

        return count($parts)
            ? 'COALESCE(' . implode(",\n", $parts) . ', 0)'
            : '0::numeric';
    }

    /**
     * Urutan tanggal tetap sama seperti model sebelumnya.
     */
    private function angsuranDateExpr(
        string $alias,
        array $columns
    ): string {
        $parts = [];

        foreach ($columns as $column) {
            if (!$this->hasColumn('sr_angsuran', $column)) {
                continue;
            }

            $parts[] = "
                CASE
                    WHEN COALESCE(CAST({$alias}.{$column} AS text), '')
                        ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN CAST({$alias}.{$column} AS date)
                END
            ";
        }

        return count($parts)
            ? 'COALESCE(' . implode(",\n", $parts) . ')'
            : 'NULL::date';
    }

    /**
     * Master lokasi untuk dropdown Daftar Surat Pesanan.
     *
     * Deskripsi lokasi tidak tersedia pada database web, sehingga pasangan
     * kode dan deskripsi dipelihara di aplikasi. Nilai KD_LOKASI harus tetap
     * berupa kode karena nilai tersebut dipakai sebagai parameter filter
     * laporan.
     */
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
     * Mengambil daftar sektor/cluster untuk lookup pada form.
     *
     * Kode sektor berasal dari sr_stok karena tabel yang sama juga dipakai
     * sebagai sumber filter laporan. Master sr_sektor/sr_lokasi hanya
     * melengkapi deskripsi. Dengan pola ini, kode yang masih memiliki stok
     * tetapi belum mempunyai master DTSA, seperti CLA dan EMC, tetap muncul.
     */
    public function obtainSektor($kdPerusahaan): Collection
    {
        $kdPerusahaan = strtoupper(trim((string) $kdPerusahaan));

        $stokKode = $this->stokSektorExpr('stok');
        $stokPerusahaan = $this->stokPerusahaanExpr('stok');

        $sektorKode = $this->directTextExpr(
            'sr_sektor',
            'sektor',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            true
        );
        $sektorDeskripsi = $this->directTextExpr(
            'sr_sektor',
            'sektor',
            ['deskripsi'],
            false
        );
        $sektorPerusahaan = $this->directTextExpr(
            'sr_sektor',
            'sektor',
            ['kd_perusahaan'],
            true
        );
        $sektorAktif = $this->directTextExpr(
            'sr_sektor',
            'sektor',
            ['flag_aktif'],
            true
        );

        $lokasiKode = $this->directTextExpr(
            'sr_lokasi',
            'lokasi',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor'],
            true
        );
        $lokasiDeskripsi = $this->directTextExpr(
            'sr_lokasi',
            'lokasi',
            ['deskripsi'],
            false
        );
        $lokasiPerusahaan = $this->directTextExpr(
            'sr_lokasi',
            'lokasi',
            ['kd_perusahaan'],
            true
        );
        $lokasiAktif = $this->directTextExpr(
            'sr_lokasi',
            'lokasi',
            ['flag_aktif'],
            true
        );

        $smKode = $this->directTextExpr(
            'sr_sektor',
            'sm',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            '',
            true,
            true
        );

        $smPerusahaan = $this->directTextExpr(
            'sr_sektor',
            'sm',
            ['kd_perusahaan'],
            '',
            true,
            true
        );

        $smAktif = $this->directTextExpr(
            'sr_sektor',
            'sm',
            ['flag_aktif'],
            'A',
            true,
            true
        );

        $sql = <<<SQL
            WITH stok_terpilih AS MATERIALIZED (
                SELECT DISTINCT
                    kode,
                    kd_perusahaan
                FROM (
                    SELECT
                        {$stokKode} AS kode,
                        {$stokPerusahaan} AS kd_perusahaan
                    FROM public.sr_stok AS stok
                    WHERE {$stokKode} <> ''

                    UNION ALL

                    /*
                     * Cluster yang belum memiliki stok tetap harus bisa dipilih.
                     * Sebelumnya daftar sektor hanya diambil dari kode yang
                     * muncul di sr_stok, sehingga cluster seperti CHELIA
                     * RESIDENCE, EMERALD COMMERCIAL, dan VANICA RESIDENCE tidak
                     * ikut tampil. Desktop mengambil daftarnya langsung dari
                     * master sektor, dan itu yang ditiru di sini.
                     */
                    SELECT
                        {$smKode} AS kode,
                        {$smPerusahaan} AS kd_perusahaan
                    FROM public.sr_sektor AS sm
                    WHERE {$smKode} <> ''
                      AND COALESCE(NULLIF({$smAktif}, ''), 'A') <> 'T'
                ) AS sumber_kode
                WHERE kd_perusahaan <> ''
                  AND (
                        kd_perusahaan = :kd_perusahaan
                        OR :kd_perusahaan = '*'
                        OR :kd_perusahaan = ''
                  )
            ),

            master_sektor AS MATERIALIZED (
                SELECT
                    {$sektorKode} AS kode,
                    {$sektorDeskripsi} AS deskripsi,
                    {$sektorPerusahaan} AS kd_perusahaan,
                    COALESCE(NULLIF({$sektorAktif}, ''), 'A') AS flag_aktif
                FROM public.sr_sektor AS sektor

                UNION ALL

                SELECT
                    {$lokasiKode} AS kode,
                    {$lokasiDeskripsi} AS deskripsi,
                    {$lokasiPerusahaan} AS kd_perusahaan,
                    COALESCE(NULLIF({$lokasiAktif}, ''), 'A') AS flag_aktif
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

        return collect(
            DB::connection(self::CONNECTION)->select($sql, [
                'kd_perusahaan' => $kdPerusahaan,
            ])
        );
    }

    public function obtainAgen()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_agen as agen')
            ->selectRaw('
                agen.kd_agen AS "KD_AGEN",
                agen.kd_group AS "KD_GROUP",
                agen.nama_agen AS "NAMA_AGEN",
                agen.nama_pt AS "NAMA_PT",
                agen.npwp AS "NPWP",
                agen.nama_pj AS "NAMA_PJ"
            ')
            ->where('agen.flag_aktif', 'A')
            ->orderBy('agen.nama_agen')
            ->get();
    }

    public function obtainSales($kdAgen = '*')
    {
        $kdAgen = strtoupper(trim((string) $kdAgen));

        $query = DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_sales as sales')
            /*
             * Setiap kolom dilewatkan CAST(... AS text) lebih dulu supaya
             * tetap jalan pada database yang menyimpan kode sebagai angka.
             * BTRIM dan COALESCE hanya menerima teks.
             */
            ->leftJoin(self::SCHEMA . '.sr_agen as agen', function ($join) {
                $join->on(
                    DB::raw('BTRIM(CAST(sales.kd_agen AS text))'),
                    '=',
                    DB::raw('BTRIM(CAST(agen.kd_agen AS text))')
                );
            })
            ->selectRaw('
                BTRIM(CAST(sales.deskripsi AS text)) AS "DESKRIPSI",
                BTRIM(CAST(sales.kd_sales AS text)) AS "KD_SALES",
                BTRIM(COALESCE(CAST(agen.nama_agen AS text), \'\')) AS "NAMA_AGEN",
                BTRIM(CAST(sales.kd_agen AS text)) AS "KD_AGEN",
                BTRIM(COALESCE(CAST(sales.status_sales AS text), \'\')) AS "STATUS_SALES"
            ')
            ->whereRaw("UPPER(BTRIM(COALESCE(CAST(sales.flag_aktif AS text), ''))) = 'A'");

        if ($kdAgen !== '*' && $kdAgen !== '') {
            $query->whereRaw(
                "UPPER(BTRIM(COALESCE(CAST(sales.kd_agen AS text), ''))) = ?",
                [$kdAgen]
            );
        }

        return $query
            ->orderByRaw('BTRIM(CAST(sales.deskripsi AS text))')
            ->get();
    }

    public function obtainTipeBayar()
    {
        return DB::connection(self::CONNECTION)
            ->table(self::SCHEMA . '.sr_tipe_bayar as tipe_bayar')
            ->selectRaw('
                tipe_bayar.tipe_bayar AS "TIPE_BAYAR",
                tipe_bayar.nama AS "NAMA"
            ')
            ->orderBy('tipe_bayar.tipe_bayar')
            ->get();
    }

    public function obtainLokasi()
    {
        return collect(self::LOKASI_LIST)
            ->map(static function (array $lokasi): object {
                return (object) $lokasi;
            });
    }

    private function getBuktiTahap1Sql(): string
    {
        $candidates = [
            'no_kuitansi',
            'no_kwitansi',
            'no_bukti',
            'no_bukti_bayar',
            'no_bayar',
        ];

        /*
         * tableColumns() membaca information_schema satu kali per tabel lalu
         * menyimpannya di memory object selama request. Tidak ada write/cache DB.
         */
        foreach ($candidates as $column) {
            if ($this->hasColumn('sr_bayar_uang_muka', $column)) {
                return 'CAST(bum.' . $column . ' AS TEXT)';
            }
        }

        return 'NULL::text';
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
            'SEMUA SALES',
            'SEMUA SEKTOR',
            'SEMUA LOKASI',
            'SEMUA JENIS',
            'HARAP PILIH TIPE PEMBAYARAN',
            'PILIH TIPE PEMBAYARAN',
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

    private function placeholderList(array $items): string
    {
        return implode(',', array_fill(0, count($items), '?'));
    }


    /**
     * Encode array PHP menjadi literal text[] PostgreSQL.
     * Dipakai supaya query besar tidak membuat ratusan/ribuan placeholder VALUES.
     * Isi ID tidak diubah, hanya cara pengiriman parameter ke PostgreSQL.
     */
    private function pgTextArray(array $items): string
    {
        $encoded = [];

        foreach ($items as $item) {
            $value = (string) $item;
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace('"', '\\"', $value);
            $encoded[] = '"' . $value . '"';
        }

        return '{' . implode(',', $encoded) . '}';
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

    /**
     * Resolve kode/deskripsi sektor sekali di PHP supaya query utama tidak harus
     * LEFT JOIN sr_sektor hanya untuk membandingkan deskripsi seperti VIOLA RESIDENCE.
     */
    private function resolveSektorValues(string $sektor, string $perusahaan): array
    {
        if ($sektor === '*' || $sektor === '') {
            return ['*'];
        }

        /*
         * Popup mengirim kode sektor. Pemetaan ini juga menjaga filter tetap
         * bekerja apabila integrasi lama masih mengirim teks deskripsinya.
         */
        $fallbackCode = [
            'CHELIA RESIDENCE' => 'CLA',
            'EMERALD COMMERCIAL' => 'EMC',
        ][$sektor] ?? null;

        /*
         * Fast path: popup sektor sudah memanggil obtainSektor(). Hasilnya di-cache,
         * sehingga deskripsi seperti VIOLA RESIDENCE dapat dipetakan ke kode tanpa
         * menjalankan lima scan sr_sektor lagi sebelum get_summary.
         * Jika tidak ada match, SQL fallback lama tetap dijalankan agar kompatibilitas
         * data lama tidak berubah.
         */
        $resolvedFromLookup = [];

        if (preg_match('/^[A-Z0-9_-]{1,30}$/', $sektor)) {
            $resolvedFromLookup[] = $sektor;
        } else {
            try {
                foreach ($this->obtainSektor($perusahaan) as $row) {
                    $kode = strtoupper(trim((string) ($row->KD_SEKTOR ?? '')));
                    $deskripsi = strtoupper(trim((string) ($row->DESKRIPSI ?? '')));

                    if ($kode === $sektor || $deskripsi === $sektor) {
                        $resolvedFromLookup[] = $kode;
                    }
                }
            } catch (\Throwable $e) {
                // Resolver SQL lama di bawah tetap menjadi fallback penuh.
            }
        }

        if ($fallbackCode !== null) {
            $resolvedFromLookup[] = $fallbackCode;
        }

        $resolvedFromLookup = $this->normalizeCodes($resolvedFromLookup);

        if (count($resolvedFromLookup) > 0) {
            return $resolvedFromLookup;
        }

        $sql = <<<'SQL'
            SELECT DISTINCT value
            FROM (
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) AS value
                FROM public.sr_sektor AS s
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) = ?

                UNION ALL
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) AS value
                FROM public.sr_sektor AS s
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) = ?

                UNION ALL
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) AS value
                FROM public.sr_sektor AS s
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) = ?

                UNION ALL
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) AS value
                FROM public.sr_sektor AS s
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) = ?

                UNION ALL
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) AS value
                FROM public.sr_sektor AS s
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_proyek', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_sektor', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_cluster', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(s) ->> 'kd_lv2', ''))) = ?
            ) AS x
            WHERE value <> ''
        SQL;

        $bindings = [];
        for ($i = 0; $i < 5; $i++) {
            array_push($bindings, $sektor, $sektor, $sektor, $sektor, $sektor, $sektor);
        }

        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $values = [$sektor];

        if ($fallbackCode !== null) {
            $values[] = $fallbackCode;
        }

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
         * Master lokasi sudah tersedia di LOKASI_LIST. Gunakan itu lebih dulu agar
         * get_summary tidak perlu query sr_lokasi hanya untuk kode/deskripsi normal.
         * SQL lama tetap dipertahankan sebagai fallback untuk nilai legacy.
         */
        $lokasiMatches = [];

        foreach (self::LOKASI_LIST as $item) {
            $kode = strtoupper(trim((string) ($item['KD_LOKASI'] ?? '')));
            $deskripsi = strtoupper(trim((string) ($item['DESKRIPSI'] ?? '')));

            if ($kode === $lokasi || $deskripsi === $lokasi) {
                $lokasiMatches[] = $kode;
            }
        }

        if (count($lokasiMatches) > 0) {
            return $this->normalizeCodes($lokasiMatches);
        }

        $sql = <<<'SQL'
            SELECT DISTINCT value
            FROM (
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lokasi', ''))) AS value
                FROM public.sr_lokasi AS l
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lv2', ''))) = ?

                UNION ALL
                SELECT UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lv2', ''))) AS value
                FROM public.sr_lokasi AS l
                WHERE UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'deskripsi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lokasi', ''))) = ?
                   OR UPPER(BTRIM(COALESCE(to_jsonb(l) ->> 'kd_lv2', ''))) = ?
            ) AS x
            WHERE value <> ''
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($sql, [
            $lokasi,
            $lokasi,
            $lokasi,
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

    private function buildFilterSql(
        string $jenis,
        array $lokasiValues,
        array $sektorValues,
        string $tipeBayar,
        string $bgb,
        array &$bindings
    ): string {
        $where = [];

        if ($jenis !== '*') {
            $where[] = "UPPER(BTRIM(CAST(jenis_bangunan.flag_laporan AS text))) = ?";
            $bindings[] = $jenis;
        }

        if (!(count($lokasiValues) === 1 && $lokasiValues[0] === '*')) {
            $where[] = $this->stokLokasiExpr('stok') . " IN (" . $this->placeholderList($lokasiValues) . ")";
            array_push($bindings, ...$lokasiValues);
        }

        if (!(count($sektorValues) === 1 && $sektorValues[0] === '*')) {
            $where[] = $this->stokSektorExpr('stok') . " IN (" . $this->placeholderList($sektorValues) . ")";
            array_push($bindings, ...$sektorValues);
        }

        if ($tipeBayar !== '*') {
            $where[] = "UPPER(BTRIM(CAST(um.tipe_bayar_angs AS text))) = ?";
            $bindings[] = $tipeBayar;
        }

        if ($bgb !== '*') {
            $where[] = "UPPER(BTRIM(COALESCE(CAST(um.flag_bgb AS text), 'T'))) = ?";
            $bindings[] = $bgb;
        }

        return count($where) ? "\n                    AND " . implode("\n                    AND ", $where) : '';
    }

    private function buildCandidateFilterSql(
        string $jenis,
        array $lokasiValues,
        array $sektorValues,
        string $tipeBayar,
        string $bgb,
        array &$bindings
    ): string {
        $where = [];
        $schema = self::SCHEMA;

        if ($jenis !== '*') {
            $where[] = "EXISTS (
                        SELECT 1
                        FROM {$schema}.sr_tipe AS t
                        INNER JOIN {$schema}.sr_jenis_bangunan AS jb
                            ON UPPER(BTRIM(CAST(jb.kd_jenis AS text))) = UPPER(BTRIM(CAST(t.kd_jenis AS text)))
                        WHERE UPPER(BTRIM(CAST(t.kd_jenis AS text))) = UPPER(BTRIM(COALESCE(NULLIF(to_jsonb(stok) ->> 'kd_jenis_bgn', ''), NULLIF(to_jsonb(stok) ->> 'kd_jenis', ''), '')))
                          AND UPPER(BTRIM(CAST(t.kd_tipe AS text))) = UPPER(BTRIM(COALESCE(NULLIF(to_jsonb(stok) ->> 'kd_tipe_bgn', ''), NULLIF(to_jsonb(stok) ->> 'kd_tipe', ''), '')))
                          AND UPPER(BTRIM(CAST(jb.flag_laporan AS text))) = ?
                    )";
            $bindings[] = $jenis;
        }

        if (!(count($lokasiValues) === 1 && $lokasiValues[0] === '*')) {
            $where[] = $this->stokLokasiExpr('stok') . " IN (" . $this->placeholderList($lokasiValues) . ")";
            array_push($bindings, ...$lokasiValues);
        }

        if (!(count($sektorValues) === 1 && $sektorValues[0] === '*')) {
            $where[] = $this->stokSektorExpr('stok') . " IN (" . $this->placeholderList($sektorValues) . ")";
            array_push($bindings, ...$sektorValues);
        }

        if ($tipeBayar !== '*') {
            $where[] = "UPPER(BTRIM(CAST(um.tipe_bayar_angs AS text))) = ?";
            $bindings[] = $tipeBayar;
        }

        if ($bgb !== '*') {
            $where[] = "UPPER(BTRIM(COALESCE(CAST(um.flag_bgb AS text), 'T'))) = ?";
            $bindings[] = $bgb;
        }

        return count($where) ? "\n                  AND " . implode("\n                  AND ", $where) : '';
    }

    /**
     * Bangun sumber candidate uang muka langsung sebagai CTE untuk query utama.
     *
     * Versi sebelumnya menjalankan SELECT kandidat lebih dulu lalu query utama
     * membaca sr_uang_muka lagi berdasarkan daftar ID tersebut. Pada rentang tanggal
     * panjang ini berarti tabel uang muka dan stok dibaca dua kali.
     *
     * Method ini hanya membentuk SQL + binding. Tidak melakukan write apa pun.
     */
    private function buildCandidateUangMukaSource(
        string $flagTgl,
        string $tglAwal,
        string $tglAkhir,
        string $perusahaan,
        string $status,
        string $agen,
        string $sales,
        string $jenis,
        array $lokasiValues,
        array $sektorValues,
        string $tipeBayar,
        string $bgb
    ): array {
        $schema = self::SCHEMA;
        $dateColumn = $flagTgl === '1' ? 'um.tgl_entry' : 'um.tgl_uang_muka';

        $filterBindings = [];
        $dynamicFilterSql = $this->buildCandidateFilterSql(
            $jenis,
            $lokasiValues,
            $sektorValues,
            $tipeBayar,
            $bgb,
            $filterBindings
        );

        if ($status === 'B') {
            $statusSql = "
                  AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'T'
                  AND UPPER(BTRIM(COALESCE(CAST(um.flag_batal AS text), 'T'))) = 'Y'";
        } else {
            $statusSql = "
                  AND UPPER(BTRIM(COALESCE(CAST(um.flag_aktif AS text), ''))) = 'A'";

            if ($status === 'T') {
                $statusSql .= "
                  AND NOT EXISTS (
                        SELECT 1
                        FROM {$schema}.sr_ppjb AS px
                        WHERE {$this->idJoin('sr_ppjb', 'px', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                          AND NULLIF(BTRIM(COALESCE(CAST(px.parent_id AS text), '')), '') IS NULL
                          AND UPPER(BTRIM(COALESCE(CAST(px.flag_aktif AS text), ''))) = 'A'
                  )
                  AND UPPER(BTRIM(COALESCE(CAST(um.status AS text), ''))) = 'T'";
            } elseif ($status === '2') {
                $statusSql .= "
                  AND NOT EXISTS (
                        SELECT 1
                        FROM {$schema}.sr_ppjb AS px
                        WHERE {$this->idJoin('sr_ppjb', 'px', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                          AND NULLIF(BTRIM(COALESCE(CAST(px.parent_id AS text), '')), '') IS NULL
                          AND UPPER(BTRIM(COALESCE(CAST(px.flag_aktif AS text), ''))) = 'A'
                  )";
            } elseif ($status === '1') {
                $statusSql .= "
                  AND EXISTS (
                        SELECT 1
                        FROM {$schema}.sr_ppjb AS px
                        WHERE {$this->idJoin('sr_ppjb', 'px', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                          AND NULLIF(BTRIM(COALESCE(CAST(px.parent_id AS text), '')), '') IS NULL
                          AND UPPER(BTRIM(COALESCE(CAST(px.flag_aktif AS text), ''))) = 'A'
                  )";
            } elseif ($status !== '*') {
                $statusSql .= "
                  AND 1 = 0";
            }
        }

        $agentSalesSql = '';
        $agentSalesBindings = [];

        if ($agen !== '*') {
            $agentSalesSql .= "
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(um.kd_agen AS text), ''))) = ?
                        OR EXISTS (
                            SELECT 1
                            FROM {$schema}.sr_ppjb AS pa
                            WHERE {$this->idJoin('sr_ppjb', 'pa', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                              AND NULLIF(BTRIM(COALESCE(CAST(pa.parent_id AS text), '')), '') IS NULL
                              AND UPPER(BTRIM(COALESCE(CAST(pa.flag_aktif AS text), ''))) = 'A'
                              AND UPPER(BTRIM(COALESCE(CAST(pa.kd_agen AS text), ''))) = ?
                        )
                  )";
            $agentSalesBindings[] = $agen;
            $agentSalesBindings[] = $agen;
        }

        if ($sales !== '*') {
            $agentSalesSql .= "
                  AND (
                        UPPER(BTRIM(COALESCE(CAST(um.kd_sales AS text), ''))) = ?
                        OR EXISTS (
                            SELECT 1
                            FROM {$schema}.sr_ppjb AS ps
                            WHERE {$this->idJoin('sr_ppjb', 'ps', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                              AND NULLIF(BTRIM(COALESCE(CAST(ps.parent_id AS text), '')), '') IS NULL
                              AND UPPER(BTRIM(COALESCE(CAST(ps.flag_aktif AS text), ''))) = 'A'
                              AND UPPER(BTRIM(COALESCE(CAST(ps.kd_sales AS text), ''))) = ?
                        )
                  )";
            $agentSalesBindings[] = $sales;
            $agentSalesBindings[] = $sales;
        }

        $sql = <<<SQL
            SELECT
                um.*
            FROM {$schema}.sr_uang_muka AS um
            INNER JOIN {$schema}.sr_stok AS stok
                ON {$this->idJoin('sr_stok', 'stok', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
            WHERE {$dateColumn} >= ?::date
              AND {$dateColumn} < (?::date + INTERVAL '1 day')
              AND UPPER(BTRIM(CAST(stok.kd_perusahaan AS text))) = ?
              AND NULLIF(BTRIM(COALESCE(CAST(um.parent_id AS text), '')), '') IS NULL
              {$dynamicFilterSql}
              {$statusSql}
              {$agentSalesSql}
        SQL;

        return [
            'sql' => $sql,
            'bindings' => array_merge(
                [$tglAwal, $tglAkhir, $perusahaan],
                $filterBindings,
                $agentSalesBindings
            ),
        ];
    }

    public function obtainSuratPesanan($request)
    {
        $perfStartedAt = microtime(true);
        $perfCandidateMs = 0.0;
        $perfMainQueryMs = 0.0;
        $perfHydrateMs = 0.0;

        $flagTgl = $this->normalizeOption($request->flag_tgl ?? '1', '1');
        $flagTgl = in_array($flagTgl, ['1', '2'], true) ? $flagTgl : '1';

        $tglAwal = $this->normalizeDate($request->tgl_awal ?? null);
        $tglAkhir = $this->normalizeDate($request->tgl_akhir ?? null);
        $tglBayar = $this->normalizeDate($request->tgl_bayar ?? null, $tglAkhir);

        $jenis = $this->normalizeOption($request->jenis ?? '*');
        $lokasi = $this->normalizeOption($request->lokasi ?? '*');
        $sektor = $this->normalizeOption($request->sektor ?? '*');

        $defaultPerusahaan = session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA';
        $perusahaan = $this->normalizeOption($request->perusahaan ?? $defaultPerusahaan, $defaultPerusahaan);

        $status = $this->normalizeOption($request->status ?? '*');
        $tipeBayar = $this->normalizeOption($request->tipe_bayar ?? '*');
        $agen = $this->normalizeOption($request->agen ?? '*');
        $sales = $this->normalizeOption($request->sales ?? '*');
        $bgb = $this->normalizeOption($request->bgb ?? '*');

        $schema = self::SCHEMA;
        $noBuktiTahap1Sql = $this->getBuktiTahap1Sql();
        /*
         * View dan Excel tetap memakai kumpulan data penuh yang sama.
         * Candidate sekarang menjadi CTE di query utama agar tabel besar tidak dibaca dua kali.
         */
        $lokasiValues = $this->resolveLokasiValues($lokasi);
        $sektorValues = $this->resolveSektorValues($sektor, $perusahaan);

        DB::connection(self::CONNECTION)->statement("SET statement_timeout TO '180000'");

        $candidateBuildStartedAt = microtime(true);

        $candidateSource = $this->buildCandidateUangMukaSource(
            $flagTgl,
            $tglAwal,
            $tglAkhir,
            $perusahaan,
            $status,
            $agen,
            $sales,
            $jenis,
            $lokasiValues,
            $sektorValues,
            $tipeBayar,
            $bgb
        );

        $candidateSourceSql = $candidateSource['sql'];
        $candidateBindings = $candidateSource['bindings'];
        $perfCandidateMs = (microtime(true) - $candidateBuildStartedAt) * 1000;

        /*
         * Optimasi v21 READ ONLY SINGLE-PASS tanpa mengurangi data laporan:
         * - Join STOK_ID kandidat/PPJB memakai kolom asli tanpa CAST/BTRIM agar
         *   index PostgreSQL tetap dapat digunakan.
         * - Kondisi status kandidat dibentuk langsung sesuai pilihan user agar
         *   PostgreSQL tidak merencanakan banyak EXISTS/NOT EXISTS yang tidak dipakai.
         * - Enrichment stok/lokasi/sektor/model dihitung sekali per STOK_ID kandidat,
         *   bukan diulang untuk setiap baris pembayaran pada CTE base.
         * - Fallback pembeli PPJB hanya dihitung bila nama dari DP belum tersedia.
         * - Fallback pembeli inline hanya dihitung bila DP dan PPJB sama-sama kosong.
         * - Lookup pembayaran tetap mempertahankan exact + fallback digit, tetapi
         *   duplikasi lookup identik dihindari.
         * - Seluruh prioritas/fallback data tetap sama sehingga data yang sudah tampil
         *   tidak sengaja dikurangi.
         */
        /*
         * Normalisasi kode stok/master dibangun satu kali.
         * Master kecil dimaterialisasi sekali, bukan dipindai ulang lewat LATERAL
         * dengan to_jsonb() untuk setiap STOK_ID kandidat.
         */
        $stokSektorKeySql = $this->stokSektorExpr('stok');
        $stokLokasiKeySql = $this->stokLokasiExpr('stok');
        $stokPerusahaanKeySql = $this->stokPerusahaanExpr('stok');
        $stokJenisKeySql = $this->directTextExpr(
            'sr_stok',
            'stok',
            ['kd_jenis_bgn', 'kd_jenis'],
            true
        );
        $stokTipeKeySql = $this->directTextExpr(
            'sr_stok',
            'stok',
            ['kd_tipe_bgn', 'kd_tipe'],
            true
        );
        $stokModelKeySql = $this->directTextExpr(
            'sr_stok',
            'stok',
            ['kd_model_bgn', 'kd_model'],
            true
        );

        $lokasiMasterKeySql = $this->directTextExpr(
            'sr_lokasi',
            'l',
            ['kd_lokasi', 'kd_lv2'],
            true
        );
        $lokasiMasterDescSql = $this->directTextExpr(
            'sr_lokasi',
            'l',
            ['deskripsi'],
            false
        );

        $sektorMasterKeySql = $this->directTextExpr(
            'sr_sektor',
            's',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2'],
            true
        );
        $sektorMasterDescSql = $this->directTextExpr(
            'sr_sektor',
            's',
            ['deskripsi'],
            false
        );
        $sektorMasterPerusahaanSql = $this->directTextExpr(
            'sr_sektor',
            's',
            ['kd_perusahaan'],
            true
        );
        $sektorMasterAktifSql = $this->directTextExpr(
            'sr_sektor',
            's',
            ['flag_aktif'],
            true
        );

        $lokasiAsSektorKeySql = $this->directTextExpr(
            'sr_lokasi',
            'ls',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor'],
            true
        );
        $lokasiAsSektorDescSql = $this->directTextExpr(
            'sr_lokasi',
            'ls',
            ['deskripsi'],
            false
        );
        $lokasiAsSektorPerusahaanSql = $this->directTextExpr(
            'sr_lokasi',
            'ls',
            ['kd_perusahaan'],
            true
        );
        $lokasiAsSektorAktifSql = $this->directTextExpr(
            'sr_lokasi',
            'ls',
            ['flag_aktif'],
            true
        );

        $modelMasterKeySql = $this->directTextExpr(
            'sr_model',
            'm',
            ['kd_model_bgn', 'kd_model'],
            true
        );
        $modelMasterDescSql = $this->directTextExpr(
            'sr_model',
            'm',
            ['deskripsi'],
            false
        );

        $umInlineNameSql = $this->directTextExpr(
            'sr_uang_muka',
            'um',
            ['nama_pembeli', 'nama_nasabah', 'nama_customer', 'nama'],
            true
        );
        $ppjbInlineNameSql = $this->directTextExpr(
            'sr_ppjb',
            'ppjb',
            ['nama_pembeli', 'nama_nasabah', 'nama_customer', 'nama'],
            true
        );
        $umInlineNasabahSql = $this->directTextExpr(
            'sr_uang_muka',
            'um',
            ['nasabah_id', 'pembeli_id', 'customer_id'],
            false
        );
        $ppjbInlineNasabahSql = $this->directTextExpr(
            'sr_ppjb',
            'ppjb',
            ['nasabah_id', 'pembeli_id', 'customer_id'],
            false
        );

        $sql = <<<SQL
            WITH candidate_um AS MATERIALIZED (
                {$candidateSourceSql}
            ),

            candidate_stok AS (
                SELECT DISTINCT
                    um.stok_id
                FROM candidate_um AS um
                WHERE um.stok_id IS NOT NULL
            ),

            /*
             * Enrichment master stok dilakukan satu kali per STOK_ID kandidat.
             * Sebelumnya lookup LATERAL lokasi/sektor/model berada di CTE base dan
             * bisa dieksekusi berulang ketika satu uang muka memiliki beberapa baris
             * pembayaran. Pemindahan ini tidak mengubah isi data, hanya mengurangi
             * pekerjaan berulang.
             */
            candidate_stock_rows AS MATERIALIZED (
                SELECT
                    stok.*,
                    {$stokSektorKeySql} AS sektor_key,
                    {$stokLokasiKeySql} AS lokasi_key,
                    {$stokPerusahaanKeySql} AS perusahaan_key,
                    {$stokJenisKeySql} AS jenis_key,
                    {$stokTipeKeySql} AS tipe_key,
                    COALESCE(NULLIF({$stokModelKeySql}, ''), '000') AS model_key
                FROM {$schema}.sr_stok AS stok
                INNER JOIN candidate_stok AS cs
                    ON {$this->idJoin('sr_uang_muka', 'cs', 'stok_id', 'sr_stok', 'stok', 'stok_id')}
            ),

            master_lokasi AS MATERIALIZED (
                SELECT
                    {$lokasiMasterKeySql} AS kode,
                    {$lokasiMasterDescSql} AS deskripsi
                FROM {$schema}.sr_lokasi AS l
                WHERE {$lokasiMasterKeySql} <> ''
            ),

            master_sektor AS MATERIALIZED (
                SELECT
                    {$sektorMasterKeySql} AS kode,
                    {$sektorMasterDescSql} AS deskripsi,
                    {$sektorMasterPerusahaanSql} AS kd_perusahaan,
                    COALESCE(NULLIF({$sektorMasterAktifSql}, ''), 'A') AS flag_aktif
                FROM {$schema}.sr_sektor AS s

                UNION ALL

                SELECT
                    {$lokasiAsSektorKeySql} AS kode,
                    {$lokasiAsSektorDescSql} AS deskripsi,
                    {$lokasiAsSektorPerusahaanSql} AS kd_perusahaan,
                    COALESCE(NULLIF({$lokasiAsSektorAktifSql}, ''), 'A') AS flag_aktif
                FROM {$schema}.sr_lokasi AS ls
            ),

            master_model AS MATERIALIZED (
                SELECT
                    {$modelMasterKeySql} AS kode,
                    {$modelMasterDescSql} AS deskripsi
                FROM {$schema}.sr_model AS m
                WHERE {$modelMasterKeySql} <> ''
            ),

            stok_enriched AS (
                SELECT
                    stok.*,
                    tipe.deskripsi AS tipe_bgn_enriched,
                    jenis_bangunan.flag_laporan AS flag_laporan_enriched,
                    jenis_bangunan.deskripsi AS jenis_bgn_enriched,
                    lokasi.deskripsi AS nama_lokasi_enriched,
                    COALESCE(
                        NULLIF(sektor.deskripsi, ''),
                        CASE stok.sektor_key
                            WHEN 'CLA' THEN 'CHELIA RESIDENCE'
                            WHEN 'EMC' THEN 'EMERALD COMMERCIAL'
                            ELSE stok.sektor_key
                        END
                    ) AS nama_sektor_enriched,
                    model.deskripsi AS model_deskripsi_enriched
                FROM candidate_stock_rows AS stok

                LEFT JOIN {$schema}.sr_tipe AS tipe
                    ON UPPER(BTRIM(CAST(tipe.kd_jenis AS text))) = stok.jenis_key
                   AND UPPER(BTRIM(CAST(tipe.kd_tipe AS text))) = stok.tipe_key

                LEFT JOIN {$schema}.sr_jenis_bangunan AS jenis_bangunan
                    ON UPPER(BTRIM(CAST(jenis_bangunan.kd_jenis AS text)))
                        = UPPER(BTRIM(CAST(tipe.kd_jenis AS text)))

                LEFT JOIN LATERAL (
                    SELECT l.deskripsi
                    FROM master_lokasi AS l
                    WHERE l.kode = stok.lokasi_key
                    LIMIT 1
                ) AS lokasi ON TRUE

                LEFT JOIN LATERAL (
                    SELECT pilihan.deskripsi
                    FROM master_sektor AS pilihan
                    WHERE pilihan.kode = stok.sektor_key
                      AND pilihan.deskripsi <> ''
                    ORDER BY
                        CASE
                            WHEN pilihan.kd_perusahaan = stok.perusahaan_key THEN 0
                            WHEN pilihan.kd_perusahaan = '' THEN 1
                            ELSE 2
                        END,
                        CASE
                            WHEN pilihan.flag_aktif <> 'T' THEN 0
                            ELSE 1
                        END,
                        pilihan.deskripsi
                    LIMIT 1
                ) AS sektor ON TRUE

                LEFT JOIN LATERAL (
                    SELECT m.deskripsi
                    FROM master_model AS m
                    WHERE m.kode = stok.model_key
                    LIMIT 1
                ) AS model ON TRUE
            ),

            ppjb_candidates AS (
                /*
                 * Prioritas 1: PPJB yang memang berasal dari uang_muka_id yang sama.
                 * Prioritas 2: fallback PPJB aktif terbaru berdasarkan stok_id.
                 * Ini lebih dekat dengan desktop dan mengurangi risiko salah PPJB.
                 */
                SELECT
                    um.uang_muka_id AS um_key,
                    0 AS prioritas,
                    p.*
                FROM candidate_um AS um
                INNER JOIN {$schema}.sr_ppjb AS p
                    ON {$this->idJoin('sr_ppjb', 'p', 'uang_muka_id', 'sr_uang_muka', 'um', 'uang_muka_id')}
                WHERE NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
                  AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'

                UNION ALL

                SELECT
                    um.uang_muka_id AS um_key,
                    1 AS prioritas,
                    p.*
                FROM candidate_um AS um
                INNER JOIN {$schema}.sr_ppjb AS p
                    ON {$this->idJoin('sr_ppjb', 'p', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}
                WHERE NULLIF(BTRIM(COALESCE(CAST(p.parent_id AS text), '')), '') IS NULL
                  AND UPPER(BTRIM(COALESCE(CAST(p.flag_aktif AS text), ''))) = 'A'
            ),

            ppjb_selected AS (
                SELECT DISTINCT ON (um_key)
                    *
                FROM ppjb_candidates
                ORDER BY
                    um_key,
                    prioritas ASC,
                    tgl_ppjb DESC NULLS LAST,
                    ppjb_id DESC
            ),

            base AS (
                SELECT
                    BTRIM(CAST(um.uang_muka_id AS text)) AS uang_muka_id_text,
                    BTRIM(CAST(um.stok_id AS text)) AS stok_id_text,

                    um.uang_muka_id,
                    um.tgl_uang_muka,
                    um.no_uang_muka,
                    um.dpp,
                    um.biaya_bphtb,
                    um.harga_jual,
                    um.tipe_bayar_angs,
                    um.user_entry,
                    um.tgl_entry,
                    um.flag_bgb,
                    um.nilai_bgb,
                    um.status AS um_status,
                    um.flag_aktif AS um_flag_aktif,
                    um.flag_batal AS um_flag_batal,
                    um.parent_id AS um_parent_id,
                    um.kd_agen AS um_kd_agen,
                    um.kd_sales AS um_kd_sales,

                    bum.jumlah_bayar,
                    bum.tgl_bayar,
                    {$noBuktiTahap1Sql} AS no_bukti_tahap_1,
                    cara_bayar.nama AS cara_bayar_nama,

                    stok.stok_id,
                    stok.blok,
                    stok.nomor,
                    UPPER(BTRIM(COALESCE(CAST(stok.blok AS text), '')))
                        || '/'
                        || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS text), '')))
                        AS blok_nomor,
                    stok.luas_tanah,
                    stok.luas_bangunan,
                    stok.spesifikasi,
                    stok.harga_dasar1,
                    stok.harga_dasar2,
                    stok.harga_dasar3,
                    stok.harga_dasar4,
                    stok.harga_dasar5,
                    stok.harga_dasar6,
                    stok.harga_dasar7,
                    stok.harga_dasar8,
                    stok.harga_dasar9,
                    stok.harga_dasar10,
                    stok.kd_perusahaan,

                    stok.tipe_bgn_enriched AS tipe_bgn,
                    stok.flag_laporan_enriched AS flag_laporan,
                    stok.jenis_bgn_enriched AS jenis_bgn,

                    stok.nama_lokasi_enriched AS nama_lokasi,
                    stok.nama_sektor_enriched AS nama_sektor,
                    stok.model_deskripsi_enriched AS model_deskripsi,
                    tipe_bayar.nama AS tipe_pembayaran,
                    sales_data.kd_sales AS kd_sales_display,
                    sales_data.deskripsi AS nama_sales,
                    agen_data.nama_agen AS nama_agen,

                    ppjb.ppjb_id,
                    BTRIM(CAST(ppjb.ppjb_id AS text)) AS ppjb_id_text,
                    ppjb.no_ppjb,
                    ppjb.tgl_ppjb,
                    ppjb.tgl_tanda_tangan,
                    ppjb.tgl_ttd_notaris,
                    ppjb.harga_jual AS harga_jual_ppjb_pokok,
                    ppjb.kd_agen AS ppjb_kd_agen,
                    ppjb.kd_sales AS ppjb_kd_sales,

                    /*
                     * Fallback inline tetap sama, tetapi memakai kolom fisik yang
                     * tersedia agar row kandidat tidak perlu diubah ke JSONB.
                     */
                    COALESCE(
                        NULLIF({$umInlineNameSql}, ''),
                        NULLIF({$ppjbInlineNameSql}, ''),
                        ''
                    ) AS nama_pembeli_inline,

                    COALESCE(
                        NULLIF({$umInlineNasabahSql}, ''),
                        NULLIF({$ppjbInlineNasabahSql}, ''),
                        ''
                    ) AS nasabah_id_inline

                FROM candidate_um AS um

                /*
                 * LEFT JOIN, bukan INNER JOIN.
                 *
                 * Dengan INNER JOIN, surat pesanan yang belum punya baris
                 * pembayaran sama sekali di sr_bayar_uang_muka ikut hilang dari
                 * laporan, padahal unitnya tetap harus tampil dengan kolom
                 * Jumlah Bayar / Tanggal Bayar kosong. Inilah penyebab jumlah
                 * unit di web jauh lebih sedikit daripada desktop.
                 */
                LEFT JOIN {$schema}.sr_bayar_uang_muka AS bum
                    ON {$this->idJoin('sr_bayar_uang_muka', 'bum', 'uang_muka_id', 'sr_uang_muka', 'um', 'uang_muka_id')}

                INNER JOIN stok_enriched AS stok
                    ON {$this->idJoin('sr_stok', 'stok', 'stok_id', 'sr_uang_muka', 'um', 'stok_id')}

                LEFT JOIN {$schema}.sr_cara_bayar AS cara_bayar
                    ON UPPER(BTRIM(CAST(cara_bayar.kd_cara_bayar AS text))) = UPPER(BTRIM(CAST(bum.kd_cara_bayar AS text)))

                LEFT JOIN {$schema}.sr_tipe_bayar AS tipe_bayar
                    ON UPPER(BTRIM(CAST(tipe_bayar.tipe_bayar AS text))) = UPPER(BTRIM(CAST(um.tipe_bayar_angs AS text)))

                LEFT JOIN {$schema}.sr_sales AS sales_data
                    ON UPPER(BTRIM(CAST(sales_data.kd_sales AS text))) = UPPER(BTRIM(CAST(um.kd_sales AS text)))

                LEFT JOIN {$schema}.sr_agen AS agen_data
                    ON UPPER(BTRIM(CAST(agen_data.kd_agen AS text))) = UPPER(BTRIM(CAST(um.kd_agen AS text)))

                LEFT JOIN ppjb_selected AS ppjb
                    ON ppjb.um_key = um.uang_muka_id
            ),

            base_uang_muka AS (
                SELECT DISTINCT
                    uang_muka_id,
                    BTRIM(CAST(uang_muka_id AS text)) AS uang_muka_id_text,
                    NULLIF(
                        REGEXP_REPLACE(
                            COALESCE(CAST(uang_muka_id AS text), ''),
                            '[^0-9]',
                            '',
                            'g'
                        ),
                        ''
                    ) AS uang_muka_id_digits,
                    CASE
                        WHEN BTRIM(CAST(uang_muka_id AS text)) ~ '^[0-9]+$'
                        THEN CAST(BTRIM(CAST(uang_muka_id AS text)) AS numeric)
                    END AS uang_muka_id_numeric
                FROM base
            ),

            base_ppjb AS (
                SELECT DISTINCT
                    ppjb_id,
                    BTRIM(CAST(ppjb_id AS text)) AS ppjb_id_text,
                    NULLIF(
                        REGEXP_REPLACE(
                            COALESCE(CAST(ppjb_id AS text), ''),
                            '[^0-9]',
                            '',
                            'g'
                        ),
                        ''
                    ) AS ppjb_id_digits,
                    CASE
                        WHEN REGEXP_REPLACE(COALESCE(CAST(ppjb_id AS text), ''), '[^0-9]', '', 'g') ~ '^[0-9]+$'
                        THEN CAST(REGEXP_REPLACE(COALESCE(CAST(ppjb_id AS text), ''), '[^0-9]', '', 'g') AS numeric)
                    END AS ppjb_id_numeric
                FROM base
                WHERE ppjb_id IS NOT NULL
            ),

            biaya_by_um AS (
                SELECT
                    bu.uang_muka_id,
                    COALESCE(SUM(CASE WHEN BTRIM(CAST(bdp.kd_biaya AS text)) = 'PPN' AND biaya.balance = 1 THEN bdp.jumlah ELSE 0 END), 0) AS ppn,
                    COALESCE(SUM(CASE WHEN biaya.balance = -1 THEN bdp.jumlah ELSE 0 END), 0) AS discount
                FROM base_uang_muka AS bu
                INNER JOIN {$schema}.sr_biaya_dp AS bdp
                    ON {$this->idJoinPrepared('sr_biaya_dp', 'bdp', 'uang_muka_id', 'bu.uang_muka_id_numeric', 'bu.uang_muka_id_text')}
                INNER JOIN {$schema}.sr_biaya AS biaya
                    ON BTRIM(CAST(biaya.kd_biaya AS text)) = BTRIM(CAST(bdp.kd_biaya AS text))
                GROUP BY bu.uang_muka_id
            ),

            jadwal_by_ppjb AS (
                SELECT
                    bp.ppjb_id,
                    COALESCE(SUM(ja.jumlah), 0) AS extra_harga
                FROM {$schema}.sr_jadwal_angsuran AS ja
                INNER JOIN base_ppjb AS bp
                    ON {$this->idJoinPrepared('sr_jadwal_angsuran', 'ja', 'ppjb_id', 'bp.ppjb_id_numeric', 'bp.ppjb_id_text')}
                INNER JOIN {$schema}.sr_kode_transaksi AS kt
                    ON BTRIM(CAST(kt.kd_transaksi AS text))
                     = BTRIM(CAST(ja.kd_transaksi AS text))
                WHERE (
                        (
                            UPPER(BTRIM(COALESCE(CAST(kt.flag_hitung AS text), ''))) = 'Y'
                            AND UPPER(BTRIM(COALESCE(CAST(kt.flag_pajak AS text), ''))) = 'Y'
                        )
                        OR BTRIM(CAST(kt.kd_transaksi AS text)) = 'DCB'
                      )
                  AND BTRIM(CAST(ja.kd_transaksi AS text)) NOT IN ('ANG', 'UMK')
                  AND COALESCE(NULLIF(UPPER(BTRIM(to_jsonb(ja) ->> 'flag_aktif')), ''), 'A') = 'A'
                GROUP BY bp.ppjb_id
            ),

            /*
             * TOTAL_BAYAR tidak dihitung di SQL utama.
             * Perhitungan angsuran dipindahkan ke hydrateTotalBayarColumns()
             * supaya query utama tidak timeout akibat scan sr_angsuran.
             */

            npv_latest AS (
                SELECT DISTINCT ON (bp.ppjb_id)
                    bp.ppjb_id,
                    n.total_npv
                FROM base_ppjb AS bp
                INNER JOIN {$schema}.sr_npv AS n
                    ON {$this->idJoinPrepared('sr_npv', 'n', 'ppjb_id', 'bp.ppjb_id_numeric', 'bp.ppjb_id_text')}
                WHERE {$this->idJoinPreparedGuard('sr_npv', 'ppjb_id', 'bp.ppjb_id_numeric', 'bp.ppjb_id_text')}
                  AND UPPER(BTRIM(COALESCE(CAST(n.jenis_trn AS text), ''))) = 'P'
                ORDER BY
                    bp.ppjb_id,
                    n.npv_id DESC
            )


            SELECT
                b.tgl_uang_muka AS "TGL_UANG_MUKA",
                b.no_uang_muka AS "NO_UANG_MUKA",
                CASE WHEN ? = 'B' THEN '*' || b.blok_nomor ELSE b.blok_nomor END AS "BLOK_NOMOR",
                b.nama_lokasi AS "NAMA_LOKASI",
                b.nama_sektor AS "NAMA_SEKTOR",
                b.tipe_bgn AS "TIPE_BGN",
                b.model_deskripsi AS "MODEL",
                b.luas_tanah AS "LUAS_TANAH",
                b.luas_bangunan AS "LUAS_BANGUNAN",
                '-'::text AS "NASABAH_NAMA",
                b.uang_muka_id AS "UANG_MUKA_ID_INTERNAL",
                b.nasabah_id_inline AS "NASABAH_ID_INLINE_INTERNAL",
                b.nama_pembeli_inline AS "NAMA_PEMBELI_INLINE_INTERNAL",
                b.dpp AS "HRGJUAL_SBLM_PPN",
                COALESCE(b.biaya_bphtb, 0) AS "BIAYA_BPHTB",
                COALESCE(biaya.ppn, 0) AS "PPN",
                COALESCE(biaya.discount, 0) AS "DISCOUNT",
                b.harga_jual AS "HARGA_JUAL",
                CASE WHEN b.ppjb_id IS NULL THEN NULL ELSE COALESCE(b.harga_jual_ppjb_pokok, 0) + COALESCE(jadwal.extra_harga, 0) END AS "HARGA_JUAL_PPJB",
                b.tipe_pembayaran AS "TIPE_PEMBAYARAN",
                b.jumlah_bayar AS "JUMLAH_BAYAR",
                b.tgl_bayar AS "TGL_BAYAR",
                b.no_bukti_tahap_1 AS "NO_BUKTI_TAHAP_1",
                b.tgl_bayar AS "TGL_BUKTI_TAHAP_1",
                b.cara_bayar_nama AS "NAMA",
                b.kd_sales_display AS "KD_SALES",
                b.nama_sales AS "NAMA_SALES",
                b.nama_agen AS "NAMA_AGEN",
                b.kd_perusahaan AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",
                b.flag_laporan AS "FLAG_LAPORAN",
                b.jenis_bgn AS "JENIS_BGN",
                EXTRACT(DAY FROM b.tgl_uang_muka)::integer AS "TGL",
                EXTRACT(MONTH FROM b.tgl_uang_muka)::integer AS "BLN",
                EXTRACT(YEAR FROM b.tgl_uang_muka)::integer AS "THN",
                CASE WHEN ? = 'B' THEN 'B' WHEN b.ppjb_id IS NULL THEN '2' ELSE '1' END AS "STATUS_PPJB",
                b.no_ppjb AS "NO_PPJB",
                b.tgl_ppjb AS "TGL_PPJB",
                b.tgl_tanda_tangan AS "TGL_TANDA_TANGAN",
                COALESCE(b.tgl_ttd_notaris, b.tgl_tanda_tangan) AS "TGL_TTD_NOTARIS",
                b.ppjb_id AS "PPJB_ID_INTERNAL",
                0::numeric AS "TOTAL_BAYAR",
                0::numeric AS "PROSENTASE_BAYAR",
                b.stok_id AS "STOK_ID",
                b.spesifikasi AS "SPESIFIKASI",
                b.user_entry AS "USER_ENTRY",
                b.tgl_entry AS "TGL_ENTRY",
                COALESCE(b.flag_bgb, 'T') AS "FLAG_BGB",
                b.nilai_bgb AS "NILAI_BGB",
                COALESCE(npv.total_npv, b.harga_dasar1, b.harga_dasar2, b.harga_dasar3, b.harga_dasar4, b.harga_dasar5, b.harga_dasar6, b.harga_dasar7, b.harga_dasar8, b.harga_dasar9, b.harga_dasar10, 0) AS "HARGA_DASAR"
            FROM base AS b
            LEFT JOIN biaya_by_um AS biaya
                ON biaya.uang_muka_id = b.uang_muka_id
            LEFT JOIN jadwal_by_ppjb AS jadwal
                ON jadwal.ppjb_id = b.ppjb_id
            LEFT JOIN npv_latest AS npv
                ON npv.ppjb_id = b.ppjb_id
            /*
             * Urutan mengikuti hasil desktop.
             *
             * Query desktop tidak memiliki ORDER BY sama sekali; ia berupa
             * UNION (bukan UNION ALL) dari tiga SELECT, jadi tidak ada
             * ORDER BY di sisi query. Urutan yang terlihat pada laporan
             * dibentuk oleh report desktop sendiri: dikelompokkan menurut
             * TGL_UANG_MUKA lalu diurutkan menurut BLOK_NOMOR di dalam tiap
             * tanggal.
             *
             * Contoh dari laporan desktop untuk tanggal 04-06-2026:
             * AP6/B15, AP6/B16, AP6/B17, ... AP6/B28, AP6/C07, AP6/C15, ...
             * AP6/C22, lalu BOM/020. Nomor surat pesanan pada baris tersebut
             * melompat-lompat (0108, 0107, 0111, 0118, ...), jadi NO_UANG_MUKA
             * jelas bukan kunci urutan kedua.
             *
             * Karena pengelompokan memakai TGL_UANG_MUKA, urutan tetap sama
             * baik filter memakai Tgl. Entry Surat Pesanan maupun Tgl. Surat
             * Pesanan; pilihan tanggal hanya menentukan baris mana yang ikut.
             *
             * NULLS FIRST dipakai karena PostgreSQL menaruh NULL di akhir pada
             * urutan menaik, sedangkan SQL Server menaruhnya di awal.
             */
            ORDER BY
                b.tgl_uang_muka NULLS FIRST,
                b.blok_nomor NULLS FIRST,
                b.no_uang_muka NULLS FIRST,
                b.tgl_bayar NULLS FIRST,
                b.uang_muka_id
        SQL;

        $bindings = array_merge(
            $candidateBindings,
            [
                $status,
                $status,
            ]
        );

        $mainQueryStartedAt = microtime(true);
        $rows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $perfMainQueryMs = (microtime(true) - $mainQueryStartedAt) * 1000;

        $hydrateStartedAt = microtime(true);

        /*
         * Nama pembeli diambil pada pipeline SELECT terpisah agar query utama
         * tidak membawa CTE pembeli/nasabah besar. Prioritas tetap:
         * DP -> PPJB -> INLINE.
         */
        $this->hydrateNasabahNames($rows);

        $this->hydrateTotalBayarColumns($rows, $tglBayar);
        $perfHydrateMs = (microtime(true) - $hydrateStartedAt) * 1000;

        if (config('app.debug')) {
            /*
             * candidate_count menjawab pertanyaan "baris hilang di tahap
             * kandidat atau sesudahnya". Seluruh join sesudah candidate_um
             * bersifat LEFT JOIN, jadi bila candidate_count sudah sama dengan
             * row_count berarti penyaringan terjadi di tahap kandidat, dan
             * daftar filter di bawah menunjukkan nilai mana yang menyebabkan.
             *
             * Hitungan ini hanya berjalan ketika APP_DEBUG aktif.
             */
            $candidateCount = null;

            try {
                $candidateRow = DB::connection(self::CONNECTION)->selectOne(
                    'SELECT COUNT(*) AS jumlah FROM (' . $candidateSourceSql . ') AS kandidat',
                    $candidateBindings
                );

                $candidateCount = $candidateRow->jumlah ?? null;
            } catch (\Throwable $e) {
                $candidateCount = 'gagal dihitung: ' . $e->getMessage();
            }

            Log::debug('Daftar Surat Pesanan performance', [
                'candidate_ms' => round($perfCandidateMs, 2),
                'main_query_ms' => round($perfMainQueryMs, 2),
                'hydrate_ms' => round($perfHydrateMs, 2),
                'total_ms' => round((microtime(true) - $perfStartedAt) * 1000, 2),
                'candidate_count' => $candidateCount,
                'row_count' => count($rows),
                /*
                 * Nama database ikut dicatat agar terlihat bila aplikasi
                 * ternyata menunjuk database yang berbeda dari yang dipakai
                 * saat memeriksa data secara manual.
                 */
                'koneksi' => self::CONNECTION,
                'database' => DB::connection(self::CONNECTION)->getDatabaseName(),
                'flag_tgl' => $flagTgl,
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'perusahaan' => $perusahaan,
                'sektor' => $sektor,
                'sektor_values' => $sektorValues,
                'lokasi' => $lokasi,
                'lokasi_values' => $lokasiValues,
                'jenis' => $jenis,
                'status' => $status,
                'tipe_bayar' => $tipeBayar,
                'agen' => $agen,
                'sales' => $sales,
                'bgb' => $bgb,
            ]);
        }

        return $rows;
    }


    /**
     * Hydrate NASABAH_NAMA secara terpisah.
     *
     * Semua query di sini bersifat SELECT.
     * Hasil tetap memakai prioritas yang sama dengan query lama:
     * sr_pembeli_dp -> sr_pembeli_ppjb -> data inline.
     */
    private function hydrateNasabahNames(array &$rows): void
    {
        if (count($rows) < 1) {
            return;
        }

        $uangMukaIds = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row->UANG_MUKA_ID_INTERNAL ?? ''));
            if ($id !== '') {
                $uangMukaIds[$id] = true;
            }
        }

        $namaByUangMuka = [];
        $dpRows = [];

        if (count($uangMukaIds)) {
            $umArray = $this->pgArrayForColumn('sr_pembeli_dp', 'uang_muka_id', array_keys($uangMukaIds));

            /*
             * Exact match terlebih dahulu. Pada data normal jalur ini cukup
             * dan PostgreSQL tidak perlu menjalankan REGEXP_REPLACE ke seluruh tabel.
             */
            $sql = <<<SQL
                SELECT
                    BTRIM(CAST(pd.uang_muka_id AS text)) AS uang_muka_id,
                    NULLIF(UPPER(BTRIM(CAST(pd.nasabah_id AS text))), '') AS nasabah_id,
                    COALESCE(
                        NULLIF(BTRIM(to_jsonb(pd) ->> 'nama'), ''),
                        NULLIF(BTRIM(to_jsonb(pd) ->> 'nama_nasabah'), ''),
                        NULLIF(BTRIM(to_jsonb(pd) ->> 'nama_pembeli'), ''),
                        ''
                    ) AS nama_inline
                FROM public.sr_pembeli_dp AS pd
                WHERE {$this->idAnyArray('sr_pembeli_dp', 'pd', 'uang_muka_id', '?')}
                  AND COALESCE(
                        NULLIF(UPPER(BTRIM(to_jsonb(pd) ->> 'flag_nama_dp')), ''),
                        'Y'
                      ) = 'Y'
            SQL;

            $dpRows = DB::connection(self::CONNECTION)->select($sql, [$umArray]);

            $dpNasabahIds = [];

            foreach ($dpRows as $dp) {
                $key = trim((string) ($dp->uang_muka_id ?? ''));
                if ($key === '') {
                    continue;
                }

                $inline = trim((string) ($dp->nama_inline ?? ''));
                if ($inline !== '') {
                    $namaByUangMuka[$key][strtoupper($inline)] = true;
                }

                $nasabahId = strtoupper(trim((string) ($dp->nasabah_id ?? '')));
                if ($nasabahId !== '') {
                    $dpNasabahIds[$nasabahId] = true;
                }
            }

            $nasabahMap = count($dpNasabahIds)
                ? $this->fetchNasabahNames(array_keys($dpNasabahIds))
                : [];

            foreach ($dpRows as $dp) {
                $key = trim((string) ($dp->uang_muka_id ?? ''));
                $nasabahId = strtoupper(trim((string) ($dp->nasabah_id ?? '')));

                if ($key !== '' && $nasabahId !== '' && !empty($nasabahMap[$nasabahId])) {
                    $namaByUangMuka[$key][$nasabahMap[$nasabahId]] = true;
                }
            }

            /*
             * Digit fallback HANYA dijalankan untuk uang muka yang belum
             * mendapatkan nama dari exact match.
             */
            $unmatched = [];
            foreach (array_keys($uangMukaIds) as $id) {
                if (empty($namaByUangMuka[$id])) {
                    $digits = preg_replace('/[^0-9]/', '', $id);
                    if ($digits !== '') {
                        $unmatched[$digits][] = $id;
                    }
                }
            }

            if (count($unmatched)) {
                $digitArray = $this->pgTextArray(array_keys($unmatched));

                $fallbackSql = <<<SQL
                    SELECT
                        NULLIF(
                            REGEXP_REPLACE(
                                COALESCE(CAST(pd.uang_muka_id AS text), ''),
                                '[^0-9]',
                                '',
                                'g'
                            ),
                            ''
                        ) AS uang_muka_digits,
                        NULLIF(UPPER(BTRIM(CAST(pd.nasabah_id AS text))), '') AS nasabah_id,
                        COALESCE(
                            NULLIF(BTRIM(to_jsonb(pd) ->> 'nama'), ''),
                            NULLIF(BTRIM(to_jsonb(pd) ->> 'nama_nasabah'), ''),
                            NULLIF(BTRIM(to_jsonb(pd) ->> 'nama_pembeli'), ''),
                            ''
                        ) AS nama_inline
                    FROM public.sr_pembeli_dp AS pd
                    WHERE NULLIF(
                            REGEXP_REPLACE(
                                COALESCE(CAST(pd.uang_muka_id AS text), ''),
                                '[^0-9]',
                                '',
                                'g'
                            ),
                            ''
                          ) = ANY(?::text[])
                      AND COALESCE(
                            NULLIF(UPPER(BTRIM(to_jsonb(pd) ->> 'flag_nama_dp')), ''),
                            'Y'
                          ) = 'Y'
                SQL;

                $fallbackRows = DB::connection(self::CONNECTION)
                    ->select($fallbackSql, [$digitArray]);

                $fallbackNasabahIds = [];

                foreach ($fallbackRows as $fallback) {
                    $digits = trim((string) ($fallback->uang_muka_digits ?? ''));
                    if ($digits === '' || empty($unmatched[$digits])) {
                        continue;
                    }

                    $inline = trim((string) ($fallback->nama_inline ?? ''));
                    $nasabahId = strtoupper(trim((string) ($fallback->nasabah_id ?? '')));

                    if ($nasabahId !== '') {
                        $fallbackNasabahIds[$nasabahId] = true;
                    }

                    foreach ($unmatched[$digits] as $originalId) {
                        if ($inline !== '') {
                            $namaByUangMuka[$originalId][strtoupper($inline)] = true;
                        }

                        if ($nasabahId !== '') {
                            $namaByUangMuka[$originalId]['__NASABAH__' . $nasabahId] = true;
                        }
                    }
                }

                $fallbackNasabahMap = count($fallbackNasabahIds)
                    ? $this->fetchNasabahNames(array_keys($fallbackNasabahIds))
                    : [];

                foreach ($namaByUangMuka as $key => $names) {
                    foreach (array_keys($names) as $nameKey) {
                        if (strpos($nameKey, '__NASABAH__') !== 0) {
                            continue;
                        }

                        unset($namaByUangMuka[$key][$nameKey]);
                        $nasabahId = substr($nameKey, 11);

                        if (!empty($fallbackNasabahMap[$nasabahId])) {
                            $namaByUangMuka[$key][$fallbackNasabahMap[$nasabahId]] = true;
                        }
                    }
                }
            }
        }

        /*
         * PPJB fallback hanya untuk row tanpa nama DP.
         */
        $ppjbNeeded = [];

        foreach ($rows as $row) {
            $umId = trim((string) ($row->UANG_MUKA_ID_INTERNAL ?? ''));
            $ppjbId = trim((string) ($row->PPJB_ID_INTERNAL ?? ''));

            if ($ppjbId !== '' && empty($namaByUangMuka[$umId])) {
                $ppjbNeeded[$ppjbId] = true;
            }
        }

        $namaByPpjb = [];

        if (count($ppjbNeeded)) {
            $ppjbArray = $this->pgArrayForColumn('sr_pembeli_ppjb', 'ppjb_id', array_keys($ppjbNeeded));

            $sql = <<<SQL
                SELECT
                    BTRIM(CAST(pp.ppjb_id AS text)) AS ppjb_id,
                    NULLIF(UPPER(BTRIM(CAST(pp.nasabah_id AS text))), '') AS nasabah_id,
                    COALESCE(
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama'), ''),
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama_nasabah'), ''),
                        NULLIF(BTRIM(to_jsonb(pp) ->> 'nama_pembeli'), ''),
                        ''
                    ) AS nama_inline
                FROM public.sr_pembeli_ppjb AS pp
                WHERE {$this->idAnyArray('sr_pembeli_ppjb', 'pp', 'ppjb_id', '?')}
                  AND COALESCE(
                        NULLIF(UPPER(BTRIM(CAST(pp.flag_aktif AS text))), ''),
                        'Y'
                      ) = 'Y'
            SQL;

            $ppRows = DB::connection(self::CONNECTION)->select($sql, [$ppjbArray]);
            $nasabahIds = [];

            foreach ($ppRows as $pp) {
                $key = trim((string) ($pp->ppjb_id ?? ''));
                if ($key === '') {
                    continue;
                }

                $inline = trim((string) ($pp->nama_inline ?? ''));
                if ($inline !== '') {
                    $namaByPpjb[$key][strtoupper($inline)] = true;
                }

                $nasabahId = strtoupper(trim((string) ($pp->nasabah_id ?? '')));
                if ($nasabahId !== '') {
                    $nasabahIds[$nasabahId] = true;
                }
            }

            $nasabahMap = count($nasabahIds)
                ? $this->fetchNasabahNames(array_keys($nasabahIds))
                : [];

            foreach ($ppRows as $pp) {
                $key = trim((string) ($pp->ppjb_id ?? ''));
                $nasabahId = strtoupper(trim((string) ($pp->nasabah_id ?? '')));

                if ($key !== '' && $nasabahId !== '' && !empty($nasabahMap[$nasabahId])) {
                    $namaByPpjb[$key][$nasabahMap[$nasabahId]] = true;
                }
            }
        }

        /*
         * INLINE fallback hanya untuk row yang masih tidak mempunyai nama.
         */
        $inlineIds = [];

        foreach ($rows as $row) {
            $umId = trim((string) ($row->UANG_MUKA_ID_INTERNAL ?? ''));
            $ppjbId = trim((string) ($row->PPJB_ID_INTERNAL ?? ''));

            if (!empty($namaByUangMuka[$umId]) || !empty($namaByPpjb[$ppjbId])) {
                continue;
            }

            $id = strtoupper(trim((string) ($row->NASABAH_ID_INLINE_INTERNAL ?? '')));
            if ($id !== '') {
                $inlineIds[$id] = true;
            }
        }

        $inlineNasabahMap = count($inlineIds)
            ? $this->fetchNasabahNames(array_keys($inlineIds))
            : [];

        /*
         * Bentuk output sama seperti STRING_AGG(DISTINCT UPPER(...) ORDER BY ...).
         *
         * Baris tanpa nama pembeli TIDAK dibuang.
         *
         * Query desktop memanggil dbo.F_GET_PEMBELI_DP(UANG_MUKA_ID) hanya pada
         * daftar SELECT dan tidak pernah menyaring berdasarkan hasilnya, jadi
         * surat pesanan yang datanya belum lengkap tetap muncul dengan kolom
         * Nama Pembeli berisi '-'. Pembuangan baris di sini membuat laporan web
         * kehilangan sebagian besar unit dibanding desktop, misalnya tanggal
         * 04-06-2026 hanya menampilkan 5 dari 21 surat pesanan.
         */
        foreach ($rows as $row) {
            $umId = trim((string) ($row->UANG_MUKA_ID_INTERNAL ?? ''));
            $ppjbId = trim((string) ($row->PPJB_ID_INTERNAL ?? ''));
            $names = [];

            if (!empty($namaByUangMuka[$umId])) {
                $names = array_keys($namaByUangMuka[$umId]);
            } elseif (!empty($namaByPpjb[$ppjbId])) {
                $names = array_keys($namaByPpjb[$ppjbId]);
            } else {
                $inlineId = strtoupper(trim((string) ($row->NASABAH_ID_INLINE_INTERNAL ?? '')));
                $inlineName = strtoupper(trim((string) ($row->NAMA_PEMBELI_INLINE_INTERNAL ?? '')));

                if ($inlineId !== '' && !empty($inlineNasabahMap[$inlineId])) {
                    $names[] = $inlineNasabahMap[$inlineId];
                }

                if ($inlineName !== '') {
                    $names[] = $inlineName;
                }
            }

            $unique = [];
            foreach ($names as $name) {
                $name = strtoupper(trim((string) $name));
                if ($name !== '' && $name !== '-') {
                    $unique[$name] = true;
                }
            }

            $finalNames = array_keys($unique);
            sort($finalNames, SORT_STRING);

            $row->NASABAH_NAMA = count($finalNames)
                ? implode(', ', $finalNames)
                : '-';

            unset($row->UANG_MUKA_ID_INTERNAL);
            unset($row->NASABAH_ID_INLINE_INTERNAL);
            unset($row->NAMA_PEMBELI_INLINE_INTERNAL);
        }
    }

    /**
     * Ambil nama nasabah hanya untuk NASABAH_ID yang dibutuhkan.
     */
    private function fetchNasabahNames(array $nasabahIds): array
    {
        $nasabahIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => strtoupper(trim((string) $id)),
            $nasabahIds
        ))));

        if (!count($nasabahIds)) {
            return [];
        }

        $result = [];
        $exactArray = $this->pgArrayForColumn('sr_nasabah', 'nasabah_id', $nasabahIds);

        /*
         * NASABAH_ID pada database ini varchar. Exact equality dikerjakan lebih dulu
         * tanpa fungsi di sisi kolom sehingga PostgreSQL dapat memakai index yang
         * sudah mungkin tersedia di database, tanpa kita membuat index baru.
         */
        $exactSql = <<<SQL
            SELECT
                UPPER(BTRIM(CAST(nasabah.nasabah_id AS text))) AS nasabah_id,
                UPPER(
                    COALESCE(
                        NULLIF(BTRIM(CAST(nasabah.nama AS text)), ''),
                        NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_nasabah'), ''),
                        NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_pembeli'), ''),
                        ''
                    )
                ) AS nama
            FROM public.sr_nasabah AS nasabah
            WHERE {$this->idAnyArray('sr_nasabah', 'nasabah', 'nasabah_id', '?')}
        SQL;

        $rows = DB::connection(self::CONNECTION)->select($exactSql, [$exactArray]);

        foreach ($rows as $row) {
            $id = strtoupper(trim((string) ($row->nasabah_id ?? '')));
            $name = strtoupper(trim((string) ($row->nama ?? '')));

            if ($id !== '' && $name !== '') {
                $result[$id] = $name;
            }
        }

        /*
         * Jika ada data lama dengan spasi/case berbeda, jalur normalized lama tetap
         * dijalankan hanya untuk ID yang belum ketemu. Tidak ada data yang dihapus.
         */
        $unmatched = array_values(array_filter(
            $nasabahIds,
            static fn ($id) => empty($result[$id])
        ));

        if (count($unmatched)) {
            $fallbackArray = $this->pgTextArray($unmatched);

            $fallbackSql = <<<SQL
                SELECT
                    NULLIF(
                        UPPER(BTRIM(CAST(nasabah.nasabah_id AS text))),
                        ''
                    ) AS nasabah_id,
                    UPPER(
                        COALESCE(
                            NULLIF(BTRIM(CAST(nasabah.nama AS text)), ''),
                            NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_nasabah'), ''),
                            NULLIF(BTRIM(to_jsonb(nasabah) ->> 'nama_pembeli'), ''),
                            ''
                        )
                    ) AS nama
                FROM public.sr_nasabah AS nasabah
                WHERE NULLIF(
                        UPPER(BTRIM(CAST(nasabah.nasabah_id AS text))),
                        ''
                      ) = ANY(?::text[])
            SQL;

            $fallbackRows = DB::connection(self::CONNECTION)
                ->select($fallbackSql, [$fallbackArray]);

            foreach ($fallbackRows as $row) {
                $id = strtoupper(trim((string) ($row->nasabah_id ?? '')));
                $name = strtoupper(trim((string) ($row->nama ?? '')));

                if ($id !== '' && $name !== '') {
                    $result[$id] = $name;
                }
            }
        }

        return $result;
    }

    private function hydrateTotalBayarColumns(array &$rows, string $tglBayar): void
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

            $exactKey = $ppjbId . '|EXACT|' . $ppjbId;

            if (!isset($seen[$exactKey])) {
                $lookupPairs[] = [$ppjbId, $ppjbId];
                $seen[$exactKey] = true;
            }

            $digits = preg_replace('/[^0-9]/', '', $ppjbId);

            if ($digits !== '' && $digits !== $ppjbId) {
                $digitKey = $ppjbId . '|DIGIT|' . $digits;

                if (!isset($seen[$digitKey])) {
                    $lookupPairs[] = [$ppjbId, $digits];
                    $seen[$digitKey] = true;
                }
            }
        }

        if (count($lookupPairs) < 1) {
            foreach ($rows as $row) {
                unset($row->PPJB_ID_INTERNAL);
            }

            return;
        }

        $lookupIdKeys = [];
        $lookupValues = [];

        /*
         * Ketika sr_angsuran.ppjb_id bertipe angka, pasangan yang nilainya
         * bukan angka dibuang bersama kunci pasangannya agar kedua array tetap
         * sejajar dan cast ke numeric tidak gagal.
         */
        $angsuranNumerik = $this->isNumericColumn('sr_angsuran', 'ppjb_id');
        $lookupTipe = $angsuranNumerik ? 'numeric' : 'text';

        foreach ($lookupPairs as $pair) {
            if ($angsuranNumerik && preg_match('/^[0-9]+$/', trim((string) $pair[1])) !== 1) {
                continue;
            }

            $lookupIdKeys[] = $pair[0];
            $lookupValues[] = $pair[1];
        }

        if (count($lookupIdKeys) < 1) {
            foreach ($rows as $row) {
                unset($row->PPJB_ID_INTERNAL);
            }

            return;
        }

        $bindings = [
            $this->pgTextArray($lookupIdKeys),
            $angsuranNumerik
                ? '{' . implode(',', array_map(static fn ($v) => trim((string) $v), $lookupValues)) . '}'
                : $this->pgTextArray($lookupValues),
            $tglBayar,
            $tglBayar,
        ];

        $schema = self::SCHEMA;

        /*
         * Semua ekspresi ini hanya dibangun dari metadata information_schema.
         * Tidak ada perubahan data. Fallback nilainya tetap sama.
         */
        $nominalBayarSql = $this->angsuranNumericExpr('angsuran');
        $tanggalBayarSql = $this->angsuranDateExpr(
            'angsuran',
            ['tgl_kuitansi', 'tgl_cair', 'tgl_bayar', 'tgl_entry']
        );
        $tanggalBatalSql = $this->angsuranDateExpr(
            'angsuran',
            ['tgl_batal']
        );

        $flagCairSql = $this->hasColumn('sr_angsuran', 'flag_cair')
            ? "NULLIF(UPPER(BTRIM(CAST(angsuran.flag_cair AS text))), '')"
            : 'NULL';

        $flagAktifSql = $this->hasColumn('sr_angsuran', 'flag_aktif')
            ? "NULLIF(UPPER(BTRIM(CAST(angsuran.flag_aktif AS text))), '')"
            : 'NULL';

        $tglCairAdaSql = $this->hasColumn('sr_angsuran', 'tgl_cair')
            ? "NULLIF(BTRIM(COALESCE(CAST(angsuran.tgl_cair AS text), '')), '') IS NOT NULL"
            : 'FALSE';

        /*
         * Query ini sengaja dipisah dari query utama.
         * Join ke sr_angsuran memakai kolom asli angsuran.ppjb_id tanpa BTRIM/CAST
         * di sisi tabel, supaya index sr_angsuran_ppjb_id_aktif_idx bisa tetap dipakai.
         */
        $sql = <<<SQL
            WITH lookup(id_key, ppjb_lookup) AS (
                SELECT *
                FROM unnest(?::text[], ?::{$lookupTipe}[])
            ),

            sumber AS (
                SELECT
                    lookup.id_key,
                    angsuran.angsuran_id,
                    angsuran.kd_transaksi,

                    {$nominalBayarSql} AS nominal_bayar,

                    {$tanggalBayarSql} AS tanggal_bayar,

                    COALESCE(
                        {$flagCairSql},
                        CASE
                            WHEN {$tglCairAdaSql}
                            THEN 'Y'
                        END,
                        'Y'
                    ) AS flag_cair_norm,

                    COALESCE(
                        {$flagAktifSql},
                        'A'
                    ) AS flag_aktif_norm,

                    {$tanggalBatalSql} AS tgl_batal_norm

                FROM lookup
                INNER JOIN {$schema}.sr_angsuran AS angsuran
                    ON angsuran.ppjb_id = lookup.ppjb_lookup
            ),

            hasil AS (
                SELECT
                    sumber.id_key,

                    COUNT(*) FILTER (
                        WHERE UPPER(BTRIM(COALESCE(CAST(kode.flag_hitung AS text), ''))) = 'Y'
                          AND COALESCE(sumber.tanggal_bayar, DATE '1900-01-01') < (?::date + INTERVAL '1 day')
                    ) AS jumlah_flag_hitung_per_tgl,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(BTRIM(COALESCE(CAST(kode.flag_hitung AS text), ''))) = 'Y'
                                 AND COALESCE(sumber.tanggal_bayar, DATE '1900-01-01') < (?::date + INTERVAL '1 day')
                                THEN sumber.nominal_bayar
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_bayar_hitung_per_tgl,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN COALESCE(sumber.tanggal_bayar, DATE '1900-01-01') < (?::date + INTERVAL '1 day')
                                THEN sumber.nominal_bayar
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_bayar_fallback_per_tgl

                FROM sumber
                LEFT JOIN {$schema}.sr_kode_transaksi AS kode
                    ON {$this->idJoin('sr_kode_transaksi', 'kode', 'kd_transaksi', 'sr_angsuran', 'sumber', 'kd_transaksi')}
                WHERE sumber.flag_cair_norm = 'Y'
                  AND (
                        sumber.flag_aktif_norm = 'A'
                        OR sumber.tgl_batal_norm > ?::date
                  )
                GROUP BY sumber.id_key
            )

            SELECT
                id_key,
                CASE
                    WHEN jumlah_flag_hitung_per_tgl > 0
                    THEN total_bayar_hitung_per_tgl
                    ELSE total_bayar_fallback_per_tgl
                END AS total_bayar
            FROM hasil
        SQL;

        // tglBayar dipakai 4 kali pada SQL hydrate.
        $bindings[] = $tglBayar;
        $bindings[] = $tglBayar;

        $bayarRows = DB::connection(self::CONNECTION)->select($sql, $bindings);
        $bayarByPpjb = [];

        foreach ($bayarRows as $bayar) {
            $key = strtoupper(trim((string) ($bayar->id_key ?? '')));
            $bayarByPpjb[$key] = (float) ($bayar->total_bayar ?? 0);
        }

        foreach ($rows as $row) {
            $ppjbId = strtoupper(trim((string) ($row->PPJB_ID_INTERNAL ?? '')));
            $totalBayar = $bayarByPpjb[$ppjbId] ?? 0;
            $hargaJualPpjb = $this->numericValue($row->HARGA_JUAL_PPJB ?? 0);

            $row->TOTAL_BAYAR = $totalBayar;

            /*
             * Desktop menampilkan persen bayar maksimal 100.
             * Di PostgreSQL total bayar bisa sedikit lebih besar dari harga jual PPJB
             * karena pembulatan/selisih komponen pembayaran, jadi persen dikunci 0..100.
             */
            $persenBayar = $hargaJualPpjb > 0
                ? ($totalBayar / $hargaJualPpjb) * 100
                : 0;

            $row->PROSENTASE_BAYAR = min(100, max(0, $persenBayar));

            unset($row->PPJB_ID_INTERNAL);
        }
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
}