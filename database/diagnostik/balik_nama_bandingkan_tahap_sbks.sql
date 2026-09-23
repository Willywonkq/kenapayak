/* ============================================================
 * CORONG DAFTAR PENGAJUAN SERTIPIKAT BALIK NAMA, UNIT SBKS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN
 *
 *   desktop   966 baris
 *   web       jauh lebih sedikit
 *
 * Saringan: unit SBKS, blok A sampai Z, sektor semua,
 * Tgl Input AJB 01-07-2023 sampai 23-09-2026.
 *
 * ------------------------------------------------------------
 * DUA TERSANGKA, KEDUANYA DI KODE
 *
 * TERSANGKA 1: SARINGAN BLOK, DUA KESALAHAN SEKALIGUS.
 *
 * Model menyusun syarat bloknya begini:
 *
 *     ( blok || '/' || nomor >= :blok_awal_unit
 *       AND blok || '/' || nomor <= :blok_akhir_unit )
 *     OR
 *     ( blok >= :blok_akhir_blok_min
 *       AND blok <= :blok_akhir_blok_max )
 *
 * Pertama, kedua penanda pada cabang kedua sama-sama diisi blok
 * AKHIR, bukan awal dan akhir, sehingga cabang itu berarti
 * blok = 'Z' persis.
 *
 * Kedua, dan ini yang menentukan, batas atasnya tetap 'Z'.
 * Dalam perbandingan teks 'ZBJ' LEBIH BESAR daripada 'Z',
 * sebab 'Z' adalah awalan yang lebih pendek. Jadi blok seperti
 * ZBJ atau ZCL terbuang pada kedua cabang sekaligus, padahal
 * unit SBKS punya banyak blok berawalan Z.
 *
 * Model Daftar Sertipikat Pecahan tidak kena karena mengubah
 * blok akhir 'Z' menjadi 'ZZ' lebih dulu, dan itu memang
 * perilaku desktopnya: pada laporan pecahan, masukan 'A' sampai
 * 'Z' dicetak sebagai "BLOK : A s/d ZZ" di kepala laporannya.
 *
 * TERSANGKA 2: SAMBUNGAN WAJIB KE TABEL INDUK.
 *
 * Laporan disusun mulai dari sr_sertipikat_idk dengan INNER
 * JOIN, padahal tabel itu sudah terbukti kehilangan 4.242 dari
 * 27.550 baris dan TIDAK PUNYA SATU PUN baris sesudah 2024.
 * Rentang laporan ini justru 2023 sampai 2026. Kolom yang
 * diambil dari tabel itu hanya keterangan tambahan seperti
 * nama PT dan nomor induk, bukan penentu ada tidaknya baris.
 *
 * QUERY 1 memisahkan keduanya.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * CORONG. Perhatikan tiga pasang angka:
 *
 *   t6_blok_cara_model  lawan  t6_blok_cara_benar
 *       selisihnya = baris yang hilang karena saringan blok
 *
 *   t7_dengan_idk       lawan  t6_blok_cara_benar
 *       selisihnya = baris yang hilang karena tabel induk
 *
 *   t7_sertipikat_unik
 *       kalau lebih kecil daripada t7_dengan_idk, berarti
 *       sambungan ke induk juga MENGGANDAKAN sebagian baris
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit
),
akta AS MATERIALIZED (
    SELECT
        CASE
            WHEN BTRIM(CAST(a.sertipikat_id AS TEXT)) !~ '^[0-9]+$'
            THEN BTRIM(CAST(a.sertipikat_id AS TEXT))
            WHEN BTRIM(CAST(a.ppjb_id AS TEXT)) ~ '^[^0-9]+[0-9]+$'
            THEN REGEXP_REPLACE(BTRIM(CAST(a.ppjb_id AS TEXT)), '[0-9]+$', '')
                 || BTRIM(CAST(a.sertipikat_id AS TEXT))
            ELSE BTRIM(CAST(a.sertipikat_id AS TEXT))
        END AS kunci
    FROM public.sr_akta AS a
    WHERE SUBSTRING(CAST(a.tgl_input AS TEXT) FROM 1 FOR 10)
              BETWEEN '2023-07-01' AND '2026-09-23'
),
ser AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci,
        BTRIM(CAST(s.stok_id AS TEXT))       AS kunci_stok,
        UPPER(BTRIM(COALESCE(CAST(s.status_blk_nm AS TEXT), ''))) AS status_blk,
        (s.status_blk_nm IS NULL)            AS status_kosong,
        (s.stok_id IS NOT NULL)              AS ada_stok_id
    FROM public.sr_sertipikat AS s
),
stok AS MATERIALIZED (
    SELECT
        BTRIM(CAST(st.stok_id AS TEXT)) AS kunci_stok,
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) AS unit,
        UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), '')))  AS blok,
        UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), ''))) AS nomor,
        (st.blok IS NOT NULL AND st.nomor IS NOT NULL)     AS blok_nomor_ada
    FROM public.sr_stok AS st
    CROSS JOIN kolom AS k
),
punya_induk AS MATERIALIZED (
    SELECT DISTINCT 'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat_idk AS i
),
banyak_induk AS MATERIALIZED (
    SELECT
        'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci,
        COUNT(*) AS jumlah
    FROM public.sr_sertipikat_idk AS i
    GROUP BY 1
),
gabung AS (
    SELECT
        akta.kunci,
        ser.kunci      AS ser_kunci,
        stok.kunci_stok AS stok_kunci,
        stok.unit, stok.blok, stok.nomor, stok.blok_nomor_ada,
        ser.status_blk, ser.status_kosong, ser.ada_stok_id,
        punya_induk.kunci AS induk,
        COALESCE(banyak_induk.jumlah, 0) AS jumlah_induk
    FROM akta
    LEFT JOIN ser  ON ser.kunci = akta.kunci
    LEFT JOIN stok ON stok.kunci_stok = ser.kunci_stok
    LEFT JOIN punya_induk  ON punya_induk.kunci = akta.kunci
    LEFT JOIN banyak_induk ON banyak_induk.kunci = akta.kunci
)
SELECT
    COUNT(*)                                                AS t1_akta_dalam_tanggal,
    COUNT(*) FILTER (WHERE ser_kunci IS NOT NULL)           AS t2_sertipikat_ketemu,
    COUNT(*) FILTER (WHERE ser_kunci IS NOT NULL
                       AND (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id)                     AS t3_belum_balik_nama,
    COUNT(*) FILTER (WHERE (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id AND unit = 'SBKS')   AS t4_unit_sbks,
    COUNT(*) FILTER (WHERE (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id AND unit = 'SBKS'
                       AND blok_nomor_ada)                  AS t5_blok_nomor_ada,
    COUNT(*) FILTER (WHERE (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id AND unit = 'SBKS'
                       AND blok_nomor_ada
                       AND ((blok || '/' || nomor BETWEEN 'A' AND 'Z')
                            OR (blok >= 'Z' AND blok <= 'Z')))
                                                            AS t6_blok_cara_model,
    COUNT(*) FILTER (WHERE (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id AND unit = 'SBKS'
                       AND blok_nomor_ada
                       AND ((blok || '/' || nomor BETWEEN 'A' AND 'ZZ')
                            OR (blok BETWEEN 'A' AND 'ZZ')))
                                                            AS t6_blok_cara_benar,
    COUNT(*) FILTER (WHERE (status_blk = 'T' OR status_kosong)
                       AND ada_stok_id AND unit = 'SBKS'
                       AND blok_nomor_ada
                       AND ((blok || '/' || nomor BETWEEN 'A' AND 'ZZ')
                            OR (blok BETWEEN 'A' AND 'ZZ'))
                       AND induk IS NOT NULL)               AS t7_sertipikat_unik,
    SUM(CASE WHEN (status_blk = 'T' OR status_kosong)
                  AND ada_stok_id AND unit = 'SBKS'
                  AND blok_nomor_ada
                  AND ((blok || '/' || nomor BETWEEN 'A' AND 'ZZ')
                       OR (blok BETWEEN 'A' AND 'ZZ'))
             THEN jumlah_induk ELSE 0 END)                  AS t7_dengan_idk
FROM gabung;


/* ------------------------------------------------------------
 * QUERY 2
 * BLOK APA SAJA YANG TERBUANG oleh saringan yang salah pasang.
 * Kalau daftarnya berisi blok berawalan Z, tersangka 1 terbukti.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT
        (SELECT column_name FROM information_schema.columns
          WHERE table_schema='public' AND table_name='sr_stok'
            AND column_name = ANY (ARRAY['kd_perusahaan','kd_unit','kd_pt'])
          ORDER BY array_position(ARRAY['kd_perusahaan','kd_unit','kd_pt'], column_name)
          LIMIT 1) AS stok_unit
)
SELECT
    UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) AS blok,
    COUNT(*)                                          AS baris_stok
FROM public.sr_stok AS st
CROSS JOIN kolom AS k
WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
  AND st.blok IS NOT NULL
  AND st.nomor IS NOT NULL
  AND NOT (
        (UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) || '/'
         || UPPER(BTRIM(COALESCE(CAST(st.nomor AS TEXT), ''))))
             BETWEEN 'A' AND 'Z'
        OR UPPER(BTRIM(COALESCE(CAST(st.blok AS TEXT), ''))) = 'Z'
      )
GROUP BY 1
ORDER BY 2 DESC, 1
LIMIT 30;


/* ------------------------------------------------------------
 * QUERY 3
 * SEBARAN TAHUN sr_akta untuk SBKS, untuk memastikan sampai
 * kapan tabelnya terisi. Catatan terdahulu menyebut sr_akta
 * berhenti Februari 2024.
 * ------------------------------------------------------------ */
SELECT
    SUBSTRING(CAST(tgl_input AS TEXT) FROM 1 FOR 4) AS tahun,
    COUNT(*)                                        AS baris
FROM public.sr_akta
WHERE tgl_input IS NOT NULL
  AND SUBSTRING(CAST(tgl_input AS TEXT) FROM 1 FOR 4) BETWEEN '1900' AND '2100'
GROUP BY 1
ORDER BY 1 DESC
LIMIT 12;
