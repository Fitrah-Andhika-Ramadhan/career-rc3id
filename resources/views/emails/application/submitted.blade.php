<x-mail::message>
# Halo {{ $candidate->name }}, 👋

Terima kasih atas ketertarikan Anda untuk bergabung bersama **Research Center for Care and Control of Infectious Diseases (RC3ID) Universitas Padjadjaran**.

Kami ingin menginformasikan bahwa sistem kami telah **berhasil menerima lamaran Anda** untuk posisi:

<x-mail::panel>
**Posisi:** {{ $job->title }}  
**Status:** Sedang Ditinjau (Under Review)  
**Waktu Submit:** {{ now()->translatedFormat('l, d F Y - H:i') }}
</x-mail::panel>

Tim Rekrutmen (HR) kami akan segera meninjau kualifikasi serta kelengkapan dokumen yang Anda kirimkan. 

Proses seleksi di RC3ID dilakukan secara saksama. Jika profil dan pengalaman Anda sesuai dengan kualifikasi yang kami butuhkan, perwakilan dari tim kami akan segera menghubungi Anda melalui email atau telepon untuk menjadwalkan tahapan seleksi berikutnya (*Screening* / *Interview*).

Sembari menunggu, Anda dapat melihat informasi lebih lanjut mengenai kegiatan dan riset terbaru kami melalui portal resmi RC3ID.

<x-mail::button :url="config('app.url')" color="primary">
Kunjungi Portal RC3ID
</x-mail::button>

Semoga sukses dalam proses seleksi ini! 🌟

Salam hangat,<br>
**Tim Rekrutmen & SDM**<br>
{{ config('app.name') }}
</x-mail::message>
