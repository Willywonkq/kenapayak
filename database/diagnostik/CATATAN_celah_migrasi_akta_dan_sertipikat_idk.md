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

Kolomnya char(1) berisi kode satu karakter, bukan tulisan seperti pada
dropdown layar. Sebarannya pada baris siap tampil:

    4 = 2.324    2 = 667    (kosong) = 327    A = 162
    H = 110      P = 70     3 = 42            5 = 28      T = 1

Tidak ada tabel acuan artinya, baik di PostgreSQL maupun di SQL Server
sumbernya; sudah dicari ke seluruh schema dan ke seluruh isi kolom teks.
Artinya hanya hidup di dalam kode aplikasi desktop.

Pemetaan 1=IMB, 2=Akta Jual Beli, 3=Sertipikat, 4=PPJB, 5=Peralihan Hak
akhirnya dipasang berdasarkan perilaku data, bukan tebakan buta. Dasar
dan tingkat keyakinan tiap kode dicatat pada konstanta JENIS_JAMINAN_KODE
di `dftr_jaminan_bank_m.php`. Yang perlu diketahui pembaca berkas ini:
kode 3 hanya disimpulkan lewat eliminasi, dan kode 1 tidak bisa diuji
karena tidak muncul satu baris pun. Kalau keduanya meleset, yang keliru
hanya tulisan di dropdown, bukan isi laporannya.

Kode lama A, H, P, T, dan kosong sebanyak 670 baris dipakai pada berkas
bertahun 1992 sampai 2004, sebelum sistem berganti ke kode angka mulai
1998. Baris itu hanya keluar pada pilihan Semua, di desktop pun begitu.


## Tabel mana yang berhenti dimigrasi, dan kapan

Tanggal pemeriksaan: 18 September 2026
Query: `jaminan_bank_cek_kemutakhiran_pg.sql` dan
`sqlserver_jaminan_bank_cek_kemutakhiran.sql`. Keduanya hanya membaca.

Temuan ini MENGUBAH kesimpulan sebelumnya. Semula celah baris pada
sr_jaminan diduga migrasi sebagian yang acak. Ternyata bukan. Pola
tanggal perekaman terakhir tiap tabel memperlihatkan sesuatu yang jauh
lebih jelas.

Sebagian besar tabel masih mutakhir sampai September 2026:

| tabel              | perekaman terakhir  |
|--------------------|---------------------|
| sr_undangan_st     | 2026-09-16          |
| sr_sektor          | 2026-09-15          |
| sr_sertipikat      | 2026-07-28          |
| sr_ppjb            | 2026-07-07          |
| sr_jadwal_angsuran | 2026-07-07          |
| sr_stok            | 2026-07-02          |
| sr_nasabah         | 2026-04-17          |

Tetapi TIGA tabel berhenti serentak pada akhir Februari 2024:

| tabel        | tgl_entry terakhir        | tgl_update terakhir       |
|--------------|---------------------------|---------------------------|
| sr_jaminan   | 2024-02-27 10:19:05       | 2024-02-27 10:09:33       |
| sr_peralihan | 2024-02-27 16:30:44       | 2024-02-27 16:47:43       |
| sr_akta      | 2024-02-26 12:53:06       | 2024-02-15 16:40:18       |

Jadi ini BUKAN salinan lama yang seragam. Sebagian besar tabel terus
diperbarui, hanya ketiga tabel itu yang tertinggal lebih dari dua tahun
setengah.

## Hubungannya dengan awalan kunci yang hilang

Ketiga tabel yang berhenti itu ADA DI DALAM daftar tabel yang kolom
kuncinya dimigrasi sebagai numeric sehingga awalannya terbuang:

    sr_akta.sertipikat_id       sr_peralihan.ppjb_id
    sr_jaminan.sertipikat_id    sr_pengambilan.ppjb_id
    sr_biaya_ajb.ppjb_id        sr_sertipikat_idk.sertipikat_id

Dua gejala yang selama ini ditangani terpisah ternyata satu akar: ada
proses pemindahan tersendiri, dijalankan sekali sekitar 26 sampai 27
Februari 2024, yang (a) membuang awalan karena kolom kuncinya dijadikan
angka, dan (b) tidak pernah dijalankan lagi sejak itu. Tabel yang
ditangani proses lain tetap terbarui dan awalannya tetap utuh.

Karena QUERY 4 dibatasi 60 baris teratas, daftar di atas belum tentu
lengkap. Tabel lain yang juga tertinggal masih mungkin ada. QUERY 6 pada
berkas PostgreSQL mendaftarnya dari yang paling tertinggal.

## Akibatnya pada laporan

Diuji pada Daftar Jaminan Bank, unit SBKS, blok A sampai ZZ, TGL BANK
01-07-2023 sampai 18-09-2026, semua cluster, semua status AJB, semua
jenis jaminan:

    aplikasi desktop   163 baris
    aplikasi web         8 baris

Rincian 163 baris itu menurut tahun, diambil dari SQL Server dengan
filter yang sama persis:

    2023 = 63    2024 = 59    2025 = 39    2026 = 2

Di PostgreSQL yang tersedia hanya 2023 sebanyak 8 dan 2024 sebanyak 1.
Seluruh 2025 dan 2026 tidak ada sama sekali.

Jumlah keseluruhan tabel jaminan:

    SQL Server SRIS_PUSAT   6.544 baris
    PostgreSQL sr_jaminan   5.190 baris

Modelnya sendiri sudah diperiksa dan tidak keliru. Pada corong penyusutan
baris, tahap penyusunan ulang kunci sertipikat tidak menghilangkan satu
baris pun, 22 lawan 22. Seluruh penyusutan terjadi pada saringan tanggal,
3.732 menjadi 22, yaitu murni karena barisnya tidak ada.

