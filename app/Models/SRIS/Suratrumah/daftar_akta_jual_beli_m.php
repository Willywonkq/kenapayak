<?php

// MODEL POSTGRESQL V1 - DAFTAR AKTA JUAL BELI
//
// Hasil migrasi dari model SQL Server (SQLSERVER-WEBSA-SRIS-PUSAT-V1-20260902).
// Query desktop tetap menjadi acuan. Perbedaan yang diperlukan hanya karena
// dialek dan karena hasil salinan data PostgreSQL belum selengkap SQL Server.
//
// Catatan penerjemahan dialek:
//
//   ISNULL(a, '')                 -> COALESCE(a, '')
//   a + '/' + b                   -> a || '/' || b
//   GETDATE()                     -> CURRENT_TIMESTAMP
//   SELECT TOP (1) ...            -> SELECT ... LIMIT 1
//   OUTER APPLY ( ... )           -> LEFT JOIN LATERAL ( ... ) ON TRUE
//   CONVERT(DATETIME, :t, 112)    -> CAST(:t AS DATE)
//   NOT LIKE '%[^0-9]%'           -> ~ '^[0-9]+$'
//   RIGHT(REPLICATE('0',50)+x,50) -> LPAD(x, 50, '0')
//   RTRIM(LTRIM(x))               -> BTRIM(x)
//
// SQL Server mengabaikan spasi di belakang ketika membandingkan varchar,
// PostgreSQL tidak. Karena itu setiap perbandingan teks di sini dibungkus
// RTRIM atau BTRIM lebih dulu, sama seperti model laporan lain yang sudah
// selesai dimigrasi.

namespace App\Models\SRIS\Suratrumah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use RuntimeException;

class daftar_akta_jual_beli_m extends Model
{
    use HasFactory;

    private const CONNECTION = 'pgsql';
    private const SCHEMA = 'public';

    /**
     * Nama tabel hasil migrasi memakai awalan sr_. Beberapa tabel laporan ini
     * belum pernah dipakai fitur lain, sehingga namanya dicari lebih dulu di
     * katalog PostgreSQL. Pencarian ini hanya SELECT terhadap
     * information_schema dan tidak mengubah apa pun.
     */
    private const TABLE_CANDIDATES = [
        'akta' => ['sr_akta', 'sr_akta_jual_beli', 'akta'],
        'sertipikat' => ['sr_sertipikat', 'sertipikat'],
        'pengambilan' => [
            'sr_pengambilan',
            'sr_pengambilan_sertipikat',
            'pengambilan',
        ],
        'stok' => ['sr_stok'],
        'ppjb' => ['sr_ppjb'],
        'pembeli_ppjb' => ['sr_pembeli_ppjb'],
        'nasabah' => ['sr_nasabah'],
        'lokasi' => ['sr_lokasi'],
        'sektor' => ['sr_sektor'],
        'angsuran' => ['sr_angsuran'],
    ];

    /* ------------------------------------------------------------------
     * Pembacaan katalog
     * ------------------------------------------------------------------ */

    /**
     * Memetakan nama logis tabel ke nama fisik yang benar-benar ada.
     * Tabel yang tidak ditemukan bernilai null, dan pemanggilnya yang
     * menentukan apakah tabel itu wajib atau boleh tidak ada.
     */
    private function resolvedTables(): array
    {
        static $resolved;

        if ($resolved !== null) {
            return $resolved;
        }

        $all = [];

        foreach (self::TABLE_CANDIDATES as $candidates) {
            foreach ($candidates as $candidate) {
                $all[$candidate] = true;
            }
        }

        $rows = DB::connection(self::CONNECTION)->select(
            <<<'SQL'
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = :schema
            SQL,
            ['schema' => self::SCHEMA]
        );

        $ada = [];

        foreach ($rows as $row) {
            $ada[strtolower($row->table_name)] = true;
        }

        $resolved = [];

        foreach (self::TABLE_CANDIDATES as $logis => $candidates) {
            $resolved[$logis] = null;

            foreach ($candidates as $candidate) {
                if (isset($ada[$candidate])) {
                    $resolved[$logis] = $candidate;
                    break;
                }
            }
        }

        return $resolved;
    }

    private function requiredTable(string $logis): string
    {
        $tabel = $this->resolvedTables()[$logis] ?? null;

        if ($tabel === null) {
            throw new RuntimeException(sprintf(
                'Tabel %s tidak ditemukan pada schema %s. Kandidat yang dicoba: %s.',
                $logis,
                self::SCHEMA,
                implode(', ', self::TABLE_CANDIDATES[$logis] ?? [])
            ));
        }

        return $tabel;
    }

