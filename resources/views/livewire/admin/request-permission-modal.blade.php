<?php

use Livewire\Volt\Component;
use App\Models\PermissionRequest;

new class extends Component
{
    public $permissionName = '';
    public $permissionLabel = '';
    public $reason = '';

    public function submitRequest()
    {
        $this->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if a pending request already exists
        $existing = PermissionRequest::where('user_id', auth()->id())
            ->where('permission_name', $this->permissionName)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            session()->flash('error', 'Anda sudah memiliki permohonan akses yang sedang menunggu persetujuan untuk fitur ini.');
            $this->dispatch('close-permission-modal');
            return;
        }

        PermissionRequest::create([
            'user_id' => auth()->id(),
            'permission_name' => $this->permissionName,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        session()->flash('success', 'Permohonan akses berhasil dikirim! Silakan tunggu persetujuan dari Super Admin.');
        $this->dispatch('close-permission-modal');
    }
};
?>

<div x-data="{ 
    showModal: false, 
    permissionName: '', 
    permissionLabel: '' 
}" 
@open-permission-modal.window="
    showModal = true; 
    permissionName = $event.detail.permissionName; 
    permissionLabel = $event.detail.permissionLabel;
    $wire.set('permissionName', permissionName);
    $wire.set('permissionLabel', permissionLabel);
"
@close-permission-modal.window="showModal = false"
x-cloak>
    
    <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

        <!-- Modal Panel -->
        <div class="bg-surface-bg border border-surface-border rounded-xl shadow-2xl w-full max-w-md overflow-hidden relative z-[101] flex flex-col max-h-[90vh]" @click.stop>
            <div class="p-5 border-b border-surface-border flex justify-between items-center bg-surface-container-low">
                <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">lock</span>
                    Akses Terkunci
                </h3>
                <button @click="showModal = false" type="button" class="text-secondary hover:text-error transition-colors rounded-full p-1 hover:bg-surface-container">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form wire:submit="submitRequest" class="flex flex-col flex-1 overflow-hidden">
                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                    <div class="bg-warning/10 text-warning border border-warning/20 p-4 rounded-lg flex items-start gap-3">
                        <span class="material-symbols-outlined shrink-0 mt-0.5">info</span>
                        <div class="text-sm">
                            <p class="font-semibold mb-1">Anda tidak memiliki akses ke fitur ini.</p>
                            <p>Silakan isi form di bawah ini untuk meminta akses <strong x-text="permissionLabel"></strong> kepada Super Admin.</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="font-label-md text-label-md text-on-surface-variant block">Alasan Permintaan Akses</label>
                        <textarea wire:model="reason" rows="4" class="w-full px-4 py-3 border border-surface-border rounded-lg bg-surface-container-lowest focus:ring-primary focus:border-primary text-sm resize-none" placeholder="Jelaskan secara singkat mengapa Anda membutuhkan akses ke fitur ini..." required></textarea>
                        @error('reason') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-5 border-t border-surface-border bg-surface-container-lowest flex justify-end gap-3 shrink-0">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 border border-surface-border text-on-surface-variant rounded-lg font-label-md hover:bg-surface-container transition-all">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 transition-all flex items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove class="material-symbols-outlined text-[18px]">send</span>
                        <span wire:loading class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                        Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