## Yang perlu dilakukan

1. Jalankan ulang pemindahan untuk sr_jaminan, sr_akta, sr_peralihan,
   dan tabel lain yang QUERY 6 tunjukkan ikut tertinggal.
2. Pindahkan kolom kuncinya sebagai TEKS, bukan angka, supaya penanda
   asal databasenya ikut terbawa. Ini sekaligus menghapus kebutuhan
   menebak awalan, yang sekarang membuat 6 dari 8 baris yang tampil
   berstatus rancu.
3. Setelah keduanya beres, tidak ada perubahan kode yang diperlukan.
   Model sudah memakai awalan apa adanya bila kolomnya bertipe teks.


## Celah pada sr_imb dan sr_pbb, serta akibatnya pada enam unit

Tanggal pemeriksaan: 18 September 2026
Query: `imb_dan_pbb_cek_skema.sql`, `imb_dan_pbb_cek_asal_database.sql`,
dan `sqlserver_imb_dan_pbb_cek_asal.sql`. Seluruhnya hanya membaca.

### Temuan 1: hanya SRIS_PUSAT yang terbawa

|          | SRIS_PUSAT | SRIS_SERPONG | PostgreSQL | kurang |
|----------|------------|--------------|------------|--------|
| IMB      | 25.784     | 13.381       | 21.998     | 3.786  |
| PBB      | 19.397     | 20.366       | 15.429     | 3.968  |

Jumlah di PostgreSQL bahkan lebih kecil daripada SRIS_PUSAT saja. Jadi
kedua tabel tidak memuat satu pun baris SRIS_SERPONG, dan baris
SRIS_PUSAT-nya pun belum lengkap.

### Temuan 2: penyusunan ulang awalan sudah tepat

Jumlah baris per unit dibandingkan dengan sumbernya, khusus unit
berawalan DBPSA- yang memang berasal dari SRIS_PUSAT:

|     | jumlah sumber | jumlah hasil susun | selisih | melebihi sumber |
|-----|---------------|--------------------|---------|-----------------|
| IMB | 25.780        | 21.994             | 3.786   | tidak ada       |
| PBB | 19.259        | 15.291             | 3.968   | tidak ada       |

Selisih itu sama persis dengan kekurangan baris pada tabel hasil
migrasi, 3.786 dan 3.968. Artinya seluruh selisihnya adalah baris yang
belum termigrasi, bukan baris yang tertukar. Beberapa unit malah cocok
sampai satuan: pada IMB yaitu WGP, BHMS, MNST, dan pada PBB yaitu GDOR,
BHMS, MNST, SKPN.

Perlu dicatat, persentase baris "rancu" pada unit-unit itu tinggi, 79
sampai 99 persen, tetapi jumlahnya tetap cocok. Jadi angka rancu adalah
ukuran RISIKO, bukan ukuran kesalahan. Risiko itu tidak berbuah karena
tabel sumbernya kebetulan hanya berisi satu keluarga awalan.

### Temuan 3: enam unit akan menampilkan laporan karangan

Enam unit memakai awalan DBPSS-, yaitu SSPG, SPCK, KSLV, KSVT, KSLL, dan
SPCH. Karena hampir semua angka dipakai kedua keluarga awalan,
penyusunan ulang tetap menemukan sertipikat untuk unit itu, padahal
tabel sumbernya tidak memuat satu pun barisnya.

Bila dibiarkan, yang tampil adalah baris milik unit lain:

    IMB  20.140 baris     PBB  13.331 baris     total 33.471 baris

dan tidak satu pun di antaranya tergolong pasti.

Karena itu model dilengkapi `pastikanKeluargaAda()` yang menghentikan
laporan dengan pesan, bukan menampilkannya. Keputusannya dibaca dari
data, memakai baris yang angkanya hanya dipakai satu keluarga awalan
karena asal-usul baris semacam itu pasti:

    sr_imb   DBPSA- 1.650 baris pasti    DBPSS- 0
    sr_pbb   DBPSA- 1.947 baris pasti    DBPSS- 0

Penjagaan ini membuka sendiri begitu migrasinya diperbaiki, tanpa perlu
mengubah kode.

### Ringkasan tabel yang bermasalah sejauh ini

| tabel         | kunci jadi angka | baris kurang | SERPONG terbawa |
|---------------|------------------|--------------|-----------------|
| sr_akta       | ya               | ya           | belum diperiksa |
| sr_peralihan  | ya               | ya           | belum diperiksa |
| sr_jaminan    | ya               | ya           | belum diperiksa |
| sr_sertipikat_idk | ya           | ya           | belum diperiksa |
| sr_imb        | ya               | ya, 3.786    | TIDAK           |
| sr_pbb        | ya               | ya, 3.968    | TIDAK           |

Karena sr_imb dan sr_pbb ternyata sama sekali tidak memuat SERPONG,
tabel lain pada daftar itu patut diperiksa dengan cara yang sama
sebelum laporannya dipercaya untuk unit berawalan DBPSS-.

### Yang perlu dilakukan

1. Pindahkan kolom kunci sebagai TEKS, bukan angka, supaya penanda asal
   databasenya ikut terbawa. Ini menghapus seluruh kebutuhan menebak.
2. Sertakan SRIS_SERPONG pada pemindahan sr_imb dan sr_pbb.
3. Lengkapi baris SRIS_PUSAT yang belum terbawa.
4. Setelah itu tidak ada perubahan kode yang diperlukan.


## sr_nasabah tidak lengkap, dan ini berdampak ke BANYAK laporan

