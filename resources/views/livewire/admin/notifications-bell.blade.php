<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
};
?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <!-- Bell Icon -->
    <button @click="open = !open" class="relative p-2 text-secondary hover:text-primary transition-colors focus:outline-none rounded-full hover:bg-surface-variant">
        <span class="material-symbols-outlined text-[28px]" data-icon="notifications">notifications</span>
        
        <!-- Unread Badge -->
        @if(auth()->user()->unreadNotifications->count() > 0)
        <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-error text-[10px] font-bold text-white border-2 border-surface-bg">
            {{ auth()->user()->unreadNotifications->count() > 99 ? '99+' : auth()->user()->unreadNotifications->count() }}
        </span>
        @endif
    </button>

    <!-- Dropdown History -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 transform -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 transform -translate-y-2"
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest rounded-xl shadow-lg border border-surface-border overflow-hidden z-50"
         style="display: none;">
        
        <!-- Header -->
        <div class="px-margin py-3 border-b border-surface-border flex justify-between items-center bg-surface-bg">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Notifications</h3>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <button wire:click="markAllAsRead" class="text-xs font-semibold text-primary hover:underline focus:outline-none">
                Mark all as read
            </button>
            @endif
        </div>

        <!-- Notification List -->
        <div class="max-h-[60vh] overflow-y-auto">
            @forelse(auth()->user()->notifications()->take(10)->get() as $notification)
            <div wire:click="markAsRead('{{ $notification->id }}')" 
                 class="px-margin py-3 border-b border-surface-border last:border-0 cursor-pointer transition-colors {{ $notification->read_at ? 'bg-surface-container-lowest hover:bg-surface-bg' : 'bg-primary-fixed-dim hover:bg-primary-fixed border-l-4 border-l-primary' }}">
                <div class="flex items-start gap-3">
                    <div class="mt-1 flex-shrink-0">
                        <span class="material-symbols-outlined text-primary {{ $notification->read_at ? 'opacity-50' : '' }}" data-icon="assignment_ind">assignment_ind</span>
                    </div>
                    <div>
                        <p class="text-sm text-on-surface {{ $notification->read_at ? '' : 'font-semibold' }}">
                            {{ $notification->data['message'] ?? 'New notification' }}
                        </p>
                        <p class="text-xs text-on-surface-variant mt-1">
                            <span class="font-medium text-secondary">{{ $notification->data['candidate_name'] ?? 'Unknown' }}</span> applied for <span class="font-medium">{{ $notification->data['job_title'] ?? 'Unknown Job' }}</span>
                        </p>
                        <p class="text-[10px] text-secondary mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-margin py-8 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2 opacity-50" data-icon="notifications_off">notifications_off</span>
                <p class="font-body-md text-body-md">You're all caught up!</p>
            </div>
            @endforelse
            
            @if(auth()->user()->notifications()->count() > 10)
            <div class="px-margin py-2 text-center border-t border-surface-border bg-surface-bg">
                <p class="text-xs text-secondary italic">Showing 10 most recent</p>
            </div>
            @endif
        </div>
    </div>
</div>
