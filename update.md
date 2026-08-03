# Weekly Update & Changelog

## 🚀 Latest Updates (Minggu Ini)

Pada minggu ini, banyak perbaikan krusial dan penambahan fitur baru yang berfokus pada **Sistem Hak Akses (Permissions)**, **Performa (Speed)**, dan **Keamanan (Security)**.

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
