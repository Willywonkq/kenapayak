/* ============================================================
 * REKAP AJB, MENCARI SIMPUL YANG MEMAKAN WAKTU
 *
 * BERKAS INI HANYA MEMBACA. Isinya tiga perintah EXPLAIN atas
 * SELECT yang sama. Tidak ada INSERT, UPDATE, DELETE, DDL,
 * maupun CREATE INDEX.
 *
 * ------------------------------------------------------------
 * DUA HAL YANG PERLU DILURUSKAN LEBIH DULU
 *
 * SATU. Pada layar yang timeout, kotak "Belum Ttd Akta" TIDAK
 * dicentang. Artinya yang berjalan cabang "sudah ada akta",
 * bukan cabang yang kemarin saya perbaiki. Perbaikan kemarin
 * nyata, tetapi mengenai cabang yang satunya, jadi wajar
 * layar ini belum berubah.
 *
 * DUA. Keganjilan yang ditemukan memang benar dan penting.
 * Daftar Akta Jual Beli memakai sr_akta, sr_ppjb,
 * sr_pembeli_ppjb, sr_nasabah, dan sr_sertipikat yang sama
 * persis, bahkan sambungan nasabahnya sama sama dibungkus
 * BTRIM(CAST(...)), tetapi selesai 3,89 detik dengan 546
 * baris. Jadi BUKAN TABELNYA yang lambat, dan bukan pula
 * BTRIM pada sambungan nasabah. Kalau itu sebabnya, Daftar
 * Akta Jual Beli ikut lambat.
 *
 * Kesimpulan yang bisa ditarik: yang berbeda ada pada BENTUK
 * kuerinya, dan itu berarti pada RENCANA yang dipilih
 * Postgres. Untuk tahu simpul mana yang memakan waktu, tidak
 * ada jalan lain selain melihat rencananya.
 *
 * ------------------------------------------------------------
 * SATU DUGAAN, BELUM TENTU BENAR
 *
 * Perbedaan bentuk yang paling menonjol antara kedua model:
 *
 *   daftar_akta_jual_beli_m :  WITH akta_terpilih AS (
 *   rekap_ajb_m             :  WITH akta_terpilih AS MATERIALIZED (
 *
 * Di rekap_ajb_m semua CTE dipagari MATERIALIZED, delapan
 * buah; di model yang cepat tidak satu pun. MATERIALIZED itu
 * pagar bagi perencana: isinya dihitung lebih dulu apa adanya,
 * dan hasilnya tidak membawa statistik, sehingga perencana
 * menebak jumlah barisnya. Tebakan yang meleset membuat
 * urutan sambungan salah pilih.
 *
 * TETAPI JANGAN LANGSUNG PERCAYA DUGAAN INI. MATERIALIZED
 * dipakai di lima belas model lain di aplikasi ini dan
 * model model itu cepat. Jadi MATERIALIZED sendiri bukan
 * penjahatnya. Paling banter ia memperburuk keadaan kalau
 * kebetulan ada tabel yang statistiknya meleset, dan di sini
 * ada satu: sr_nasabah, yang belum pernah dianalisis sama
 * sekali.
 *
 * KUERI C menguji dugaan itu. Kalau ternyata sama saja
 * lambatnya, dugaan ini gugur, dan jawabannya ada pada simpul
 * yang terlihat di KUERI B.
 *
 * ------------------------------------------------------------
 * CARA PAKAI
 *
 * KUERI A jalan seketika, jalankan dulu. Ia tidak menjalankan
 * kuerinya, hanya menampilkan rencananya.
 *
 * KUERI B dan C MEMANG LAMBAT, mungkin dua sampai lima menit,
 * karena EXPLAIN ANALYZE benar benar menjalankan kuerinya.
 * Kalau alat basis datanya punya batas waktu sendiri, naikkan
 * dulu, atau jalankan lewat psql yang tidak berbatas waktu.
 * Kalau tidak bisa menunggu selama itu, KUERI A saja sudah
 * cukup berguna untuk dikirimkan.
 *
 * Yang dicari pada hasilnya:
 *
 *   - baris "Execution Time" di paling bawah
 *   - simpul dengan "actual time" terbesar
 *   - simpul yang "rows=" tebakannya jauh berbeda dari
 *     "actual rows="; selisih ratusan kali adalah tanda
 *     statistik yang meleset
 *   - kata "Nested Loop" yang di bawahnya ada "Seq Scan" pada
 *     tabel besar; itu pola yang membuat satu tabel disapu
 *     berulang ulang
 *
 * Saringannya disetel sama dengan yang di layar: unit SBKS,
 * blok A sampai Z, semua lokasi, semua sektor, tanggal AJB
 * 01-07-2023 sampai 24-09-2026, kotak Belum Ttd Akta tidak
 * dicentang.
 *
 * Kalau unit yang dibuka bukan SBKS, ganti 'SBKS' di ketiga
 * kueri. Kalau nama kolom unitnya bukan kd_perusahaan, ganti
 * juga menjadi kd_unit atau kd_pt.
 * ============================================================ */