Tanggal pemeriksaan: 18 September 2026
Query: `imb_bandingkan_tahap_sbks.sql`, `sqlserver_imb_bandingkan_tahap_sbks.sql`,
`nasabah_cek_kelengkapan.sql`. Seluruhnya hanya membaca.

Temuan ini yang paling luas akibatnya sejauh ini, dan ditemukan tanpa
sengaja ketika menelusuri selisih baris pada Daftar IMB.

### Bagaimana ketahuannya

Corong tujuh tahap dijalankan berdampingan di kedua basis data, dengan
filter yang sama persis: unit SBKS, blok A sampai ZZ, TGL INPUT
01-01-2020 sampai 31-12-2023.

| tahap                        | PostgreSQL | SQL Server | selisih |
|------------------------------|-----------:|-----------:|--------:|
| sr_imb dalam rentang tanggal |      8.789 |      8.994 |     205 |
| + ketemu sertipikatnya       |      8.788 |      8.994 |     206 |
| + stok SBKS aktif            |      6.064 |      6.178 |     114 |
| + PPJB aktif bukan turunan   |      6.028 |      6.143 |     115 |
| + pembeli aktif              |      6.029 |      6.144 |     115 |
| + nasabahnya ketemu          |      5.596 |      6.144 | **548** |
| + saringan blok, yang tampil |      5.596 |      6.144 |     548 |

Selisihnya bertahan di angka 115 sampai tahap pembeli, lalu melompat ke
548 pada tahap nasabah. Jadi 433 baris hilang HANYA karena nasabahnya
tidak ada di hasil migrasi.

Tahap PPJB terbukti sehat: PostgreSQL kehilangan 36 stok, SQL Server 35,
praktis setara.

### Ukuran celahnya

    baris pembeli aktif                  62.326
    di antaranya nasabahnya ketemu       34.077   (54,7 %)
    nasabahnya TIDAK ADA                 28.249   (45,3 %)

    sr_nasabah di PostgreSQL             42.464 baris

Sudah dipastikan ini BUKAN soal bentuk nilai seperti awalan yang
terbuang pada kolom kunci lain. Perbandingan sebagai teks dan sebagai
angka sama-sama menghasilkan 34.077, jadi barisnya memang tidak ada.

### Kenapa ini penting

sr_nasabah dipakai hampir SEMUA laporan yang menampilkan nama pembeli,
bukan Daftar IMB saja. Daftar Jaminan Bank, Daftar Sertipikat Pecahan,
Pengajuan Balik Nama, Sertipikat Balik Nama, Berakhir Haknya, dan PBB
semuanya menyambung lewat sr_pembeli_ppjb ke sr_nasabah dengan INNER
JOIN, persis seperti query desktopnya.

Artinya kekurangan baris pada laporan-laporan itu sebagian mungkin
berasal dari sini, bukan dari tabel utamanya masing-masing. Ketika
sr_nasabah dilengkapi, laporan-laporan itu akan ikut bertambah barisnya
tanpa perlu mengubah kode sama sekali.

### Yang perlu dilakukan

1. Pindahkan ulang NASABAH dari kedua database sumber.
2. Periksa juga apakah NASABAH_ID ikut kehilangan penandanya, sama
   seperti kolom kunci lain, karena dua database sumber sama-sama
   menomori dari satu.

### Ringkasan celah Daftar IMB unit SBKS

Dari 548 baris selisih, seluruhnya kini terjelaskan dan tidak satu pun
berasal dari kesalahan model:

    114 baris  sr_imb belum termigrasi pada rentang itu
    433 baris  nasabahnya tidak ada di sr_nasabah
      1 baris  sertipikatnya tidak ketemu

Untuk rentang 2020 sampai 2023 kekurangan sr_imb tergolong kecil, 205
baris untuk seluruh unit. Yang hilang besar adalah tahun 2024 ke atas,
3.538 baris, karena sr_imb berhenti pada 28 Desember 2023.


## sr_sertipikat_idk ikut berhenti, dan akibatnya pada Daftar Sertipikat Pecahan

Tanggal pemeriksaan: 23 September 2026
Query: `pecahan_bandingkan_tahap_sbks.sql`, `pecahan_cari_kolom_tanggal.sql`,
`pecahan_cek_kemutakhiran_idk.sql`, dan `sqlserver_pecahan_bandingkan_sbks.sql`.
Seluruhnya hanya membaca.

Diuji pada unit SBKS, blok A sampai ZZ, Tgl Input Sert/Gabung 01-07-2023
sampai 23-09-2026, semua sektor, Apartemen tidak dicentang, Kartu Surat
Tanah tidak dicentang, Tampilkan Sertipikat Penggabungan tidak dicentang,
Status AJB Semua.

    aplikasi desktop   753 baris
    aplikasi web       129 baris

### Modelnya tidak keliru

Pada SQL Server, dengan saringan yang sama persis:

| cara menyaring tanggal        | baris |
|-------------------------------|-------|
| TGL_INPUT_SER                 |   753 |
| ISNULL(TGL_INPUT_GABUNG, ...) |   753 |
| TGL_SERTIPIKAT                | 1.353 |
| SERTIPIKAT_IDK.TGL_INPUT      |   651 |

Angka 753 itu sama persis dengan yang tampil di desktop, dan hanya
TGL_INPUT_SER yang menghasilkannya. Itulah kolom yang dipakai model.
COALESCE dengan TGL_INPUT_GABUNG lebih dulu pun memberi angka yang sama,
jadi urutannya tidak merugikan.

Tiga dugaan lain sudah diuji dan seluruhnya meleset:

* bukan kolom tanggal pada tabel induk, sebab keempatnya memberi nol
  baris dalam rentang di PostgreSQL;
