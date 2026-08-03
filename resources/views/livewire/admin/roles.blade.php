<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new
#[Layout('layouts.admin')]
class extends Component
{
    public $showModal = false;
    public $roleId = null;
    public $roleName = '';
    public array $selectedPermissions = [];

    public function mount()
    {
        // Only Super Admin should typically access this page entirely,
        // but since we are making a menu-based UI, let's use the standard gate.
        if (!auth()->user()->can('access roles') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function with()
    {
        // Exclude Super Admin from being edited in this simple UI to prevent lockouts,
        // unless you want to show it. Super Admin bypasses anyway.
        return [
            'roles' => Role::with('permissions')->where('name', '!=', 'Super Admin')->get(),
            'permissions' => Permission::all(),
        ];
    }

    public function editPermissions($id)
    {
        $this->roleId = $id;
        $role = Role::find($id);
        $this->roleName = $role->name;
        // Pluck the permission names that this role currently has
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function savePermissions()
    {
        $role = Role::find($this->roleId);
        // Sync permissions expects array of names or IDs. We use names.
        $role->syncPermissions($this->selectedPermissions);
        
        $this->showModal = false;
        
        // Dispatch browser event or simple session flash (using basic Volt approach)
        session()->flash('message', 'Permissions updated successfully.');
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        if (in_array($role->name, ['Admin', 'Super Admin'])) {
            session()->flash('error', 'Cannot delete protected roles.');
            return;
        }
        
        $role->delete();
        session()->flash('message', 'Role deleted successfully.');
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="flex justify-between items-end mb-stack-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">Roles & Permissions</h2>
            <p class="text-on-surface-variant mt-1">Manage which menus each role can access.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-success/10 text-success rounded-lg font-medium border border-success/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-error/10 text-error rounded-lg font-medium border border-error/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-stack-md sm:grid-cols-2 lg:grid-cols-3">
        @foreach($roles as $role)
            <div class="bg-surface-bg border border-surface-border rounded-xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-lg bg-primary-fixed flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[24px]">shield_person</span>
                        </div>
                        
                        @if(!in_array($role->name, ['Admin', 'Super Admin']))
                        <button wire:click="deleteRole({{ $role->id }})" 
                                wire:confirm="Are you sure you want to delete the role '{{ $role->name }}'?"
                                class="text-secondary hover:text-error transition-colors p-2 rounded-lg hover:bg-error/10 tooltip-trigger"
                                title="Delete Role">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                        @endif
                    </div>
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface mb-2">{{ $role->name }}</h3>
                    <p class="text-sm text-on-surface-variant mb-4">
                        {{ $role->permissions->count() }} permissions assigned
                    </p>
                    
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($role->permissions->take(3) as $perm)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-surface-variant text-on-surface-variant">
                                {{ str_replace('access ', '', $perm->name) }}
                            </span>
                        @endforeach
                        @if($role->permissions->count() > 3)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-surface-variant text-on-surface-variant">
                                +{{ $role->permissions->count() - 3 }} more
                            </span>
                        @endif
                    </div>
                </div>
                
                <button wire:click="editPermissions({{ $role->id }})" class="w-full py-2.5 px-4 bg-surface-container-low hover:bg-surface-container text-on-surface font-semibold rounded-lg border border-surface-border transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                    Edit Permissions
                </button>
            </div>
        @endforeach
    </div>

    <!-- Edit Permissions Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" x-data @keydown.escape.window="$wire.set('showModal', false)">
        <div class="bg-white border border-surface-border rounded-xl shadow-2xl w-full max-w-lg p-6 relative animate-in zoom-in-95 duration-200" @click.outside="$wire.set('showModal', false)">
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-surface-border">
                <h3 class="font-headline-sm text-headline-sm font-bold">Edit Permissions: {{ $roleName }}</h3>
                <button wire:click="$set('showModal', false)" class="text-secondary hover:text-error transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto pr-2 mb-6 space-y-3">
                @foreach($permissions as $permission)
                    <label class="flex items-center p-3 rounded-lg border {{ in_array($permission->name, $selectedPermissions) ? 'border-primary bg-primary/5' : 'border-surface-border bg-surface-container-lowest' }} cursor-pointer transition-colors hover:border-primary/50">
                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="w-5 h-5 rounded border-surface-border text-primary focus:ring-primary focus:ring-offset-0 mr-3">
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface capitalize">{{ str_replace('access ', '', $permission->name) }} Menu</span>
                            <span class="text-xs text-secondary">Allow {{ $roleName }} to view and manage {{ str_replace('access ', '', $permission->name) }}.</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <button wire:click="$set('showModal', false)" class="px-5 py-2.5 rounded-lg font-semibold text-secondary hover:bg-surface-container transition-colors">
                    Cancel
                </button>
                <button wire:click="savePermissions" class="px-5 py-2.5 rounded-lg font-semibold bg-primary text-white hover:bg-primary/90 transition-colors shadow-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
