/* ============================================================
 * UJI FILTER DAFTAR JAMINAN BANK, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Aman dijalankan pada database yang dipakai bersama.
 *
 * Meniru persis penyaring yang dipakai model, dengan nilai yang
 * sama seperti di layar:
 *
 *   UNIT          : SBKS
 *   BLOK          : A  s/d  ZZ
 *   TGL BANK      : 01-07-2023  s/d  18-09-2026
 *   SEKTOR/CLUSTER: Semua Cluster
 *   STATUS AJB    : Semua
 *   JENIS JAMINAN : Semua
 *
 * Jalankan QUERY 1 lebih dulu. Query itu memastikan nama kolom
 * dan cara penyusunan kunci yang berlaku pada database ini. Kalau
 * hasilnya berbeda dengan yang tertulis di QUERY 2 dan seterusnya,
 * beri tahu saya sebelum hasilnya dipakai menyimpulkan apa pun.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Nama kolom yang dipilih model, dan cara penyusunan kunci
 * sr_jaminan -> sr_sertipikat yang berlaku.
 *
 * Model memilih nama kolom secara berurutan dari daftar calon,
 * memakai yang pertama ditemukan. Query ini meniru urutan itu.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT table_name, LOWER(column_name) AS column_name
    FROM information_schema.columns
    WHERE table_schema = 'public'
),
pilih AS (
    SELECT
        'sr_stok / perusahaan' AS keperluan,
        COALESCE(
            (SELECT c FROM UNNEST(ARRAY['kd_perusahaan','kd_unit','kd_pt']) AS c
              WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
              LIMIT 1),
            'TIDAK ADA'
        ) AS kolom_terpakai
    UNION ALL
    SELECT
        'sr_stok / sektor',
        COALESCE(
            (SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
              WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
              LIMIT 1),
            'TIDAK ADA')
    UNION ALL
    SELECT
        'sr_stok / lokasi',
        COALESCE(
            (SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster']) AS c
              WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
              LIMIT 1),
            'TIDAK ADA')
    UNION ALL
    SELECT
        'sr_lokasi / kode',
        COALESCE(
            (SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster','kd_sektor']) AS c
              WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_lokasi')
              LIMIT 1),
            'TIDAK ADA')
    UNION ALL
    SELECT
        'sr_sektor / kode',
        COALESCE(
            (SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
              WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_sektor')
              LIMIT 1),
            'TIDAK ADA')
    UNION ALL
    SELECT
        'sr_jaminan punya kd_perusahaan?',
        CASE WHEN EXISTS (
                 SELECT 1 FROM kolom
                 WHERE table_name='sr_jaminan' AND column_name='kd_perusahaan'
             )
             THEN 'ADA, kunci diambil dari peta unit (cara paling pasti)'
             ELSE 'TIDAK ADA, kunci memakai satu awalan hasil suara'
        END
)
SELECT * FROM pilih;


/* ------------------------------------------------------------
 * QUERY 2
 * Awalan yang dipilih model lewat suara baris yang sudah pasti,
 * beserta kekuatan suaranya. Dipakai QUERY 3 dan seterusnya.
 *
 * Model hanya menerima awalan bila suaranya minimal 95 persen.
 * Kolom persen di bawah memperlihatkan angka sebenarnya.
 * ------------------------------------------------------------ */
WITH pasti AS (
    SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
    FROM (
        SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
        FROM public.sr_jaminan AS j
        WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
    ) AS jm
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
            COUNT(*) AS banyak
        FROM public.sr_sertipikat
        WHERE sertipikat_id IS NOT NULL
        GROUP BY 1
    ) AS ser
        ON ser.angka = jm.angka
    WHERE ser.banyak = 1
    GROUP BY 1
)
SELECT
    awalan,
    jumlah                                                  AS baris_pasti,
    ROUND(100.0 * jumlah / SUM(jumlah) OVER (), 2)          AS persen,
    CASE WHEN jumlah >= 0.95 * SUM(jumlah) OVER ()
         THEN 'DIPAKAI model'
         ELSE 'tidak dipakai'
    END                                                     AS keputusan
