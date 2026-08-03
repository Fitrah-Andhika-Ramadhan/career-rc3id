<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermissionRequest;
use Livewire\WithPagination;

new
#[Layout('layouts.admin')]
class extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $newPermissionName = '';
    public $newReason = '';

    public function mount()
    {
        // Any verified admin can access this page
    }

    public function getAvailablePermissionsProperty()
    {
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name');
        return \Spatie\Permission\Models\Permission::whereNotIn('name', $userPermissions)->get();
    }

    public function submitNewRequest()
    {
        $this->validate([
            'newPermissionName' => 'required|string|exists:permissions,name',
            'newReason' => 'required|string|max:500',
        ]);

        $existing = PermissionRequest::where('user_id', auth()->id())
            ->where('permission_name', $this->newPermissionName)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            session()->flash('error', 'Anda sudah memiliki permohonan akses yang sedang menunggu persetujuan untuk fitur ini.');
            $this->showCreateModal = false;
            return;
        }

        PermissionRequest::create([
            'user_id' => auth()->id(),
            'permission_name' => $this->newPermissionName,
            'reason' => $this->newReason,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Permohonan akses berhasil dikirim! Silakan tunggu persetujuan dari Super Admin.');
        $this->showCreateModal = false;
        $this->newPermissionName = '';
        $this->newReason = '';
    }

    public function getRequestsProperty()
    {
        $query = PermissionRequest::with('user');

        // Regular admins only see their own requests
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function approve($id)
    {
        $request = PermissionRequest::findOrFail($id);
        if ($request->status !== 'pending') return;

        $request->status = 'approved';
        $request->save();

        $request->user->givePermissionTo($request->permission_name);
        session()->flash('message', 'Akses ke ' . $request->permission_name . ' berhasil diberikan kepada ' . $request->user->name);
    }

    public function reject($id)
    {
        $request = PermissionRequest::findOrFail($id);
        if ($request->status !== 'pending') return;

        $request->status = 'rejected';
        $request->save();

        session()->flash('message', 'Permohonan akses ditolak.');
    }

    public function revoke($id)
    {
        $request = PermissionRequest::findOrFail($id);
        if ($request->status !== 'approved') return;

        $request->user->revokePermissionTo($request->permission_name);

        $request->status = 'rejected';
        $request->save();

        session()->flash('message', 'Akses ke ' . $request->permission_name . ' berhasil dicabut dari ' . $request->user->name);
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="mb-stack-lg flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">{{ auth()->user()->hasRole('Super Admin') ? 'Permission Requests' : 'My Requests' }}</h2>
            <p class="text-on-surface-variant mt-1">{{ auth()->user()->hasRole('Super Admin') ? 'Kelola permohonan akses fitur dari pengguna lain.' : 'Pantau status permohonan akses fitur Anda.' }}</p>
        </div>
        
        @if(!auth()->user()->hasRole('Super Admin'))
        <button type="button" @click="$dispatch('open-create-request-modal')" class="bg-primary text-on-primary px-4 py-2 rounded-lg font-label-md hover:bg-primary/90 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Add Request
        </button>
        @endif
    </div>

    @if (session()->has('error'))
        <div class="mb-stack-lg p-stack-md bg-error/10 text-error border border-error/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">error</span>
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('message'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-lowest border-b border-surface-border font-label-md text-label-md text-on-surface-variant">
                    <tr>
                        @if(auth()->user()->hasRole('Super Admin'))
                        <th class="px-6 py-4 font-semibold">User</th>
                        @endif
                        <th class="px-6 py-4 font-semibold">Requested Permission</th>
                        <th class="px-6 py-4 font-semibold">Reason</th>
                        <th class="px-6 py-4 font-semibold">Date</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        @if(auth()->user()->hasRole('Super Admin'))
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border font-body-sm text-body-sm">
                    @forelse($this->requests as $req)
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        @if(auth()->user()->hasRole('Super Admin'))
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">
                                    {{ strtoupper(substr($req->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-on-surface">{{ $req->user->name }}</p>
                                    <p class="text-xs text-secondary">{{ $req->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-surface-container text-on-surface-variant rounded-md font-mono text-xs">{{ $req->permission_name }}</span>
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate text-secondary" title="{{ $req->reason }}">
                            {{ $req->reason }}
                        </td>
                        <td class="px-6 py-4 text-secondary whitespace-nowrap">
                            {{ $req->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($req->status === 'pending')
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-warning/10 text-warning border border-warning/20">Pending</span>
                            @elseif($req->status === 'approved')
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-success/10 text-success border border-success/20">Approved</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-error/10 text-error border border-error/20">Rejected</span>
                            @endif
                        </td>
                        @if(auth()->user()->hasRole('Super Admin'))
                        <td class="px-6 py-4 text-right">
                            @if($req->status === 'pending')
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" x-on:click="window.confirmAction('Berikan akses {{ $req->permission_name }} kepada {{ $req->user->name }}?', () => $wire.approve({{ $req->id }}), 'question')" class="p-1.5 bg-success/10 text-success hover:bg-success hover:text-white rounded-lg transition-colors" title="Approve">
                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                </button>
                                <button type="button" x-on:click="window.confirmAction('Tolak permohonan ini?', () => $wire.reject({{ $req->id }}), 'warning')" class="p-1.5 bg-error/10 text-error hover:bg-error hover:text-white rounded-lg transition-colors" title="Reject">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            @elseif($req->status === 'approved')
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" x-on:click="window.confirmAction('Cabut akses {{ $req->permission_name }} dari {{ $req->user->name }}?', () => $wire.revoke({{ $req->id }}), 'warning')" class="p-1.5 bg-warning/10 text-warning hover:bg-warning hover:text-white rounded-lg transition-colors" title="Cabut Akses (Revoke)">
                                    <span class="material-symbols-outlined text-[18px]">key_off</span>
                                </button>
                            </div>
                            @else
                                <span class="text-secondary italic text-xs">Dicabut/Ditolak</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('Super Admin') ? '6' : '4' }}" class="px-6 py-10 text-center text-secondary">
                            <div class="flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl opacity-50">inbox</span>
                                <p>Belum ada permohonan akses.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->requests->hasPages())
        <div class="p-4 border-t border-surface-border">
            {{ $this->requests->links() }}
        </div>
        @endif
    </div>

    <!-- Generic Add Request Modal -->
    <div x-data="{ showCreateModal: @entangle('showCreateModal') }" @open-create-request-modal.window="showCreateModal = true" x-cloak>
        <div x-show="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>

            <!-- Modal Panel -->
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-2xl w-full max-w-md overflow-hidden relative z-[101] flex flex-col" @click.stop>
                <div class="p-5 border-b border-surface-border flex justify-between items-center bg-surface-container-low">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">add</span>
                        New Request
                    </h3>
                    <button @click="showCreateModal = false" type="button" class="text-secondary hover:text-error transition-colors rounded-full p-1 hover:bg-surface-container">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <form wire:submit="submitNewRequest" class="flex flex-col flex-1">
                    <div class="p-5 space-y-4">
                        <div class="space-y-2">
                            <label class="font-label-md text-label-md text-on-surface-variant block">Select Feature</label>
                            <select wire:model="newPermissionName" class="w-full px-4 py-3 border border-surface-border rounded-lg bg-surface-container-lowest focus:ring-primary focus:border-primary text-sm" required>
                                <option value="">-- Choose Feature --</option>
                                @foreach($this->availablePermissions as $perm)
                                    <option value="{{ $perm->name }}">{{ ucwords(str_replace('access ', '', $perm->name)) }} ({{ $perm->name }})</option>
                                @endforeach
                            </select>
                            @error('newPermissionName') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="font-label-md text-label-md text-on-surface-variant block">Reason for Access</label>
                            <textarea wire:model="newReason" rows="3" class="w-full px-4 py-3 border border-surface-border rounded-lg bg-surface-container-lowest focus:ring-primary focus:border-primary text-sm resize-none" placeholder="Please briefly explain why you need access to this feature..." required></textarea>
                            @error('newReason') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="p-5 border-t border-surface-border bg-surface-container-lowest flex justify-end gap-3">
                        <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 border border-surface-border text-on-surface-variant rounded-lg font-label-md hover:bg-surface-container transition-all">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 transition-all flex items-center gap-2 disabled:opacity-50">
                            <span wire:loading.remove class="material-symbols-outlined text-[18px]">send</span>
                            <span wire:loading class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
