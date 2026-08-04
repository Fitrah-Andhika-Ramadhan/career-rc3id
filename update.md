# Weekly Update & Changelog

## 🚀 Latest Updates (Minggu Ini)

Pada minggu ini, pengembangan sistem berfokus pada **Fitur Ekspor & Integrasi HR**, **Pembuatan Laporan Profil PDF**, **Perombakan Sistem Backup**, dan **Penyempurnaan Email Automasi**.

### 1. Fitur Advanced ZIP Export (Opsi 1: Unduh ATS) 🗂️
- **Struktur Folder Cerdas**: Saat menekan "Export ZIP (ATS)", sistem kini akan membuat struktur folder yang sangat rapi: `[Nama Departemen] -> [Nomor_Nama Kandidat]`.
- **Integrasi Dokumen**: Seluruh berkas yang diunggah pelamar (CV, Ijazah, Dokumen Pendukung) otomatis diekstrak dan disusun ke dalam folder pelamar masing-masing.

### 2. Auto-Generate PDF Profil Kandidat 📄
- **PDF Dinamis**: Tidak lagi menggunakan file `.txt` biasa. Sistem kini otomatis melahirkan/men-*generate* file **PDF berpenampilan cantik** untuk setiap kandidat di dalam folder ZIP mereka.
- **Tampilan Ala Pop-Up**: Desain PDF dibuat elegan menyerupai *Pop-Up Detail* di website, lengkap dengan informasi Kontak, Posisi, Status, dan **Semua Jawaban Custom Form**.
- **Fitur Hyperlink Lokal**: Di dalam file PDF tersebut, terdapat daftar nama dokumen pendukung pelamar yang tertaut (*hyperlinked*). HR cukup menekan klik pada nama file di dalam PDF untuk langsung membuka file asli CV/Ijazah mereka (selama file tersebut tidak dipindah foldernya).

### 3. Upload & Email ZIP Terkurasi (Opsi 2: Review & Kirim) 📧
- **Panel Khusus Opsi 2**: Menambahkan panel baru di menu `Settings` untuk mengunggah ulang file ZIP yang telah di-*review*/dikurasi oleh Admin.
- **Validasi Ketat**: Dilengkapi validasi keamanan batas maksimal ukuran file `25 MB` (standar global limit lampiran Email) dan *Pop-up* konfirmasi SweetAlert.
- **Pengiriman Mailable Zip**: Saat ZIP diunggah, sistem akan secara otomatis melampirkan ZIP tersebut dan mengirimkannya langsung ke alamat Email HR yang terdaftar.

### 4. Customisasi Email HR (Greeting Text) ✍️
- Admin kini bisa mengganti kalimat pembuka (*greeting*) untuk email otomatis yang dikirimkan ke HR.
- Teks ini tersimpan di *database* (melalui halaman Settings) dan akan digunakan baik pada Notifikasi Pelamar Baru maupun Notifikasi Pengiriman ZIP Opsi 2.

### 5. Perombakan Total Sistem Backup (Local Server Storage) 💾
- Menghapus keseluruhan integrasi *Google Drive Backup* yang sering memicu isu otentikasi.
- **Sistem Local Backup**: Mengganti arsitektur *backup* menjadi berbasis *Local Storage* di server Hostinger menggunakan modul `spatie/laravel-backup`.
- **File Management**: Hasil *backup* berupa file `.zip` (Database + File Upload) kini langsung tersimpan di server lokal dan bisa diunduh (*Download*) atau dihapus (*Delete*) dengan instan, aman, dan sangat cepat tanpa perlu token eksternal.

### 6. Penambahan Role "HR" 👔
- **Database Migration**: Menambahkan sebuah *file migration* untuk secara otomatis menciptakan peran (Role) "HR" ke dalam tabel `roles` di *database*.
- **Integrasi UI User Management**: Opsi "HR" kini tersedia dan bisa langsung dipilih saat menambahkan atau mengubah posisi pengguna di halaman *User Management*.

---

## 📅 Arsip Pembaruan (Minggu Sebelumnya)

Pada minggu sebelumnya, banyak perbaikan krusial dan penambahan fitur baru yang berfokus pada **Sistem Hak Akses (Permissions)**, **Performa (Speed)**, dan **Keamanan (Security)**.

### 1. SPA (Single Page Application) & Performa Super Cepat ⚡
- Mengaktifkan fitur `wire:navigate` pada seluruh menu di sidebar Admin.
- Transisi halaman (pindah dari Dashboard ke Settings, dsb.) sekarang terjadi secara instan tanpa perlu memuat ulang (*reload*) seluruh halaman browser.
- Menghapus pemanggilan manual AlpineJS ganda di `app.js` yang sebelumnya memicu konflik internal dan membuat tombol macet.

### 2. Peningkatan Sistem Permohonan Akses (Permission Requests) 🔐
- **Untuk Admin Biasa:** 
  - Penambahan menu baru **"My Requests"** di sidebar. Admin biasa kini bisa memantau status permohonan mereka secara mandiri.
  - Penambahan tombol **"Add Request"** yang memunculkan *dropdown* berisi daftar fitur yang belum mereka miliki, sehingga admin bisa memohon akses kapan saja.
- **Untuk Super Admin:**
  - Penambahan fitur **Cabut Akses (Revoke)**. Super Admin kini bisa mencabut secara instan permohonan yang sebelumnya sudah disetujui hanya dengan satu klik (ikon kunci dicoret) pada halaman Permission Requests.

### 3. Perbaikan Bug Keamanan & Logika Akses 🐛
- Memperbaiki bug kritis di halaman **System Settings** dan **User Management** yang sebelumnya terkunci secara kaku (*hardcoded*) hanya untuk `Super Admin`.
- Sekarang, halaman tersebut secara dinamis mengecek *Permission* aktual pengguna. Jika Admin biasa sudah disetujui (Approved) untuk memegang akses `access settings`, mereka kini bisa membuka halamannya tanpa error `403 Unauthorized`.

### 4. Optimalisasi Tampilan UI/UX 🎨
- Menghapus ikon/lencana "Req" yang mengganggu pada daftar menu terkunci.
- Merapikan struktur *dropdown* dan notifikasi (alert) pada form permohonan akses agar lebih interaktif dan ramah pengguna.
- **Global SweetAlert2 Integration:** Mengganti *pop-up* konfirmasi bawaan browser (seperti saat akan mencabut akses, menolak/menerima permohonan, atau menghapus pengguna) yang kaku menjadi *pop-up* **SweetAlert2** yang elegan, beranimasi, dan menyatu sempurna dengan *design system* aplikasi.
