<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

new
#[Layout('layouts.admin')]
class extends Component
{
    public $showModal = false;
    public $isEdit = false;
    public $userId = null;

    public $name = '';
    public $email = '';
    public $password = '';
    public $role = '';

    public function mount()
    {
        if (!auth()->user()->can('access users')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function with()
    {
        return [
            'users' => User::with('roles')->orderBy('created_at', 'desc')->get(),
            'rolesList' => Role::all(),
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEdit = true;
        $this->userId = $id;

        $user = User::find($id);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? '';

        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email' . ($this->isEdit ? ',' . $this->userId : ''),
            'role' => 'required|string|exists:roles,name',
        ];

        if (!$this->isEdit || !empty($this->password)) {
            $rules['password'] = ['required', Rules\Password::defaults()];
        }

        $this->validate($rules);

        if ($this->isEdit) {
            $user = User::find($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            
            if (!empty($this->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
            $user->syncRoles([$this->role]);
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            $user->assignRole($this->role);
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        if ($id == auth()->id()) {
            return; // Cannot delete self
        }
        User::find($id)->delete();
    }

    public function reset2FA($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->google2fa_secret = null;
            $user->save();
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = '';
        $this->userId = null;
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="flex justify-between items-end mb-stack-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">User Management</h2>
            <p class="text-on-surface-variant mt-1">Manage system administrators, HR staff, and their permissions.</p>
        </div>
        <button wire:click="create" class="px-margin py-stack-md bg-primary text-on-primary rounded-lg font-semibold flex items-center gap-stack-sm hover:opacity-90 transition-all">
            <span class="material-symbols-outlined" data-icon="add">add</span>
            Add User
        </button>
    </div>

    <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-surface-border">
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Name</th>
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Email</th>
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Role</th>
                        <th class="p-stack-md text-right font-label-md text-on-surface-variant uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    @foreach($users as $user)
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="p-stack-md">
                            <div class="flex items-center gap-stack-sm">
                                <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-bold text-xs">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-on-surface">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="p-stack-md text-on-surface-variant">{{ $user->email }}</td>
                        <td class="p-stack-md">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-variant text-on-surface-variant">
                                {{ $user->roles->first()?->name ?? 'None' }}
                            </span>
                        </td>
                        <td class="p-stack-md text-right flex justify-end gap-2">
                            <button wire:click="edit({{ $user->id }})" class="p-2 hover:bg-surface-container rounded-lg transition-opacity text-primary" title="Edit User">
                                <span class="material-symbols-outlined" data-icon="edit">edit</span>
                            </button>
                            @if($user->google2fa_secret)
                            <button type="button" x-on:click="confirmDelete('Reset & hapus perlindungan 2FA untuk user ini?', () => $wire.reset2FA({{ $user->id }}))" class="p-2 hover:bg-surface-container rounded-lg transition-opacity text-orange-500" title="Reset 2FA">
                                <span class="material-symbols-outlined">key_off</span>
                            </button>
                            @endif
                            @if($user->id !== auth()->id())
                            <button type="button" x-on:click="confirmDelete('Are you sure you want to delete this user?', () => $wire.delete({{ $user->id }}))" class="p-2 hover:bg-surface-container rounded-lg transition-opacity text-error" title="Delete User">
                                <span class="material-symbols-outlined" data-icon="delete">delete</span>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="p-margin border-b border-surface-border flex justify-between items-center">
                <h2 class="font-headline-md text-headline-md">{{ $isEdit ? 'Edit User' : 'Add New User' }}</h2>
                <button wire:click="$set('showModal', false)" class="text-secondary hover:text-on-surface">
                    <span class="material-symbols-outlined" data-icon="close">close</span>
                </button>
            </div>
            <form wire:submit="save" class="p-margin space-y-stack-md">
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Name</label>
                    <input wire:model="name" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                    @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Email</label>
                    <input wire:model="email" type="email" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                    @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Role</label>
                    <select wire:model="role" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                        <option value="">Select Role</option>
                        @foreach($rolesList as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Password {{ $isEdit ? '(Leave blank to keep current)' : '' }}</label>
                    <input wire:model="password" type="password" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" {{ $isEdit ? '' : 'required' }}>
                    @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-4 mt-6 pt-6 border-t border-surface-border">
                    <button type="button" wire:click="$set('showModal', false)" class="px-6 py-2 rounded-lg font-label-md border hover:bg-surface-container">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