FROM pasti
ORDER BY jumlah DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * LAPORAN LENGKAP, persis seperti yang dihasilkan model.
 *
 * Inilah jawaban atas pertanyaan "benarkah hanya 8 baris".
 * Jumlah baris yang keluar di sini harus sama dengan yang tampil
 * di layar. Kolomnya sengaja diurutkan seperti laporan.
 *
 * Nama kolom di bawah sudah disesuaikan dengan hasil QUERY 1
 * pada database ini:
 *   sr_stok   sektor -> kd_sektor,  lokasi -> kd_lokasi
 *   sr_lokasi kode   -> kd_lv2
 *   sr_sektor kode   -> kd_proyek
 * Perhatikan sr_lokasi dan sr_sektor memakai nama yang berbeda
 * dari sr_stok. Model memang memilih nama per tabel, bukan satu
 * nama untuk semuanya.
 * ------------------------------------------------------------ */
WITH awalan_terpilih AS (
    SELECT awalan
    FROM (
        SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
        FROM (
            SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
            FROM public.sr_jaminan AS j
            WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
        ) AS jm
        INNER JOIN (
            SELECT
                REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
                MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
                COUNT(*) AS banyak
            FROM public.sr_sertipikat
            WHERE sertipikat_id IS NOT NULL
            GROUP BY 1
        ) AS ser ON ser.angka = jm.angka
        WHERE ser.banyak = 1
        GROUP BY 1
    ) AS pasti
    WHERE jumlah >= 0.95 * (SELECT SUM(jumlah) FROM (
        SELECT COUNT(*) AS jumlah
        FROM (
            SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
            FROM public.sr_jaminan AS j
            WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
        ) AS jm
        INNER JOIN (
            SELECT
                REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
                MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
                COUNT(*) AS banyak
            FROM public.sr_sertipikat
            WHERE sertipikat_id IS NOT NULL
            GROUP BY 1
        ) AS ser ON ser.angka = jm.angka
        WHERE ser.banyak = 1
        GROUP BY ser.awalan
    ) AS semua)
    ORDER BY jumlah DESC
    LIMIT 1
),
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE (
            stok.kd_perusahaan = 'SBKS'
            OR UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
          )
      AND (
            stok.flag_aktif = 'A'
            OR UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
          )
      /* Sektor = Semua Cluster, jadi cabang '*' selalu benar. */
      AND (
            UPPER(BTRIM(COALESCE(CAST(stok.kd_sektor AS TEXT), ''))) = '*'
            OR '*' = '*'
          )
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
),
jaminan_terpilih AS (
    SELECT
        jaminan.*,
        CASE
            WHEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
            ELSE awalan_terpilih.awalan
                 || BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
        END AS kunci_sertipikat
    FROM public.sr_jaminan AS jaminan
    CROSS JOIN awalan_terpilih
    WHERE jaminan.no_jaminan IS NOT NULL
      AND jaminan.no_lunas IS NULL
      AND jaminan.no_batal IS NULL
      /* Jenis Jaminan = Semua, jadi cabang '*' selalu benar. */
      AND (
            UPPER(BTRIM(COALESCE(CAST(jaminan.jenis_jaminan AS TEXT), ''))) = '*'
            OR '*' = '*'
          )
      AND jaminan.tgl_jaminan >= CAST('2023-07-01' AS DATE)
      AND jaminan.tgl_jaminan <= CAST('2026-09-18' AS DATE)
),
lokasi_ref AS MATERIALIZED (
    SELECT DISTINCT ON (kode) kode, deskripsi
    FROM (
        SELECT
            UPPER(BTRIM(COALESCE(CAST(lokasi.kd_lv2 AS TEXT), ''))) AS kode,
            BTRIM(COALESCE(CAST(lokasi.deskripsi AS TEXT), '')) AS deskripsi,
            lokasi.ctid AS urutan_fisik
        FROM public.sr_lokasi AS lokasi
    ) AS daftar
    ORDER BY kode, urutan_fisik
),
sektor_ref AS MATERIALIZED (
    SELECT DISTINCT ON (kode) kode, deskripsi
    FROM (
        SELECT
            UPPER(BTRIM(COALESCE(CAST(sektor.kd_proyek AS TEXT), ''))) AS kode,
            BTRIM(COALESCE(CAST(sektor.deskripsi AS TEXT), '')) AS deskripsi,
            sektor.ctid AS urutan_fisik
        FROM public.sr_sektor AS sektor
    ) AS daftar
    ORDER BY kode, urutan_fisik
),
ppjb_aktif AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    WHERE (
            ppjb.flag_aktif = 'A'
            OR UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
          )
      AND ppjb.parent_id IS NULL
),
pembeli_nasabah AS MATERIALIZED (
    SELECT
        BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        nasabah.nama AS nama
    FROM public.sr_pembeli_ppjb AS pembeli_ppjb
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
    WHERE (
            pembeli_ppjb.flag_aktif = 'Y'
            OR UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y'
          )
),
plafond_kpr AS MATERIALIZED (
    SELECT
        BTRIM(CAST(jadwal.ppjb_id AS TEXT)) AS kunci_ppjb,
        SUM(jadwal.jumlah) AS jumlah
    FROM public.sr_jadwal_angsuran AS jadwal
    WHERE UPPER(BTRIM(COALESCE(CAST(jadwal.flag_kpr AS TEXT), 'T'))) = 'Y'
    GROUP BY 1
),
hasil_dasar AS MATERIALIZED (
    SELECT
        BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
            || COALESCE(CAST(stok.nomor AS TEXT), '') AS "BLOK_NOMOR",
        nasabah.nama          AS "NAMA",
        jaminan.pengajuan     AS "PENGAJUAN",
        jaminan.tgl_pengajuan AS "TGL_PENGAJUAN",
        jaminan.no_jaminan    AS "NO_JAMINAN",
        jaminan.tgl_jaminan   AS "TGL_JAMINAN",
        jaminan.nama_bank     AS "NAMA_BANK",
        jaminan.alamat_bank   AS "ALAMAT_BANK",
        jaminan.jenis_jaminan AS "JENIS_JAMINAN",
        jaminan.nama_ambil    AS "NAMA_AMBIL",
        jaminan.tgl_ambil     AS "TGL_AMBIL",
        stok.kd_perusahaan    AS "KD_PERUSAHAAN",
        lokasi_ref.deskripsi  AS "NAMA_LOKASI",
        sektor_ref.deskripsi  AS "NAMA_SEKTOR",
        COALESCE(plafond_kpr.jumlah, 0) AS "PLAFOND_KPR",
        stok.blok  AS "BLOK",
        stok.nomor AS "NOMOR"
    FROM jaminan_terpilih AS jaminan
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) = jaminan.kunci_sertipikat
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
    INNER JOIN ppjb_aktif AS ppjb
        ON ppjb.kunci_stok = stok.kunci_stok
    INNER JOIN pembeli_nasabah AS nasabah
        ON nasabah.kunci_ppjb = ppjb.kunci_ppjb
    LEFT JOIN lokasi_ref
        ON lokasi_ref.kode = UPPER(BTRIM(COALESCE(CAST(stok.kd_lokasi AS TEXT), '')))
    LEFT JOIN sektor_ref
        ON sektor_ref.kode = UPPER(BTRIM(COALESCE(CAST(stok.kd_sektor AS TEXT), '')))
    LEFT JOIN plafond_kpr
        ON plafond_kpr.kunci_ppjb = ppjb.kunci_ppjb
    WHERE sertipikat.stok_id IS NOT NULL
    /* STATUS AJB = Semua, jadi tidak ada saringan akta. */
)
SELECT
    ROW_NUMBER() OVER (
        ORDER BY "BLOK", "NOMOR", "NAMA", "TGL_JAMINAN", "NO_JAMINAN"
    ) AS "NO",
    "BLOK_NOMOR", "NAMA", "PENGAJUAN", "TGL_PENGAJUAN",
    "NO_JAMINAN", "TGL_JAMINAN", "NAMA_BANK", "ALAMAT_BANK",
    "PLAFOND_KPR", "JENIS_JAMINAN", "NAMA_AMBIL", "TGL_AMBIL",
    "NAMA_LOKASI", "NAMA_SEKTOR"
