-- =====================================================================
-- DIAGNOSTIK - MEMASTIKAN BARIS GANDA PADA DAFTAR SERTIPIKAT PECAHAN
-- =====================================================================
-- Seluruh query HANYA MEMBACA. Tidak ada CREATE, INSERT, UPDATE, DELETE,
-- DROP, ALTER, TRUNCATE, maupun GRANT. Aman dijalankan pada database
-- produksi.
--
-- PERTANYAANNYA
-- Pada laporan, satu BLOK/NOMOR bisa muncul lebih dari satu kali.
-- Apakah itu data yang terduplikasi, atau memang riwayat yang berbeda?
--
-- LATAR BELAKANG
-- Kunci sr_sertipikat_idk bukan sertipikat_id saja, melainkan gabungan
-- sertipikat_id dan urut. Jadi satu sertipikat pecahan memang boleh
-- punya beberapa sertipikat induk, yaitu bila tanahnya berasal dari
-- lebih dari satu bidang induk.
--
-- Laporannya menampilkan satu baris untuk tiap baris sr_sertipikat_idk,
-- tanpa perkalian, karena sambungan berikutnya satu-ke-satu.
--
-- Jalankan QUERY 1 sampai QUERY 4 untuk membuktikannya.
-- =====================================================================


-- ---------------------------------------------------------------------
-- QUERY 1 : apakah sertipikat_id + urut benar-benar unik
-- ---------------------------------------------------------------------
-- Kalau kolom "kunci_kembar" bernilai 0, berarti tidak ada baris yang
-- benar-benar terduplikasi di dalam tabelnya. Baris ganda pada laporan
-- berarti bukan duplikat.
SELECT
    COUNT(*)                                        AS baris_idk,
    COUNT(DISTINCT (sertipikat_id, urut))           AS kombinasi_unik,
    COUNT(*) - COUNT(DISTINCT (sertipikat_id, urut)) AS kunci_kembar
FROM public.sr_sertipikat_idk;


-- ---------------------------------------------------------------------
-- QUERY 2 : berapa sertipikat yang punya lebih dari satu induk
-- ---------------------------------------------------------------------
-- Ini menjelaskan berapa banyak baris ganda yang akan muncul di layar.
SELECT
    jumlah_induk                AS induk_per_sertipikat,
    COUNT(*)                    AS jumlah_sertipikat,
    SUM(jumlah_induk)           AS baris_di_laporan
FROM (
    SELECT sertipikat_id, COUNT(*) AS jumlah_induk
    FROM public.sr_sertipikat_idk
    WHERE sertipikat_id IS NOT NULL
    GROUP BY sertipikat_id
) AS ringkas
GROUP BY 1
ORDER BY 1;


-- ---------------------------------------------------------------------
-- QUERY 3 : isi lengkap baris ganda, contoh nyata
-- ---------------------------------------------------------------------
-- Diambil satu sertipikat yang punya dua induk, lalu ditampilkan kedua
-- barisnya berdampingan. Kalau isinya memang berbeda, berarti bukan
-- duplikat. Ganti 'RAF' dan '009' bila ingin memeriksa unit lain.
WITH sasaran AS (
    SELECT s.sertipikat_id
    FROM public.sr_sertipikat AS s
    INNER JOIN public.sr_stok AS stok
        ON BTRIM(CAST(stok.stok_id AS TEXT)) = BTRIM(CAST(s.stok_id AS TEXT))
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.blok  AS TEXT), ''))) = 'RAF'
      AND UPPER(BTRIM(COALESCE(CAST(stok.nomor AS TEXT), ''))) = '009'
)
SELECT
    idk.urut                                AS urut,
    BTRIM(CAST(idk.sertipikat_idk AS TEXT)) AS sertipikat_induk,
    idk.tgl_ser_idk                         AS tgl_induk,
    BTRIM(CAST(idk.su_induk AS TEXT))       AS surat_ukur_induk,
    idk.tgl_su_induk                        AS tgl_surat_ukur,
    idk.luas_su_induk                       AS luas_induk,
    BTRIM(CAST(idk.mohon_pisah AS TEXT))    AS permohonan_pemecahan,
    idk.tgl_mohon_pisah                     AS tgl_permohonan,
    BTRIM(CAST(idk.ser_pisah AS TEXT))      AS sertipikat_pemisahan,
    idk.tgl_ser_pisah                       AS tgl_pemisahan
FROM public.sr_sertipikat_idk AS idk
INNER JOIN sasaran
    ON BTRIM(CAST(sasaran.sertipikat_id AS TEXT))
     = 'DBPSA-' || BTRIM(CAST(idk.sertipikat_id AS TEXT))
ORDER BY idk.urut;


-- ---------------------------------------------------------------------
-- QUERY 4 : membuktikan laporannya tidak melipatgandakan baris
-- ---------------------------------------------------------------------
-- Kolom "baris_idk" adalah jumlah baris sr_sertipikat_idk untuk unit
-- ini, dan "baris_setelah_disambung" adalah jumlah baris setelah seluruh
-- penggabungan tabel dikerjakan. Kalau keduanya sama, berarti tidak ada
-- perkalian baris sama sekali; laporan hanya menampilkan apa yang ada.
--
-- Ganti 'SBKS' dengan unit yang sedang dipakai.
WITH idk AS (
    SELECT
        i.ctid AS baris,
        'DBPSA-' || BTRIM(CAST(i.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat_idk AS i
    WHERE BTRIM(CAST(i.sertipikat_id AS TEXT)) ~ '^[0-9]+$'
),
stok_unit AS (
    SELECT BTRIM(CAST(stok.stok_id AS TEXT)) AS kunci_stok
    FROM public.sr_stok AS stok
    WHERE UPPER(BTRIM(COALESCE(CAST(stok.kd_perusahaan AS TEXT), ''))) = 'SBKS'
      AND UPPER(BTRIM(COALESCE(CAST(stok.flag_aktif AS TEXT), ''))) = 'A'
),
sertipikat_unit AS (
    SELECT BTRIM(CAST(s.sertipikat_id AS TEXT)) AS kunci
    FROM public.sr_sertipikat AS s
    INNER JOIN stok_unit
        ON stok_unit.kunci_stok = BTRIM(CAST(s.stok_id AS TEXT))
)
SELECT
    COUNT(DISTINCT idk.baris) AS baris_idk,
    COUNT(*)                  AS baris_setelah_disambung
FROM idk
INNER JOIN sertipikat_unit ON sertipikat_unit.kunci = idk.kunci;
