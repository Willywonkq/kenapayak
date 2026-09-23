/* ============================================================
 * MENCARI KOLOM TANGGAL YANG DIPAKAI DESKTOP
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL. Dijalankan di POSTGRESQL.
 *
 * ------------------------------------------------------------
 * HASIL CORONG SEBELUMNYA
 *
 *   23.308  baris sr_sertipikat_idk
 *   23.304  kuncinya ketemu di sr_sertipikat
 *    8.892  stoknya SBKS dan aktif
 *    4.022  bukan APT dan bukan KTR
 *      129  masuk rentang tanggal        <-- terjun di sini
 *      129  yang tampil
 *
 * DUA DUGAAN LAMA SUDAH MATI:
 *
 * A. Awalan kunci. Dengan DBPSA- ketemu 23.304 dari batas atas
 *    23.307, jadi hanya 4 baris yang hilang karena awalan.
 *    Bukan penyebab selisih. Tetap perlu dicatat bahwa 19.465
 *    dari 23.308 baris angkanya dipakai kedua keluarga, jadi
 *    penyusunan ulangnya berisiko keliru menautkan, walaupun
 *    jumlahnya kebetulan tidak berubah.
 *
 * B. COALESCE(tgl_input_gabung, tgl_input_ser). Dihitung dengan
 *    tgl_input_ser saja hasilnya SAMA PERSIS, 129. Jadi bukan
 *    urutan COALESCE-nya yang salah.
 *
 * ------------------------------------------------------------
 * DUGAAN BARU
 *
 * Kalau bukan cara memilihnya, berarti KOLOMNYA yang berbeda.
 * Model menyaring dengan tanggal input pada sr_sertipikat,
 * yaitu tabel sertipikat pecahannya. Desktop mungkin menyaring
 * dengan tanggal pada sr_sertipikat_idk, yaitu tabel induknya.
 *
 * Petunjuknya ada pada layar: baris yang tampil di web
 * bertanggal 2021, sedangkan di desktop 2023. Kalau keduanya
 * menyaring kolom yang sama, baris 2021 tidak mungkin lolos
 * batas bawah 01-07-2023. Jadi kolom yang disaring memang bukan
 * kolom yang ditampilkan.
 *
 * Dugaan kedua, lebih kecil tetapi perlu dipastikan: arti
 * centang Apartemen. Model membuang APT dan KTR ketika centang
 * itu kosong, dan itu memangkas 8.892 menjadi 4.022, hampir
 * separuh. Kalau di desktop centang kosong berarti SEMUA jenis,
 * dasarnya bukan 4.022 melainkan 8.892.
 *
 * QUERY 1 mencoba semua kemungkinan sekaligus.
 * ------------------------------------------------------------ */


