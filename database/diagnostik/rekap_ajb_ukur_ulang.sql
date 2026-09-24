/* ============================================================
 * REKAP AJB, MENGUKUR ULANG SESUDAH SAMBUNGANNYA DIBENAHI
 *
 * BERKAS INI HANYA MEMBACA. Dua perintah EXPLAIN atas SELECT
 * yang sama. Tidak ada INSERT, UPDATE, DELETE, DDL, maupun
 * CREATE INDEX.
 *
 * ------------------------------------------------------------
 * APA YANG TERBACA DARI PENGUKURAN SEBELUMNYA
 *
 * KUERI B lama: 254.485 ms. KUERI C lama (tanpa MATERIALIZED):
 * 120.497 ms. Jadi MATERIALIZED bukan penyakitnya; membuangnya
 * hanya memindahkan masalah.
 *
 * Penyakitnya satu baris ini:
 *
 *   CTE stok_terpilih
 *     ->  Gather (cost=1000.00..9031.42 rows=1)
 *                (actual rows=9807)
 *
 * Perencana menaksir stok_terpilih berisi SATU baris,
 * kenyataannya 9.807. Meleset 9.807 kali lipat.
 *
 * Sebabnya bukan statistik tabel yang basi, sebab sr_stok
 * sudah dianalisis 03-08-2026. Sebabnya SEMUA SYARAT DI CTE
 * ITU DIBUNGKUS FUNGSI:
 *
 *   UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), '')))   = 'A'
 *   UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
 *   (blok || '/' || nomor BETWEEN ...) OR (blok BETWEEN ...)
 *
 * Statistik Postgres disimpan untuk NILAI KOLOM, bukan untuk
 * hasil fungsi atas kolom. Begitu dibungkus, semua taksiran
 * jatuh ke tebakan baku, dan enam tebakan yang dikalikan
 * berujung di angka 1.
 *
 * Akibatnya, karena mengira pihak luarnya cuma satu baris,
 * perencana memilih Nested Loop:
 *
 *   Nested Loop (rows=1) (actual time=92..239150 rows=9560)
 *     Join Filter: btrim(ppjb.stok_id) = stok.kunci_stok
 *     Rows Removed by Join Filter: 485.701.729
 *     ->  CTE Scan on stok_terpilih  (rows=9807)
 *     ->  Seq Scan on sr_ppjb        (rows=49527, loops=9807)
 *
 * sr_ppjb disapu utuh 9.807 kali. 485,7 JUTA pembandingan,
 * 30,7 juta akses penyangga, 239 detik dari total 254 detik.
 * Itu 94 persen waktunya, di satu simpul.
 *
 * Tiga simpul berikutnya pola yang sama:
 *   sr_pembeli_ppjb disapu 546 kali  -> 34,0 juta baris, 12,6 s
 *   akta_terpilih dipindai 9.560 kali ->  7,2 juta baris,  2 s
 *   sertipikat_unit dipindai 546 kali ->  5,2 juta baris,  0,7 s
 *
 * Sisanya, seluruh CTE bantu yang sempat dicurigai, tidak
 * bersalah: sr_sales 11 ms, sr_perjanjian_bank 2 ms, sr_agen
 * 1 ms, sr_tipe_bayar 0,2 ms, sr_lokasi 0,1 ms. Pembacaan
 * sr_nasabah pun hanya 9 ms, sudah berupa Hash Right Join.
 * Dugaan saya soal sr_nasabah tidak terbukti di sini.
 *
 * ------------------------------------------------------------
 * APA YANG DIUBAH
 *
 * Taksiran yang meleset tidak bisa dibetulkan tanpa mengubah
 * bentuk syaratnya, dan itu berisiko mengubah hasil. Yang bisa
 * dilakukan: MEMBUAT PASANGAN BESAR ITU MUSTAHIL TERJADI,
 * apa pun rencana yang dipilih perencana.
 *
 * sr_ppjb dan sr_pembeli_ppjb sekarang disaring lebih dulu di
 * CTE tersendiri, dengan EXISTS yang berdiri sebagai syarat
 * AND di tingkat atas sehingga menjadi sambungan semi:
 *
 *   ppjb_terpilih    : hanya ppjb yang punya akta dalam
 *                      rentang tanggal  (+- 757 baris,
 *                      dari 49.527)
 *   pembeli_terpilih : hanya pembeli milik ppjb itu
 *                      (+- 766 baris, dari 62.326)
 *
 * Dengan begitu pasangan terburuknya menjadi 9.807 x 757 =
 * 7,4 juta, bukan 9.807 x 49.527 = 485,7 juta. Turun 65 kali
 * lipat SEBELUM perencana memilih apa pun.
 *
 * Maknanya tidak berubah: baris yang dibuang EXISTS itu
 * memang akan dibuang juga oleh INNER JOIN yang sudah ada.
 * Sudah diuji dengan menjalankan model lama dan model baru
 * berdampingan; barisnya sama persis di kedua cabang.
 *
 * Cabang "belum ada akta" diperlakukan sama, disaring oleh
 * stok_terpilih. Cabang itu belum pernah diukur, jadi
 * perubahannya berdasar bentuk yang sama, bukan pengukuran.
 *
 * ------------------------------------------------------------
 * CARA PAKAI
 *
 * Tarik dulu perubahan kodenya, lalu jalankan.
 *
 * KUERI A jalan seketika. Yang dicari: baris "CTE Scan on
 * ppjb_terpilih" atau "Hash Join" menggantikan "Nested Loop"
 * yang dulu memakan 239 detik.
 *
 * KUERI B menjalankan sungguhan. Kalau perbaikannya mengena,
 * "Execution Time"-nya turun dari 254.485 ms menjadi hitungan
 * detik. Yang perlu dilihat pada hasilnya:
 *
 *   - Execution Time di paling bawah
 *   - masih adakah "Rows Removed by Join Filter" yang puluhan
 *     juta
 *   - masih adakah "Seq Scan" pada tabel dengan "loops="
 *     ribuan
 *
 * Saringannya sama dengan yang di layar: unit SBKS, blok A
 * sampai Z, semua lokasi, semua sektor, tanggal AJB 01-07-2023
 * sampai 24-09-2026, kotak Belum Ttd Akta tidak dicentang.
 *
 * Ganti 'SBKS' kalau unitnya lain, dan kd_perusahaan kalau
 * nama kolomnya kd_unit atau kd_pt.
 * ============================================================ */


