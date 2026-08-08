# MANUAL BOOK / USER MANUAL
**Sistem Rekrutmen & Portal Karir RC3ID**

Versi Aplikasi: 1.0.0  
Versi Dokumen: 1.0  
Tanggal Penerbitan: 08 Agustus 2026  
Nama Instansi: **Research Center for Care and Control of Infectious Diseases (RC3ID)**

---

# DAFTAR ISI

**I. Pendahuluan**
1.1. Tentang Sistem/Aplikasi
1.2. Tujuan Penggunaan Sistem
1.3. Sasaran Pengguna
1.4. Persyaratan Penggunaan Sistem
1.5. Cara Mendapatkan Akses/Akun

**II. Login dan Navigasi Awal**
2.1. Cara Login (Pengguna Terdaftar)
2.2. Verifikasi Dua Langkah (2FA)
2.3. Tampilan Halaman Utama (Dashboard)
2.4. Navigasi Menu Utama
2.5. Logout

**III. Modul Manajemen Lowongan (Jobs)**
3.1. Penjelasan Modul
3.2. Melihat Daftar Lowongan
3.3. Menambahkan Lowongan Baru
3.4. Mengubah Data Lowongan
3.5. Menghapus Lowongan

**IV. Modul Form Builder (Formulir Khusus)**
4.1. Penjelasan Modul
4.2. Cara Mengakses Modul
4.3. Menambahkan Isian Baru (Teks, Select, File)
4.4. Menyusun Urutan Isian (Drag & Drop)

**V. Modul Pengajuan/Lamaran (Submissions)**
5.1. Penjelasan Modul
5.2. Melihat Daftar Pelamar
5.3. Melihat Detail Data Pelamar
5.4. Mengunduh Dokumen/Lampiran
5.5. Pencarian dan Filter Pelamar

**VI. Status dan Monitoring (Screening)**
6.1. Melihat Status Lamaran
6.2. Mengubah Status Pelamar (Persetujuan/Penolakan)

**VII. Modul Integrasi Otomatis (Laporan & Penyimpanan)**
7.1. Pencadangan File Otomatis (Google Drive)
7.2. Laporan Otomatis Real-Time (Google Sheets)

**VIII. Troubleshooting**
8.1. Tidak Dapat Login atau Lupa Password
8.2. Kode 2FA Tidak Muncul
8.3. Gagal Menyimpan Data Lowongan

**IX. Penutup**
9.1. Catatan Penggunaan
9.2. Kontak Administrator/Helpdesk

---

# I. Pendahuluan

### 1.1. Tentang Sistem/Aplikasi
Portal Karir RC3ID adalah aplikasi pelacakan pelamar (Applicant Tracking System) berbasis web yang terintegrasi secara otomatis dengan ekosistem Google Workspace. 

### 1.2. Tujuan Penggunaan Sistem
Tujuan utama sistem ini adalah mendigitalisasi proses rekrutmen pegawai, memudahkan pembuatan formulir dinamis, dan menyeleksi ratusan berkas pelamar secara cepat dan rapi.

### 1.3. Sasaran Pengguna
Manual Book ini ditujukan khusus untuk **Administrator, Staf HRD, dan Manajer Perekrutan** yang memiliki hak akses (*Role Admin*) ke dalam sistem Dasbor.

### 1.4. Persyaratan Penggunaan Sistem
- Perangkat keras (Komputer/Laptop/Smartphone).
- Peramban web modern (Disarankan Google Chrome atau Mozilla Firefox).
- Koneksi Internet yang stabil.
- Akses ke email aktif untuk menerima kode Otentikasi (2FA).

### 1.5. Cara Mendapatkan Akses/Akun
Akun Administrator dikelola secara tertutup. Pembuatan atau pengaturan ulang sandi akun Administrator hanya dapat diajukan kepada Tim IT / Webmaster Internal RC3ID.

---

# II. Login dan Navigasi Awal

### 2.1. Cara Login (Pengguna Terdaftar)
**Deskripsi:**  
Fungsi ini digunakan untuk masuk ke dalam dasbor sistem dengan menggunakan kredensial yang telah didaftarkan.

**Cara Mengakses:**  
Ketikkan URL: `[Alamat Web]/admin` di peramban Anda.

**Langkah-Langkah:**
1. Akses alamat web panel admin.
2. Isi data yang diperlukan pada kolom **Email Address**.
3. Masukkan kata sandi pada kolom **Password**.
4. Klik tombol **Sign In**.
5. Sistem menampilkan halaman verifikasi lanjutan (2FA).

**Gambar 1. Halaman Login**
```text
┌──────────────────────────────────────┐
│                                      │
│               [Logo]                 │
│                                      │
│    Email:      [ ① ]                 │
│    Password:   [ ② ]                 │
│                                      │
│             [ ③ Sign In ]            │
│                                      │
└──────────────────────────────────────┘
```
| No. | Komponen | Keterangan |
| --- | --- | --- |
| 1 | Input Email | Kolom untuk memasukkan alamat email terdaftar |
| 2 | Input Password | Kolom untuk memasukkan kata sandi rahasia |
| 3 | Tombol Sign In | Mengeksekusi proses login ke tahap selanjutnya |

