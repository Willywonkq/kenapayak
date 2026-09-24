/* ============================================================
 * REKAP AJB, MENCARI SEBAB LAMBAT SAMPAI TIMEOUT
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL, dan tidak ada CREATE INDEX. Dijalankan di
 * POSTGRESQL.
 *
 * ------------------------------------------------------------
 * KEDUDUKAN
 *
 * Berkas model yang diunggah sesudah merge sudah disandingkan
 * bait demi bait dengan yang ada di cabang kerja. Selisihnya
 * hanya baris kosong di ujung berkas. Artinya:
 *
 *     MERGE TIDAK MENGUBAH MODEL REKAP AJB SAMA SEKALI.
 *
 * Kodenya sama persis dengan yang kemarin jalan. Jadi yang
 * berubah ada di luar berkas itu: isi tabel, atau berkas lain
 * yang ikut terbawa merge.
 *
 * Berkas ini mengukur sisi datanya.
 *
 * ------------------------------------------------------------
 * KENAPA KUERI INI RAWAN MELEDAK KALAU DATA BERTAMBAH
 *
 * Tiga hal ini sudah ada sejak kemarin, tetapi ketiganya
 * membuat waktu jalan naik jauh lebih cepat daripada
 * pertambahan barisnya.
 *
 * 1. SEMUA KUNCI SAMBUNGAN DIBUNGKUS BTRIM(CAST(... AS TEXT)).
 *    Contohnya:
 *
 *        ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
 *         = BTRIM(CAST(akta.ppjb_id AS TEXT))
 *
 *    Begitu kolom dibungkus fungsi, indeks biasa pada kolom
 *    itu tidak bisa dipakai. Postgres terpaksa membaca seluruh
 *    tabel lalu menyusun tabel bantu di memori. Selama tabel
 *    kecil ini tidak terasa; begitu tabelnya membesar,
 *    biayanya naik tajam.
 *
 * 2. CTE sertipikat_unit MEMBACA SELURUH sr_sertipikat.
 *
 *        FROM public.sr_sertipikat AS x
 *
 *    Tanpa saringan unit, tanpa saringan tanggal, lalu
 *    diurutkan untuk DISTINCT ON. Jadi tiap kali laporan
 *    dibuka, seluruh tabel sertipikat dibaca dan diurutkan,
 *    walaupun yang dipakai cuma segelintir unit.
 *
 * 3. BAGIAN "BELUM ADA AKTA" MEMAKAI DUA ANAK KUERI BERKORELASI
 *    KE sr_akta UNTUK SETIAP BARIS CALON:
 *
 *        NOT EXISTS (SELECT 1 FROM sr_akta a WHERE ...)
 *        OR  EXISTS (SELECT 1 FROM sr_akta a WHERE ... )
 *
 *    dengan kunci yang juga dibungkus BTRIM(CAST(...)), jadi
 *    tiap pemeriksaan berpotensi menyapu sr_akta sekali lagi.
 *
 * Dugaan utama: sr_akta atau sr_sertipikat bertambah banyak
 * waktu merge kemarin (mungkin migrasinya diulang). Pemeriksaan
 * sebelumnya mencatat sr_akta kurang 2.260 dari 2.898 baris
 * pada rentang yang sama. Kalau kekurangan itu ditambal, jumlah
 * barisnya bisa naik beberapa kali lipat, dan dengan tiga hal
 * di atas waktunya ikut naik jauh lebih dari beberapa kali.
 *
 * ------------------------------------------------------------
 * HASIL PENGUKURAN, 24-09-2026
 *
 * KUERI 1, jumlah baris:
 *     sr_stok          61.994   72 MB
 *     sr_ppjb          62.328   38 MB
 *     sr_nasabah       42.464   23 MB
 *     sr_pembeli_ppjb  63.447   19 MB
 *     sr_sertipikat    50.479   15 MB
 *     sr_akta          17.407  6,8 MB
 *
 * KUERI 4, akta dalam rentang: 757 baris, 03-07-2023 sampai
 * 26-02-2024. KUERI 5, kembang sambungan: 757 -> 756 -> 766,
 * perbandingan 1,01.
 *
 * Jadi DUGAAN SEMULA SALAH. sr_akta tidak membengkak, dan
 * bagian "sudah ada akta" cuma menghasilkan 766 baris. Bagian
 * itu bukan yang lambat.
 *
 * KUERI 7, baris calon bagian "belum ada akta": 9.560.
 *
 * KUERI 3 menunjukkan hal yang menentukan: daftar indeks hanya
 * memuat sr_nasabah, sr_pembeli_ppjb, sr_ppjb, dan sr_stok.
 *
 *     sr_akta DAN sr_sertipikat SAMA SEKALI TIDAK PUNYA INDEKS.
 *
 * ------------------------------------------------------------
 * SEBABNYA: DUA ANAK KUERI YANG DIRANGKAI DENGAN "OR"
 *
 * Postgres bisa mengubah EXISTS dan NOT EXISTS menjadi
 * sambungan semi dan anti, dan itu cepat. Tetapi HANYA kalau
 * anak kueri itu berdiri sebagai syarat AND di tingkat atas
 * WHERE. Begitu dirangkai dengan OR, seperti di model ini:
 *
 *     NOT EXISTS (...) OR EXISTS (...)
 *
 * perubahan itu tidak bisa dilakukan. Keduanya tinggal sebagai
 * anak kueri yang dijalankan ULANG UNTUK SETIAP BARIS.
 *
 * Hitungannya: 9.560 baris calon x 2 anak kueri x 17.407 baris
 * sr_akta yang disapu utuh karena tidak ada indeks, dan
 * kuncinya pun dibungkus BTRIM(CAST(...)) di kedua sisi
 * sehingga indeks biasa tidak akan menolong sekalipun ada.
 *
 *     ~333 juta pembandingan, ~666 juta panggilan fungsi,
 *     untuk satu kali buka laporan.
 *
 * Itu yang menembus batas 2 menit.
 *
 * ------------------------------------------------------------
 * CATATAN SAMPINGAN: STATISTIK sr_nasabah TIDAK ADA
 *
 * Pada KUERI 2, sr_nasabah tercatat BARIS_HIDUP 0 dan kedua
 * kolom ANALYZE kosong, padahal KUERI 1 menghitung 42.464
 * baris. Artinya perencana kueri tidak punya gambaran sama
 * sekali tentang tabel itu, dan bisa memilih rencana yang
 * keliru pada LEFT JOIN ke sr_nasabah.
 *
 * Penyembuhannya satu perintah, ANALYZE public.sr_nasabah.
 * Itu perawatan, bukan DDL, dan tidak mengubah isi data, tapi
 * tetap perlu izin pemilik tabel. Sebaiknya diminta ke yang
 * memegang basis data, jangan dijalankan sendiri.
 *
 * Tabel lain sudah dianalisis: sr_pembeli_ppjb dan sr_ppjb
 * 21-07-2026, sr_stok 03-08-2026, sr_akta 08-09-2026,
 * sr_sertipikat 15-09-2026.
 *
 * ------------------------------------------------------------
 * CARA PAKAI
 *
 * Jalankan berurutan. Kalau ada angka yang jauh berbeda dari
 * dugaan, catat dan kirimkan hasilnya.
 * ============================================================ */