* bukan arti centang Apartemen, sebab seluruh baris yang masuk rentang
  memang bukan apartemen sehingga memasukkannya tidak menambah apa pun;
* bukan penyusunan ulang awalan kunci, sebab dengan DBPSA- ketemu 23.304
  dari batas atas 23.307, hanya 4 baris yang hilang di tahap itu.

### Barisnya memang tidak ada

| ukuran                        | PostgreSQL | SQL Server | kurang |
|-------------------------------|-----------:|-----------:|-------:|
| SERTIPIKAT_IDK seluruhnya     |     23.308 |     27.550 |  4.242 |
| SBKS, semua jenis             |      8.892 |      9.996 |  1.104 |
| SBKS, bukan APT dan KTR       |      4.022 |      5.126 |  1.104 |
| SBKS, dalam rentang tanggal   |        129 |        753 |    624 |

Sebaran per tahun memperlihatkan kapan berhentinya. Sampai 2019 kedua
sisi cocok sampai satuan, lalu menyimpang dan melebar:

| tahun | PostgreSQL | SQL Server | selisih |
|-------|-----------:|-----------:|--------:|
| 2013  |      1.659 |      1.659 |       0 |
| 2019  |        178 |        178 |       0 |
| 2020  |        528 |        530 |       2 |
| 2021  |        259 |        277 |      18 |
| 2022  |        509 |        604 |      95 |
| 2023  |        107 |        402 |     295 |
| 2024  |         22 |        380 |     358 |
| 2025  |          0 |        262 |     262 |
| 2026  |          0 |          4 |       4 |

Pada seluruh unit, sr_sertipikat_idk berhenti pada 2024 dengan 53 baris,
sedangkan 2023 masih 1.415. Tabel pasangannya, sr_sertipikat, tetap
mutakhir: 2024 sebanyak 2.666, 2025 sebanyak 2.249, dan 2026 sebanyak
631. Jadi yang tertinggal memang hanya tabel induknya.

Dilihat dari sisi sertipikat, dari 650 sertipikat SBKS yang memenuhi
syarat dan masuk rentang tanggal, 575 di antaranya TIDAK punya pasangan
di sr_sertipikat_idk. Itulah 624 baris laporan yang hilang.

Dengan ini sr_sertipikat_idk resmi masuk rombongan sr_akta, sr_jaminan,
dan sr_peralihan: tabel yang kolom kuncinya dijadikan angka oleh proses
pemindahan yang sama, dan berhenti dijalankan sejak awal 2024.

### Catatan kecil

Tanggal 6201-05-02 pada SERTIPIKAT_IDK.TGL_INPUT ada di SQL Server juga,
jadi itu salah ketik pada data aslinya, bukan cacat migrasi.

### Dua hal pada kode yang perlu ditindaklanjuti terpisah

1. SUDAH DIKERJAKAN, lihat bagian berikutnya. Ralat atas catatan awal:
   laporan ini TIDAK berisiko menampilkan baris milik unit lain, sebab
   saringan unit diterapkan pada stok SESUDAH sambungan kunci, sehingga
   baris unit lain selalu terbuang. Gejalanya berbeda dan lebih sunyi,
   yaitu laporan yang kosong tanpa sebab yang kelihatan.

2. Centang "Tampilkan Sertipikat Penggabungan" tidak mengubah baris yang
   tampil sama sekali. Nilainya hanya dipakai memilih rumus selisih
   luas, sedangkan sumber tanggal ditentukan oleh jenis laporannya,
   bukan oleh centang itu. Perlu dipastikan dulu ke desktop apa yang
   semestinya berubah.


## Perbaikan awalan per unit pada Daftar Sertipikat Pecahan

Tanggal: 23 September 2026

### Apa yang keliru

`awalanSertipikatIdk()` menyusun kembali awalan kunci dengan mencocokkan
isi `ser_pisah` dan `su_pisah` antara sr_sertipikat_idk dan sr_sertipikat.
Caranya benar, tetapi hasilnya diambil satu saja yang paling sering cocok,
lalu awalan itu dipakai untuk SELURUH unit.

Pada data sekarang pemenangnya DBPSA-. Akibatnya unit berawalan DBPSS-
menyusun kunci dengan awalan yang salah, kuncinya tidak ketemu, dan
laporannya kosong. Yang membuatnya sulit disadari: kosong karena awalan
salah tidak bisa dibedakan dari kosong karena memang tidak ada data.

### Apa yang diubah

Awalan kini diambil dari unitnya sendiri lewat `awalanUnit()`, dibaca
dari `sr_stok.stok_id` milik unit itu, sama seperti yang sudah dipakai
model IMB dan PBB. `awalanSertipikatIdk()` dibuang karena tidak terpakai
lagi.

Ditambah `pastikanKeluargaAda()` yang menolak melayani unit yang tidak
punya satu pun baris idk yang (a) asal databasenya dapat dipastikan,
yaitu angkanya hanya dipakai satu keluarga, dan (b) terhubung ke stok
unit itu. Syarat kedua lebih ketat daripada model lain, dan memang perlu:
pada sr_sertipikat_idk baris yang pasti milik DBPSS- hanya 3 dari 19.468,
sehingga penjagaan yang hanya menghitung per keluarga akan lolos oleh
angka yang praktis derau.

### Bukti

Diuji dengan data kecil bersusun dua keluarga: stok DBPSA-100 milik SBKS
dan DBPSS-200 milik SSPG, sertipikat DBPSA-500 dan DBPSS-600 yang khas,
serta pasangan DBPSA-700 dan DBPSS-700 yang angkanya dipakai keduanya.