### 2.2. Verifikasi Dua Langkah (2FA)
**Deskripsi:**  
Sistem proteksi ganda yang mewajibkan pengguna memasukkan kode khusus dari email, demi mencegah akses ilegal.

**Langkah-Langkah:**
1. Setelah menekan tombol *Sign In*, cek *Inbox* atau kotak *Spam* email Anda.
2. Buka pesan berisi subjek Verifikasi dari RC3ID.
3. Salin/catat 6 digit kode dari email tersebut.
4. Masukkan kode tersebut pada kolom **Verification Code** di web.
5. Klik **Verify**.
6. Sistem menampilkan halaman utama (Dashboard).

> **Catatan:** Kode verifikasi hanya berlaku selama beberapa menit. Jika batas waktu habis, klik tombol "Resend Code".

---

# III. Modul Manajemen Lowongan (Jobs)

### 3.1. Penjelasan Modul
Modul ini berfungsi untuk membuka lowongan baru, mengatur tenggat waktu, serta merincikan detail kualifikasi pekerjaan agar tampil di halaman publik.

### 3.2. Menambahkan Lowongan Baru
**Cara Mengakses:**  
`Menu Utama (Sidebar) → Jobs → Tombol 'New Job'`

**Langkah-Langkah:**
1. Klik menu **Jobs** di sisi kiri.
2. Klik tombol **New Job** di pojok kanan atas.
3. Isi data pada kolom **Title** (Posisi), **Department**, dan **Location**.
4. Pilih/masukkan tanggal pada field **Deadline Date**.
5. Isi rincian tugas pada area **Description** menggunakan teks editor.
6. Klik tombol **Create**.
7. Sistem menampilkan notifikasi hijau "Saved" dan lowongan langsung aktif.

**Gambar 2. Halaman Create Job**
```text
┌──────────────────────────────────────┐
│  Create Job                          │
│                                      │
│  Title     [ ① ]  Department [ ② ]   │
│  Deadline  [ ③ ]                     │
│                                      │
│  Description                         │
│  [ ④ Area Teks Rich Editor       ]   │
│                                      │
│                         [ ⑤ Create ] │
└──────────────────────────────────────┘
```
| No. | Komponen | Keterangan |
| --- | --- | --- |
| 1 | Field Title | Menentukan nama lowongan/posisi (Wajib) |
| 2 | Field Department | Departemen yang menaungi posisi tersebut |
| 3 | Date Picker | Menentukan tanggal otomatis penutupan lowongan |
| 4 | Teks Editor | Area rincian pekerjaan dan syarat kualifikasi |
| 5 | Tombol Create | Menyimpan data ke dalam basis data dan mempublikasikan |

> **Catatan:** Fitur *Deadline Date* bersifat *Autopilot*. Ketika tanggal tersebut terlewati, lowongan akan otomatis ditutup di sistem publik tanpa campur tangan admin.

---

# IV. Modul Form Builder (Formulir Khusus)

### 4.1. Penjelasan Modul
Digunakan untuk mengkostumisasi pertanyaan lamaran secara dinamis sesuai kebutuhan setiap lowongan pekerjaan (misal: isian nilai IPK, opsi pilihan ganda jurusan, atau unggah sertifikat).

### 4.2. Cara Mengakses Modul
`Menu Utama → Jobs → [Pilih/Edit Lowongan] → Area Form Builder`

### 4.3. Menambahkan Isian Baru (Teks, Select, File)
**Langkah-Langkah:**
1. Gulir layar ke area **Form Builder** pada halaman edit Job.
2. Klik tombol **Add Field**.
3. Pilih tipe isian pada kolom **Type** (Contoh: Text Input, Select, File Upload).
4. Masukkan kalimat pertanyaan pada kolom **Label**.
5. Jika memilih tipe *Select*, masukkan opsi pilihan pada kolom **Options** dan **tekan tombol Enter** di keyboard untuk memisahkannya.
6. Centang kotak **Is Required?** jika wajib diisi.
7. Klik tombol **Save Changes**.
8. Sistem menampilkan kolom formulir baru tersebut di halaman publik.

**Gambar 3. Pengaturan Field Formulir**
```text
┌──────────────────────────────────────┐
│  Form Builder (Fields)               │
│                                      │
│  Type:  [ ① Select ▼ ]               │
│  Label: [ ② Pendidikan Terakhir ]    │
│  Options:[ ③ SMA (x) S1 (x)     ]    │
│  ☑ Is Required? [ ④ ]                │
│                                      │
│                 [ ⑤ Add Field ]      │
└──────────────────────────────────────┘
```
| No. | Komponen | Keterangan |
| --- | --- | --- |
| 1 | Dropdown Type | Jenis isian (Teks, Paragraf, File, dll) |
| 2 | Field Label | Pertanyaan yang akan dibaca oleh pelamar |
| 3 | Opsi Pilihan | Area untuk mengetik jawaban pilihan ganda (Wajib Enter) |
| 4 | Checkbox Required| Untuk memaksa pelamar mengisi pertanyaan ini |
| 5 | Tombol Add Field | Menambah pertanyaan baru ke baris berikutnya |