/* ------------------------------------------------------------
 * KUERI 1. BESAR TABEL DAN JUMLAH BARIS
 *
 * Yang dicari: tabel mana yang membengkak. Bandingkan dengan
 * ingatan angka kemarin kalau ada.
 * ------------------------------------------------------------ */
SELECT
    t.nama                                            AS "TABEL",
    to_char(t.jumlah_baris, 'FM999,999,999')          AS "JUMLAH_BARIS",
    pg_size_pretty(pg_total_relation_size(t.oid))     AS "BESAR_TOTAL",
    pg_size_pretty(pg_relation_size(t.oid))           AS "BESAR_DATA"
FROM (
    SELECT
        c.relname::text AS nama,
        c.oid           AS oid,
        (SELECT count(*) FROM public.sr_akta)         AS jumlah_baris
    FROM pg_class c
    JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_akta'
  UNION ALL
    SELECT c.relname::text, c.oid, (SELECT count(*) FROM public.sr_sertipikat)
    FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_sertipikat'
  UNION ALL
    SELECT c.relname::text, c.oid, (SELECT count(*) FROM public.sr_ppjb)
    FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_ppjb'
  UNION ALL
    SELECT c.relname::text, c.oid, (SELECT count(*) FROM public.sr_pembeli_ppjb)
    FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_pembeli_ppjb'
  UNION ALL
    SELECT c.relname::text, c.oid, (SELECT count(*) FROM public.sr_stok)
    FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_stok'
  UNION ALL
    SELECT c.relname::text, c.oid, (SELECT count(*) FROM public.sr_nasabah)
    FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public' AND c.relname = 'sr_nasabah'
) AS t
ORDER BY pg_total_relation_size(t.oid) DESC;


