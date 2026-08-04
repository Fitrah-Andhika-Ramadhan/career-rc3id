<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

new
#[Layout('layouts.admin')]
class extends Component
{
    public function with()
    {
        return [
            'backups' => $this->getBackups()
        ];
    }

    private function getBackups()
    {
        $appName = env('APP_NAME', 'laravel-backup');
        $files = Storage::disk('local')->files($appName);
        $backups = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $backups[] = [
                    'name' => basename($file),
                    'size' => round(Storage::disk('local')->size($file) / 1024 / 1024, 2) . ' MB',
                    'timestamp' => Storage::disk('local')->lastModified($file),
                    'date' => date('d M Y, H:i', Storage::disk('local')->lastModified($file)),
                    'path' => $file
                ];
            }
        }
        
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $backups;
    }

    public function triggerBackup()
    {
        try {
            set_time_limit(300); // 5 minutes max for backup process
            Artisan::call('backup:run');
            session()->flash('success_backup', 'Proses pencadangan (backup) berhasil diselesaikan!');
        } catch (\Exception $e) {
            session()->flash('error_backup', 'Terjadi kesalahan saat memproses backup: ' . $e->getMessage());
        }
    }

    public function downloadBackup($file)
    {
        if (Storage::disk('local')->exists($file)) {
            return Storage::disk('local')->download($file);
        }
        session()->flash('error_backup', 'File tidak ditemukan!');
    }

    public function deleteBackup($file)
    {
        if (Storage::disk('local')->exists($file)) {
            Storage::disk('local')->delete($file);
            session()->flash('success_backup', 'File backup berhasil dihapus!');
        }
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg text-on-background">Backup Data Lokal</h2>
        <p class="text-on-surface-variant mt-1">Kelola pencadangan data aplikasi (Database & Dokumen Pelamar) langsung di dalam server dan unduh kapan saja.</p>
    </div>

    @if (session()->has('success_backup'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
            {{ session('success_backup') }}
        </div>
    @endif
    @if (session()->has('error_backup'))
        <div class="mb-stack-lg p-stack-md bg-error/10 text-error border border-error/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">error</span>
            {{ session('error_backup') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-stack-lg">
        
        {{-- Kolom Kiri: Eksekusi Backup --}}
        <div class="lg:col-span-1">
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden sticky top-4">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
                        Eksekusi Backup Baru
                    </h3>
                </div>
                <div class="p-margin space-y-stack-md">
                    <p class="text-sm text-secondary">Jalankan proses pencadangan database dan seluruh dokumen lamaran kandidat menjadi satu file .zip di server lokal.</p>
                    
                    <div class="flex items-center gap-4 py-4 px-4 bg-surface-container-low rounded-lg border border-surface-border">
                        <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[24px]">database</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-on-surface text-sm">Status Kapasitas Data</p>
                            <p class="text-xs text-secondary">
                                DB: @php
                                    echo config('database.default') === 'mysql' ? 'MySQL' : (config('database.default') === 'sqlite' ? 'SQLite' : 'Database');
                                @endphp | 
                                Dokumen: @php
                                    $size = 0;
                                    $path = storage_path('app/public');
                                    if (File::exists($path)) {
                                        foreach (File::allFiles($path) as $file) {
                                            $size += $file->getSize();
                                        }
                                    }
                                    echo round($size / 1024 / 1024, 2) . ' MB';
                                @endphp
                            </p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-surface-border" x-data>
                        <button @click="
                            Swal.fire({
                                title: 'Mulai Pencadangan?',
                                text: 'Proses ini akan memakan waktu beberapa saat tergantung ukuran data.',
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonColor: '#005bbf',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Ya, Mulai Backup!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $wire.triggerBackup();
                                }
                            })
                        " type="button" class="w-full px-6 py-3 bg-primary text-white rounded-lg font-label-md hover:opacity-90 flex items-center justify-center gap-2 transition-all shadow-md">
                            <span wire:loading.remove wire:target="triggerBackup" class="material-symbols-outlined text-[18px]">backup</span>
                            <span wire:loading wire:target="triggerBackup" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                            <span wire:loading.remove wire:target="triggerBackup">Mulai Backup Sekarang</span>
                            <span wire:loading wire:target="triggerBackup">Memproses Backup...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Daftar Backup --}}
        <div class="lg:col-span-2">
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-margin border-b border-surface-border flex items-center justify-between">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-[24px]">folder_zip</span>
                            Riwayat File Backup
                        </h3>
                        <p class="text-sm text-secondary mt-1">Daftar file backup yang tersimpan di dalam server.</p>
                    </div>
                    <button wire:click="$refresh" class="p-2 text-secondary hover:text-primary transition-colors rounded-full hover:bg-surface-container" title="Refresh Daftar">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant text-label-sm border-b border-surface-border">
                                <th class="py-3 px-margin font-semibold">Nama File</th>
                                <th class="py-3 px-4 font-semibold w-32">Ukuran</th>
                                <th class="py-3 px-4 font-semibold w-48">Tanggal</th>
                                <th class="py-3 px-margin font-semibold text-right w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                                <tr class="border-b border-surface-border hover:bg-surface-container-lowest transition-colors group">
                                    <td class="py-4 px-margin">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-success text-[28px]">inventory_2</span>
                                            <div>
                                                <p class="font-semibold text-on-surface text-sm break-all">{{ $backup['name'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-block px-2 py-1 bg-surface-container rounded text-xs font-semibold text-secondary">{{ $backup['size'] }}</span>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-secondary">
                                        {{ $backup['date'] }}
                                    </td>
                                    <td class="py-4 px-margin text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                            <button wire:click="downloadBackup('{{ $backup['path'] }}')" class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Download">
                                                <span class="material-symbols-outlined text-[20px]">download</span>
                                            </button>
                                            <button wire:click="deleteBackup('{{ $backup['path'] }}')" wire:confirm="Anda yakin ingin menghapus file backup ini secara permanen?" class="p-2 text-error hover:bg-error/10 rounded-lg transition-colors" title="Hapus">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <div class="inline-flex flex-col items-center justify-center text-surface-container-highest">
                                            <span class="material-symbols-outlined text-[48px] mb-2">folder_off</span>
                                            <p class="text-on-surface-variant font-semibold">Belum ada file backup</p>
                                            <p class="text-sm text-secondary mt-1">Silakan klik tombol "Mulai Backup Sekarang" untuk membuat cadangan data pertama Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
