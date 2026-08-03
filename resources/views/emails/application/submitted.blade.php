<x-mail::message>
# Halo {{ $candidate->name }},

Terima kasih telah melamar untuk posisi **{{ $job->title }}** di RC3ID.

Kami telah menerima lamaran beserta dokumen (CV/Resume, Ijazah, dll) yang Anda kirimkan. Tim Rekrutmen (HR) kami akan segera meninjau kualifikasi Anda.

Jika profil Anda sesuai dengan kualifikasi yang kami cari, kami akan menghubungi Anda kembali untuk menjadwalkan tahap seleksi berikutnya (Screening / Interview).

Anda dapat melihat informasi lebih lanjut mengenai karir di RC3ID melalui portal kami.

<x-mail::button :url="config('app.url')">
Kunjungi Portal Karir
</x-mail::button>

Semoga berhasil!<br>
Tim Rekrutmen,<br>
{{ config('app.name') }}
</x-mail::message>
