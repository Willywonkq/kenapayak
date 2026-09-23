/* ============================================================
 * CORONG DAFTAR SERTIPIKAT PECAHAN, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN
 *
 *   web       129 baris
 *   desktop   753 baris atau lebih
 *
 * Saringan yang dipakai, sama pada kedua sisi:
 *   unit SBKS, sektor semua, blok A sampai ZZ,
 *   Tgl Input Sert/Gabung 01-07-2023 sampai 23-09-2026,
 *   Apartemen tidak dicentang, Kartu Surat Tanah tidak
 *   dicentang, Tampilkan Sertipikat Penggabungan tidak
 *   dicentang, Status AJB Semua.
 *
 * ------------------------------------------------------------
 * TIGA DUGAAN YANG DIUJI
 *
 * A. AWALAN KUNCI DITEBAK SEKALI UNTUK SELURUH TABEL.
 *    sr_sertipikat_idk.sertipikat_id dimigrasikan sebagai angka
 *    sehingga penanda DBPSA- atau DBPSS- terbuang. Model
 *    menyusunnya kembali, tetapi memakai SATU awalan yang sama
 *    untuk semua baris, yaitu yang paling sering cocok.
 *    Catatan diagnosis terdahulu sudah menyebut dari 23.308
 *    baris idk ada 19.465 yang angkanya dipakai kedua keluarga,
 *    jadi awalannya memang tidak bisa ditentukan sekali untuk
 *    seluruh tabel.
 *
 * B. SUMBER TANGGALNYA SALAH PILIH.
 *    Model menyaring dengan COALESCE(tgl_input_gabung,
 *    tgl_input_ser). Baris yang pernah digabung punya
 *    tgl_input_gabung terisi, sehingga tanggal sertipikatnya
 *    sendiri tidak pernah dipakai. Bila tanggal gabungnya di
 *    luar rentang, barisnya hilang walaupun tanggal
 *    sertipikatnya di dalam rentang.
 *
 * C. BARISNYA MEMANG BELUM TERMIGRASI.
 *    Catatan terdahulu mencatat sr_sertipikat_idk kehilangan
 *    4.189 baris dari 27.497, dan pada beberapa unit hilang
 *    seluruhnya.
 *
 * QUERY 1 memisahkan ketiganya dengan menghitung sisa baris
 * pada tiap tahap penyaringan.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG. Lihat pada tahap mana angkanya terjun.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn','kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn','kd_jenis'], column_name)
          LIMIT 1) AS stok_jenis
),
awalan AS MATERIALIZED (
    SELECT ser.awalan AS awalan
    FROM (
        SELECT
            BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
            UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat_idk AS i
        WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
          AND (BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')) <> ''
            OR BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), '')) <> '')
    ) AS idk
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(s.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat AS s
        WHERE s.sertipikat_id IS NOT NULL
    ) AS ser ON ser.angka = idk.angka
    WHERE (idk.no_ser <> '' AND idk.no_ser = ser.no_ser)
       OR (idk.su_pisah <> '' AND idk.su_pisah = ser.su_pisah)
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1 ASC
    LIMIT 1
),
idk AS MATERIALIZED (
    SELECT
        CASE
            WHEN BTRIM(CAST(i.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(i.sertipikat_id AS TEXT))
            ELSE (SELECT awalan FROM awalan) || BTRIM(CAST(i.sertipikat_id AS TEXT))
        END AS kunci
    FROM public.sr_sertipikat_idk AS i
),
ser AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci,
        BTRIM(CAST(s.stok_id AS TEXT))       AS kunci_stok,
        CASE WHEN COALESCE(CAST(s.tgl_input_gabung AS TEXT),'') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(s.tgl_input_gabung AS TIMESTAMP) END AS tgl_gabung,
        CASE WHEN COALESCE(CAST(s.tgl_input_ser AS TEXT),'') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(s.tgl_input_ser AS TIMESTAMP) END AS tgl_ser
    FROM public.sr_sertipikat AS s
),
stok AS MATERIALIZED (
    SELECT
        BTRIM(CAST(st.stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, '')))  AS unit,
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) AS jenis,
        UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), '')))   AS flag_aktif,
        UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), '')))         AS blok,
        UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), '')))        AS nomor,
        (st.blok IS NOT NULL AND st.nomor IS NOT NULL)            AS blok_nomor_ada
    FROM public.sr_stok AS st
    CROSS JOIN kolom AS k
),
gabung AS (
    SELECT
        idk.kunci,
        ser.kunci      AS ser_kunci,
        ser.kunci_stok AS ser_stok,
        stok.kunci_stok AS stok_kunci,
        stok.unit, stok.jenis, stok.flag_aktif,
        stok.blok, stok.nomor, stok.blok_nomor_ada,
        COALESCE(ser.tgl_gabung, ser.tgl_ser) AS tgl_cara_model
    FROM idk
    LEFT JOIN ser  ON ser.kunci = idk.kunci
    LEFT JOIN stok ON stok.kunci_stok = ser.kunci_stok
)
SELECT
    COUNT(*)                                                   AS t1_baris_idk,
    COUNT(*) FILTER (WHERE ser_kunci IS NOT NULL)              AS t2_kunci_ketemu,
    COUNT(*) FILTER (WHERE ser_stok IS NOT NULL)               AS t3_punya_stok_id,
    COUNT(*) FILTER (WHERE stok_kunci IS NOT NULL)             AS t4_stok_ketemu,
    COUNT(*) FILTER (WHERE unit = 'SBKS')                      AS t5_unit_sbks,
    COUNT(*) FILTER (WHERE unit = 'SBKS' AND flag_aktif = 'A') AS t6_stok_aktif,
    COUNT(*) FILTER (WHERE unit = 'SBKS' AND flag_aktif = 'A'
                       AND blok_nomor_ada)                     AS t7_blok_nomor_ada,
    COUNT(*) FILTER (WHERE unit = 'SBKS' AND flag_aktif = 'A'
                       AND blok_nomor_ada
                       AND jenis NOT IN ('APT','KTR'))         AS t8_bukan_apt_ktr,
    COUNT(*) FILTER (WHERE unit = 'SBKS' AND flag_aktif = 'A'
                       AND blok_nomor_ada
                       AND jenis NOT IN ('APT','KTR')
                       AND tgl_cara_model >= CAST('2023-07-01' AS DATE)
                       AND tgl_cara_model <  CAST('2026-09-24' AS DATE)) AS t9_dalam_tanggal,
    COUNT(*) FILTER (WHERE unit = 'SBKS' AND flag_aktif = 'A'
                       AND blok_nomor_ada
                       AND jenis NOT IN ('APT','KTR')
                       AND tgl_cara_model >= CAST('2023-07-01' AS DATE)
                       AND tgl_cara_model <  CAST('2026-09-24' AS DATE)
                       AND ((blok || '/' || nomor) BETWEEN 'A' AND 'ZZ'
                            OR blok BETWEEN 'A' AND 'ZZ'))     AS t10_yang_tampil
FROM gabung;


/* ------------------------------------------------------------
 * QUERY 2
 * DUGAAN B: SUMBER TANGGAL.
 *
 * Dihitung pada kumpulan yang sudah lolos unit, stok aktif,
 * blok nomor ada, dan bukan apartemen. Yang dibandingkan hanya
 * cara memilih tanggalnya.
 *
 *   cara_model   COALESCE(tgl_input_gabung, tgl_input_ser)
 *   cara_ser     tgl_input_ser saja
 *   cara_salah_satu  salah satu di dalam rentang
 *
 * Kalau cara_ser jauh lebih besar daripada cara_model, inilah
 * sebabnya, dan perbaikannya ada di model.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit,
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_jenis_bgn','kd_jenis'])
          ORDER BY array_position(ARRAY['kd_jenis_bgn','kd_jenis'], column_name)
          LIMIT 1) AS stok_jenis
),
awalan AS MATERIALIZED (
    SELECT ser.awalan AS awalan
    FROM (
        SELECT
            BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
            UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat_idk AS i
        WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
          AND (BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')) <> ''
            OR BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), '')) <> '')
    ) AS idk
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(s.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat AS s
        WHERE s.sertipikat_id IS NOT NULL
    ) AS ser ON ser.angka = idk.angka
    WHERE (idk.no_ser <> '' AND idk.no_ser = ser.no_ser)
       OR (idk.su_pisah <> '' AND idk.su_pisah = ser.su_pisah)
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1 ASC
    LIMIT 1
),
siap AS MATERIALIZED (
    SELECT
        CASE WHEN COALESCE(CAST(s.tgl_input_gabung AS TEXT),'') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(s.tgl_input_gabung AS TIMESTAMP) END AS tgl_gabung,
        CASE WHEN COALESCE(CAST(s.tgl_input_ser AS TEXT),'') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             THEN CAST(s.tgl_input_ser AS TIMESTAMP) END AS tgl_ser
    FROM public.sr_sertipikat_idk AS i
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(i.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(i.sertipikat_id AS TEXT))
               ELSE (SELECT awalan FROM awalan) || BTRIM(CAST(i.sertipikat_id AS TEXT))
           END
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) NOT IN ('APT','KTR')
)
SELECT
    COUNT(*)                                            AS siap_sebelum_tanggal,
    COUNT(*) FILTER (WHERE tgl_gabung IS NOT NULL)      AS punya_tgl_gabung,
    COUNT(*) FILTER (
        WHERE COALESCE(tgl_gabung, tgl_ser) >= CAST('2023-07-01' AS DATE)
          AND COALESCE(tgl_gabung, tgl_ser) <  CAST('2026-09-24' AS DATE)
    )                                                   AS cara_model,
    COUNT(*) FILTER (
        WHERE tgl_ser >= CAST('2023-07-01' AS DATE)
          AND tgl_ser <  CAST('2026-09-24' AS DATE)
    )                                                   AS cara_ser_saja,
    COUNT(*) FILTER (
        WHERE (tgl_ser >= CAST('2023-07-01' AS DATE)
               AND tgl_ser < CAST('2026-09-24' AS DATE))
           OR (tgl_gabung >= CAST('2023-07-01' AS DATE)
               AND tgl_gabung < CAST('2026-09-24' AS DATE))
    )                                                   AS cara_salah_satu
FROM siap;


/* ------------------------------------------------------------
 * QUERY 3
 * DUGAAN A: AWALAN.
 *
 * Membandingkan berapa baris idk yang menemukan sertipikatnya
 * bila awalannya DBPSA-, bila DBPSS-, dan bila dipilih per
 * baris mana pun yang ketemu. Kolom terakhir itulah batas atas
 * yang bisa dicapai kalau awalannya ditentukan baris per baris.
 * ------------------------------------------------------------ */
