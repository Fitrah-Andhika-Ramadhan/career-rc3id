<x-mail::message>
# 🔔 Lamaran Baru Diterima!

Halo **Tim HR**,

{{ env('MAIL_HR_GREETING') ?: 'Ada lamaran baru yang baru saja masuk melalui portal karir. Berikut adalah ringkasan datanya:' }}

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

@php
    $rawNotes = $application->notes->first()->note ?? '';
    $lines = explode("\n", $rawNotes);
@endphp
@foreach($lines as $line)
@if(trim($line) === '--- Pertanyaan Kustom ---')

**📌 Tambahan Form:**
@elseif(trim($line) !== '')
@php $parts = explode(':', $line, 2); @endphp
@if(count($parts) == 2)
- **{{ trim($parts[0]) }}:** {{ trim($parts[1]) }}
@else
- {{ trim($line) }}
@endif
@endif
@endforeach
</x-mail::panel>
@endif

{{-- File lampiran SELALU tampil (di luar gating MAIL_INCLUDE_FULL_DATA) --}}
{{-- Link pakai /download/media/{uuid} agar tidak 403 di Hostinger (symlink diblokir) --}}
@if($application->hasMedia('documents'))
<x-mail::panel>
**📁 File Lampiran Kandidat**

@foreach($application->getMedia('documents') as $media)
@if($media->hasCustomProperty('gdrive_url'))
- [📄 {{ $media->file_name }} (Google Drive)]({{ $media->getCustomProperty('gdrive_url') }})
@else
- [📄 {{ $media->file_name }}]({{ url('/download/media/' . $media->uuid) }})
@endif
@endforeach

> ⚠️ Jika link di atas tidak bisa dibuka, silakan login ke Dasbor Admin untuk mengunduh langsung.
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
