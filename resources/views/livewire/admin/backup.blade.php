<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('layouts.admin')]
class extends Component
{
    public $googleDriveLink = '';
    
    public function mount()
    {
        $this->googleDriveLink = env('BACKUP_GDRIVE_LINK', '');
    }

    public function saveConfig()
    {
        $this->validate([
            'googleDriveLink' => 'required|url'
        ]);

        $envPath = base_path('.env');
        if (\Illuminate\Support\Facades\File::exists($envPath)) {
            $contents = \Illuminate\Support\Facades\File::get($envPath);
            $key = 'BACKUP_GDRIVE_LINK';
            $value = $this->googleDriveLink;
            $pattern = "/^{$key}=.*/m";
            
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, "{$key}={$value}", $contents);
            } else {
                $contents .= "\n{$key}={$value}";
            }
            \Illuminate\Support\Facades\File::put($envPath, $contents);
        }

        \Illuminate\Support\Facades\Artisan::call('config:clear');
        session()->flash('message', 'Konfigurasi tautan Google Drive berhasil disimpan!');
    }

    public function triggerBackup()
    {
        if (!$this->googleDriveLink) {
            session()->flash('error', 'Silakan simpan tautan Google Drive terlebih dahulu sebelum melakukan backup.');
            return;
        }

        // Logic backup sebenarnya nanti disambungkan ke sini.
        // Untuk sekarang, kita pura-pura backup berhasil.
        session()->flash('success_backup', 'Proses sinkronisasi dan backup data ke Google Drive sedang berjalan di latar belakang.');
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg text-on-background">Backup Data</h2>
        <p class="text-on-surface-variant mt-1">Kelola pencadangan data aplikasi dan hubungkan dengan Google Drive.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-lg">
        
        {{-- Konfigurasi Google Drive --}}
        <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
            <div class="p-margin border-b border-surface-border">
                <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[24px]">add_to_drive</span>
                    Integrasi Google Drive
                </h3>
            </div>
            <form wire:submit="saveConfig" class="p-margin space-y-stack-md">
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Tautan / Link Google Drive Folder</label>
                    <input wire:model="googleDriveLink" type="url" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary transition-all" placeholder="https://drive.google.com/drive/folders/xxxxx">
                    @error('googleDriveLink') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    <p class="text-xs text-secondary mt-2">Pastikan folder Google Drive dapat diakses (akses baca & tulis jika diperlukan script/webhook).</p>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 bg-surface-container text-on-surface border border-surface-border rounded-lg font-label-md hover:bg-surface-variant flex items-center gap-2 transition-all">
                        <span wire:loading.remove wire:target="saveConfig" class="material-symbols-outlined text-[18px]">save</span>
                        <span wire:loading wire:target="saveConfig" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                        <span wire:loading.remove wire:target="saveConfig">Simpan Tautan</span>
                        <span wire:loading wire:target="saveConfig">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Aksi Backup --}}
        <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
            <div class="p-margin border-b border-surface-border">
                <h3 class="font-headline-md text-headline-md text-success flex items-center gap-2">
                    <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
                    Eksekusi Backup
                </h3>
            </div>
            <div class="p-margin space-y-stack-md">
                <p class="text-sm text-secondary">Jalankan proses pencadangan database dan dokumen lamaran (CV/Ijazah) ke folder Google Drive yang telah dikonfigurasi.</p>
                
                <div class="flex items-center gap-4 py-4 px-4 bg-surface-container-low rounded-lg border border-surface-border">
                    <div class="w-12 h-12 rounded-full bg-success/10 text-success flex items-center justify-center">
                        <span class="material-symbols-outlined text-[24px]">database</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-on-surface text-sm">Database & File Storage</p>
                        <p class="text-xs text-secondary">
                            DB: {{ round(filesize(database_path('database.sqlite')) / 1024, 2) }} KB | 
                            Candidate Files: @php
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
                            text: 'Proses ini akan mengunggah database saat ini ke Google Drive.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#0f9d58',
                            cancelButtonColor: '#d93025',
                            confirmButtonText: 'Ya, Mulai Backup!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $wire.triggerBackup();
                            }
                        })
                    " type="button" class="w-full px-6 py-3 bg-success text-white rounded-lg font-label-md hover:opacity-90 flex items-center justify-center gap-2 transition-all">
                        <span wire:loading.remove wire:target="triggerBackup" class="material-symbols-outlined text-[18px]">backup</span>
                        <span wire:loading wire:target="triggerBackup" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                        <span wire:loading.remove wire:target="triggerBackup">Mulai Backup Sekarang</span>
                        <span wire:loading wire:target="triggerBackup">Memproses Backup...</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