| unit | kode lama | kode baru       |
|------|-----------|-----------------|
| SBKS | 2 baris   | 2 baris, sama   |
| SSPG | 0 baris   | 2 baris, miliknya sendiri |

Ketika baris pasti milik DBPSS- dihapus dari data uji, unit SSPG DITOLAK
dengan pesan, bukan dikosongkan diam-diam.

Unit yang keluarganya sama dengan pemenang lama, termasuk SBKS, tidak
berubah sama sekali.

### Masih ada di dua model lain

`awalanSertipikatIdk()` dengan pola yang sama masih dipakai oleh
`dftr_pengajuan_balik_nama_m.php` dan
`daftar_sertifikat_berakhir_haknya_m.php`. Keduanya belum diukur, jadi
belum diubah.


## Daftar Pengajuan Sertipikat Balik Nama: satu cacat kode, sisanya sr_akta

Tanggal pemeriksaan: 23 September 2026
Query: `balik_nama_bandingkan_tahap_sbks.sql` dan
`sqlserver_balik_nama_bandingkan_sbks.sql`. Keduanya hanya membaca.

Diuji pada unit SBKS, blok A sampai Z, sektor semua, Tgl Input AJB
01-07-2023 sampai 23-09-2026.

    aplikasi desktop   966 baris
    aplikasi web        87 baris

### Corong

| tahap                                   | baris |
|-----------------------------------------|------:|
| AKTA dalam rentang, SELURUH unit        |   638 |
| kuncinya ketemu di sertipikat           |   638 |
| belum balik nama dan punya stok         |   232 |
| stoknya milik SBKS                      |   151 |
| lolos saringan blok CARA LAMA           |    87 |
| lolos saringan blok CARA BENAR          |   151 |
| sesudah disambung ke tabel induk        |   155 |

### Cacat kode: saringan blok

Dari 87 menjadi 151, yaitu 64 baris kembali. Sebabnya dua, dan keduanya
sudah diperbaiki:

* kedua penanda pada cabang kedua sama-sama diisi blok akhir, sehingga
  cabang itu hanya cocok bila bloknya persis sama dengan batas atas;
* batas atasnya dibiarkan 'Z', padahal dalam perbandingan teks 'ZBJ'
  lebih besar daripada 'Z'.

Blok yang terbuang seluruhnya berawalan Z, dan untuk SBKS jumlahnya
besar: ZCC 98 stok, ZD 85, ZCB 66, ZCD 65, ZAC 60, dan seterusnya.

Sepuluh model lain sudah mengubah blok akhir 'Z' menjadi 'ZZ' lebih
dulu, mengikuti desktop yang mencetak "BLOK : A s/d ZZ" dari masukan
'A' sampai 'Z'.

### Sisanya bukan kode

Seluruh tabel sr_akta di PostgreSQL hanya memuat 638 baris pada rentang
itu UNTUK SELURUH UNIT, sedangkan desktop menampilkan 966 untuk SBKS
saja. Berapa pun saringan yang dilonggarkan, angka itu tidak mungkin
tercapai.

Sebaran tahun sr_akta:

    2022  1.513     2023  929     2024  12     2025  0     2026  0

Berhenti pada awal 2024, sesuai catatan terdahulu bahwa sr_akta,
sr_jaminan, dan sr_peralihan berhenti serentak Februari 2024.

### Catatan ketelitian

Corong ini mengukur tahap unit SESUDAH saringan status balik nama, jadi
belum diketahui berapa baris AKTA milik SBKS sebelum saringan itu.
Untuk kesimpulan di atas hal itu tidak berpengaruh, sebab 638 baris
untuk seluruh unit sudah lebih kecil daripada 966.

### Dipastikan di sumber

Angka SQL Server, dengan saringan yang sama persis:

| tahap                                   | PostgreSQL | SQL Server |
|-----------------------------------------|-----------:|-----------:|
| AKTA dalam rentang, seluruh unit        |        638 |      2.898 |
| belum balik nama                        |        232 |      1.662 |
| unit SBKS                               |        151 |        935 |
| blok dan nomor ada                      |        151 |        935 |
| sesudah disambung ke tabel induk        |        155 |        966 |

Angka 966 pada baris terakhir sama persis dengan yang tampil di
desktop. Artinya dua hal.

Pertama, desktop MEMANG menyambung ke SERTIPIKAT_IDK dengan INNER JOIN,
dan memang menggandakan baris untuk sertipikat yang punya lebih dari
satu induk: 935 sertipikat berbeda menjadi 966 baris. Jadi perilaku
model kita, 151 sertipikat menjadi 155 baris, SUDAH BENAR dan bukan
cacat. Dugaan semula bahwa sambungan itu berlebihan ternyata salah.

Kedua, seluruh sisa selisihnya murni kekurangan baris sr_akta.

Sebaran tahun membuktikannya. Sampai 2023 kedua sisi cocok hampir
sempurna, lalu terjun:

| tahun | PostgreSQL | SQL Server | selisih |
|-------|-----------:|-----------:|--------:|
| 2020  |        850 |        850 |       0 |
| 2021  |      1.438 |      1.438 |       0 |
| 2022  |      1.513 |      1.513 |       0 |
| 2023  |        929 |        930 |       1 |
| 2024  |         12 |      1.046 |   1.034 |
| 2025  |          0 |        888 |     888 |
| 2026  |          0 |        339 |     339 |

Keseluruhan sr_akta kehilangan 2.260 dari 2.898 baris pada rentang ini,
atau 78 persen.

### Catatan ketelitian