/* ------------------------------------------------------------
 * KUERI 0. BESAR TABEL YANG BELUM PERNAH DIUKUR
 *
 * Rekap AJB memakai empat CTE bantu yang tidak ada di Daftar
 * Akta Jual Beli: tipe_bayar_unik, bank_perjanjian, agen_unik,
 * dan sales_unik. Tabel tabelnya belum pernah kita ukur, jadi
 * belum bisa dicoret dari daftar tersangka. bank_perjanjian
 * yang paling perlu dilihat karena ia menyambung dua tabel
 * lalu menyaring DISTINCT ON.
 *
 * Jalan seketika.
 * ------------------------------------------------------------ */
SELECT
    c.relname::text                                AS "TABEL",
    CASE c.relname
        WHEN 'sr_tipe_bayar'     THEN (SELECT count(*) FROM public.sr_tipe_bayar)
        WHEN 'sr_perjanjian_bank' THEN (SELECT count(*) FROM public.sr_perjanjian_bank)
        WHEN 'sr_bank'           THEN (SELECT count(*) FROM public.sr_bank)
        WHEN 'sr_agen'           THEN (SELECT count(*) FROM public.sr_agen)
        WHEN 'sr_sales'          THEN (SELECT count(*) FROM public.sr_sales)
        WHEN 'sr_lokasi'         THEN (SELECT count(*) FROM public.sr_lokasi)
    END                                            AS "JUMLAH_BARIS",
    pg_size_pretty(pg_total_relation_size(c.oid))  AS "BESAR_TOTAL",
    (SELECT count(*) FROM pg_indexes i
     WHERE i.schemaname = 'public' AND i.tablename = c.relname)
                                                   AS "JUMLAH_INDEKS"
FROM pg_class AS c
JOIN pg_namespace AS n ON n.oid = c.relnamespace
WHERE n.nspname = 'public'
  AND c.relname IN (
        'sr_tipe_bayar', 'sr_perjanjian_bank', 'sr_bank',
        'sr_agen', 'sr_sales', 'sr_lokasi'
      )
ORDER BY pg_total_relation_size(c.oid) DESC;


/* ------------------------------------------------------------
 * KUERI A. RENCANA SAJA, TANPA MENJALANKAN. JALAN SEKETIKA.
 * ------------------------------------------------------------ */