WITH idk AS MATERIALIZED (
    SELECT
        BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
        (BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$') AS polos
    FROM public.sr_sertipikat_idk AS i
),
kunci_ser AS MATERIALIZED (
    SELECT DISTINCT BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat AS s
    WHERE s.sertipikat_id IS NOT NULL
)
SELECT
    COUNT(*)                                        AS baris_idk,
    COUNT(*) FILTER (WHERE NOT polos)               AS awalan_masih_utuh,
    COUNT(*) FILTER (WHERE polos AND a.kunci IS NOT NULL) AS ketemu_dengan_dbpsa,
    COUNT(*) FILTER (WHERE polos AND b.kunci IS NOT NULL) AS ketemu_dengan_dbpss,
    COUNT(*) FILTER (WHERE polos AND a.kunci IS NOT NULL
                       AND b.kunci IS NOT NULL)     AS rancu_dua_duanya,
    COUNT(*) FILTER (WHERE polos
                       AND (a.kunci IS NOT NULL OR b.kunci IS NOT NULL))
                                                    AS ketemu_bila_per_baris
FROM idk
LEFT JOIN kunci_ser AS a ON a.kunci = 'DBPSA-' || idk.angka
LEFT JOIN kunci_ser AS b ON b.kunci = 'DBPSS-' || idk.angka;


/* ------------------------------------------------------------
 * QUERY 4
 * DUGAAN C DAN BARIS GANDA.
 *
 * Berapa banyak sertipikat SBKS yang punya lebih dari satu
 * induk. Satu sertipikat pecahan memang boleh punya beberapa
 * induk, jadi baris ganda pada layar belum tentu keliru, tetapi
 * angkanya perlu diketahui supaya jumlah di kedua sisi bisa
 * dibandingkan dengan adil.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit
),
awalan AS MATERIALIZED (
    SELECT ser.awalan AS awalan
    FROM (
        SELECT
            BTRIM(CAST(i.sertipikat_id AS TEXT)) AS angka,
            UPPER(BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat_idk AS i
        WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
          AND (BTRIM(COALESCE(CAST(i.ser_pisah AS TEXT), '')) <> ''
            OR BTRIM(COALESCE(CAST(i.su_pisah AS TEXT), '')) <> '')
    ) AS idk
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '[0-9]+$', '') AS awalan,
            REGEXP_REPLACE(BTRIM(CAST(s.sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            UPPER(BTRIM(COALESCE(CAST(s.no_sertipikat AS TEXT), ''))) AS no_ser,
            UPPER(BTRIM(COALESCE(CAST(s.su_pisah AS TEXT), ''))) AS su_pisah
        FROM public.sr_sertipikat AS s
        WHERE s.sertipikat_id IS NOT NULL
    ) AS ser ON ser.angka = idk.angka
    WHERE (idk.no_ser <> '' AND idk.no_ser = ser.no_ser)
       OR (idk.su_pisah <> '' AND idk.su_pisah = ser.su_pisah)
    GROUP BY 1
    ORDER BY COUNT(*) DESC, 1 ASC
    LIMIT 1
),
baris AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci_ser,
        UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) || '/'
            || UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), ''))) AS blok_nomor
    FROM public.sr_sertipikat_idk AS i
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(i.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(i.sertipikat_id AS TEXT))
               ELSE (SELECT awalan FROM awalan) || BTRIM(CAST(i.sertipikat_id AS TEXT))
           END
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
)
SELECT
    COUNT(*)                            AS baris_laporan,
    COUNT(DISTINCT kunci_ser)           AS sertipikat_berbeda,
    COUNT(DISTINCT blok_nomor)          AS blok_nomor_berbeda,
    COUNT(*) - COUNT(DISTINCT kunci_ser) AS induk_tambahan
FROM baris;


/* ------------------------------------------------------------
 * QUERY 5
 * Kemutakhiran sr_sertipikat_idk, untuk memastikan apakah
 * tabelnya ikut berhenti dimigrasikan seperti sr_akta dan
 * sr_jaminan yang berhenti Februari 2024.
 * ------------------------------------------------------------ */
SELECT
    COUNT(*)                     AS baris,
    MIN(tgl_input)               AS tgl_input_terawal,
    MAX(tgl_input)               AS tgl_input_terakhir
FROM public.sr_sertipikat_idk;