    /**
     * Tipe kolom dibaca sekali supaya join ID bisa memakai "=" langsung
     * ketika kedua sisi bertipe sama, dan supaya kolom yang tidak ada pada
     * hasil migrasi tidak membuat query gagal.
     */
    private function columnMetadata(): array
    {
        static $metadata;

        if ($metadata !== null) {
            return $metadata;
        }

        $tables = array_values(array_filter($this->resolvedTables()));

        if ($tables === []) {
            return $metadata = [];
        }

        $placeholders = [];
        $bindings = ['schema' => self::SCHEMA];

        foreach ($tables as $index => $table) {
            $key = 'tabel_' . $index;
            $placeholders[] = ':' . $key;
            $bindings[$key] = $table;
        }

        $rows = DB::connection(self::CONNECTION)->select(
            'SELECT table_name, column_name, udt_name'
            . ' FROM information_schema.columns'
            . ' WHERE table_schema = :schema'
            . ' AND table_name IN (' . implode(', ', $placeholders) . ')',
            $bindings
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
            throw new RuntimeException('Nama kolom tidak valid: ' . $identifier);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function qualifiedTable(string $logis): string
    {
        return self::SCHEMA . '.' . $this->requiredTable($logis);
    }

    private function firstExistingColumn(
        string $logis,
        array $candidates
    ): ?string {
        $tabel = $this->resolvedTables()[$logis] ?? null;

        if ($tabel === null) {
            return null;
        }

        $metadata = $this->columnMetadata();

        foreach ($candidates as $candidate) {
            if (isset($metadata[$tabel][$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    private function requiredColumn(string $logis, array $candidates): string
    {
        $kolom = $this->firstExistingColumn($logis, $candidates);

        if ($kolom === null) {
            throw new RuntimeException(sprintf(
                'Kolom wajib tidak ditemukan pada tabel %s. Kandidat: %s.',
                $this->requiredTable($logis),
                implode(', ', $candidates)
            ));
        }

        return $kolom;
    }

    private function columnType(string $logis, string $column): ?string
    {
        $tabel = $this->resolvedTables()[$logis] ?? null;

        if ($tabel === null) {
            return null;
        }

        return $this->columnMetadata()[$tabel][$column] ?? null;
    }

    /* ------------------------------------------------------------------
     * Pembentuk ekspresi SQL
     * ------------------------------------------------------------------ */

    /**
     * Teks yang sudah dibuang spasi depan belakang, dengan nilai pengganti
     * bila kolomnya tidak ada pada hasil migrasi.
     */
    private function textExpression(
        string $logis,
        string $alias,
        array $candidates,
        string $default = ''
    ): string {
        $parts = [];

        foreach ($candidates as $candidate) {
            if ($this->firstExistingColumn($logis, [$candidate]) === null) {
                continue;
            }

            $parts[] = sprintf(
                "NULLIF(BTRIM(CAST(%s.%s AS TEXT)), '')",
                $alias,
                $this->quoteIdentifier($candidate)
            );
        }

        $parts[] = "'" . str_replace("'", "''", $default) . "'";

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    /**
     * Dipakai untuk penyaring. Sama dengan UPPER(RTRIM(LTRIM(ISNULL(x,''))))
     * pada query desktop.
     */
    private function normalizedExpression(
        string $logis,
        string $alias,
        array $candidates
    ): string {
        return 'UPPER(' . $this->textExpression($logis, $alias, $candidates) . ')';
    }

    /**
     * Kolom tanggal. Bila hasil migrasi sudah bertipe tanggal, kolomnya
     * dipakai apa adanya. Bila masih teks, pembacaannya dijaga pola tanggal
     * lebih dulu. Penjagaan itu menggantikan ISDATE() milik SQL Server.
     */
    private function dateExpression(
        string $logis,
        string $alias,
        array $candidates
    ): string {
        $parts = [];

        foreach ($candidates as $candidate) {
            if ($this->firstExistingColumn($logis, [$candidate]) === null) {
                continue;
            }

            $field = $alias . '.' . $this->quoteIdentifier($candidate);
            $type = $this->columnType($logis, $candidate);

            if (in_array($type, ['date', 'timestamp', 'timestamptz'], true)) {
                $parts[] = sprintf('CAST(%s AS TIMESTAMP)', $field);
                continue;
            }

            $parts[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '')"
                . " ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'"
                . ' THEN CAST(%s AS TIMESTAMP) END',
                $field,
                $field
            );
        }

        return $parts === []
            ? 'NULL::TIMESTAMP'
            : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function numericExpression(
        string $logis,
        string $alias,
        array $candidates
    ): string {
        $parts = [];

        foreach ($candidates as $candidate) {
            if ($this->firstExistingColumn($logis, [$candidate]) === null) {
                continue;
            }

            $field = $alias . '.' . $this->quoteIdentifier($candidate);
            $type = $this->columnType($logis, $candidate);

            if (in_array($type, ['int2', 'int4', 'int8', 'numeric', 'float4', 'float8'], true)) {
                $parts[] = sprintf('CAST(%s AS NUMERIC)', $field);
                continue;
            }

            $parts[] = sprintf(
                "CASE WHEN COALESCE(CAST(%s AS TEXT), '')"
                . " ~ '^-?[0-9]+([.][0-9]+)?$'"
                . ' THEN CAST(CAST(%s AS TEXT) AS NUMERIC) END',
                $field,
                $field
            );
        }

        return $parts === []
            ? 'NULL::NUMERIC'
            : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    /**
     * Perbandingan ID. Tipe yang sama dibandingkan langsung supaya index
     * tetap terpakai; selebihnya dibandingkan sebagai teks yang sudah
     * dibuang spasinya.
     */
    private function idJoinExpression(
        string $leftLogis,
        string $leftAlias,
        string $leftColumn,
        string $rightLogis,
        string $rightAlias,
        string $rightColumn
    ): string {
        $leftType = $this->columnType($leftLogis, $leftColumn);
        $rightType = $this->columnType($rightLogis, $rightColumn);
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

    /* ------------------------------------------------------------------
     * Lookup
     * ------------------------------------------------------------------ */

    /**
     * Master lokasi.
     *
     * Query desktop membuktikan lokasi disimpan di tabel LOKASI lewat dua
     * baris berikut:
     *
     *     ( SELECT DESKRIPSI FROM LOKASI WHERE KD_LOKASI = STOK.KD_LOKASI )
     *     ( STOK.KD_LOKASI = :lokasi OR :lokasi = '*' )
     *
     * Daftar dibaca apa adanya seperti lookup desktop, tanpa disaring lewat
     * STOK. Menyaring lewat STOK harus membandingkan kode yang sudah di-BTRIM
     * sehingga index tidak terpakai dan pencarian menjadi berat.
     */
    public function obtainLokasi($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $kodeLokasi = $this->requiredColumn('lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $kode = $this->textExpression('lokasi', 'lokasi', [$kodeLokasi]);
        $deskripsi = $this->textExpression('lokasi', 'lokasi', ['deskripsi']);
        $tabel = $this->qualifiedTable('lokasi');

        $sql = <<<SQL
            SELECT
                kode AS "KD_LOKASI",
                deskripsi AS "DESKRIPSI"
            FROM (
                SELECT DISTINCT ON (kode)
                    kode,
                    deskripsi
                FROM (
                    SELECT
                        UPPER({$kode}) AS kode,
                        {$deskripsi} AS deskripsi
                    FROM {$tabel} AS lokasi
                ) AS daftar
                WHERE kode <> ''
                ORDER BY kode, deskripsi
            ) AS unik
            ORDER BY deskripsi ASC, kode ASC
        SQL;

        return collect(DB::connection(self::CONNECTION)->select($sql));
    }

    /**
     * Master sektor, mengikuti pola fitur laporan lain yang sudah dimigrasi.
     *
     * Kolom FLAG_AKTIF dan KD_PERUSAHAAN ikut disaring hanya bila kolomnya
     * memang ada pada hasil migrasi.
     */
    public function obtainSektor($kdPerusahaan)
    {
        $kdPerusahaan = $this->normalizeText($kdPerusahaan);

        if ($kdPerusahaan === '') {
            return collect([]);
        }

        $kodeSektor = $this->requiredColumn('sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);
        $kode = $this->textExpression('sektor', 'sektor', [$kodeSektor]);
        $deskripsi = $this->textExpression('sektor', 'sektor', ['deskripsi']);
        $perusahaan = $this->normalizedExpression(
            'sektor',
            'sektor',
            ['kd_perusahaan', 'kd_unit', 'kd_pt']
        );
        $tabel = $this->qualifiedTable('sektor');

        $where = ["kode <> ''"];
        $bindings = ['kd_perusahaan' => $kdPerusahaan];

        /*
         * Desktop menyaring FLAG_AKTIF = 'A'. Bila kolom itu tidak ikut
         * tersalin, penyaringnya dilewati supaya daftar tidak menjadi kosong.
         */
        $flagAktif = $this->firstExistingColumn('sektor', ['flag_aktif']);
        $flagAktifExpression = $flagAktif === null
            ? "'A'"
            : $this->normalizedExpression('sektor', 'sektor', [$flagAktif]);

        if ($flagAktif !== null) {
            $where[] = "flag_aktif = 'A'";
        }

        /*
         * Kode perusahaan yang kosong ikut ditampilkan. Pada hasil migrasi
         * sebagian baris master tidak membawa kode perusahaan, dan desktop
         * tetap memakai sektornya lewat STOK.
         */
        $where[] = "(kd_perusahaan = :kd_perusahaan OR kd_perusahaan = '')";

        $kondisi = implode("\n              AND ", $where);

        $sql = <<<SQL
            SELECT
                kode AS "KD_SEKTOR",
                deskripsi AS "DESKRIPSI",
                kd_perusahaan AS "KD_PERUSAHAAN"
            FROM (
                SELECT DISTINCT ON (kode)
                    kode,
                    deskripsi,
                    kd_perusahaan
                FROM (
                    SELECT
                        UPPER({$kode}) AS kode,
                        {$deskripsi} AS deskripsi,
                        {$perusahaan} AS kd_perusahaan,
                        {$flagAktifExpression} AS flag_aktif
                    FROM {$tabel} AS sektor
                ) AS daftar
                WHERE {$kondisi}
                ORDER BY
                    kode,
                    CASE
                        WHEN kd_perusahaan = :kd_perusahaan_urut THEN 0
                        ELSE 1
                    END,
                    deskripsi
            ) AS unik
            ORDER BY deskripsi ASC, kode ASC
        SQL;

        $bindings['kd_perusahaan_urut'] = $kdPerusahaan;

        return collect(
            DB::connection(self::CONNECTION)->select($sql, $bindings)
        );
    }

    /* ------------------------------------------------------------------
     * Laporan
     * ------------------------------------------------------------------ */

    /**
     * Laporan Daftar Akta Jual Beli.
     *
     * Struktur dan seluruh syarat WHERE mengikuti query desktop. Yang berbeda
     * hanya hal berikut, dan semuanya disengaja:
     *
     * 1. Join implisit pada FROM diubah menjadi JOIN eksplisit. Relasi antar
     *    tabel tidak berubah.
     * 2. Batas blok bawah pada cabang kedua memakai :blok_awal. Pada query
     *    asli cabang itu tertulis
     *    ( STOK.BLOK >= :BLOK_AKHIR AND STOK.BLOK <= :BLOK_AKHIR ),
     *    sehingga hanya cocok untuk satu blok saja.
     * 3. Batas tanggal atas dibuat eksklusif (tanggal akhir + 1 hari) agar
     *    baris yang jamnya bukan 00:00 pada tanggal akhir tetap ikut, sama
     *    seperti fitur lain di aplikasi ini.
     * 4. NASABAH disambung dengan LEFT JOIN, bukan INNER JOIN seperti
     *    desktop. Pada SQL Server seluruh 38.821 baris PEMBELI_PPJB punya
     *    pasangan di NASABAH, sedangkan pada salinan PostgreSQL hanya 34.560
     *    dari 63.447 baris yang punya pasangan. INNER JOIN di sini akan
     *    membuang unit yang di desktop tetap tampil. Dengan LEFT JOIN unitnya
     *    tetap tampil, hanya kolom Nama Pemilik yang kosong.
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
        $tglAkhir = $this->normalizeDate($request->tgl_akhir ?? date('Y-m-d'));

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

        /* ---------- nama tabel ---------- */

        $tabelAkta = $this->qualifiedTable('akta');
        $tabelPpjb = $this->qualifiedTable('ppjb');
        $tabelPembeli = $this->qualifiedTable('pembeli_ppjb');
        $tabelNasabah = $this->qualifiedTable('nasabah');
        $tabelSertipikat = $this->qualifiedTable('sertipikat');
        $tabelStok = $this->qualifiedTable('stok');
        $tabelLokasi = $this->qualifiedTable('lokasi');
        $tabelSektor = $this->qualifiedTable('sektor');
        $tabelAngsuran = $this->qualifiedTable('angsuran');
        $tabelPengambilan = $this->resolvedTables()['pengambilan'] ?? null;

        /* ---------- kolom kunci ---------- */

        $stokBlokColumn = $this->requiredColumn('stok', ['blok']);
        $stokNomorColumn = $this->requiredColumn('stok', ['nomor']);
        $stokPerusahaanColumn = $this->requiredColumn(
            'stok',
            ['kd_perusahaan', 'kd_unit', 'kd_pt']
        );
        $stokLokasiColumn = $this->requiredColumn(
            'stok',
            ['kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor']
        );
        $stokSektorColumn = $this->requiredColumn(
            'stok',
            ['kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2']
        );
        $lokasiKodeColumn = $this->requiredColumn('lokasi', [
            'kd_lokasi', 'kd_lv2', 'kd_proyek', 'kd_cluster', 'kd_sektor',
        ]);
        $sektorKodeColumn = $this->requiredColumn('sektor', [
            'kd_sektor', 'kd_proyek', 'kd_cluster', 'kd_lokasi', 'kd_lv2',
        ]);

        /* ---------- ekspresi kolom ---------- */

        $stokBlok = 'UPPER(' . $this->textExpression(
            'stok',
            'stok',
            [$stokBlokColumn]
        ) . ')';
        $stokNomor = 'UPPER(' . $this->textExpression(
            'stok',
            'stok',
            [$stokNomorColumn]
        ) . ')';
        $blokNomor = $stokBlok . " || '/' || " . $stokNomor;

        $stokPerusahaan = $this->normalizedExpression(
            'stok',
            'stok',
            [$stokPerusahaanColumn]
        );
        $stokLokasi = $this->normalizedExpression(
            'stok',
            'stok',
            [$stokLokasiColumn]
        );
        $stokSektor = $this->normalizedExpression(
            'stok',
            'stok',
            [$stokSektorColumn]
        );
        $stokFlagAktif = $this->normalizedExpression(
            'stok',
            'stok',
            [$this->requiredColumn('stok', ['flag_aktif'])]
        );
        $ppjbFlagAktif = $this->normalizedExpression(
            'ppjb',
            'ppjb',
            [$this->requiredColumn('ppjb', ['flag_aktif'])]
        );
        $pembeliFlagAktif = $this->normalizedExpression(
            'pembeli_ppjb',
            'pembeli_ppjb',
            [$this->requiredColumn('pembeli_ppjb', ['flag_aktif'])]
        );

        $tglAkta = $this->dateExpression('akta', 'akta', ['tgl_akta']);

        $lokasiKode = $this->normalizedExpression(
            'lokasi',
            'lokasi',
            [$lokasiKodeColumn]
        );
        $lokasiDeskripsi = $this->textExpression(
            'lokasi',
            'lokasi',
            ['deskripsi']
        );
        $sektorKode = $this->normalizedExpression(
            'sektor',
            'sektor',
            [$sektorKodeColumn]
        );
        $sektorDeskripsi = $this->textExpression(
            'sektor',
            'sektor',
            ['deskripsi']
        );

        $angsuranTgl = $this->dateExpression(
            'angsuran',
            'angsuran',
            ['tgl_kuitansi']
        );
        $angsuranKdTransaksi = $this->normalizedExpression(
            'angsuran',
            'angsuran',
            [$this->requiredColumn('angsuran', ['kd_transaksi'])]
        );

        /* ---------- join ---------- */

        $joinAktaPpjb = $this->idJoinExpression(
            'akta', 'akta', $this->requiredColumn('akta', ['ppjb_id']),
            'ppjb', 'ppjb', $this->requiredColumn('ppjb', ['ppjb_id'])
        );
        $joinPembeliPpjb = $this->idJoinExpression(
            'pembeli_ppjb', 'pembeli_ppjb',
            $this->requiredColumn('pembeli_ppjb', ['ppjb_id']),
            'ppjb', 'ppjb', $this->requiredColumn('ppjb', ['ppjb_id'])
        );
        $joinAktaSertipikat = $this->idJoinExpression(
            'sertipikat', 'sertipikat',
            $this->requiredColumn('sertipikat', ['sertipikat_id']),
            'akta', 'akta', $this->requiredColumn('akta', ['sertipikat_id'])
        );
        $joinStokSertipikat = $this->idJoinExpression(
            'stok', 'stok', $this->requiredColumn('stok', ['stok_id']),
            'sertipikat', 'sertipikat',
            $this->requiredColumn('sertipikat', ['stok_id'])
        );
        $joinAngsuranPpjb = $this->idJoinExpression(
            'angsuran', 'angsuran',
            $this->requiredColumn('angsuran', ['ppjb_id']),
            'ppjb', 'ppjb', $this->requiredColumn('ppjb', ['ppjb_id'])
        );

        /*
         * Sambungan ke NASABAH dibuat bertingkat, sama seperti laporan Daftar
         * Serah Terima. Membuang karakter bukan angka lebih dulu membuat
         * nasabah_id bergaya N1 tidak pernah cocok, dan seluruh kolom Nama
         * Pemilik menjadi tanda hubung.
         */
        $nasabahIdType = $this->columnType('nasabah', 'nasabah_id');
        $pembeliNasabahIdType = $this->columnType('pembeli_ppjb', 'nasabah_id');

        if (
            $nasabahIdType !== null
            && $nasabahIdType === $pembeliNasabahIdType
        ) {
            $joinNasabah = 'pembeli_ppjb."nasabah_id" = nasabah."nasabah_id"';
        } elseif (
            in_array($nasabahIdType, ['int2', 'int4', 'int8', 'numeric'], true)
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

        /*
         * PENGAMBILAN hanya dipakai untuk dua kolom tanggal dan disambung
         * LEFT OUTER seperti desktop. Bila tabelnya belum ikut tersalin,
         * joinnya dilewati dan kedua kolomnya bernilai kosong.
         */
        if ($tabelPengambilan !== null) {
            $joinPengambilan = 'LEFT JOIN ' . self::SCHEMA . '.'
                . $tabelPengambilan . ' AS pengambilan'
                . "\n                ON " . $this->idJoinExpression(
                    'pengambilan', 'pengambilan',
                    $this->requiredColumn('pengambilan', ['sertipikat_id']),
                    'sertipikat', 'sertipikat',
                    $this->requiredColumn('sertipikat', ['sertipikat_id'])
                );
            $tglAmbilAkta = $this->dateExpression(
                'pengambilan',
                'pengambilan',
                ['tgl_ambil_akta']
            );
            $tglCetakAkta = $this->dateExpression(
                'pengambilan',
                'pengambilan',
                ['tgl_cetak_akta']
            );
        } else {
            $joinPengambilan = '';
            $tglAmbilAkta = 'NULL::TIMESTAMP';
            $tglCetakAkta = 'NULL::TIMESTAMP';
        }

        /* ---------- WHERE ---------- */

        $bindings = [
            'blok_awal' => $blokAwal,
            'blok_akhir' => $blokAkhir,
            'tgl_awal' => $tglAwal,
            'tgl_akhir' => $tglAkhir,
            'perusahaan' => $perusahaan,
        ];

        $where = [
            $stokFlagAktif . " = 'A'",
            $ppjbFlagAktif . " = 'A'",
            $pembeliFlagAktif . " = 'Y'",
            'ppjb.' . $this->quoteIdentifier(
                $this->requiredColumn('ppjb', ['parent_id'])
            ) . ' IS NULL',
            '(('
                . $blokNomor . ' >= :blok_awal AND '
                . $blokNomor . ' <= :blok_akhir'
                . ') OR ('
                . $stokBlok . ' >= :blok_awal AND '
                . $stokBlok . ' <= :blok_akhir'
                . '))',
            'tgl_ref.tgl_akta_valid >= CAST(:tgl_awal AS DATE)',
            "tgl_ref.tgl_akta_valid < CAST(:tgl_akhir AS DATE) + INTERVAL '1 day'",
            $stokPerusahaan . ' = :perusahaan',
            'stok.' . $this->quoteIdentifier($stokBlokColumn) . ' IS NOT NULL',
            'stok.' . $this->quoteIdentifier($stokNomorColumn) . ' IS NOT NULL',
            'sertipikat.' . $this->quoteIdentifier(
                $this->requiredColumn('sertipikat', ['stok_id'])
            ) . ' IS NOT NULL',
        ];

        if ($lokasi !== '*') {
            $where[] = $stokLokasi . ' = :lokasi';
            $bindings['lokasi'] = $lokasi;
        }

        if ($sektor !== '*') {
            $where[] = $stokSektor . ' = :sektor';
            $bindings['sektor'] = $sektor;
        }

        $sql = <<<'SQL'
            SELECT
                __BLOK_NOMOR__ AS "BLOK_NOMOR",
                __STOK_BLOK__ AS "BLOK",
                __STOK_NOMOR__ AS "NOMOR",
                __NASABAH_NAMA__ AS "NAMA",

                __LUAS_TANAH__ AS "LUAS_TANAH",
                __LUAS_BANGUNAN__ AS "LUAS_BANGUNAN",

                __AKTA_NO_NOTARIS__ AS "NO_NOTARIS",
                __AKTA_TGL_NOTARIS__ AS "TGL_NOTARIS",
                __AKTA_NOTARIS__ AS "NOTARIS",
                __AKTA_NO_AKTA__ AS "NO_AKTA",
                tgl_ref.tgl_akta_valid AS "TGL_AKTA",
                __AKTA_TGL_INPUT__ AS "TGL_INPUT",
                __AKTA_TTD_AKTA__ AS "TTD_AKTA",
                __AKTA_TGL_ENTRY__ AS "TGL_ENTRY",
                __AKTA_USER_ENTRY__ AS "USER_ENTRY",

                __TGL_AMBIL_AKTA__ AS "TGL_AMBIL_AKTA",
                __TGL_CETAK_AKTA__ AS "TGL_CETAK_AKTA",

                __NASABAH_TELP_RMH__ AS "TELP_RMH",
                __NASABAH_FAX_RMH__ AS "FAX_RMH",
                __NASABAH_TELP_KTR__ AS "TELP_KTR",
                __NASABAH_FAX_KTR__ AS "FAX_KTR",
                __NASABAH_NO_HP__ AS "NO_HP",
                __NASABAH_ALAMAT_RMH__ AS "ALAMAT_RMH",
                __NASABAH_KOTA_RMH__ AS "KOTA_RMH",
                __NASABAH_KODE_POS_RMH__ AS "KODE_POS_RMH",

                __PPJB_NO_PPJB__ AS "NO_PPJB",
                __PPJB_TGL_PPJB__ AS "TGL_PPJB",
                __PPJB_HARGA_JUAL__ AS "HARGA_JUAL",

                __STOK_PERUSAHAAN__ AS "KD_PERUSAHAAN",
                CURRENT_TIMESTAMP AS "TGL_CETAK",

                (
                    SELECT __LOKASI_DESKRIPSI__
                    FROM __TABEL_LOKASI__ AS lokasi
                    WHERE __LOKASI_KODE__ = __STOK_LOKASI__
                    ORDER BY __LOKASI_DESKRIPSI__
                    LIMIT 1
                ) AS "NAMA_LOKASI",
                COALESCE(
                    NULLIF((
                        SELECT __SEKTOR_DESKRIPSI__
                        FROM __TABEL_SEKTOR__ AS sektor
                        WHERE __SEKTOR_KODE__ = __STOK_SEKTOR__
                        ORDER BY __SEKTOR_DESKRIPSI__
                        LIMIT 1
                    ), ''),
                    __STOK_SEKTOR__
                ) AS "NAMA_SEKTOR",
                bbn.tgl_kuitansi AS "TGL_KUITANSI_BBN"

            FROM __TABEL_AKTA__ AS akta

            INNER JOIN __TABEL_PPJB__ AS ppjb
                ON __JOIN_AKTA_PPJB__

            INNER JOIN __TABEL_PEMBELI__ AS pembeli_ppjb
                ON __JOIN_PEMBELI_PPJB__

            /*
             * Desktop memakai INNER JOIN ke NASABAH. Di PostgreSQL sebagian
             * besar baris PEMBELI_PPJB belum punya pasangan NASABAH, sehingga
             * INNER JOIN akan menghapus unit yang di desktop tetap tampil.
             */
            LEFT JOIN __TABEL_NASABAH__ AS nasabah
                ON __JOIN_NASABAH__

            INNER JOIN __TABEL_SERTIPIKAT__ AS sertipikat
                ON __JOIN_AKTA_SERTIPIKAT__

            __JOIN_PENGAMBILAN__

            INNER JOIN __TABEL_STOK__ AS stok
                ON __JOIN_STOK_SERTIPIKAT__

            /*
             * Pengganti OUTER APPLY milik desktop. Kolom tanggal akta pada
             * data lama bisa berisi teks yang bukan tanggal, jadi dibaca lewat
             * penjagaan pola lebih dulu.
             */
            LEFT JOIN LATERAL (
                SELECT __TGL_AKTA__ AS tgl_akta_valid
            ) AS tgl_ref ON TRUE

            /*
             * Pengganti subquery TGL_KUITANSI_BBN. Dibuat LATERAL supaya
             * pencarian memakai index PPJB_ID dan hanya mengambil satu baris,
             * bukan memindai seluruh tabel angsuran.
             */
            LEFT JOIN LATERAL (
                SELECT __ANGSURAN_TGL__ AS tgl_kuitansi
                FROM __TABEL_ANGSURAN__ AS angsuran
                WHERE __JOIN_ANGSURAN_PPJB__
                  AND __ANGSURAN_KD_TRANSAKSI__ = 'BBN'
                ORDER BY __ANGSURAN_TGL__ ASC NULLS LAST
                LIMIT 1
            ) AS bbn ON TRUE

            WHERE __WHERE__

            ORDER BY
                __STOK_SEKTOR__ ASC,
                __STOK_BLOK__ ASC,
                CASE
                    WHEN __STOK_NOMOR__ <> ''
                     AND __STOK_NOMOR__ ~ '^[0-9]+$'
                    THEN 0
                    ELSE 1
                END ASC,
                CASE
                    WHEN __STOK_NOMOR__ <> ''
                     AND __STOK_NOMOR__ ~ '^[0-9]+$'
                    THEN LPAD(__STOK_NOMOR__, 50, '0')
                    ELSE ''
                END ASC,
                __STOK_NOMOR__ ASC,
                __AKTA_NO_AKTA__ ASC
        SQL;

        $sql = strtr($sql, [
            '__BLOK_NOMOR__' => $blokNomor,
            '__STOK_BLOK__' => $stokBlok,
            '__STOK_NOMOR__' => $stokNomor,
            '__STOK_PERUSAHAAN__' => $stokPerusahaan,
            '__STOK_LOKASI__' => $stokLokasi,
            '__STOK_SEKTOR__' => $stokSektor,
            '__NASABAH_NAMA__' => $this->textExpression(
                'nasabah',
                'nasabah',
                ['nama', 'nama_nasabah', 'nama_pembeli', 'nama_lengkap']
            ),
            '__NASABAH_TELP_RMH__' => $this->textExpression(
                'nasabah', 'nasabah', ['telp_rmh']
            ),
            '__NASABAH_FAX_RMH__' => $this->textExpression(
                'nasabah', 'nasabah', ['fax_rmh']
            ),
            '__NASABAH_TELP_KTR__' => $this->textExpression(
                'nasabah', 'nasabah', ['telp_ktr']
            ),
            '__NASABAH_FAX_KTR__' => $this->textExpression(
                'nasabah', 'nasabah', ['fax_ktr']
            ),
            '__NASABAH_NO_HP__' => $this->textExpression(
                'nasabah', 'nasabah', ['no_hp']
            ),
            '__NASABAH_ALAMAT_RMH__' => $this->textExpression(
                'nasabah', 'nasabah', ['alamat_rmh']
            ),
            '__NASABAH_KOTA_RMH__' => $this->textExpression(
                'nasabah', 'nasabah', ['kota_rmh']
            ),
            '__NASABAH_KODE_POS_RMH__' => $this->textExpression(
                'nasabah', 'nasabah', ['kode_pos_rmh']
            ),
            '__LUAS_TANAH__' => $this->numericExpression(
                'stok', 'stok', ['luas_tanah']
            ),
            '__LUAS_BANGUNAN__' => $this->numericExpression(
                'stok', 'stok', ['luas_bangunan']
            ),
            '__AKTA_NO_NOTARIS__' => $this->textExpression(
                'akta', 'akta', ['no_notaris']
            ),
            '__AKTA_TGL_NOTARIS__' => $this->dateExpression(
                'akta', 'akta', ['tgl_notaris']
            ),
            '__AKTA_NOTARIS__' => $this->textExpression(
                'akta', 'akta', ['notaris']
            ),
            '__AKTA_NO_AKTA__' => $this->textExpression(
                'akta', 'akta', ['no_akta']
            ),
            '__AKTA_TGL_INPUT__' => $this->dateExpression(
                'akta', 'akta', ['tgl_input']
            ),
            '__AKTA_TTD_AKTA__' => $this->textExpression(
                'akta', 'akta', ['ttd_akta']
            ),
            '__AKTA_TGL_ENTRY__' => $this->dateExpression(
                'akta', 'akta', ['tgl_entry']
            ),
            '__AKTA_USER_ENTRY__' => $this->textExpression(
                'akta', 'akta', ['user_entry']
            ),
            '__TGL_AMBIL_AKTA__' => $tglAmbilAkta,
            '__TGL_CETAK_AKTA__' => $tglCetakAkta,
            '__PPJB_NO_PPJB__' => $this->textExpression(
                'ppjb', 'ppjb', ['no_ppjb']
            ),
            '__PPJB_TGL_PPJB__' => $this->dateExpression(
                'ppjb', 'ppjb', ['tgl_ppjb']
            ),
            '__PPJB_HARGA_JUAL__' => $this->numericExpression(
                'ppjb', 'ppjb', ['harga_jual']
            ),
            '__TGL_AKTA__' => $tglAkta,
            '__LOKASI_KODE__' => $lokasiKode,
            '__LOKASI_DESKRIPSI__' => $lokasiDeskripsi,
            '__SEKTOR_KODE__' => $sektorKode,
            '__SEKTOR_DESKRIPSI__' => $sektorDeskripsi,
            '__ANGSURAN_TGL__' => $angsuranTgl,
            '__ANGSURAN_KD_TRANSAKSI__' => $angsuranKdTransaksi,
            '__TABEL_AKTA__' => $tabelAkta,
            '__TABEL_PPJB__' => $tabelPpjb,
            '__TABEL_PEMBELI__' => $tabelPembeli,
            '__TABEL_NASABAH__' => $tabelNasabah,
            '__TABEL_SERTIPIKAT__' => $tabelSertipikat,
            '__TABEL_STOK__' => $tabelStok,
            '__TABEL_LOKASI__' => $tabelLokasi,
            '__TABEL_SEKTOR__' => $tabelSektor,
            '__TABEL_ANGSURAN__' => $tabelAngsuran,
            '__JOIN_AKTA_PPJB__' => $joinAktaPpjb,
            '__JOIN_PEMBELI_PPJB__' => $joinPembeliPpjb,
            '__JOIN_NASABAH__' => $joinNasabah,
            '__JOIN_AKTA_SERTIPIKAT__' => $joinAktaSertipikat,
            '__JOIN_STOK_SERTIPIKAT__' => $joinStokSertipikat,
            '__JOIN_PENGAMBILAN__' => $joinPengambilan,
            '__JOIN_ANGSURAN_PPJB__' => $joinAngsuranPpjb,
            '__WHERE__' => implode("\n              AND ", $where),
        ]);

        return DB::connection(self::CONNECTION)->select($sql, $bindings);
    }

    /* ------------------------------------------------------------------
     * Normalisasi masukan
     * ------------------------------------------------------------------ */

    /**
     * Menormalisasi tanggal request menjadi Y-m-d, format yang diterima
     * PostgreSQL tanpa bergantung pada DateStyle server.
     */
    private function normalizeDate($value): string
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