FROM hasil_dasar
WHERE (
        (
            BTRIM(COALESCE(CAST("BLOK" AS TEXT), '')) || '/'
            || COALESCE(CAST("NOMOR" AS TEXT), '') >= 'A'
            AND
            BTRIM(COALESCE(CAST("BLOK" AS TEXT), '')) || '/'
            || COALESCE(CAST("NOMOR" AS TEXT), '') <= 'ZZ'
        )
        OR ("BLOK" >= 'ZZ' AND "BLOK" <= 'ZZ')
      )
ORDER BY "BLOK", "NOMOR", "NAMA", "TGL_JAMINAN", "NO_JAMINAN";


/* ------------------------------------------------------------
 * QUERY 4
 * CORONG PENYUSUTAN BARIS.
 *
 * Memperlihatkan di tahap mana baris berkurang, dari seluruh isi
 * sr_jaminan sampai tinggal yang tampil di layar. Inilah yang
 * menjawab "kenapa cuma 8", bukan sekadar "benar 8".
 *
 * Perhatikan dua hal saat membacanya:
 *   - Tahap "sambung pembeli" BISA MENAMBAH baris, karena satu
 *     PPJB boleh punya lebih dari satu pembeli dan laporan desktop
 *     memang menampilkan satu baris untuk tiap pembeli.
 *   - Tahap yang menyusut paling tajam adalah penyebab utamanya.
 * ------------------------------------------------------------ */
