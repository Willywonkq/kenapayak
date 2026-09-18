/* ============================================================
 * CEK SKEMA UNTUK MIGRASI DAFTAR UNDANGAN DAN DAFTAR PENGAMBILAN
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Kedua laporan menyentuh banyak tabel yang belum pernah dipakai
 * fitur sebelumnya. Yang paling menentukan: apakah kolom kunci
 * penghubungnya masih membawa awalan DBPSA- dan DBPSS-, atau
 * sudah terbuang menjadi angka seperti yang terjadi pada sr_akta,
 * sr_jaminan, sr_imb, dan sr_pbb.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Tabel yang dibutuhkan, ada atau tidak, dan berapa isinya.
 * ------------------------------------------------------------ */
SELECT
    daftar.nama AS tabel,
    CASE WHEN c.oid IS NULL THEN 'TIDAK ADA' ELSE 'ada' END AS keberadaan,
    COALESCE(isi.jumlah, 0) AS baris
FROM (VALUES
    ('sr_undangan_ppjb'), ('sr_undangan_st'), ('sr_undangan_ajb'),
    ('sr_undangan_skb'), ('sr_perpanjangan_sertipikat'),
    ('sr_pengambilan'), ('sr_sertipikat_idk'), ('sr_akta'),
    ('sr_bank'), ('sr_users_app'), ('sr_tipe'), ('sr_jenis_bangunan'),
    ('sr_sektor'), ('sr_lokasi'), ('sr_stok'), ('sr_ppjb'),
    ('sr_pembeli_ppjb'), ('sr_nasabah'), ('sr_sertipikat')
) AS daftar(nama)
LEFT JOIN pg_class AS c
    ON c.relname = daftar.nama
   AND c.relnamespace = 'public'::regnamespace
   AND c.relkind = 'r'
LEFT JOIN LATERAL (
    SELECT (xpath('/row/n/text()',
        query_to_xml(format('SELECT COUNT(*) AS n FROM public.%I', daftar.nama),
                     false, true, '')))[1]::text::bigint AS jumlah
) AS isi ON c.oid IS NOT NULL
ORDER BY keberadaan DESC, daftar.nama;


/* ------------------------------------------------------------
 * QUERY 2
 * INI YANG PALING MENENTUKAN.
 *
 * Bentuk nilai pada kolom kunci penghubung. Kalau berbunyi
 * "angka polos", awalannya terbuang dan model harus menyusunnya
 * ulang dari unit yang diminta, seperti pada Daftar IMB dan PBB.
 * ------------------------------------------------------------ */
WITH kunci(tabel, kolom) AS (
    VALUES
        ('sr_undangan_ppjb', 'ppjb_id'),
        ('sr_undangan_st', 'ppjb_id'),
        ('sr_undangan_ajb', 'ppjb_id'),
        ('sr_undangan_skb', 'ppjb_id'),
        ('sr_perpanjangan_sertipikat', 'sertipikat_id'),
        ('sr_pengambilan', 'sertipikat_id'),
        ('sr_sertipikat_idk', 'sertipikat_id'),
        ('sr_akta', 'ppjb_id'),
        ('sr_ppjb', 'ppjb_id'),
        ('sr_sertipikat', 'sertipikat_id')
)
SELECT
    kunci.tabel,
    kunci.kolom,
    COALESCE(tipe.data_type, 'KOLOM TIDAK ADA') AS tipe,
    COALESCE(bentuk.hasil, '-') AS bentuk_nilai
FROM kunci
LEFT JOIN information_schema.columns AS tipe
    ON tipe.table_schema = 'public'
   AND tipe.table_name = kunci.tabel
   AND LOWER(tipe.column_name) = kunci.kolom
LEFT JOIN LATERAL (
    SELECT (xpath('/row/h/text()', query_to_xml(
        format(
            'SELECT CASE
                        WHEN COUNT(*) = 0 THEN ''tabel kosong''
                        WHEN SUM(CASE WHEN BTRIM(CAST(%I AS TEXT)) ~ ''^[0-9]+$''
                                 THEN 1 ELSE 0 END) = COUNT(*)
                        THEN ''angka polos, awalan terbuang''
                        WHEN SUM(CASE WHEN BTRIM(CAST(%I AS TEXT)) ~ ''^[0-9]+$''
                                 THEN 1 ELSE 0 END) = 0
                        THEN ''berawalan, masih utuh''
                        ELSE ''campuran''
                    END AS h
               FROM public.%I WHERE %I IS NOT NULL',
            kunci.kolom, kunci.kolom, kunci.tabel, kunci.kolom
        ), false, true, '')))[1]::text AS hasil
    WHERE tipe.column_name IS NOT NULL
) AS bentuk ON TRUE
ORDER BY kunci.tabel, kunci.kolom;


/* ------------------------------------------------------------
 * QUERY 3
 * Kolom yang dipakai kedua model, dipastikan ada semua.
 * Seluruh barisnya harus berbunyi "ada".
 * ------------------------------------------------------------ */
