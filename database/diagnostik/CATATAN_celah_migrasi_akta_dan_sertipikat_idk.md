# Catatan: celah migrasi pada tabel AKTA dan SERTIPIKAT_IDK

Tanggal pemeriksaan: 17 September 2026
Sumber: SRIS_PUSAT (SQL Server) dibandingkan dengan PostgreSQL hasil migrasi

Berkas ini hanya catatan temuan. Seluruh pemeriksaannya memakai query yang
hanya membaca, ada pada `unit_cek_kelengkapan_migrasi.sql` (PostgreSQL) dan
`sqlserver_unit_cek_kelengkapan.sql` (SQL Server).

## Ringkasan

Dua tabel tidak termigrasi seluruhnya. Masing-masing kehilangan sekitar
15 persen barisnya, dan pada beberapa unit hilang sama sekali.

| tabel           | di SQL Server | di PostgreSQL | hilang        |
|-----------------|---------------|---------------|---------------|
| AKTA            | 19.773        | 16.794        | 2.979  (15 %) |
| SERTIPIKAT_IDK  | 27.497        | 23.308        | 4.189  (15 %) |

Tabel STOK, PPJB, dan SERTIPIKAT tidak bermasalah. Pada unit DTSA, tabel
SERTIPIKAT termigrasi tepat 1.126 lawan 1.126.

## Contoh terjelas: unit DTSA

| tabel          | SQL Server | PostgreSQL |
|----------------|------------|------------|
| STOK           | 1.875      | 1.869      |
| SERTIPIKAT     | 1.126      | 1.126      |
| SERTIPIKAT_IDK | 1.135      | 0          |
| AKTA           | 219        | 0          |

Tabel induknya utuh, tabel rinciannya kosong.

Hal berikut sudah diperiksa dan terbukti BUKAN penyebabnya:

* bukan karena unitnya belum termigrasi, sebab STOK dan SERTIPIKAT ada;
* bukan karena FLAG_AKTIF, sebab 1.804 dari 1.869 stok DTSA bertanda 'A'
  dan seluruh 1.126 sertipikatnya berada pada stok yang aktif;
* bukan karena salah menyusun awalan kunci, sebab seluruh sertipikat DTSA
  memakai awalan DBPSA- dan pencarian dengan awalan DBPSA- maupun DBPSS-
  sama-sama menghasilkan nol.

## Unit yang kehilangan seluruh barisnya

AKTA nol di PostgreSQL padahal ada di SQL Server:

    DTSA  219        KCJA  156        SMTH  17

SERTIPIKAT_IDK nol di PostgreSQL padahal ada di SQL Server:

    DTSA  1.135      GNSP  71         SGMF  59
    SMTH  52         XSMTH  4         KCJM  1

SGMC nyaris habis: 1.321 di SQL Server, hanya 1 yang terjangkau di
PostgreSQL.

## Unit yang hilang sebagian

| unit | AKTA sumber | AKTA migrasi | selisih |
|------|-------------|--------------|---------|
| SBKS | 6.617       | 5.229        | -1.388  |
| MKPP | 1.936       | 1.373        | -563    |
| SKLG | 8.116       | 7.724        | -392    |
| SKRW | 829         | 696          | -133    |
| SMSF | 79          | 1            | -78     |
| GDOR | 550         | 524          | -26     |
| WGP  | 971         | 965          | -6      |
| BHMS | 134         | 133          | -1      |

SKPN dan MNST termigrasi utuh.

Catatan ketelitian: angka PostgreSQL per unit untuk SERTIPIKAT_IDK masih
memakai syarat stok aktif, sehingga sebagian selisih kecil dapat berasal
dari stok nonaktif, bukan dari baris yang hilang. Unit yang nilainya nol
bulat tidak terpengaruh catatan ini, dan angka DTSA dihitung tanpa syarat
apa pun.

## Akibatnya pada aplikasi

Laporan berikut akan kosong untuk unit yang datanya hilang, dan itu bukan
kesalahan query:

