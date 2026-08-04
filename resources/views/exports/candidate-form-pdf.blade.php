<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kandidat - {{ $candidate->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #005bbf; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #005bbf; }
        .header p { margin: 5px 0 0; color: #555; }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 18px; border-bottom: 1px solid #ccc; padding-bottom: 5px; color: #444; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table th { width: 150px; text-align: left; padding: 8px 0; color: #555; vertical-align: top; }
        .info-table td { padding: 8px 0; font-weight: bold; vertical-align: top; }
        .notes-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 13px; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ config('app.name', 'RC3ID ATS') }}</h1>
        <p>Formulir Pendaftaran Kandidat</p>
    </div>

    <div class="section">
        <h2>Informasi Pekerjaan</h2>
        <table class="info-table">
            <tr>
                <th>Posisi Dilamar</th>
                <td>: {{ $job->title }}</td>
            </tr>
            <tr>
                <th>Departemen</th>
                <td>: {{ $job->department ?? 'General' }}</td>
            </tr>
            <tr>
                <th>Tanggal Melamar</th>
                <td>: {{ $application->created_at->format('d M Y, H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Biodata Kandidat</h2>
        <table class="info-table">
            <tr>
                <th>Nama Lengkap</th>
                <td>: {{ strtoupper($candidate->name) }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>: {{ $candidate->email }}</td>
            </tr>
            <tr>
                <th>Nomor Telepon</th>
                <td>: {{ $candidate->phone ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Jawaban Form Kustom & Catatan</h2>
        @if(!empty($notes))
            <div class="notes-box">{{ $notes }}</div>
        @else
            <p style="color: #888; font-style: italic;">Tidak ada catatan tambahan atau jawaban form.</p>
        @endif
    </div>

    <div class="footer">
        <p>Dicetak secara otomatis oleh sistem {{ config('app.name', 'RC3ID ATS') }} pada {{ now()->format('d M Y H:i:s') }}</p>
    </div>

</body>
</html>
