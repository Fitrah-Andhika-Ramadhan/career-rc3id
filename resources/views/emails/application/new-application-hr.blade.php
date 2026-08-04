<x-mail::message>
# 🔔 Lamaran Baru Diterima!

Halo **Tim HR RC3ID**,

Ada lamaran baru yang baru saja masuk melalui portal karir. Berikut adalah ringkasan datanya:

---

<x-mail::panel>
**👤 Data Pelamar**

- **Nama:** {{ $candidate->name }}
- **Email:** {{ $candidate->email }}
- **No. Telepon:** {{ $candidate->phone ?? '-' }}
</x-mail::panel>

<x-mail::panel>
**💼 Posisi yang Dilamar**

- **Jabatan:** {{ $job->title }}
- **Departemen:** {{ $job->department ?? '-' }}
- **Tipe Pekerjaan:** {{ $job->work_type ?? '-' }}
- **Tanggal Lamaran:** {{ $application->created_at->format('d F Y, H:i') }} WIB
</x-mail::panel>

@if(filter_var(env('MAIL_INCLUDE_FULL_DATA', false), FILTER_VALIDATE_BOOLEAN) && $application->notes()->exists())
<x-mail::panel>
**📝 Jawaban Formulir Kustom**

{!! nl2br(e($application->notes->first()->note)) !!}
</x-mail::panel>
@endif

Silakan klik tombol di bawah ini untuk langsung meninjau lamaran dan dokumen pelamar di Dasbor Admin:

<x-mail::button :url="config('app.url') . '/admin/submissions'" color="primary">
🔍 Lihat Lamaran di Dasbor Admin
</x-mail::button>

---

*Email notifikasi ini dikirim secara otomatis oleh sistem CareerRC3ID setiap kali ada pelamar baru yang mengirimkan lamarannya.*

Salam,<br>
**Sistem CareerRC3ID**
</x-mail::message>