Nisbah tahap "belum balik nama" berbeda di kedua sisi, 36 persen di
PostgreSQL dan 57 persen di SQL Server. Itu wajar dan bukan pertanda
saringan yang berbeda: baris PostgreSQL yang tersisa hampir seluruhnya
bertahun 2023, dan akta lama lebih besar kemungkinannya sudah selesai
balik nama, sedangkan baris 2025 dan 2026 yang hanya ada di SQL Server
sebagian besar memang masih tertunda.

Model ini juga masih memakai `awalanSertipikatIdk()`, yaitu awalan
tunggal untuk seluruh unit, sama seperti yang sudah diperbaiki pada
model pecahan. Untuk SBKS tidak berpengaruh karena keluarganya memang
DBPSA-, tetapi untuk unit DBPSS- laporannya akan kosong tanpa sebab
yang kelihatan.


## Cacat saringan blok ternyata ada di dua model, dan keduanya diperbaiki

Tanggal: 23 September 2026

### Cacat yang sama persis

Dua model menulis syarat bloknya dengan dua kekeliruan yang sama:

* kedua penanda pada cabang kedua sama-sama diisi blok AKHIR, bukan awal
  dan akhir, sehingga cabang itu hanya cocok bila bloknya persis sama
  dengan batas atas;
* batas atasnya dibiarkan 'Z', padahal dalam perbandingan teks 'ZBJ'
  lebih besar daripada 'Z' karena 'Z' awalan yang lebih pendek.

Akibatnya seluruh blok berawalan Z gugur pada kedua cabang sekaligus.
Untuk unit SBKS itu berat sekali: ZCC 98 stok, ZD 85, ZCB 66, ZCD 65,
ZAC 60, dan puluhan blok lain.

    dftr_pengajuan_balik_nama_m.php     diukur, 87 menjadi 151 baris
    dftr_sertifikat_balik_nama_m.php    cacatnya identik, belum diukur

Sepuluh model lain sudah benar karena mengubah blok akhir 'Z' menjadi
'ZZ' lebih dulu, mengikuti desktop yang mencetak "BLOK : A s/d ZZ" dari
masukan 'A' sampai 'Z'. Kedua model di atas kini ikut.

### Awalan per unit pada pengajuan balik nama

`awalanSertipikatIdk()` diganti `awalanUnit()` dan ditambah
`pastikanKeluargaAda()`, sama persis seperti yang sudah dikerjakan pada
model Daftar Sertipikat Pecahan. Alasannya sama: satu awalan untuk
seluruh unit membuat unit di luar keluarga pemenang menyusun kunci yang
tidak akan pernah ketemu, dan laporannya kosong tanpa sebab yang
kelihatan.

### Bukti

Data uji bersusun dua keluarga, dengan blok AA milik SBKS, blok ZBJ
milik SBKS, blok ZCL milik SSPG, dan satu unit KOSG yang punya stok
tetapi tidak punya baris idk sama sekali.

Daftar Pengajuan Sertipikat Balik Nama:

| unit | kode lama | kode baru                |
|------|-----------|--------------------------|
| SBKS | 1 baris   | 2 baris, ZBJ kembali     |
| SSPG | 0 baris   | 1 baris, ZCL kembali     |
| KOSG | 0 baris   | DITOLAK dengan pesan     |

Daftar Sertipikat Balik Nama:

| unit | kode lama | kode baru            |
|------|-----------|----------------------|
| SBKS | 1 baris   | 2 baris, ZBJ kembali |

Unit SSPG kembali bukan hanya karena saringan blok, melainkan karena
kedua perbaikan sekaligus: bloknya berawalan Z dan keluarganya DBPSS-.

### Masih tersisa satu

`daftar_sertifikat_berakhir_haknya_m.php` masih memakai
`awalanSertipikatIdk()`. Saringan bloknya sudah benar. Belum diukur,
jadi belum diubah.


## Daftar Sertipikat Berakhir Hak: terbukti tabel induk, bukan kode

Tanggal pemeriksaan: 23 September 2026
Query: `balik_nama_dan_berakhir_hak_cek_sbks.sql`. Hanya membaca.

Diuji pada unit SBKS, blok A sampai Z, sektor semua, Tgl Berlaku
01-07-2023 sampai 23-09-2026, Apartemen tidak dicentang, Status AJB
Semua.

    aplikasi desktop   4 baris
    aplikasi web       2 baris

Keempat barisnya ditelusuri satu per satu:

| blok    | sertipikat_id | tgl_berlaku | dalam rentang | jenis | punya induk |
|---------|---------------|-------------|---------------|-------|-------------|
| RCH/009 | DBPSA-25836   | 2025-03-20  | ya            | RMH   | TIDAK       |
| RDE/007 | DBPSA-23657   | 2025-04-04  | ya            | RMH   | ya          |
| TD/021  | DBPSA-13943   | 2024-02-16  | ya            | RKN   | ya          |
| ZCB/070 | DBPSA-28442   | 2025-06-12  | ya            | RMH   | TIDAK       |

Keempatnya lolos saringan tanggal, unit, blok, dan jenis. Yang
membedakan hanya satu kolom: dua baris yang hilang dari web TIDAK punya
pasangan di sr_sertipikat_idk.

Corong keseluruhannya menegaskan hal yang sama, 4 calon menjadi 4
sesudah saringan jenis, 4 sesudah saringan blok, dan 2 sesudah
sambungan ke tabel induk.

Modelnya tidak perlu diubah. Laporan ini akan terisi sendiri begitu
sr_sertipikat_idk dimigrasikan ulang.

## Daftar Sertipikat Balik Nama: seluruh selisihnya sr_nasabah

Saringan dan corong yang sama, unit SBKS.

    aplikasi desktop   353 baris
    aplikasi web       317 baris