/* ------------------------------------------------------------
 * KUERI 2. APAKAH TABELNYA BARU DIISI ULANG
 *
 * n_tup_ins yang besar menandakan baris baru masuk. Kalau
 * n_tup_del juga besar, tabelnya dikosongkan lalu diisi lagi,
 * dan itu persis ciri migrasi yang diulang.
 *
 * last_autoanalyze yang kosong atau jauh lebih tua daripada
 * pengisian terakhir berarti PERENCANA KUERI MASIH MEMAKAI
 * STATISTIK LAMA. Ini sendiri sudah bisa membuat kueri yang
 * kemarin cepat jadi memilih rencana yang salah dan lambat.
 * ------------------------------------------------------------ */
SELECT
    relname                        AS "TABEL",
    n_live_tup                     AS "BARIS_HIDUP",
    n_tup_ins                      AS "PERNAH_DISISIPKAN",
    n_tup_upd                      AS "PERNAH_DIUBAH",
    n_tup_del                      AS "PERNAH_DIHAPUS",
    n_mod_since_analyze            AS "BERUBAH_SEJAK_ANALYZE",
    last_vacuum                    AS "VACUUM_TERAKHIR",
    last_autovacuum                AS "AUTOVACUUM_TERAKHIR",
    last_analyze                   AS "ANALYZE_TERAKHIR",
    last_autoanalyze               AS "AUTOANALYZE_TERAKHIR"
FROM pg_stat_user_tables
WHERE schemaname = 'public'
  AND relname IN (
        'sr_akta', 'sr_sertipikat', 'sr_ppjb',
        'sr_pembeli_ppjb', 'sr_stok', 'sr_nasabah'
      )
ORDER BY relname;


/* ------------------------------------------------------------
 * KUERI 3. INDEKS YANG ADA
 *
 * Dua hal yang dicari.
 *
 * Pertama, apakah indeks yang kemarin ada sekarang hilang.
 * Migrasi yang mengisi ulang tabel kadang membuang indeksnya.
 *
 * Kedua, perhatikan bahwa indeks biasa pada ppjb_id TIDAK AKAN
 * TERPAKAI oleh kueri ini, karena kuncinya dibungkus
 * BTRIM(CAST(...)). Jadi kalaupun indeksnya lengkap, kueri
 * tetap menyapu tabel. Ini catatan untuk perbaikan kode,
 * bukan untuk dijalankan sekarang.
 * ------------------------------------------------------------ */
SELECT
    tablename   AS "TABEL",
    indexname   AS "NAMA_INDEKS",
    indexdef    AS "DEFINISI"
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN (
        'sr_akta', 'sr_sertipikat', 'sr_ppjb',
        'sr_pembeli_ppjb', 'sr_stok', 'sr_nasabah'
      )
ORDER BY tablename, indexname;


/* ------------------------------------------------------------
 * KUERI 4. BERAPA BARIS AKTA MASUK RENTANG TANGGAL
 *
 * Ini isi CTE akta_terpilih, disalin apa adanya dari model.
 * Kalau angkanya sekarang jauh lebih besar daripada perkiraan
 * lama, berarti sr_akta memang bertambah dan itu sudah cukup
 * menjelaskan lambatnya.
 *
 * Ganti tanggalnya kalau saringan di layar berbeda.
 * ------------------------------------------------------------ */
SELECT
    count(*)                                 AS "BARIS_AKTA_DALAM_RENTANG",
    min(t.tgl_akta_valid)                    AS "TANGGAL_TERAWAL",
    max(t.tgl_akta_valid)                    AS "TANGGAL_TERAKHIR"
FROM (
    SELECT
        CASE
            WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(akta.tgl_akta AS TIMESTAMP)
        END AS tgl_akta_valid
    FROM public.sr_akta AS akta
) AS t
WHERE t.tgl_akta_valid >= CAST('2023-07-01' AS DATE)
  AND t.tgl_akta_valid <  CAST('2026-09-24' AS DATE);