WITH awalan_terpilih AS (
    SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
    FROM (
        SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
        FROM public.sr_jaminan AS j
        WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
    ) AS jm
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
            COUNT(*) AS banyak
        FROM public.sr_sertipikat
        WHERE sertipikat_id IS NOT NULL
        GROUP BY 1
    ) AS ser ON ser.angka = jm.angka
    WHERE ser.banyak = 1
    GROUP BY 1
    ORDER BY 2 DESC
    LIMIT 1
),
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE (stok.kd_perusahaan = 'SBKS'
           OR UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS')
      AND (stok.flag_aktif = 'A'
           OR UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A')
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
),
ppjb_aktif AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    WHERE (ppjb.flag_aktif = 'A'
           OR UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A')
      AND ppjb.parent_id IS NULL
),
pembeli_nasabah AS MATERIALIZED (
    SELECT
        BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        nasabah.nama AS nama
    FROM public.sr_pembeli_ppjb AS pembeli_ppjb
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
    WHERE (pembeli_ppjb.flag_aktif = 'Y'
           OR UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y')
),

/* Tahap 1, seluruh isi sr_jaminan. */
t1 AS (SELECT * FROM public.sr_jaminan),

/* Tahap 2, hanya yang siap tampil: ada nomor, belum lunas, belum batal. */
t2 AS (
    SELECT * FROM t1
    WHERE no_jaminan IS NOT NULL AND no_lunas IS NULL AND no_batal IS NULL
),

/* Tahap 3, disaring rentang TGL BANK 01-07-2023 s/d 18-09-2026. */
t3 AS (
    SELECT * FROM t2
    WHERE tgl_jaminan >= CAST('2023-07-01' AS DATE)
      AND tgl_jaminan <= CAST('2026-09-18' AS DATE)
),

/* Tahap 4, kunci sertipikat disusun ulang, lalu disambung ke sr_sertipikat. */
t4 AS (
    SELECT t3.*, sertipikat.stok_id
    FROM t3
    CROSS JOIN awalan_terpilih
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(t3.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(t3.sertipikat_id AS TEXT))
               ELSE awalan_terpilih.awalan || BTRIM(CAST(t3.sertipikat_id AS TEXT))
           END
    WHERE sertipikat.stok_id IS NOT NULL
),

/* Tahap 5, disambung ke stok UNIT SBKS yang aktif. Di sinilah unit menyaring. */
t5 AS (
    SELECT t4.*, stok.kunci_stok, stok.blok, stok.nomor
    FROM t4
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(t4.stok_id AS TEXT))
),

/* Tahap 6, disambung ke PPJB aktif. */
t6 AS (
    SELECT t5.*, ppjb.kunci_ppjb
    FROM t5
    INNER JOIN ppjb_aktif AS ppjb ON ppjb.kunci_stok = t5.kunci_stok
),

/* Tahap 7, disambung ke pembeli. BISA MENAMBAH baris. */
t7 AS (
    SELECT t6.*, nasabah.nama
    FROM t6
    INNER JOIN pembeli_nasabah AS nasabah ON nasabah.kunci_ppjb = t6.kunci_ppjb
),

/* Tahap 8, saringan BLOK A s/d ZZ. */
t8 AS (
    SELECT * FROM t7
    WHERE (
            (BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || COALESCE(CAST(nomor AS TEXT), '') >= 'A'
             AND BTRIM(COALESCE(CAST(blok AS TEXT), '')) || '/'
             || COALESCE(CAST(nomor AS TEXT), '') <= 'ZZ')
            OR (blok >= 'ZZ' AND blok <= 'ZZ')
          )
)
SELECT 1 AS urut, 'Tahap 1  seluruh sr_jaminan'                      AS tahap, COUNT(*) AS baris FROM t1
UNION ALL SELECT 2, 'Tahap 2  siap tampil (ada nomor, belum lunas/batal)', COUNT(*) FROM t2
UNION ALL SELECT 3, 'Tahap 3  + rentang TGL BANK',                      COUNT(*) FROM t3
UNION ALL SELECT 4, 'Tahap 4  + ketemu sertipikatnya',                  COUNT(*) FROM t4
UNION ALL SELECT 5, 'Tahap 5  + stok UNIT SBKS yang aktif',             COUNT(*) FROM t5
UNION ALL SELECT 6, 'Tahap 6  + PPJB aktif',                            COUNT(*) FROM t6
UNION ALL SELECT 7, 'Tahap 7  + pembeli (bisa menambah baris)',         COUNT(*) FROM t7
UNION ALL SELECT 8, 'Tahap 8  + saringan BLOK  = YANG TAMPIL',          COUNT(*) FROM t8
ORDER BY urut;


/* ------------------------------------------------------------
 * QUERY 5
 * Pembanding: sebaran tahun TGL_JAMINAN untuk stok UNIT SBKS,
 * TANPA saringan tanggal.
 *
 * Gunanya memastikan apakah angka kecil itu wajar. Kalau ternyata
 * SBKS memang hanya punya sedikit jaminan di rentang 2023-2026
 * sementara tahun lain banyak, berarti 8 itu benar dan yang perlu
 * diubah cuma rentang tanggalnya, bukan modelnya.
 * ------------------------------------------------------------ */
WITH awalan_terpilih AS (
    SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
    FROM (
        SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
        FROM public.sr_jaminan AS j
        WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
    ) AS jm
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
            COUNT(*) AS banyak
        FROM public.sr_sertipikat
        WHERE sertipikat_id IS NOT NULL
        GROUP BY 1
    ) AS ser ON ser.angka = jm.angka
    WHERE ser.banyak = 1
    GROUP BY 1
    ORDER BY 2 DESC
    LIMIT 1
),
stok_terpilih AS (
    SELECT BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE (stok.kd_perusahaan = 'SBKS'
           OR UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS')
      AND (stok.flag_aktif = 'A'
           OR UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A')
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
)
SELECT
    CASE WHEN jaminan.tgl_jaminan IS NULL
         THEN '(TANGGAL KOSONG)'
         ELSE CAST(EXTRACT(YEAR FROM jaminan.tgl_jaminan) AS TEXT)
    END AS tahun_tgl_jaminan,
    COUNT(*) AS baris,
    CASE WHEN jaminan.tgl_jaminan >= CAST('2023-07-01' AS DATE)
          AND jaminan.tgl_jaminan <= CAST('2026-09-18' AS DATE)
         THEN 'masuk rentang layar'
         ELSE '-'
    END AS keterangan
FROM public.sr_jaminan AS jaminan
CROSS JOIN awalan_terpilih
INNER JOIN public.sr_sertipikat AS sertipikat
    ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
       CASE
           WHEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
           THEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
           ELSE awalan_terpilih.awalan || BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
       END
INNER JOIN stok_terpilih AS stok
    ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
WHERE jaminan.no_jaminan IS NOT NULL
  AND jaminan.no_lunas IS NULL
  AND jaminan.no_batal IS NULL
GROUP BY 1, 3
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 6
 * SEBERAPA BISA DIPERCAYA BARIS YANG TAMPIL.
 *
 * sr_jaminan.sertipikat_id kehilangan awalannya saat migrasi, dan
 * model menambalnya dengan satu awalan hasil suara. Cara itu benar
 * untuk hampir semua baris, tetapi TIDAK BISA benar untuk angka
 * yang dipakai dua keluarga awalan sekaligus; untuk angka semacam
 * itu model terpaksa memilih satu.
 *
 * Query ini memisahkan baris yang tampil menjadi dua golongan:
 *   PASTI  angkanya hanya ada pada satu sertipikat, tidak mungkin salah
 *   RANCU  angkanya ada di lebih dari satu keluarga, bisa jadi milik unit lain
 *
 * Kalau semuanya PASTI, jumlah yang tampil benar apa adanya.
 * Kalau ada yang RANCU, baris itulah yang perlu dicocokkan ke
 * aplikasi desktop lebih dulu.
 * ------------------------------------------------------------ */
WITH awalan_terpilih AS (
    SELECT ser.awalan AS awalan, COUNT(*) AS jumlah
    FROM (
        SELECT BTRIM(CAST(j.sertipikat_id AS TEXT)) AS angka
        FROM public.sr_jaminan AS j
        WHERE BTRIM(CAST(j.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
    ) AS jm
    INNER JOIN (
        SELECT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
            MIN(REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')) AS awalan,
            COUNT(*) AS banyak
        FROM public.sr_sertipikat
        WHERE sertipikat_id IS NOT NULL
        GROUP BY 1
    ) AS ser ON ser.angka = jm.angka
    WHERE ser.banyak = 1
    GROUP BY 1
    ORDER BY 2 DESC
    LIMIT 1
),
angka_sertipikat AS (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '^[^0-9]+', '') AS angka,
        COUNT(*) AS banyak_keluarga,
        STRING_AGG(DISTINCT
            REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', ''),
            ', ' ORDER BY REGEXP_REPLACE(BTRIM(CAST(sertipikat_id AS TEXT)), '[0-9]+$', '')
        ) AS daftar_awalan
    FROM public.sr_sertipikat
    WHERE sertipikat_id IS NOT NULL
    GROUP BY 1
),
stok_terpilih AS (
    SELECT stok.*, BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE (stok.kd_perusahaan = 'SBKS'
           OR UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS')
      AND (stok.flag_aktif = 'A'
           OR UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A')
      AND stok.blok IS NOT NULL
      AND stok.nomor IS NOT NULL
),
ppjb_aktif AS MATERIALIZED (
    SELECT
        BTRIM(CAST(ppjb.stok_id AS TEXT)) AS kunci_stok,
        BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    WHERE (ppjb.flag_aktif = 'A'
           OR UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A')
      AND ppjb.parent_id IS NULL
),
pembeli_nasabah AS MATERIALIZED (
    SELECT
        BTRIM(CAST(pembeli_ppjb.ppjb_id AS TEXT)) AS kunci_ppjb,
        nasabah.nama AS nama
    FROM public.sr_pembeli_ppjb AS pembeli_ppjb
    INNER JOIN public.sr_nasabah AS nasabah
        ON BTRIM(CAST(nasabah.nasabah_id AS TEXT))
         = BTRIM(CAST(pembeli_ppjb.nasabah_id AS TEXT))
    WHERE (pembeli_ppjb.flag_aktif = 'Y'
           OR UPPER(BTRIM(COALESCE(CAST(pembeli_ppjb.flag_aktif AS TEXT), ''))) = 'Y')
),
tampil AS (
    SELECT
        BTRIM(COALESCE(CAST(stok.blok AS TEXT), '')) || '/'
            || COALESCE(CAST(stok.nomor AS TEXT), '') AS blok_nomor,
        nasabah.nama        AS nama,
        jaminan.no_jaminan  AS no_jaminan,
        jaminan.tgl_jaminan AS tgl_jaminan,
        jaminan.nama_bank   AS nama_bank,
        BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) AS angka,
        stok.blok  AS blok,
        stok.nomor AS nomor
    FROM public.sr_jaminan AS jaminan
    CROSS JOIN awalan_terpilih
    INNER JOIN public.sr_sertipikat AS sertipikat
        ON BTRIM(CAST(sertipikat.sertipikat_id AS TEXT)) =
           CASE
               WHEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
               THEN BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
               ELSE awalan_terpilih.awalan || BTRIM(CAST(jaminan.sertipikat_id AS TEXT))
           END
    INNER JOIN stok_terpilih AS stok
        ON stok.kunci_stok = BTRIM(CAST(sertipikat.stok_id AS TEXT))
    INNER JOIN ppjb_aktif AS ppjb
        ON ppjb.kunci_stok = stok.kunci_stok
    INNER JOIN pembeli_nasabah AS nasabah
        ON nasabah.kunci_ppjb = ppjb.kunci_ppjb
    WHERE jaminan.no_jaminan IS NOT NULL
      AND jaminan.no_lunas IS NULL
      AND jaminan.no_batal IS NULL
      AND jaminan.tgl_jaminan >= CAST('2023-07-01' AS DATE)
      AND jaminan.tgl_jaminan <= CAST('2026-09-18' AS DATE)
      AND sertipikat.stok_id IS NOT NULL
)
SELECT
    CASE WHEN COALESCE(angka_sertipikat.banyak_keluarga, 1) = 1
         THEN 'PASTI'
         ELSE 'RANCU, angka dipakai lebih dari satu keluarga'
    END AS golongan,
    COALESCE(angka_sertipikat.daftar_awalan, '(tanpa awalan)') AS awalan_yang_bersaing,
    tampil.blok_nomor,
    tampil.nama,
    tampil.no_jaminan,
    tampil.tgl_jaminan,
    tampil.nama_bank
FROM tampil
LEFT JOIN angka_sertipikat ON angka_sertipikat.angka = tampil.angka
WHERE (
        (BTRIM(COALESCE(CAST(tampil.blok AS TEXT), '')) || '/'
         || COALESCE(CAST(tampil.nomor AS TEXT), '') >= 'A'
         AND BTRIM(COALESCE(CAST(tampil.blok AS TEXT), '')) || '/'
         || COALESCE(CAST(tampil.nomor AS TEXT), '') <= 'ZZ')
        OR (tampil.blok >= 'ZZ' AND tampil.blok <= 'ZZ')
      )
ORDER BY golongan DESC, tampil.blok, tampil.nomor, tampil.nama;