EXPLAIN
    WITH akta_terpilih AS MATERIALIZED (
            SELECT
                akta.*,
                CASE
                    WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                         ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN CAST(akta.tgl_akta AS TIMESTAMP)
                END AS tgl_akta_valid
            FROM public.sr_akta AS akta
            WHERE CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END >= CAST('2023-07-01' AS DATE)
              AND CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END < CAST('2026-09-25' AS DATE)
        ),
        stok_terpilih AS MATERIALIZED (
            SELECT
                stok.*,
                BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
            FROM public.sr_stok AS stok
            WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), '')))
                    = 'A'
              AND stok.blok IS NOT NULL
              AND stok.nomor IS NOT NULL
              AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')))
                    = 'SBKS'
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_sektor AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                        || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                    OR
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                  )
        ),
        sertipikat_unit AS MATERIALIZED (
            SELECT DISTINCT ON (kode)
                kode, no_sertipikat, tgl_sertipikat, luas_sup
            FROM (
                SELECT
                    BTRIM(CAST(x.stok_id AS TEXT)) AS kode,
                    x.no_sertipikat AS no_sertipikat,
                    x.tgl_sertipikat AS tgl_sertipikat,
                    x.luas_sup AS luas_sup,
                    x.ctid AS urutan_fisik
                FROM public.sr_sertipikat AS x
                WHERE EXISTS (
                    SELECT 1
                    FROM stok_terpilih AS s
                    WHERE s.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
                )
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        lokasi_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    UPPER(BTRIM(COALESCE(CAST(lokasi.kd_lv2 AS TEXT), '')))
                        AS kode,
                    lokasi.deskripsi AS deskripsi,
                    lokasi.ctid AS urutan_fisik
                FROM public.sr_lokasi AS lokasi
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        tipe_bayar_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.tipe_bayar AS TEXT), '')) AS kode,
                    a.nama AS nama,
                    a.ctid AS urutan_fisik
                FROM public.sr_tipe_bayar AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        bank_perjanjian AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(b.perjanjian_bank_id AS TEXT), ''))
                        AS kode,
                    a.nama AS nama,
                    b.ctid AS urutan_fisik
                FROM public.sr_perjanjian_bank AS b
                INNER JOIN public.sr_bank AS a
                    ON BTRIM(CAST(a.kd_bank AS TEXT))
                     = BTRIM(CAST(b.kd_bank AS TEXT))
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        agen_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama_agen
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_agen AS TEXT), '')) AS kode,
                    a.nama_agen AS nama_agen,
                    a.ctid AS urutan_fisik
                FROM public.sr_agen AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        sales_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_sales AS TEXT), '')) AS kode,
                    a.deskripsi AS deskripsi,
                    a.ctid AS urutan_fisik
                FROM public.sr_sales AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        )

        SELECT
            UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                AS "BLOK_NOMOR",
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
            akta.ttd_akta AS "TTD_AKTA",
            akta.harga AS "HARGA",
            akta.harga_njop AS "HARGA_NJOP",

            sertipikat_unit.no_sertipikat AS "NO_SERTIPIKAT",
            sertipikat_unit.tgl_sertipikat AS "TGL_SERTIPIKAT",
            sertipikat_unit.luas_sup AS "LUAS_SUP",

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

            stok.kd_perusahaan AS "KD_PERUSAHAAN",
            CURRENT_TIMESTAMP AS "TGL_CETAK",
            ppjb.user_entry AS "USER_ENTRY",

            lokasi_unik.deskripsi AS "NAMA_LOKASI",
            tipe_bayar_unik.nama AS "TIPE_BAYAR",
            bank_perjanjian.nama AS "BANK",
            agen_unik.nama_agen AS "NM_AGEN",
            sales_unik.deskripsi AS "NM_SALES",

            CAST('T' AS VARCHAR(1)) AS "BELUM_TTD_AKTA"

        FROM akta_terpilih AS akta

        INNER JOIN public.sr_ppjb AS ppjb
            ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(akta.ppjb_id AS TEXT))

        INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
            ON BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

        LEFT JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

        INNER JOIN stok_terpilih AS stok
            ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

        LEFT JOIN sertipikat_unit
            ON sertipikat_unit.kode = stok.kunci_stok
        LEFT JOIN lokasi_unik
            ON lokasi_unik.kode
             = UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
        LEFT JOIN tipe_bayar_unik
            ON tipe_bayar_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.tipe_bayar AS TEXT), ''))
        LEFT JOIN bank_perjanjian
            ON bank_perjanjian.kode
             = BTRIM(COALESCE(CAST(ppjb.perjanjian_bank_id AS TEXT), ''))
        LEFT JOIN agen_unik
            ON agen_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_agen AS TEXT), ''))
        LEFT JOIN sales_unik
            ON sales_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_sales AS TEXT), ''))

        WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
          AND UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')))
                = 'Y'
          AND ppjb.parent_id IS NULL

        ORDER BY
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
            akta.no_akta ASC;