| tahap                              | baris |
|------------------------------------|------:|
| sertipikat dalam rentang tanggal   | 1.406 |
| stoknya milik SBKS                 | 1.063 |
| bukan APT dan KTR                  |   353 |
| dalam rentang blok                 |   353 |
| ada PPJB aktif bukan turunan       |   353 |
| ada pembeli aktif                  |   353 |
| nasabahnya ketemu                  |   317 |

Dua angka terakhir menjawab semuanya. Sampai tahap pembeli aktif
hasilnya 353, sama persis dengan desktop. Begitu nasabah diwajibkan
ketemu, tinggal 317, sama persis dengan web.

Jadi seluruh selisih 36 baris berasal dari sr_nasabah yang belum
lengkap, tidak ada satu pun yang berasal dari kesalahan model.

Modelnya menyambung nama pembeli dengan INNER JOIN, dan itu SETIA pada
desktop yang juga menyambung NASABAH dengan koma. Bedanya hanya isi
tabelnya: di SQL Server NASABAH lengkap 46.818 baris, sedangkan
sr_nasabah hasil migrasi kehilangan 45 persen barisnya.

### Catatan atas kekeliruan corong yang pertama

Susunan corong yang pertama KELIRU: saringan jenis bangunan tidak ikut
dihitung, padahal model membuang APT dan KTR ketika centang Apartemen
kosong. Angkanya keluar 952 sehingga tidak bisa disandingkan dengan 317
yang tampil. Sesudah dibetulkan, tahap itu sendiri yang paling besar
memangkas, dari 1.063 menjadi 353.

---

## Rekap PPAT / Akta Jual Beli: 546 lawan 1.928, tidak ada cacat kode

Saringan: unit SBKS, blok A s/d Z, semua lokasi, semua sektor, Tanggal
AJB 01-07-2023 s/d 24-09-2026, kotak Belum Ttd Akta tidak dicentang.

### Corong tujuh tahap, disandingkan

| tahap | PostgreSQL | SQL Server | sama? |
|-------|-----------:|-----------:|:-----:|
| 1  AKTA seluruhnya                        | 17.407 | 20.386 | tidak |
| 2  akta dalam rentang, semua unit         |    757 |  3.727 | tidak |
| 3  stok unit, aktif, blok dan nomor ada   |  9.807 |  9.807 | **ya** |
| 4  ppjb aktif dan parent kosong, semua unit | 49.527 | 29.097 | tidak |
| 5  ppjb yang stoknya milik unit           |  9.560 |  9.560 | **ya** |
| 6  yang punya akta dalam rentang          |    546 |  1.928 | tidak |
| 7  sesudah pembeli aktif (yang tampil)    |    546 |  1.928 | tidak |

Tahap 7 sisi SQL Server, 1.928, sama persis dengan nomor baris terakhir
di layar desktop. Jadi corong ini memang meniru desktop dengan tepat,
dan angka pembandingnya sah.

### Tahap 3 dan tahap 5 sama persis, dan itu yang menentukan

9.807 dan 9.560, sampai satuannya. Artinya saringan unit, saringan
blok, syarat flag aktif, syarat parent_id kosong, dan sambungan ppjb ke
stok SEMUANYA SUDAH BENAR. Tidak ada satu pun cacat kode pada laporan
ini.

Ini berbeda dengan Daftar Pengajuan Sertipikat Balik Nama, yang selain
kekurangan data juga menyimpan dua cacat saringan blok.

### Seluruh selisihnya lahir di tahap 2

1.928 dikurangi 546 sama dengan 1.382 baris yang hilang, dan semuanya
berasal dari akta yang tidak ada di PostgreSQL.

Sebaran tahun sr_akta menurut tgl_akta, seluruh unit:

| tahun | PostgreSQL | SQL Server | selisih |
|-------|-----------:|-----------:|--------:|
| 2026  |      0 |   474 |   474 |
| 2025  |      0 | 1.162 | 1.162 |
| 2024  |     54 | 1.385 | 1.331 |
| 2023  |  1.052 | 1.057 |     5 |
| 2022  |  1.768 | 1.768 |     0 |
| 2021  |  1.455 | 1.456 |     1 |
| 2020  |    884 |   884 |     0 |
| 2019  |    616 |   618 |     2 |
| 2018  |    468 |   469 |     1 |
| 2014  |    492 |   493 |     1 |
| 2013 ke bawah | identik | identik | 0 |

Sampai 2023 kedua sisi cocok hampir sempurna, lalu terjun di 2024 dan
kosong sama sekali pada 2025 dan 2026.

Khusus stok SBKS, sisi SQL Server memberi 2024 sebanyak 656, 2025
sebanyak 563, dan 2026 sebanyak 165. Jumlahnya 1.384, dan itu menjawab
1.382 baris yang hilang tadi hampir tepat.

### Catatan: angka 638 yang terdahulu tidak sebanding

Catatan terdahulu menyebut sr_akta hanya memuat 638 baris pada rentang
yang mirip. Angka itu memakai kolom TGL_INPUT, dipakai oleh Daftar
Pengajuan Sertipikat Balik Nama. Rekap AJB memakai TGL_AKTA, dan
dengan kolom itu angkanya 757. Dua kolom berbeda, jadi wajar berbeda,
dan keduanya sama sama benar untuk laporannya masing-masing.

Dengan TGL_AKTA, sr_akta tidak berhenti Februari 2024 melainkan masih
menyisakan 54 baris sepanjang 2024, lalu kosong. Kesimpulannya sama,
angkanya saja yang perlu diluruskan.

### Temuan sampingan: sr_akta tampaknya hanya membawa satu basis data