WITH perlu(tabel, kolom) AS (
    VALUES
        ('sr_nasabah','kd_alamt_surat'), ('sr_nasabah','alamat_rmh'),
        ('sr_nasabah','alamat_ktr'), ('sr_nasabah','alamat_srt'),
        ('sr_nasabah','kota_rmh'), ('sr_nasabah','kota_ktr'),
        ('sr_nasabah','kota_srt'), ('sr_nasabah','kode_pos_rmh'),
        ('sr_nasabah','kode_pos_ktr'), ('sr_nasabah','kode_pos_srt'),
        ('sr_stok','luas_semi_gross'), ('sr_stok','kd_jenis'),
        ('sr_stok','kd_tipe'), ('sr_stok','kd_mata_uang'),
        ('sr_stok','parent_id'), ('sr_stok','no_virtual_acc'),
        ('sr_ppjb','harga_jual'), ('sr_ppjb','tgl_tanda_tangan'),
        ('sr_ppjb','tgl_ttd_notaris'), ('sr_ppjb','nm_notaris'),
        ('sr_ppjb','kd_bank'),
        ('sr_tipe','kd_jenis'), ('sr_tipe','kd_tipe'),
        ('sr_tipe','deskripsi'), ('sr_tipe','listrik'),
        ('sr_jenis_bangunan','kd_jenis'), ('sr_jenis_bangunan','deskripsi'),
        ('sr_jenis_bangunan','flag_laporan'),
        ('sr_sertipikat','no_sertipikat'), ('sr_sertipikat','tgl_sertipikat'),
        ('sr_sertipikat','tgl_berlaku'), ('sr_sertipikat','su_pisah'),
        ('sr_sertipikat','tgl_su_pisah'), ('sr_sertipikat','luas_sup'),
        ('sr_sertipikat','tgl_ambil_legal'),
        ('sr_sertipikat','user_ambil_legal'),
        ('sr_sertipikat','flag_ambil_legal'),
        ('sr_sertipikat_idk','nama_pt'), ('sr_sertipikat_idk','ser_pisah'),
        ('sr_sertipikat_idk','tgl_ser_pisah'), ('sr_sertipikat_idk','su_pisah'),
        ('sr_sertipikat_idk','tgl_su_pisah'),
        ('sr_sertipikat_idk','luas_su_pisah'),
        ('sr_bank','kd_bank'), ('sr_bank','nama'),
        ('sr_users_app','user_name'), ('sr_users_app','alias'),
        ('sr_akta','tgl_akta'),
        ('sr_pengambilan','tgl_input_imb'), ('sr_pengambilan','tgl_input_ser'),
        ('sr_pengambilan','tgl_input_akta'), ('sr_pengambilan','tgl_input_shm'),
        ('sr_pengambilan','tgl_input_ph'), ('sr_pengambilan','tgl_input_ppjb'),
        ('sr_pengambilan','tgl_ambil_imb'), ('sr_pengambilan','tgl_ambil_ser'),
        ('sr_pengambilan','tgl_ambil_akta'), ('sr_pengambilan','tgl_ambil_shm'),
        ('sr_pengambilan','tgl_ambil_ph'), ('sr_pengambilan','tgl_ambil_ppjb'),
        ('sr_undangan_ppjb','urut'), ('sr_undangan_ppjb','no_surat'),
        ('sr_undangan_ppjb','tgl_surat'), ('sr_undangan_ppjb','tgl_undangan'),
        ('sr_undangan_ppjb','waktu'), ('sr_undangan_ppjb','tempat'),
        ('sr_undangan_ppjb','jenis_surat'),
        ('sr_undangan_st','urut'), ('sr_undangan_st','tgl_surat'),
        ('sr_undangan_ajb','urut'), ('sr_undangan_ajb','tgl_surat'),
        ('sr_undangan_ajb','tgl_diterima'), ('sr_undangan_ajb','nama_penerima'),
        ('sr_undangan_skb','urut'), ('sr_undangan_skb','tgl_surat'),
        ('sr_perpanjangan_sertipikat','urut'),
        ('sr_perpanjangan_sertipikat','no_surat'),
        ('sr_perpanjangan_sertipikat','tgl_surat')
)
SELECT
    perlu.tabel, perlu.kolom,
    CASE WHEN k.column_name IS NULL THEN 'TIDAK ADA, beri tahu saya' ELSE 'ada' END
        AS keadaan
FROM perlu
LEFT JOIN information_schema.columns AS k
    ON k.table_schema = 'public'
   AND k.table_name = perlu.tabel
   AND LOWER(k.column_name) = perlu.kolom
ORDER BY keadaan DESC, perlu.tabel, perlu.kolom;


/* ------------------------------------------------------------
 * QUERY 4
 * Nama kolom kode pada tabel pendukung, meniru cara model
 * memilihnya. Sudah diketahui sr_lokasi memakai kd_lv2 dan
 * sr_sektor memakai kd_proyek; ini memastikannya sekali lagi.
 * ------------------------------------------------------------ */
WITH kolom AS (
    SELECT table_name, LOWER(column_name) AS column_name
    FROM information_schema.columns WHERE table_schema = 'public'
)
SELECT 'sr_stok / perusahaan' AS keperluan,
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_perusahaan','kd_unit','kd_pt']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1),'TIDAK ADA') AS kolom_terpakai
UNION ALL
SELECT 'sr_stok / sektor',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1),'TIDAK ADA')
UNION ALL
SELECT 'sr_stok / lokasi',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_stok')
                  LIMIT 1),'TIDAK ADA')
UNION ALL
SELECT 'sr_lokasi / kode',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_lokasi','kd_lv2','kd_proyek','kd_cluster','kd_sektor']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_lokasi')
                  LIMIT 1),'TIDAK ADA')
UNION ALL
SELECT 'sr_sektor / kode',
       COALESCE((SELECT c FROM UNNEST(ARRAY['kd_sektor','kd_proyek','kd_cluster','kd_lokasi','kd_lv2']) AS c
                  WHERE c IN (SELECT column_name FROM kolom WHERE table_name='sr_sektor')
                  LIMIT 1),'TIDAK ADA');