/* ------------------------------------------------------------
 * KUERI B. RENCANA BESERTA WAKTU SEBENARNYA TIAP SIMPUL.
 *
 * INI YANG LAMBAT. Sabar, biarkan sampai selesai.
 * ------------------------------------------------------------ */
EXPLAIN (ANALYZE, BUFFERS)
    WITH akta_terpilih AS MATERIALIZED (
            SELECT
                akta.*,
                CASE
                    WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                         ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN CAST(akta.tgl_akta AS TIMESTAMP)
                END AS tgl_akta_valid
            FROM public.sr_akta AS akta
            WHERE CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END >= CAST('2023-07-01' AS DATE)
              AND CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END < CAST('2026-09-25' AS DATE)
        ),
        stok_terpilih AS MATERIALIZED (
            SELECT
                stok.*,
                BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
            FROM public.sr_stok AS stok
            WHERE UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), '')))
                    = 'A'
              AND stok.blok IS NOT NULL
              AND stok.nomor IS NOT NULL
              AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')))
                    = 'SBKS'
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_sektor AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                        || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                    OR
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                  )
        ),
        sertipikat_unit AS MATERIALIZED (
            SELECT DISTINCT ON (kode)
                kode, no_sertipikat, tgl_sertipikat, luas_sup
            FROM (
                SELECT
                    BTRIM(CAST(x.stok_id AS TEXT)) AS kode,
                    x.no_sertipikat AS no_sertipikat,
                    x.tgl_sertipikat AS tgl_sertipikat,
                    x.luas_sup AS luas_sup,
                    x.ctid AS urutan_fisik
                FROM public.sr_sertipikat AS x
                WHERE EXISTS (
                    SELECT 1
                    FROM stok_terpilih AS s
                    WHERE s.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
                )
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        lokasi_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    UPPER(BTRIM(COALESCE(CAST(lokasi.kd_lv2 AS TEXT), '')))
                        AS kode,
                    lokasi.deskripsi AS deskripsi,
                    lokasi.ctid AS urutan_fisik
                FROM public.sr_lokasi AS lokasi
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        tipe_bayar_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.tipe_bayar AS TEXT), '')) AS kode,
                    a.nama AS nama,
                    a.ctid AS urutan_fisik
                FROM public.sr_tipe_bayar AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        bank_perjanjian AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(b.perjanjian_bank_id AS TEXT), ''))
                        AS kode,
                    a.nama AS nama,
                    b.ctid AS urutan_fisik
                FROM public.sr_perjanjian_bank AS b
                INNER JOIN public.sr_bank AS a
                    ON BTRIM(CAST(a.kd_bank AS TEXT))
                     = BTRIM(CAST(b.kd_bank AS TEXT))
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        agen_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, nama_agen
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_agen AS TEXT), '')) AS kode,
                    a.nama_agen AS nama_agen,
                    a.ctid AS urutan_fisik
                FROM public.sr_agen AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        sales_unik AS MATERIALIZED (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_sales AS TEXT), '')) AS kode,
                    a.deskripsi AS deskripsi,
                    a.ctid AS urutan_fisik
                FROM public.sr_sales AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        )

        SELECT
            UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                AS "BLOK_NOMOR",
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
            akta.ttd_akta AS "TTD_AKTA",
            akta.harga AS "HARGA",
            akta.harga_njop AS "HARGA_NJOP",

            sertipikat_unit.no_sertipikat AS "NO_SERTIPIKAT",
            sertipikat_unit.tgl_sertipikat AS "TGL_SERTIPIKAT",
            sertipikat_unit.luas_sup AS "LUAS_SUP",

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

            stok.kd_perusahaan AS "KD_PERUSAHAAN",
            CURRENT_TIMESTAMP AS "TGL_CETAK",
            ppjb.user_entry AS "USER_ENTRY",

            lokasi_unik.deskripsi AS "NAMA_LOKASI",
            tipe_bayar_unik.nama AS "TIPE_BAYAR",
            bank_perjanjian.nama AS "BANK",
            agen_unik.nama_agen AS "NM_AGEN",
            sales_unik.deskripsi AS "NM_SALES",

            CAST('T' AS VARCHAR(1)) AS "BELUM_TTD_AKTA"

        FROM akta_terpilih AS akta

        INNER JOIN public.sr_ppjb AS ppjb
            ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(akta.ppjb_id AS TEXT))

        INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
            ON BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

        LEFT JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

        INNER JOIN stok_terpilih AS stok
            ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

        LEFT JOIN sertipikat_unit
            ON sertipikat_unit.kode = stok.kunci_stok
        LEFT JOIN lokasi_unik
            ON lokasi_unik.kode
             = UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
        LEFT JOIN tipe_bayar_unik
            ON tipe_bayar_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.tipe_bayar AS TEXT), ''))
        LEFT JOIN bank_perjanjian
            ON bank_perjanjian.kode
             = BTRIM(COALESCE(CAST(ppjb.perjanjian_bank_id AS TEXT), ''))
        LEFT JOIN agen_unik
            ON agen_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_agen AS TEXT), ''))
        LEFT JOIN sales_unik
            ON sales_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_sales AS TEXT), ''))

        WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
          AND UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')))
                = 'Y'
          AND ppjb.parent_id IS NULL

        ORDER BY
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
            akta.no_akta ASC;