/* ------------------------------------------------------------
 * KUERI A. RENCANA SAJA, TANPA MENJALANKAN. SEKETIKA.
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
        ppjb_terpilih AS MATERIALIZED (
            SELECT
                ppjb.*,
                BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok
            FROM public.sr_ppjb AS ppjb
            WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), '')))
                    = 'A'
              AND ppjb.parent_id IS NULL
              AND EXISTS ( SELECT 1 FROM akta_terpilih AS a WHERE BTRIM(CAST(a.ppjb_id AS TEXT)) = BTRIM(CAST(ppjb.ppjb_id AS TEXT)))
        ),
        pembeli_terpilih AS MATERIALIZED (
            SELECT
                pembeli.*,
                BTRIM(CAST(pembeli.ppjb_id AS TEXT)) AS kunci_ppjb
            FROM public.sr_pembeli_ppjb AS pembeli
            WHERE UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), '')))
                    = 'Y'
              AND EXISTS (
                    SELECT 1
                    FROM ppjb_terpilih AS p
                    WHERE p.kunci_ppjb
                        = BTRIM(CAST(pembeli.ppjb_id AS TEXT))
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

        INNER JOIN ppjb_terpilih AS ppjb
            ON ppjb.kunci_ppjb = BTRIM(CAST(akta.ppjb_id AS TEXT))

        INNER JOIN pembeli_terpilih AS pembeli_ppjb
            ON pembeli_ppjb.kunci_ppjb = ppjb.kunci_ppjb

        LEFT JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

        INNER JOIN stok_terpilih AS stok
            ON stok.kunci_stok = ppjb.kunci_stok

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
        ppjb_terpilih AS MATERIALIZED (
            SELECT
                ppjb.*,
                BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
                BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok
            FROM public.sr_ppjb AS ppjb
            WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), '')))
                    = 'A'
              AND ppjb.parent_id IS NULL
              AND EXISTS ( SELECT 1 FROM akta_terpilih AS a WHERE BTRIM(CAST(a.ppjb_id AS TEXT)) = BTRIM(CAST(ppjb.ppjb_id AS TEXT)))
        ),
        pembeli_terpilih AS MATERIALIZED (
            SELECT
                pembeli.*,
                BTRIM(CAST(pembeli.ppjb_id AS TEXT)) AS kunci_ppjb
            FROM public.sr_pembeli_ppjb AS pembeli
            WHERE UPPER(BTRIM(COALESCE(CAST(pembeli.flag_aktif AS TEXT), '')))
                    = 'Y'
              AND EXISTS (
                    SELECT 1
                    FROM ppjb_terpilih AS p
                    WHERE p.kunci_ppjb
                        = BTRIM(CAST(pembeli.ppjb_id AS TEXT))
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

        INNER JOIN ppjb_terpilih AS ppjb
            ON ppjb.kunci_ppjb = BTRIM(CAST(akta.ppjb_id AS TEXT))

        INNER JOIN pembeli_terpilih AS pembeli_ppjb
            ON pembeli_ppjb.kunci_ppjb = ppjb.kunci_ppjb

        LEFT JOIN public.sr_nasabah AS nasabah
            ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
             = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))

        INNER JOIN stok_terpilih AS stok
            ON stok.kunci_stok = ppjb.kunci_stok

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