> **Catatan:** Untuk isian tipe *File Upload*, batasan ukuran standar yang berlaku adalah maksimal 5MB berformat PDF.

---

# V. Modul Pengajuan/Lamaran (Submissions)

### 5.1. Penjelasan Modul
Modul utama untuk melihat, meninjau, dan mengunduh seluruh data/dokumen yang dikirimkan oleh para pelamar.

### 5.2. Melihat Detail Data Pelamar
**Cara Mengakses:**  
`Menu Utama → Submissions → Tabel Daftar Submissions`

**Langkah-Langkah:**
1. Klik menu **Submissions**.
2. Cari nama kandidat pada tabel (gunakan kolom pencarian jika perlu).
3. Klik tombol ikon **Mata (View)** atau Edit di sisi kanan nama.
4. Sistem menampilkan seluruh jawaban formulir spesifik dari pelamar.
5. Klik tautan berkas berwarna biru (contoh: *file_cv.pdf*) untuk **mengunduh (download)** lampiran ke komputer.

---

# VI. Status dan Monitoring (Screening)

### 6.1. Mengubah Status Pelamar (Persetujuan/Penolakan)
**Deskripsi:**  
Digunakan oleh tim HR atau Approver untuk menandai perkembangan proses rekrutmen setiap individu.

**Cara Mengakses:**  
`Menu Utama → Submissions → View Detail Kandidat → Kolom Status`

**Langkah-Langkah:**
1. Buka halaman detail pelamar.
2. Temukan kolom dropdown **Status**.
3. Pilih salah satu status kelayakan: 
   - *Pending* (Baru Masuk)
   - *Reviewed* (Sedang Ditinjau)
   - *Interviewed* (Tahap Wawancara)
   - *Accepted* (Diterima)
   - *Rejected* (Ditolak)
4. Perubahan akan tersimpan otomatis sesaat setelah di-klik.
5. Sistem akan memberikan warna label (*badge*) yang berbeda-beda pada tabel utama sesuai status yang dipilih.

---

# VII. Modul Integrasi Otomatis (Laporan & Penyimpanan)

### 7.1. Pencadangan File Otomatis (Google Drive)
**Deskripsi:**  
Sistem ini tidak membebani kapasitas *server website* perusahaan. 
Seluruh berkas yang diunggah pelamar pada Modul Formulir akan langsung **dikirimkan otomatis** (Sinkronisasi *Back-end*) ke dalam *Folder* Google Drive milik organisasi. Administrator dapat memantau atau mengunduh seluruh berkas tersebut secara massal langsung dari akun Google perusahaan, terlepas dari Dasbor sistem ini.

### 7.2. Laporan Otomatis Real-Time (Google Sheets)
**Deskripsi:**  
Sistem ini terintegrasi dengan Google Sheets untuk rekapitulasi cepat.
Setiap kali pelamar sukses melakukan *submit* pengajuan, data fundamentalnya (Nama, Posisi, Waktu) otomatis terketik di dalam baris (*row*) Google Sheets yang telah dikonfigurasi. Pimpinan dapat memonitor laporan pendaftar secara langsung via *smartphone* tanpa perlu login ke sistem Dasbor Admin.

---

# VIII. Troubleshooting

### 8.1. Tidak Dapat Login
**Kendala:** Layar kembali ke halaman login tanpa peringatan jelas.
**Solusi:** Pastikan penulisan Email dan *Password* sudah benar (perhatikan *Caps Lock*). Jika kredensial lupa, hubungi IT/Webmaster karena *Reset Password* dilakukan secara manual oleh pemilik web utama.

### 8.2. Kode 2FA Tidak Muncul
**Kendala:** Sudah menunggu lama, tetapi tidak ada email kode masuk.
**Solusi:** 
1. Cek folder **Spam** atau **Junk** pada layanan email Anda.
2. Klik teks **Resend Code** di halaman verifikasi. 
3. Pastikan kapasitas penyimpanan email perusahaan Anda tidak penuh (*Quota Exceeded*).

---

# IX. Penutup

### 9.1. Catatan Penggunaan
Administrator memegang peranan krusial atas kerahasiaan identitas publik pelamar. Sistem Portal Karir RC3ID telah disetel seaman dan sepraktis mungkin. Patuhilah kebijakan keamanan, hindari memberikan akses *login* kepada pihak yang tidak berkepentingan, dan selalu lakukan Logout setelah sesi kerja berakhir.

### 9.2. Kontak Helpdesk
Jika ditemukan (*bugs*), kegagalan integrasi penyimpanan, atau error 500, harap sertakan Tangkapan Layar (Screenshot) dan hubungi Staf SysAdmin/Developer Internal RC3ID di divisi Anda. 

***
*Dokumen ini bersifat Rahasia Perusahaan. Hak Cipta © RC3ID.*