/* ------------------------------------------------------------
 * KUERI C. SELECT YANG SAMA PERSIS, KATA MATERIALIZED DIBUANG.
 *
 * Tidak ada perubahan lain. Hasil barisnya wajib sama; yang
 * berbeda hanya kebebasan perencana menyusun rencananya.
 *
 * Bandingkan "Execution Time"-nya dengan KUERI B.
 * ------------------------------------------------------------ */
EXPLAIN (ANALYZE, BUFFERS)
    WITH akta_terpilih AS (
            SELECT
                akta.*,
                CASE
                    WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                         ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                    THEN CAST(akta.tgl_akta AS TIMESTAMP)
                END AS tgl_akta_valid
            FROM public.sr_akta AS akta
            WHERE CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END >= CAST('2023-07-01' AS DATE)
              AND CASE
                      WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                           ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN CAST(akta.tgl_akta AS TIMESTAMP)
                  END < CAST('2026-09-25' AS DATE)
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
              AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), '')))
                    = 'SBKS'
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    UPPER(BTRIM(COALESCE(CAST(stok.kd_sektor AS TEXT), '')))
                        = '*'
                    OR '*' = '*'
                  )
              AND (
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                        || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                    OR
                    (
                        UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')))
                        BETWEEN 'A' AND 'ZZ'
                    )
                  )
        ),
        sertipikat_unit AS (
            SELECT DISTINCT ON (kode)
                kode, no_sertipikat, tgl_sertipikat, luas_sup
            FROM (
                SELECT
                    BTRIM(CAST(x.stok_id AS TEXT)) AS kode,
                    x.no_sertipikat AS no_sertipikat,
                    x.tgl_sertipikat AS tgl_sertipikat,
                    x.luas_sup AS luas_sup,
                    x.ctid AS urutan_fisik
                FROM public.sr_sertipikat AS x
                WHERE EXISTS (
                    SELECT 1
                    FROM stok_terpilih AS s
                    WHERE s.kunci_stok = BTRIM(CAST(x.stok_id AS TEXT))
                )
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        lokasi_unik AS (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    UPPER(BTRIM(COALESCE(CAST(lokasi.kd_lv2 AS TEXT), '')))
                        AS kode,
                    lokasi.deskripsi AS deskripsi,
                    lokasi.ctid AS urutan_fisik
                FROM public.sr_lokasi AS lokasi
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        tipe_bayar_unik AS (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.tipe_bayar AS TEXT), '')) AS kode,
                    a.nama AS nama,
                    a.ctid AS urutan_fisik
                FROM public.sr_tipe_bayar AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        bank_perjanjian AS (
            SELECT DISTINCT ON (kode) kode, nama
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(b.perjanjian_bank_id AS TEXT), ''))
                        AS kode,
                    a.nama AS nama,
                    b.ctid AS urutan_fisik
                FROM public.sr_perjanjian_bank AS b
                INNER JOIN public.sr_bank AS a
                    ON BTRIM(CAST(a.kd_bank AS TEXT))
                     = BTRIM(CAST(b.kd_bank AS TEXT))
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        agen_unik AS (
            SELECT DISTINCT ON (kode) kode, nama_agen
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_agen AS TEXT), '')) AS kode,
                    a.nama_agen AS nama_agen,
                    a.ctid AS urutan_fisik
                FROM public.sr_agen AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        ),
        sales_unik AS (
            SELECT DISTINCT ON (kode) kode, deskripsi
            FROM (
                SELECT
                    BTRIM(COALESCE(CAST(a.kd_sales AS TEXT), '')) AS kode,
                    a.deskripsi AS deskripsi,
                    a.ctid AS urutan_fisik
                FROM public.sr_sales AS a
            ) AS daftar
            ORDER BY kode, urutan_fisik
        )

        SELECT
            UPPER(BTRIM(COALESCE(CAST(stok.blok AS TEXT), ''))) || '/'
                || UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), '')))
                AS "BLOK_NOMOR",
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
            akta.ttd_akta AS "TTD_AKTA",
            akta.harga AS "HARGA",
            akta.harga_njop AS "HARGA_NJOP",

            sertipikat_unit.no_sertipikat AS "NO_SERTIPIKAT",
            sertipikat_unit.tgl_sertipikat AS "TGL_SERTIPIKAT",
            sertipikat_unit.luas_sup AS "LUAS_SUP",

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

            stok.kd_perusahaan AS "KD_PERUSAHAAN",
            CURRENT_TIMESTAMP AS "TGL_CETAK",
            ppjb.user_entry AS "USER_ENTRY",

            lokasi_unik.deskripsi AS "NAMA_LOKASI",
            tipe_bayar_unik.nama AS "TIPE_BAYAR",
            bank_perjanjian.nama AS "BANK",
            agen_unik.nama_agen AS "NM_AGEN",
            sales_unik.deskripsi AS "NM_SALES",

            CAST('T' AS VARCHAR(1)) AS "BELUM_TTD_AKTA"

        FROM akta_terpilih AS akta

        INNER JOIN public.sr_ppjb AS ppjb
            ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(akta.ppjb_id AS TEXT))

        INNER JOIN public.sr_pembeli_ppjb AS pembeli_ppjb
            ON BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT))
             = BTRIM(CAST(ppjb.ppjb_id AS TEXT))

        LEFT JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

        INNER JOIN stok_terpilih AS stok
            ON stok.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))

        LEFT JOIN sertipikat_unit
            ON sertipikat_unit.kode = stok.kunci_stok
        LEFT JOIN lokasi_unik
            ON lokasi_unik.kode
             = UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
        LEFT JOIN tipe_bayar_unik
            ON tipe_bayar_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.tipe_bayar AS TEXT), ''))
        LEFT JOIN bank_perjanjian
            ON bank_perjanjian.kode
             = BTRIM(COALESCE(CAST(ppjb.perjanjian_bank_id AS TEXT), ''))
        LEFT JOIN agen_unik
            ON agen_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_agen AS TEXT), ''))
        LEFT JOIN sales_unik
            ON sales_unik.kode
             = BTRIM(COALESCE(CAST(ppjb.kd_sales AS TEXT), ''))

        WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
          AND UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), '')))
                = 'Y'
          AND ppjb.parent_id IS NULL

        ORDER BY
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
            akta.no_akta ASC;