Tahap 4 memperlihatkan pola yang terbalik. sr_ppjb di PostgreSQL berisi
49.527 baris aktif sedangkan SQL Server PUSAT hanya 29.097, selisih
20.430. Itu wajar: PostgreSQL adalah gabungan SRIS_PUSAT dan
SRIS_SERPONG, sedangkan sambungan desktop yang dipakai menunjuk
SRIS_PUSAT saja.

Tetapi sr_akta justru LEBIH SEDIKIT daripada PUSAT saja, 17.407 lawan
20.386, dan sebaran tahunnya 2013 ke bawah sama persis dengan PUSAT.
Kalau sr_akta benar-benar gabungan dua basis data, ia seharusnya lebih
banyak, bukan lebih sedikit.

Dugaan, BELUM DIPASTIKAN: sr_akta hanya membawa baris PUSAT, dan baris
SERPONG tidak ikut sama sekali. Perlu diperiksa dengan menghitung AKTA
di SRIS_SERPONG lalu mencocokkannya. Dugaan ini disimpan sebagai
dugaan, bukan temuan.

### Yang perlu dikerjakan tim migrasi

Pindahkan ulang sr_akta. Pada rentang tanggal ini saja ia kehilangan
2.970 dari 3.727 baris, atau 80 persen, dan seluruh 2025 serta 2026
kosong. Sekalian dipastikan apakah baris SERPONG memang belum ikut.

---

## Cacat saringan blok yang ketiga: Daftar Jaminan Bank

Ditemukan 24 September 2026, waktu mengecek ulang angka 8 lawan 163.

`dftr_jaminan_bank_m.php` menyimpan DUA cacat yang sama persis dengan
yang dulu ditemukan pada kedua model balik nama:

1. Blok akhir 'Z' tidak diubah menjadi 'ZZ'. Hanya keadaan kosong yang
   ditangani. Padahal dalam perbandingan teks 'ZBJ' dan 'ZCL' LEBIH
   BESAR daripada 'Z', sebab 'Z' awalan yang lebih pendek.

2. Kedua penanda pada cabang blok saja diisi blok AKHIR:

       'blok_akhir_blok_awal'  => $blokAkhir,
       'blok_akhir_blok_akhir' => $blokAkhir,

   sehingga cabang itu berarti blok = 'Z' persis, bukan rentang.

Keduanya sudah dibetulkan, mengikuti bentuk yang sudah dipakai sebelas
model lain.

### Diuji

Data uji diberi satu jaminan pada blok ZBJ dan satu pada blok AA, unit
SBKS, dengan saringan blok A sampai Z:

    cara lama  1 baris   AA saja
    cara baru  2 baris   AA dan ZBJ

### Ini TIDAK mengubah angka 8 yang sekarang

Pengukuran 18 September dijalankan dengan blok A sampai ZZ diketik
sendiri, dan hasilnya tetap 8. Jadi pada data sekarang yang membatasi
memang barisnya yang tidak ada, bukan saringan bloknya.

Perbaikan ini baru berbuah SESUDAH sr_jaminan dipindahkan ulang. Kalau
dibiarkan, laporannya akan tetap salah walaupun datanya sudah lengkap,
sebab desktop menampilkan blok ZCL pada baris 161 sampai 163.

### Dua model lain yang juga belum mengubah 'Z' menjadi 'ZZ'

Penanda bindingnya sudah benar, jadi cacatnya hanya satu, bukan dua:

    rekap_estimasi_biaya_ajb_m.php
    dftr_undangan_surat_rumah_m.php

Yang undangan punya cabang '*' yang melewati saringan blok sama sekali,
jadi hanya kena kalau pemakainya mengetik rentang.

KEDUANYA SUDAH DIBETULKAN pada hari yang sama.

Bedanya dengan Jaminan Bank: perbaikan di sini BERAKIBAT SEKARANG, bukan
tidur menunggu migrasi. Kedua model ini hanya membaca tabel yang masih
mutakhir, yaitu sr_stok, sr_ppjb, sr_pembeli_ppjb, sr_sertipikat,
sr_biaya_ajb, dan sr_tipe. Tidak ada sr_akta, sr_jaminan, sr_peralihan,
maupun sr_sertipikat_idk di antaranya, jadi blok berawalan Z memang ada
di datanya.

Nilai bawaan viewnya yang selama ini menyelamatkan:

    rekap_estimasi_biaya_ajb_v   kotak kosong -> 'ZZ'
    daftar_undangan_surat_rumah  kotak kosong -> '*'

Jadi yang kena hanya pemakai yang MENGETIK Z, dan itu justru yang
tertulis di layar desktop, "Blok: A s.d Z". Akibatnya laporan yang sama
memberi angka berbeda tergantung kotaknya diisi atau tidak.

### Diuji

Data uji berisi satu baris blok AA dan satu blok ZBJ, unit SBKS, tiga
rentang blok:

| rentang | lama | baru |
|---------|-----:|-----:|
| A s/d ZZ (atau * s/d *) | 2 | 2 |
| A s/d Z                 | 1 | **2** |
| B s/d D                 | 0 | 0 |

Baris pertama membuktikan tidak ada yang rusak ketika kotaknya
dikosongkan, baris kedua membuktikan perbaikannya bekerja, baris ketiga
membuktikan rentang selain Z tidak ikut berubah.

Cabang "belum diundang" pada model undangan TIDAK berhasil diuji: data
ujinya mengembalikan nol baris bahkan dengan blok '*', jadi yang
menghalangi bukan saringan bloknya melainkan syarat lain yang belum
terpenuhi di data uji. Perbaikannya sendiri diletakkan SEBELUM percabangan,
sehingga kedua cabang menerima nilai yang sudah dibetulkan, dan penanda
binding pada cabang itu memang sudah benar sejak semula.
