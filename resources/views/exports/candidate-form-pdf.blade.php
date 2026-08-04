<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kandidat - {{ $candidate->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #333; margin: 0; padding: 20px; line-height: 1.5; background-color: #fff; }
        .header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .header h1 { font-size: 22px; margin: 0 0 5px 0; color: #111; }
        .header p { margin: 0; color: #666; font-size: 13px; }
        .card { border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
        .card-title { font-size: 14px; font-weight: bold; margin-bottom: 15px; color: #111; display: block; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 50%; vertical-align: top; padding-bottom: 15px; }
        .label { font-size: 11px; color: #666; display: block; margin-bottom: 3px; }
        .value { font-size: 13px; font-weight: bold; color: #111; }
        .notes-box { font-size: 13px; color: #333; white-space: pre-wrap; margin: 0; line-height: 1.6; }
        .file-list { margin: 0; padding: 0; list-style: none; }
        .file-item { padding: 10px; border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 8px; background-color: #f9f9f9; }
        .file-item a { color: #005bbf; text-decoration: none; font-weight: bold; }
        .file-item a:hover { text-decoration: underline; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #999; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $candidate->name }}</h1>
        <p>Melamar untuk <strong>{{ $job->title }}</strong> pada {{ $application->created_at->format('d M Y') }}</p>
    </div>

    <div class="card">
        <span class="card-title">Informasi Kontak</span>
        <table class="grid">
            <tr>
                <td>
                    <span class="label">Email</span>
                    <span class="value">{{ $candidate->email }}</span>
                </td>
                <td>
                    <span class="label">Telepon</span>
                    <span class="value">{{ $candidate->phone ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Status Pipeline</span>
                    <span class="value">{{ $application->stage->name ?? 'Applied' }}</span>
                </td>
                <td>
                    <span class="label">Tanggal Melamar</span>
                    <span class="value">{{ $application->created_at->format('d M Y, H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <span class="card-title">Detail Lamaran & Jawaban Form</span>
        @if(!empty($notes))
            <div class="notes-box">{{ $notes }}</div>
        @else
            <div class="notes-box" style="color: #888; font-style: italic;">Tidak ada catatan tambahan atau jawaban form.</div>
        @endif
    </div>

    <div class="card">
        <span class="card-title">Dokumen Terlampir</span>
        @php
            $medias = $application->getMedia();
        @endphp
        @if($medias->count() > 0)
            <p style="font-size: 12px; color: #666; margin-top: 0; margin-bottom: 10px;">
                *Klik nama file di bawah ini untuk membukanya secara otomatis (jika didukung oleh aplikasi pembaca PDF Anda). File ini berada di folder yang sama dengan PDF ini.
            </p>
            <ul class="file-list">
                @foreach($medias as $media)
                <li class="file-item">
                    <a href="{{ $media->file_name }}" target="_blank">{{ $media->file_name }}</a>
                </li>
                @endforeach
            </ul>
        @else
            <div style="text-align: center; padding: 20px 0; color: #888; font-size: 13px;">
                Tidak ada dokumen terlampir.
            </div>
        @endif
    </div>

    <div class="footer">
        Dicetak secara otomatis dari {{ config('app.name', 'RC3ID ATS') }}
    </div>

</body>
</html>