/* ------------------------------------------------------------
 * QUERY 1
 * MATRIKS. Tiap kolom tanggal dihitung dua kali: dengan APT dan
 * KTR dibuang seperti model sekarang, dan tanpa dibuang.
 *
 * Yang dicari: angka yang mendekati jumlah baris desktop.
 * Kolom yang dipakai model sekarang adalah ser_tgl_input_ser
 * dengan dasar 4.022, yang menghasilkan 129.
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
siap AS MATERIALIZED (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) AS jenis,
        to_jsonb(s) ->> 'tgl_input_ser'     AS ser_tgl_input_ser,
        to_jsonb(s) ->> 'tgl_input_gabung'  AS ser_tgl_input_gabung,
        to_jsonb(s) ->> 'tgl_sertipikat'    AS ser_tgl_sertipikat,
        to_jsonb(i) ->> 'tgl_input'         AS idk_tgl_input,
        to_jsonb(i) ->> 'tgl_ser_idk'       AS idk_tgl_ser_idk,
        to_jsonb(i) ->> 'tgl_mohon_pisah'   AS idk_tgl_mohon_pisah,
        to_jsonb(i) ->> 'tgl_ser_pisah'     AS idk_tgl_ser_pisah,
        to_jsonb(i) ->> 'tgl_su_pisah'      AS idk_tgl_su_pisah,
        to_jsonb(i) ->> 'tgl_su_induk'      AS idk_tgl_su_induk
    FROM public.sr_sertipikat_idk AS i
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT)) =
           'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT))
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
),
panjang AS (
    SELECT jenis, nama, nilai
    FROM siap,
    LATERAL (VALUES
        ('ser.tgl_input_ser',    ser_tgl_input_ser),
        ('ser.tgl_input_gabung', ser_tgl_input_gabung),
        ('ser.tgl_sertipikat',   ser_tgl_sertipikat),
        ('idk.tgl_input',        idk_tgl_input),
        ('idk.tgl_ser_idk',      idk_tgl_ser_idk),
        ('idk.tgl_mohon_pisah',  idk_tgl_mohon_pisah),
        ('idk.tgl_ser_pisah',    idk_tgl_ser_pisah),
        ('idk.tgl_su_pisah',     idk_tgl_su_pisah),
        ('idk.tgl_su_induk',     idk_tgl_su_induk)
    ) AS t(nama, nilai)
)
SELECT
    nama                                              AS kolom_tanggal,
    COUNT(*) FILTER (WHERE nilai IS NOT NULL
                       AND nilai ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}') AS terisi,
    COUNT(*) FILTER (
        WHERE nilai ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
          AND SUBSTRING(nilai FROM 1 FOR 10) >= '2023-07-01'
          AND SUBSTRING(nilai FROM 1 FOR 10) <= '2026-09-23'
          AND jenis NOT IN ('APT','KTR')
    )                                                 AS dalam_rentang_tanpa_apt,
    COUNT(*) FILTER (
        WHERE nilai ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
          AND SUBSTRING(nilai FROM 1 FOR 10) >= '2023-07-01'
          AND SUBSTRING(nilai FROM 1 FOR 10) <= '2026-09-23'
    )                                                 AS dalam_rentang_semua_jenis
FROM panjang
GROUP BY nama
ORDER BY 4 DESC, 1;


/* ------------------------------------------------------------
 * QUERY 2
 * SEBARAN JENIS BANGUNAN pada 8.892 baris SBKS, untuk melihat
 * seberapa besar pengaruh centang Apartemen.
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
)
SELECT
    UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, '(kosong)'))) AS kd_jenis,
    COUNT(*) AS baris
FROM public.sr_sertipikat_idk AS i
CROSS JOIN kolom AS k
INNER JOIN public.sr_sertipikat AS s
    ON BTRIM(CAST(s.sertipikat_id AS TEXT)) =
       'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT))
INNER JOIN public.sr_stok AS st
    ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
  AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
GROUP BY 1
ORDER BY 2 DESC;


/* ------------------------------------------------------------
 * QUERY 3
 * SEBARAN PER TAHUN untuk dua kolom yang paling mungkin, supaya
 * kelihatan mana yang bentuknya masuk akal untuk rentang tiga
 * tahun terakhir.
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
siap AS MATERIALIZED (
    SELECT
        UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) AS jenis,
        SUBSTRING(COALESCE(to_jsonb(s) ->> 'tgl_input_ser', '') FROM 1 FOR 4) AS thn_ser,
        SUBSTRING(COALESCE(to_jsonb(i) ->> 'tgl_input', '') FROM 1 FOR 4)     AS thn_idk
    FROM public.sr_sertipikat_idk AS i
    CROSS JOIN kolom AS k
    INNER JOIN public.sr_sertipikat AS s
        ON BTRIM(CAST(s.sertipikat_id AS TEXT)) =
           'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT))
    INNER JOIN public.sr_stok AS st
        ON BTRIM(CAST(st.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_unit, ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(st.flag_aktif AS TEXT), ''))) = 'A'
      AND st.blok IS NOT NULL
      AND st.nomor IS NOT NULL
      AND UPPER(BTRIM(COALESCE(to_jsonb(st) ->> k.stok_jenis, ''))) NOT IN ('APT','KTR')
),
tahun AS (
    SELECT COALESCE(NULLIF(thn_ser, ''), '(kosong)') AS tahun, 1 AS dari_ser, 0 AS dari_idk
    FROM siap
    UNION ALL
    SELECT COALESCE(NULLIF(thn_idk, ''), '(kosong)'), 0, 1
    FROM siap
)
SELECT
    tahun,
    SUM(dari_ser) AS pakai_ser_tgl_input_ser,
    SUM(dari_idk) AS pakai_idk_tgl_input
FROM tahun
GROUP BY tahun
ORDER BY tahun;
