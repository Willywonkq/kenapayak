/* ============================================================
 * APAKAH PENYUSUNAN ULANG AWALAN BISA MENGARANG BARIS
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * ------------------------------------------------------------
 * KENAPA INI PERLU DIPERIKSA
 *
 * Corong Undangan AJB menunjukkan tahap 3 tidak kehilangan satu
 * baris pun: 3.277 masuk, 3.277 ketemu PPJB-nya. Itu kabar
 * baik, tetapi hanya menjawab setengah pertanyaan.
 *
 * "Ketemu" belum tentu "ketemu yang benar".
 *
 * Kolom ppjb_id pada sr_undangan_ajb dan sr_undangan_skb
 * termigrasi sebagai angka, jadi awalannya terbuang. Model
 * menyusunnya kembali dengan menempelkan awalan milik unit yang
 * sedang diminta. Untuk unit SBKS, awalan itu DBPSA-.
 *
 * Masalahnya, tabel itu berisi surat dari SEMUA unit. Surat
 * milik unit SERPONG pun ikut ditempeli DBPSA-. Kalau angka
 * yang sama kebetulan dipakai kedua keluarga, surat SERPONG
 * akan menyambung ke PPJB milik PUSAT. Kalau PPJB itu ternyata
 * milik SBKS, barisnya akan MUNCUL di laporan SBKS padahal
 * bukan miliknya.
 *
 * Baris karangan seperti itu jauh lebih berbahaya daripada
 * baris yang hilang, sebab tidak ada yang curiga.
 *
 * Satu petunjuk sudah ada: pada tahun 2023, yang datanya utuh,
 * PostgreSQL punya 280 surat sedangkan SQL Server PUSAT hanya
 * 275, artinya ada 5 surat dari keluarga lain. Meski begitu
 * jumlah milik SBKS tetap 4 di kedua sisi, sama persis. Kalau
 * ada karangan, angka itu akan lebih besar.
 *
 * Tetapi lima baris terlalu sedikit untuk dijadikan bukti.
 * Berkas ini mengukurnya langsung, bukan menyimpulkan dari
 * kebetulan.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * KELUARGA APA SAJA yang ada di sr_ppjb.
 *
 * Kalau ternyata sr_ppjb pun hanya berisi satu keluarga, maka
 * kerancuan tidak mungkin terjadi sama sekali dan sisa berkas
 * ini akan keluar nol semua.
 * ------------------------------------------------------------ */
SELECT
    CASE
        WHEN BTRIM(CAST(ppjb_id AS TEXT)) ~ '^[0-9]+$'
            THEN 'angka telanjang'
        ELSE 'berawalan: '
             || REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '[0-9]+$', '')
    END      AS bentuk,
    COUNT(*) AS baris
FROM public.sr_ppjb
WHERE ppjb_id IS NOT NULL
GROUP BY 1
ORDER BY 2 DESC;


/* ------------------------------------------------------------
 * QUERY 2
 * NASIB SETIAP ANGKA pada sr_undangan_ajb, dalam rentang
 * tanggal yang dipakai laporan.
 *
 *   pasti DBPSA      angkanya hanya ada di keluarga DBPSA,
 *                    jadi penempelan awalan DBPSA pasti benar
 *   pasti DBPSS      angkanya hanya ada di keluarga DBPSS,
 *                    jadi surat ini SEHARUSNYA tidak pernah
 *                    nyambung untuk unit SBKS, dan memang tidak
 *   RANCU            angkanya ada di kedua keluarga, jadi
 *                    penempelan awalan adalah TEBAKAN
 *   tidak ada        angkanya tidak ada di keluarga mana pun
 * ------------------------------------------------------------ */