* Daftar Akta Jual Beli, Rekap AJB, Rekap Estimasi Biaya AJB
  -> bergantung pada AKTA
* Daftar Sertipikat Pecahan, Kartu Surat Tanah
  -> bergantung pada SERTIPIKAT_IDK
* Daftar Pengajuan Balik Nama
  -> bergantung pada keduanya

Modelnya sendiri sudah terbukti benar memakai unit SBKS, yang menghasilkan
8.892 baris dengan isi yang sesuai. Kode dan jalur datanya sama persis;
yang membedakan hanya ada atau tidaknya baris pada kedua tabel itu.

Begitu barisnya dimigrasikan, laporannya akan terisi tanpa perlu mengubah
kode, karena awalan kuncinya sudah terbukti DBPSA-, sama seperti yang
disusun oleh model.

---

# Tambahan 17 September 2026: asal-usul awalan DBPSA- dan DBPSS-

Pemeriksaan Rekap Jaminan Bank menemukan hal yang menjelaskan seluruh
masalah awalan pada catatan di atas.

Pada SQL Server, `JAMINAN.SERTIPIKAT_ID` **tidak memakai awalan sama
sekali**. Isinya angka polos, 6.544 baris dengan nilai terkecil 16 dan
terbesar 29041.

Artinya awalan `DBPSA-` dan `DBPSS-` bukan berasal dari data aslinya,
melainkan **dibuat oleh proses migrasi** untuk membedakan baris yang
datang dari dua database SQL Server yang berbeda, yaitu SRIS_PUSAT dan
SRIS_SERPONG, yang digabung menjadi satu database PostgreSQL.

Itu menjelaskan mengapa nomornya tumpang tindih hampir seluruhnya:
kedua database punya penomoran sendiri yang sama-sama mulai dari 1.

## Akibatnya

Kolom kunci yang dimigrasikan sebagai `numeric` kehilangan penanda asal
database itu, karena awalan berupa teks tidak muat pada kolom angka.
Enam tabel terkena:

    sr_akta.sertipikat_id          sr_peralihan.ppjb_id
    sr_pengambilan.ppjb_id         sr_sertipikat_idk.sertipikat_id
    sr_biaya_ajb.ppjb_id           sr_jaminan.sertipikat_id

Kolom kunci yang dimigrasikan sebagai `varchar` selamat, misalnya
`sr_stok.stok_id`, `sr_sertipikat.sertipikat_id`, dan `sr_ppjb.ppjb_id`.

## Saran untuk yang menangani migrasi

Kolom kunci jangan dibuat bertipe angka. Bila tipenya teks, penanda asal
databasenya ikut terbawa dan seluruh sambungan antar tabel bekerja tanpa
perlu ditebak.

## Celah baris pada sr_jaminan

Dibandingkan pada baris yang siap tampil, yaitu NO_JAMINAN terisi, belum
lunas, dan belum batal:

    SQL Server SRIS_PUSAT   4.670 baris
    PostgreSQL              3.732 baris

Selisihnya paling sedikit 938 baris, dan bisa lebih besar karena
PostgreSQL semestinya memuat SRIS_PUSAT dan SRIS_SERPONG sekaligus
sedangkan angka SQL Server di atas hanya dari SRIS_PUSAT. Selisih
terbesar ada pada baris yang JENIS_JAMINAN-nya kosong, 327 lawan 1.190.

## Catatan JENIS_JAMINAN

Kolomnya varchar(1) berisi kode satu huruf, bukan tulisan seperti pada
dropdown layar. Sebarannya pada baris siap tampil:

    4 = 2.325    2 = 667    (kosong) = 327    A = 162
    H = 110      P = 70     3 = 42            5 = 28      T = 1

Kode lama A, H, P, T, dan kosong dipakai pada berkas bertahun 1992
sampai 1995; kode angka 2 sampai 5 pada berkas 2004 sampai 2013. Tidak
ada tabel acuan artinya di seluruh schema. Artinya belum ditetapkan dan
sengaja tidak ditebak.