/* ------------------------------------------------------------
 * KUERI 5. KEMBANG SAMBUNGAN
 *
 * Satu akta bisa bersambung ke beberapa baris pembeli, dan
 * jumlah baris terakhir adalah hasil kalinya. Kolom
 * PERBANDINGAN menunjukkan berapa kali lipat barisnya
 * mengembang. Kalau angkanya lebih dari 1, tiap tambahan akta
 * berbiaya lebih dari satu baris.
 * ------------------------------------------------------------ */
WITH akta_terpilih AS (
    SELECT
        akta.ppjb_id AS ppjb_id,
        CASE
            WHEN COALESCE(CAST(akta.tgl_akta AS TEXT), '')
                 ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
            THEN CAST(akta.tgl_akta AS TIMESTAMP)
        END AS tgl_akta_valid
    FROM public.sr_akta AS akta
),
akta_rentang AS (
    SELECT ppjb_id
    FROM akta_terpilih
    WHERE tgl_akta_valid >= CAST('2023-07-01' AS DATE)
      AND tgl_akta_valid <  CAST('2026-09-24' AS DATE)
),
setelah_ppjb AS (
    SELECT ppjb.ppjb_id AS ppjb_id
    FROM akta_rentang AS a
    INNER JOIN public.sr_ppjb AS ppjb
        ON BTRIM(CAST(ppjb.ppjb_id AS TEXT))
         = BTRIM(CAST(a.ppjb_id AS TEXT))
),
setelah_pembeli AS (
    SELECT 1 AS penanda
    FROM setelah_ppjb AS s
    INNER JOIN public.sr_pembeli_ppjb AS pembeli
        ON BTRIM(CAST(pembeli.ppjb_id AS TEXT))
         = BTRIM(CAST(s.ppjb_id AS TEXT))
)
SELECT
    (SELECT count(*) FROM akta_rentang)     AS "TAHAP_1_AKTA",
    (SELECT count(*) FROM setelah_ppjb)     AS "TAHAP_2_SETELAH_PPJB",
    (SELECT count(*) FROM setelah_pembeli)  AS "TAHAP_3_SETELAH_PEMBELI",
    ROUND(
        (SELECT count(*) FROM setelah_pembeli)::numeric
        / NULLIF((SELECT count(*) FROM akta_rentang), 0),
        2
    )                                       AS "PERBANDINGAN";


/* ------------------------------------------------------------
 * KUERI 6. BIAYA CTE sertipikat_unit
 *
 * CTE ini membaca seluruh sr_sertipikat lalu mengurutkannya
 * tiap kali laporan dibuka, padahal yang terpakai hanya unit
 * yang sedang dilihat. Angka SELURUH_BARIS di bawah adalah
 * yang dibaca tiap kali; STOK_BERBEDA adalah yang benar benar
 * berguna. Selisih keduanya adalah pekerjaan yang terbuang.
 * ------------------------------------------------------------ */
SELECT
    count(*)                                          AS "SELURUH_BARIS",
    count(DISTINCT BTRIM(CAST(x.stok_id AS TEXT)))    AS "STOK_BERBEDA"
FROM public.sr_sertipikat AS x;


/* ------------------------------------------------------------
 * KUERI 7. BERAPA BARIS CALON DIPERIKSA OLEH ANAK KUERI
 *          BERKORELASI DI BAGIAN "BELUM ADA AKTA"
 *
 * Tiap baris calon memicu sampai dua pemeriksaan ke sr_akta.
 * Kalikan BARIS_CALON dengan jumlah baris sr_akta dari KUERI 1
 * untuk mendapat gambaran kasar beban yang harus dikerjakan.
 *
 * Ganti 'SBKS' dengan unit yang sedang dibuka, dan ganti
 * kd_perusahaan kalau di basis data ini nama kolomnya kd_unit
 * atau kd_pt.
 * ------------------------------------------------------------ */
SELECT count(*) AS "BARIS_CALON"
FROM public.sr_ppjb AS ppjb
INNER JOIN public.sr_stok AS stok
    ON BTRIM(CAST(stok.stok_id AS TEXT))
     = BTRIM(CAST(ppjb.stok_id AS TEXT))
WHERE UPPER(BTRIM(COALESCE(CAST(ppjb.flag_aktif AS TEXT), ''))) = 'A'
  AND ppjb.parent_id IS NULL
  AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
  AND UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS';
