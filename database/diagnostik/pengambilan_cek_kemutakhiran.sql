/* ============================================================
 * CEK KEMUTAKHIRAN sr_pengambilan
 *
 * BERKAS INI HANYA MEMBACA. Tidak ada INSERT, UPDATE, DELETE,
 * maupun DDL.
 *
 * Latar belakang. Corong dijalankan berdampingan di kedua basis
 * data dengan filter yang sama, dan hasilnya:
 *
 *   tahap                            PG    SQL Server
 *   seluruh tabel                 15.913      18.728
 *   + rentang tanggal                810       3.645   <-- di sini
 *   + stok SBKS aktif                606       2.028
 *   + nasabahnya ketemu              602       2.028
 *
 * Yang menonjol: kekurangan di DALAM rentang tanggal 2.835 baris,
 * hampir sama besar dengan kekurangan SELURUH tabel 2.815 baris.
 * Artinya baris yang hilang terpusat pada rentang ini, bukan
 * tersebar merata. Itu tanda khas tabel yang berhenti dipindahkan
 * pada tanggal tertentu, sama seperti sr_imb dan sr_jaminan.
 *
 * Berkas ini menunjukkan tanggal berhentinya.
 * ============================================================ */


/* ------------------------------------------------------------
 * QUERY 1
 * Tanggal paling akhir pada tiap kolom tanggal sr_pengambilan.
 *
 * tgl_entry menunjukkan kapan barisnya direkam. Kalau tgl_entry
 * berhenti jauh lebih awal daripada hari ini, berarti memang
 * penyalinannya yang berhenti.
 * ------------------------------------------------------------ */
SELECT 'tgl_input_imb'  AS kolom, MIN(tgl_input_imb)::text  AS paling_awal,
       MAX(tgl_input_imb)::text  AS paling_akhir, COUNT(tgl_input_imb)  AS terisi
FROM public.sr_pengambilan
UNION ALL SELECT 'tgl_input_ser',  MIN(tgl_input_ser)::text,  MAX(tgl_input_ser)::text,  COUNT(tgl_input_ser)  FROM public.sr_pengambilan
UNION ALL SELECT 'tgl_input_akta', MIN(tgl_input_akta)::text, MAX(tgl_input_akta)::text, COUNT(tgl_input_akta) FROM public.sr_pengambilan
UNION ALL SELECT 'tgl_input_shm',  MIN(tgl_input_shm)::text,  MAX(tgl_input_shm)::text,  COUNT(tgl_input_shm)  FROM public.sr_pengambilan
UNION ALL SELECT 'tgl_input_ph',   MIN(tgl_input_ph)::text,   MAX(tgl_input_ph)::text,   COUNT(tgl_input_ph)   FROM public.sr_pengambilan
UNION ALL SELECT 'tgl_input_ppjb', MIN(tgl_input_ppjb)::text, MAX(tgl_input_ppjb)::text, COUNT(tgl_input_ppjb) FROM public.sr_pengambilan;


/* ------------------------------------------------------------
 * QUERY 2
 * Sebaran tahun, memakai tanggal dokumen paling awal yang terisi
 * pada tiap baris.
 *
 * Tahun yang mendadak kosong atau anjlok adalah titik berhentinya.
 * ------------------------------------------------------------ */
SELECT
    COALESCE(CAST(EXTRACT(YEAR FROM LEAST(
        tgl_input_imb, tgl_input_ser, tgl_input_akta,
        tgl_input_shm, tgl_input_ph, tgl_input_ppjb
    )) AS TEXT), '(SEMUA KOSONG)') AS tahun,
    COUNT(*) AS baris
FROM public.sr_pengambilan
GROUP BY 1
ORDER BY 1;
