/* ============================================================
 * APAKAH sr_sertipikat_idk BERHENTI DIMIGRASIKAN
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * SEMUA DUGAAN SEBELUMNYA SUDAH MATI
 *
 * Matriks sembilan kolom tanggal memberi hasil ini, pada 4.022
 * baris SBKS yang siap:
 *
 *   ser.tgl_sertipikat    terisi 8.828   dalam rentang 581
 *   ser.tgl_input_ser     terisi 4.104   dalam rentang 129
 *   ser.tgl_input_gabung  terisi 5.294   dalam rentang 105
 *   idk.tgl_ser_pisah     terisi 3.499   dalam rentang   2
 *   idk.tgl_su_pisah      terisi 3.500   dalam rentang   1
 *   idk.tgl_input         terisi 8.867   dalam rentang   0
 *   idk.tgl_mohon_pisah   terisi 6.612   dalam rentang   0
 *   idk.tgl_ser_idk       terisi 8.878   dalam rentang   0
 *   idk.tgl_su_induk      terisi 8.876   dalam rentang   0
 *
 * Dugaan bahwa desktop memakai kolom tanggal pada tabel induk
 * MATI: keempat kolom idk memberi NOL, bukan 753.
 *
 * Dugaan arti centang Apartemen juga MATI: kolom dengan APT
 * dibuang dan tanpa dibuang memberi angka yang sama persis pada
 * setiap baris matriks. Seluruh baris yang masuk rentang memang
 * bukan apartemen.
 *
 * ------------------------------------------------------------
 * YANG TERSISA, DAN INI PETUNJUK TERKUATNYA
 *
 * Sebaran per tahun memperlihatkan sesuatu yang lain sama
 * sekali. Untuk 4.022 baris SBKS itu:
 *
 *   tahun     ser.tgl_input_ser    idk.tgl_input
 *   2022                    509              535
 *   2023                    107                0
 *   2024                     22                0
 *   2025                      0                0
 *   2026                      0                0
 *
 * Keduanya BERHENTI. idk berhenti di 2022, sertipikat berhenti
 * di 2024. Padahal desktop menampilkan baris bertanggal 2023
 * ke atas dengan lancar.
 *
 * Artinya barisnya bukan tersaring, melainkan MEMANG TIDAK ADA.
 * Ini pola yang sudah kita temui pada sr_akta, sr_jaminan, dan
 * sr_peralihan yang berhenti serentak Februari 2024, dan
 * sr_sertipikat_idk memang satu rombongan dengan ketiganya
 * karena kolom kuncinya sama-sama dijadikan angka.
 *
 * QUERY 3 adalah pembuktiannya yang paling langsung.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * KEMUTAKHIRAN sr_sertipikat_idk untuk seluruh unit, per tahun.
 * Tahun sesudah 2100 dibuang sebab isinya jelas salah ketik.
 * ------------------------------------------------------------ */
SELECT
    SUBSTRING(CAST(tgl_input AS TEXT) FROM 1 FOR 4) AS tahun,
    COUNT(*)                                        AS baris
FROM public.sr_sertipikat_idk
WHERE tgl_input IS NOT NULL
  AND SUBSTRING(CAST(tgl_input AS TEXT) FROM 1 FOR 4) BETWEEN '1900' AND '2100'
GROUP BY 1
ORDER BY 1 DESC
LIMIT 15;


/* ------------------------------------------------------------
 * QUERY 2
 * KEMUTAKHIRAN sr_sertipikat, tabel pasangannya, untuk seluruh
 * unit. Kalau tabel ini masih sampai 2026 sedangkan idk mentok
 * 2022, berarti yang tertinggal memang hanya idk.
 * ------------------------------------------------------------ */
SELECT
    SUBSTRING(CAST(tgl_input_ser AS TEXT) FROM 1 FOR 4) AS tahun,
    COUNT(*)                                            AS baris
FROM public.sr_sertipikat
WHERE tgl_input_ser IS NOT NULL
  AND SUBSTRING(CAST(tgl_input_ser AS TEXT) FROM 1 FOR 4) BETWEEN '1900' AND '2100'
GROUP BY 1
ORDER BY 1 DESC
LIMIT 15;


/* ------------------------------------------------------------
 * QUERY 3
 * PEMBUKTIAN LANGSUNG.
 *
 * Diambil dari sisi sr_sertipikat, bukan dari sisi idk, supaya
 * kelihatan berapa sertipikat SBKS yang MEMENUHI SYARAT tetapi
 * tidak punya pasangan di sr_sertipikat_idk.
 *
 * Kalau kolom tanpa_induk berisi ratusan, maka baris itulah
 * yang hilang dari laporan, dan sebabnya bukan saringan
 * melainkan tabel induknya yang belum termigrasi.
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
induk AS MATERIALIZED (
    SELECT DISTINCT
        'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat_idk AS i
),
sertipikat_sbks AS MATERIALIZED (
    SELECT
        BTRIM(CAST(s.sertipikat_id AS TEXT))              AS kunci,
        SUBSTRING(CAST(s.tgl_input_ser AS TEXT) FROM 1 FOR 10) AS tgl_ser,
        SUBSTRING(CAST(s.tgl_sertipikat AS TEXT) FROM 1 FOR 10) AS tgl_sertipikat
    FROM public.sr_sertipikat AS s
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) NOT IN ('APT','KTR')
)
SELECT
    COUNT(*)                                             AS sertipikat_sbks,
    COUNT(*) FILTER (WHERE induk.kunci IS NOT NULL)      AS punya_induk,
    COUNT(*) FILTER (WHERE induk.kunci IS NULL)          AS tanpa_induk,
    COUNT(*) FILTER (
        WHERE tgl_ser BETWEEN '2023-07-01' AND '2026-09-23'
    )                                                    AS dalam_rentang_tgl_ser,
    COUNT(*) FILTER (
        WHERE tgl_ser BETWEEN '2023-07-01' AND '2026-09-23'
          AND induk.kunci IS NULL
    )                                                    AS dalam_rentang_tanpa_induk,
    COUNT(*) FILTER (
        WHERE tgl_sertipikat BETWEEN '2023-07-01' AND '2026-09-23'
    )                                                    AS dalam_rentang_tgl_sertipikat,
    COUNT(*) FILTER (
        WHERE tgl_sertipikat BETWEEN '2023-07-01' AND '2026-09-23'
          AND induk.kunci IS NULL
    )                                                    AS rentang_sertipikat_tanpa_induk
FROM sertipikat_sbks
LEFT JOIN induk ON induk.kunci = sertipikat_sbks.kunci;
