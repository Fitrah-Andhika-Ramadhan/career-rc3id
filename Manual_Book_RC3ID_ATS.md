# Buku Panduan (Manual Book) - RC3ID Applicant Tracking System (ATS)

Buku panduan ini merupakan dokumentasi lengkap mengenai penggunaan aplikasi **RC3ID ATS (Applicant Tracking System)**. Aplikasi ini dirancang menggunakan teknologi web modern untuk memberikan pengalaman perekrutan yang cepat, interaktif, dan efisien bagi tim Human Resources (HR) dan Administrator sistem.

---

## DAFTAR ISI

1. [Pengantar & Spesifikasi Teknologi](#1-pengantar--spesifikasi-teknologi)
2. [Persiapan Awal & Arsitektur Peran (Roles)](#2-persiapan-awal--arsitektur-peran-roles)
3. [Navigasi & Dashboard Utama](#3-navigasi--dashboard-utama)
4. [Manajemen Lowongan Kerja (Job Management)](#4-manajemen-lowongan-kerja-job-management)
5. [Sistem Pembuatan Formulir (Custom Form Builder)](#5-sistem-pembuatan-formulir-custom-form-builder)
6. [Manajemen Pelamar (Candidate Submissions)](#6-manajemen-pelamar-candidate-submissions)
7. [Manajemen Data Ekspor & Alur ATS (Opsi 1 & 2)](#7-manajemen-data-ekspor--alur-ats-opsi-1--2)
8. [Pengaturan Sistem (Settings) & Integrasi Email](#8-pengaturan-sistem-settings--integrasi-email)
9. [Manajemen Pencadangan Data (Local Backup)](#9-manajemen-pencadangan-data-local-backup)
10. [Sistem Permohonan Akses (Permission Requests)](#10-sistem-permohonan-akses-permission-requests)
11. [FAQ & Penanganan Masalah (Troubleshooting)](#11-faq--penanganan-masalah-troubleshooting)

---

## 1. Pengantar & Spesifikasi Teknologi

RC3ID ATS dibangun untuk mengatasi kerumitan dalam proses rekrutmen tradisional. Dengan sistem ini, perusahaan dapat membuka lowongan pekerjaan, merancang formulir pendaftaran dinamis, menerima dan menyeleksi berkas pelamar, serta melakukan pencadangan data secara terpusat.

### Teknologi Inti:
- **Framework:** Laravel 12 (PHP 8.2+)
- **Frontend / Reaktivitas:** Livewire 3 & Livewire Volt (Single Page Application SPA)
- **Desain Antarmuka:** Tailwind CSS (Material Design 3 Principles)
- **Database:** MySQL / MariaDB
- **Manajemen Media:** Spatie Media Library
- **Hak Akses:** Spatie Permission
- **Ekspor Dokumen:** ZipArchive & Barryvdh/DomPDF

Fitur **Single Page Application (SPA)** dengan atribut `wire:navigate` memastikan aplikasi berjalan sangat cepat layaknya aplikasi dekstop, tanpa ada *loading screen* memutih saat berpindah halaman.

---

## 2. Persiapan Awal & Arsitektur Peran (Roles)

Dalam sistem RC3ID ATS, keamanan data sangat dijaga melalui arsitektur Hak Akses (Permissions) dan Peran (Roles).

### Tingkatan Peran (Role)
Saat ini terdapat 3 (tiga) peran utama di dalam sistem:
1. **Super Admin:** Memiliki hak penuh tanpa batas. Dapat mengakses seluruh menu, mengatur semua fitur konfigurasi, dan menyetujui/mencabut hak akses pengguna lain.
2. **Admin:** Akun staf tingkat lanjut. Secara *default*, mereka mungkin tidak memiliki akses ke halaman sensitif (seperti Pengaturan atau Manajemen Pengguna), tetapi dapat **meminta akses (request permission)** kepada Super Admin.
3. **HR (Human Resources):** Role yang berfokus penuh pada penyaringan kandidat dan pengelolaan lowongan kerja (Jobs & Submissions). Dapat ditugaskan secara spesifik oleh Super Admin.

### Cara Login
1. Kunjungi tautan administrasi (contoh: `https://namadomain.com/login`).
2. Tampilan halaman login menggunakan antarmuka *Split-Screen* (layar terbagi) yang modern dan selaras dengan warna kementerian. Masukkan Alamat Email dan Kata Sandi Anda pada panel sebelah kanan.
3. (Khusus keperluan Demo/Testing) Daftar akun demo (seperti akun HR, Admin C&L, atau Super Admin) tidak lagi ditampilkan di halaman depan publik. Kredensial tersebut kini disimpan terpisah di dalam file dokumentasi internal (`demo_accounts.md`) demi menjaga profesionalitas tampilan UI.

---

## 3. Navigasi & Dashboard Utama

Setelah berhasil login, Anda akan disambut oleh halaman **Dashboard**.

### Komponen Dashboard:
- **Statistik Cepat (Quick Stats):** Menampilkan jumlah Total Lowongan Aktif, Total Pelamar (Kandidat), dan Total Kandidat yang dipekerjakan (Hired).
- **Grafik / Ringkasan Aktivitas:** (Bila diaktifkan) Menunjukkan lonjakan pelamar dalam periode waktu tertentu.
- **Daftar Pekerjaan Terbaru:** Jalan pintas untuk melihat lowongan mana yang sedang ramai pendaftar.

### Sidebar Navigasi:
Sidebar (Menu Samping) dirancang menggunakan ikon *Google Material Symbols*. Beberapa menu akan tampak terkunci (ditandai dengan ikon gembok kecil 🔒) jika pengguna Anda saat ini belum diberikan izin akses (*Permission*) untuk membuka halaman tersebut.

---

## 4. Manajemen Lowongan Kerja (Job Management)

Halaman **Job Management** (`/admin/jobs`) adalah pusat kendali untuk membuka lowongan baru.

### Cara Menambahkan Lowongan Baru:
1. Klik tombol biru **+ Tambah Lowongan** di sudut kanan atas.
2. Formulir pengisian akan muncul (Popup Modal).
3. Isi informasi berikut:
   - **Judul Pekerjaan (Title):** Misal "Senior Frontend Developer".
   - **Departemen / Proyek:** Misal "Information Technology". Sangat penting untuk fitur ekspor (Opsi 1).
   - **Tipe Pekerjaan:** Penuh waktu (Full-time), Paruh waktu (Part-time), Magang (Internship).
   - **Lokasi Pekerjaan:** Bisa diisi kota atau "Remote".
   - **Batas Waktu (Deadline):** Kapan lowongan akan ditutup otomatis.
   - **Deskripsi Pekerjaan:** Editor Teks Kaya (Rich Text / CKEditor) tempat Anda dapat merinci Kualifikasi, Benefit, dan Persyaratan Pekerjaan. Editor ini juga mendukung penyisipan gambar (Image Upload).
4. Klik **Simpan**.

### Mode Status Lowongan:
Setiap lowongan memiliki 2 mode status:
- **Draft:** Lowongan disimpan di sistem tetapi tidak dapat dilihat oleh publik/pelamar. Sangat berguna saat Anda masih menyusun draf deskripsi atau menunggu persetujuan atasan.
- **Published:** Lowongan resmi dibuka dan formulir aplikasinya dapat diakses melalui URL unik oleh pelamar di seluruh dunia.

### Fitur "Bulk Add Jobs" (Tambah Massal)
Jika perusahaan Anda sedang membuka rekrutmen besar-besaran (misalnya program *Management Trainee* untuk 10 departemen berbeda), Anda tidak perlu menambahkannya satu per satu.
1. Klik tombol putih **Bulk Add Jobs**.
2. Akan muncul sebuah area teks besar (*text area*).
3. Ketikkan atau tempel (*Paste*) daftar posisi pekerjaan yang ingin dibuka (pisahkan 1 posisi per baris).
4. Klik **Simpan**. Sistem akan secara instan menciptakan seluruh lowongan tersebut dalam mode **Draft**. Anda tinggal mengedit detail masing-masing lowongan (seperti departemen dan deskripsi) secara bertahap.

## 4.1. Pembuatan Formulir dengan Template Standar (Template Umum)

Alih-alih menyusun pertanyaan untuk formulir pelamar dari nol (kosong), sistem ATS ini telah menyediakan fitur **Template Standar** yang siap pakai dan disesuaikan dengan standar HR umum!

**Cara Menggunakan Template Standar:**
1. Masuk ke halaman **Custom Form** (melalui sidebar menu atau tombol form di halaman *Jobs*).
2. Perhatikan *toolbar* melayang di sisi sebelah kanan layar Anda.
3. Klik tombol berlogo sihir/bintang (✨) bernama **AI Template Generator**.
4. Saat *pop-up* terbuka, **hiraukan kotak teks AI** dan langsung klik tombol **"Template Standar"** berwarna putih di sudut kiri bawah.
5. **Selesai!** Sistem akan secara instan menghasilkan puluhan pertanyaan standar HR (Mulai dari *Identitas Diri, Pendidikan, Pengalaman Kerja,* hingga *Upload CV & Ijazah*).
6. Anda bebas mengedit, menghapus, atau menggeser urutan pertanyaan tersebut sesuai kebutuhan sebelum mengklik **Simpan Form**.

*Tips: Template standar ini sangat menghemat waktu Anda, terutama jika Anda sedang terburu-buru membuka lowongan baru tanpa perlu repot memikirkan susunan pertanyaan.*

---

## 5. Sistem Pembuatan Formulir (Custom Form Builder)

Aplikasi ATS ini sangat canggih karena tidak mengandalkan formulir statis. Anda dapat merancang formulir Anda sendiri untuk setiap lowongan!

1. Buka halaman **Custom Form** (`/admin/custom-form`).
2. Di sebelah kiri, pilih Lowongan (Job) mana yang ingin Anda buatkan formulirnya.
3. Di tengah halaman, terdapat **Form Builder**. Anda dapat:
   - Menambahkan Teks Pendek (Short Text).
   - Menambahkan Area Teks Panjang (Long Text).
   - Menambahkan Pilihan Ganda (Dropdown / Radio Buttons).
   - Menentukan apakah pertanyaan tersebut **Wajib Diisi (Required)** atau opsional.
4. Di sisi kanan layar, Anda dapat melihat **Live Preview** (Pratinjau Langsung) bagaimana bentuk formulir tersebut saat dilihat oleh pelamar.

Sistem *Builder* menggunakan fungsionalitas tarik-dan-lepas (*Drag-and-Drop*), sehingga Anda dapat menyusun urutan pertanyaan dengan mudah.

---

## 6. Manajemen Pelamar (Candidate Submissions)

Semua pendaftar yang telah mengisi formulir dan mengunggah dokumen (CV/Ijazah) akan bermuara di halaman **Candidate Submissions** (`/admin/submissions`).

### Antarmuka Pengelolaan Pelamar:
- **Kartu Filter Lowongan:** Di bagian atas layar, terdapat kotak-kotak bertuliskan nama departemen/lowongan. Klik kotak tersebut untuk menyaring dan hanya menampilkan pelamar di posisi tersebut.
- **Tabel Pendaftar:** Menampilkan Nama, Email, Tanggal Melamar, Posisi, dan **Status Pipeline**.

### Konsep Pipeline Stages (Tahapan Pelamar):
Aplikasi ini memiliki sistem *Kanban-style Pipeline* (meski ditampilkan dalam tabel). Anda bisa memberikan label pada setiap pelamar:
- **Applied:** Baru Mendaftar (Bawaan).
- **Screening:** Sedang dalam peninjauan administrasi.
- **Interview:** Masuk ke tahap wawancara.
- **Offer:** Telah diberikan penawaran kerja (Offering Letter).
- **Hired:** Resmi diterima bekerja.
- **Rejected:** Gugur / Ditolak.

### Melihat Detail Profil:
- Klik ikon mata (👁️) pada baris tabel pelamar.
- Akan muncul layar *Pop-Up Detail* berisi Info Kontak, Pipeline, Jawaban dari **Custom Form**, serta seluruh **Dokumen Terlampir** (CV, Ijazah, Surat Lainnya).
- Tombol "Buka" akan membuka dokumen pelamar di tab browser baru.

---

## 7. Manajemen Data Ekspor & Alur ATS (Opsi 1 & Opsi 2)

Bagian ini merupakan fitur eksklusif terpenting untuk integrasi alur kerja (Workflow) dengan tim penyeleksi / HRD pusat. Terdapat dua langkah atau opsi utama dalam siklus distribusi berkas:

### Langkah Awal: Ekspor Data Mentah
Anda dapat melakukan ekspor data secara massal melalui format CSV (untuk *spreadsheet* / Excel) atau melalui Export ZIP canggih.

### OPSI 1: UNDUH ATS (Advanced ZIP Export)
Jika Anda memiliki ratusan kandidat dan ingin membukanya tanpa harus terkoneksi internet (Offline), Anda dapat menekan tombol **"Export ZIP (ATS)"**. Fitur ini berjalan sangat ringan, diciptakan langsung di memori (*on-the-fly*), dan otomatis musnah dari server begitu unduhan selesai (sehingga tidak membebani kapasitas *disk space* server Anda).

Struktur dalam file `.zip` yang diunduh akan ditata otomatis oleh sistem menjadi sangat rapi:
```
📁 ATS-Export-TanggalHariIni.zip
 ┣ 📄 rekap_pelamar.csv
 ┣ 📁 Departemen Teknologi
 ┃ ┣ 📁 1_Budi Santoso
 ┃ ┃ ┣ 📄 Data_Form_Budi-Santoso.pdf (✨ PDF Otomatis)
 ┃ ┃ ┣ 📄 CV_Budi_Santoso.pdf
 ┃ ┃ ┗ 📄 Ijazah_Budi_Santoso.png
 ┃ ┗ 📁 2_Siti Aminah
 ┃   ┣ 📄 Data_Form_Siti-Aminah.pdf
 ┃   ┗ 📄 CV_Siti_Aminah.pdf
 ┗ 📁 Departemen Keuangan
   ┗ 📁 3_Ahmad Subarjo
```

**Fitur Khusus PDF Profil (Automated PDF Generator):**
Di dalam folder masing-masing pelamar, sistem akan melahirkan file **`Data_Form_[Nama].pdf`**. File PDF ini tidak ada di sistem sebelumnya, namun diciptakan khusus saat proses kompresi ZIP.
PDF ini berisi desain profil cantik, merangkum semua pertanyaan khusus dari *Custom Form*, dan dilengkapi fitur **Hyperlink**. Anda cukup menekan tulisan `CV_Kandidat.pdf` dari dalam file PDF tersebut, dan komputer Anda akan langsung membuka file PDF aslinya yang berdampingan di folder yang sama!

---

### OPSI 2: UPLOAD & KIRIM ZIP TERKURASI KE HR
Setelah Admin/Panitia mengunduh ZIP dari Opsi 1, mereka biasa mengekstraknya di komputer lokal, menyeleksinya secara manual, lalu **menghapus folder-folder pelamar yang tidak lolos seleksi awal**. Sisa folder pelamar yang bagus kemudian di-ZIP (dikompres) ulang oleh Admin secara manual di komputernya.

Untuk mengirimkan file ZIP terkurasi (hasil seleksi final) tersebut ke manajer HR:
1. Buka aplikasi ATS, masuk ke menu **Settings** (`/admin/settings`).
2. Masuk ke Tab **Data Candidate & HR**.
3. Gulir ke bawah hingga menemukan panel hitam besar bertuliskan **"Opsi 2: Upload & Kirim ZIP Terkurasi ke HR"**.
4. Klik **Choose File** dan unggah file ZIP seleksi Anda.
5. Klik **Kirim Email beserta Lampiran ZIP**.
6. Muncul *Pop-Up Confirm (SweetAlert)*. Konfirmasi pengiriman.

Sistem akan mengunggah file Anda dan langsung membungkusnya ke dalam email resmi untuk dikirimkan kepada alamat HR Pusat yang Anda daftarkan di Opsi 1.
*Catatan: Pastikan file ZIP Anda tidak melebihi 25 MB, karena server email global rata-rata menolak lampiran yang melebihi ukuran 25 MB.*

---

## 8. Pengaturan Sistem (Settings) & Integrasi Email

Halaman **Settings** (`/admin/settings`) mengendalikan variabel global aplikasi.

### Tab "Umum / General"
- Mengatur Nama Perusahaan (Site Name).
- Mengatur Logo Perusahaan.
- Mengatur *Tagline* yang muncul di beranda publik (Portal Lowongan).

### Tab "Data Candidate & HR"
- Mengatur Alamat Email HR (Pusat penerimaan notifikasi).
- **Custom HR Greeting Text**: Anda dapat menuliskan teks atau kalimat khusus (misal: "Halo Pak HRD, mohon ditinjau lamaran berikut ini dari sistem ATS kami."). Kalimat ini akan disuntikkan secara dinamis ke dalam setiap *Email Alert* yang dikirim oleh sistem.
- Panel **Opsi 2** untuk pengiriman ZIP terkurasi.

---

## 9. Manajemen Pencadangan Data (Local Backup)

Kami meyakini bahwa data kandidat adalah aset vital perusahaan. Oleh karena itu, modul pencadangan data disediakan di menu **Backup Data** (`/admin/backup`).

Sistem backup pada RC3ID telah disempurnakan dari sebelumnya (*Cloud-based* yang rentan isu koneksi token Google Drive) menjadi **Sistem Pencadangan Server Lokal** menggunakan pustaka andalan industri, `spatie/laravel-backup`.

### Apa yang Dicadangkan?
1. **Database SQL Server:** Seluruh teks formulir, konfigurasi, struktur akun pengguna, pengaturan.
2. **File Storage:** Semua file fisik berupa foto kandidat, dokumen CV format PDF, gambar Ijazah, dan logo perusahaan.

### Cara Menjalankan Backup
1. Pada panel hijau di sebelah kanan layar, klik tombol **Mulai Backup Sekarang**.
2. Aplikasi akan menginstruksikan server latar belakang (Background Process) untuk mulai mengemas data.
3. Proses ini memakan waktu beberapa detik (atau menit, tergantung besaran CV). 
4. Saat halaman diperbarui (*refresh*), Anda akan melihat riwayat backup di tabel bagian bawah.

Tabel daftar Backup memungkinkan Anda untuk men-*download* keseluruhan sistem ATS secara instan menjadi 1 (satu) file ZIP utuh, atau menghapusnya secara permanen untuk membebaskan ruang disk server.

---

## 10. Sistem Permohonan Akses (Permission Requests)

Aplikasi ATS ini tidak secara kaku memberikan semua akses kepada Admin biasa demi mencegah kebocoran konfigurasi.

**Bagaimana Admin Biasa Meminta Akses?**
1. Admin Biasa yang menemukan menu terkunci (Gembok) di navigasi kiri, dapat mengakses menu **My Requests**.
2. Klik tombol "Add Request", lalu pilih fitur apa yang diinginkan (misal: "Akses Settings"). Beri alasan pada kolom teks jika diperlukan.
3. Status permohonan akan menjadi *Pending*.

**Bagaimana Super Admin Merespons?**
1. Super Admin mengakses menu **Permission Requests** (`/admin/permission-requests`).
2. Semua daftar antrean permintaan tampil secara urut.
3. Super Admin memiliki 3 pilihan tindakan yang dikemas menggunakan Pop-up beranimasi manis (SweetAlert):
   - **Approve (Setujui):** Secara otomatis mengaktifkan gerbang akses (*permission*) di *database* ke Admin terkait. Status berubah menjadi "Approved".
   - **Reject (Tolak):** Permintaan ditolak, gerbang tetap ditutup.
   - **Revoke (Cabut Akses):** Tombol merah ikon coret kunci ini dapat ditekan untuk membatalkan izin yang *sebelumnya sudah disetujui* dengan instan!

Sistem hak akses ini memastikan kebebasan fungsional dan keamanan operasional (Role-Based Access Control / RBAC) yang terukur.

---

## 11. FAQ & Penanganan Masalah (Troubleshooting)

**Q: Saya baru saja mengupdate fitur melalui "git pull" (Misal menambahkan Ekspor ZIP PDF atau Migrasi Role HR), namun terjadi *Error 500* atau fitur tidak bekerja. Apa solusinya?**
A: Fitur-fitur baru kadang membawa dependensi atau perubahan pada arsitektur database. Sangat diwajibkan untuk menjalankan perintah instalasi paket PHP atau migrasi database. Pada server Hostinger, jika Anda kesulitan menggunakan Terminal SSH, cukup jalankan tautan rahasia ini melalui Browser Anda:
`https://palegreen-lapwing-313339.hostingersite.com/run-migrations`
Tautan tersebut otomatis memerintahkan server Anda mengeksekusi sinkronisasi database secara aman.

**Q: Bagaimana cara memulihkan atau memunculkan akun demo HR yang tidak sengaja terhapus di server live tanpa harus repot mengakses SSH Terminal?**
A: Sistem ini telah dilengkapi tautan konfigurasi rahasia (*zero-touch setup*). Cukup kunjungi tautan `https://[domain-anda]/setup-hr` melalui browser (contoh: `https://palegreen-lapwing-313339.hostingersite.com/setup-hr`). Sistem akan secara otomatis mendaftarkan ulang kredensial HR Anda di database.

**Q: ZIP Opsi 2 selalu gagal saat dikirim, dengan keterangan "File too large".**
A: Terdapat pembatasan keras sebesar **25 Megabytes (MB)** di seluruh dunia untuk lampiran *Email SMTP Protocol* (seperti Gmail atau Google Workspace). Sistem tidak akan membiarkan Anda mengirim ZIP lebih besar dari ukuran ini. Harap ekstrak ZIP Anda dan hapus CV/dokumen yang ukuran PDF-nya terlalu bengkak, lalu ZIP kembali hingga total ukurannya di bawah 25 MB.

**Q: Mengapa *hyperlink* PDF Ekspor ZIP Opsi 1 saat saya klik tidak otomatis membuka CV kandidat?**
A: Sistem tautan PDF tersebut menggunakan teknik navigasi *Relative Path URL* yang bergantung sepenuhnya kepada kebijakan aplikasi pembaca (*PDF Reader*) di komputer/HP Anda. Beberapa PDF Reader klasik memblokir *link* file lokal demi alasan keamanan. Solusi terbaik adalah membuka PDF tersebut melalui Browser Web (Seperti Google Chrome atau Microsoft Edge), karena mesin peramban secara alami mengizinkan tautan navigasi ke file lain di dalam struktur folder yang sama.

**Q: Saya tidak sengaja menghapus Lowongan yang sudah memiliki pelamar.**
A: Sistem RC3ID ATS kami memberlakukan *Cascading Delete*. Jika sebuah Lowongan Pekerjaan (Job) Anda hapus permanen, maka SELURUH berkas pelamar (Candidate Submissions) dan CV/Media terkait lamaran tersebut **akan ikut musnah** guna memastikan tidak ada file yatim-piatu (*orphan files*) yang menumpuk di server Hostinger. Harap gunakan fitur edit status menjadi "Draft" (disembunyikan) alih-alih menghapusnya jika masih memerlukan histori pelamar.

---

### *Akhir dari Dokumen*
Panduan ini dirancang eksklusif untuk staf operasional internal pengguna **RC3ID ATS**. Segala pembaruan sistem akan segera dilampirkan (*rolling-release*) ke dalam perihal revisi dokumentasi ini. Selamat melakukan perekrutan dengan mudah, modern, dan canggih! 🚀