WITH keluarga_ppjb AS MATERIALIZED (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSA-%') AS ada_a,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSS-%') AS ada_s
    FROM public.sr_ppjb
    WHERE ppjb_id IS NOT NULL
    GROUP BY 1
),
surat AS (
    SELECT BTRIM(CAST(ppjb_id AS TEXT)) AS angka
    FROM public.sr_undangan_ajb
    WHERE ppjb_id IS NOT NULL
      AND BTRIM(CAST(ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
)
SELECT
    CASE
        WHEN keluarga_ppjb.angka IS NULL      THEN 'd. tidak ada di keluarga mana pun'
        WHEN keluarga_ppjb.ada_a
         AND keluarga_ppjb.ada_s              THEN 'c. RANCU, ada di kedua keluarga'
        WHEN keluarga_ppjb.ada_a              THEN 'a. pasti DBPSA'
        ELSE                                       'b. pasti DBPSS'
    END       AS nasib,
    COUNT(*)  AS baris_surat
FROM surat
LEFT JOIN keluarga_ppjb ON keluarga_ppjb.angka = surat.angka
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 3
 * BARIS KARANGAN YANG SEBENARNYA, kalau ada.
 *
 * Dari yang RANCU tadi, berapa yang setelah ditempeli DBPSA-
 * ternyata menyambung ke PPJB milik stok SBKS yang aktif. Itulah
 * baris yang berpeluang tampil di laporan SBKS padahal
 * asal-usulnya tidak pasti.
 *
 * Kolom rancu_tapi_ikut_tampil ADALAH ukuran bahayanya. Nol
 * berarti aman. Lebih dari nol berarti laporan SBKS memuat
 * baris yang tidak bisa dipertanggungjawabkan, dan model harus
 * menolaknya, bukan menampilkannya.
 * ------------------------------------------------------------ */
WITH keluarga_ppjb AS MATERIALIZED (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSA-%') AS ada_a,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSS-%') AS ada_s
    FROM public.sr_ppjb
    WHERE ppjb_id IS NOT NULL
    GROUP BY 1
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        BTRIM(CAST(s.ppjb_id AS TEXT))                AS angka,
        'DBPSA-' || BTRIM(CAST(s.ppjb_id AS TEXT))    AS kunci_tebakan,
        EXTRACT(YEAR FROM s.tgl_surat)::INT           AS tahun
    FROM public.sr_undangan_ajb AS s
    WHERE s.ppjb_id IS NOT NULL
      AND BTRIM(CAST(s.ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
)
SELECT
    surat.tahun,
    COUNT(*)                                              AS baris_surat,
    COUNT(*) FILTER (WHERE keluarga_ppjb.ada_a
                       AND keluarga_ppjb.ada_s)           AS rancu,
    COUNT(*) FILTER (WHERE ppjb_sbks.kunci_ppjb IS NOT NULL)
                                                          AS ikut_tampil_sbks,
    COUNT(*) FILTER (WHERE keluarga_ppjb.ada_a
                       AND keluarga_ppjb.ada_s
                       AND ppjb_sbks.kunci_ppjb IS NOT NULL)
                                                  AS rancu_tapi_ikut_tampil
FROM surat
LEFT JOIN keluarga_ppjb ON keluarga_ppjb.angka = surat.angka
LEFT JOIN ppjb_sbks     ON ppjb_sbks.kunci_ppjb = surat.kunci_tebakan
GROUP BY 1
ORDER BY 1;


/* ------------------------------------------------------------
 * QUERY 4
 * HAL YANG SAMA UNTUK sr_undangan_skb, jenis Undangan SKB.
 *
 * Tabel itu punya bentuk kunci yang sama dan belum pernah
 * diperiksa, jadi sekalian diukur di sini daripada menunggu
 * laporannya dibuka dan baru ketahuan.
 * ------------------------------------------------------------ */
WITH keluarga_ppjb AS MATERIALIZED (
    SELECT
        REGEXP_REPLACE(BTRIM(CAST(ppjb_id AS TEXT)), '^[^0-9]+', '') AS angka,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSA-%') AS ada_a,
        BOOL_OR(BTRIM(CAST(ppjb_id AS TEXT)) LIKE 'DBPSS-%') AS ada_s
    FROM public.sr_ppjb
    WHERE ppjb_id IS NOT NULL
    GROUP BY 1
),
stok_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok
    WHERE UPPER(BTRIM(COALESCE(CAST(kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(flag_aktif AS TEXT), 'T'))) = 'A'
),
ppjb_sbks AS MATERIALIZED (
    SELECT BTRIM(CAST(ppjb.ppjb_id AS TEXT)) AS kunci_ppjb
    FROM public.sr_ppjb AS ppjb
    INNER JOIN stok_sbks
        ON stok_sbks.kunci_stok = BTRIM(CAST(ppjb.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
      AND ppjb.parent_id IS NULL
),
surat AS (
    SELECT
        BTRIM(CAST(s.ppjb_id AS TEXT))             AS angka,
        'DBPSA-' || BTRIM(CAST(s.ppjb_id AS TEXT)) AS kunci_tebakan
    FROM public.sr_undangan_skb AS s
    WHERE s.ppjb_id IS NOT NULL
      AND BTRIM(CAST(s.ppjb_id AS TEXT)) ~ '^[0-9]+$'
      AND s.tgl_surat >= CAST('2023-07-01' AS TIMESTAMP)
      AND s.tgl_surat <  CAST('2026-09-22' AS TIMESTAMP)
)
SELECT
    'sr_undangan_skb'                                     AS tabel,
    COUNT(*)                                              AS baris_surat,
    COUNT(*) FILTER (WHERE keluarga_ppjb.ada_a
                       AND keluarga_ppjb.ada_s)           AS rancu,
    COUNT(*) FILTER (WHERE ppjb_sbks.kunci_ppjb IS NOT NULL)
                                                          AS ikut_tampil_sbks,
    COUNT(*) FILTER (WHERE keluarga_ppjb.ada_a
                       AND keluarga_ppjb.ada_s
                       AND ppjb_sbks.kunci_ppjb IS NOT NULL)
                                                  AS rancu_tapi_ikut_tampil
FROM surat
LEFT JOIN keluarga_ppjb ON keluarga_ppjb.angka = surat.angka
LEFT JOIN ppjb_sbks     ON ppjb_sbks.kunci_ppjb = surat.kunci_tebakan;
