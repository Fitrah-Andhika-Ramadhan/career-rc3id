<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Job;

new
#[Layout('components.layouts.public')]
#[Title('CareerRC3ID | Find Your Next Opportunity')]
class extends Component
{
    use WithPagination;

    public $searchQuery = '';
    public $searchDepartment = 'All Departments';

    public function searchJobs()
    {
        // This method just triggers a re-render.
    }

    public function with()
    {
        $query = Job::where('status', 'published');

        if (!empty($this->searchQuery)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
            });
        }

        if ($this->searchDepartment !== 'All Departments') {
            $query->where('department', $this->searchDepartment);
        }

        $jobsPaginator = $query->orderBy('created_at', 'desc')->paginate(6);
        
        $groupedJobs = collect($jobsPaginator->items())->groupBy(function($job) {
            return \Carbon\Carbon::parse($job->created_at)->translatedFormat('d F Y');
        });

        return [
            'groupedJobs' => $groupedJobs,
            'jobsPaginator' => $jobsPaginator,
            'departments' => Job::where('status', 'published')->select('department')->distinct()->pluck('department')->filter(),
        ];
    }
};
?>

<div>
    <!-- Hero Section -->
    @php
        $opacity = env('HERO_OVERLAY_OPACITY', '0.8');
        $opacityGradient = min(1, floatval($opacity) + 0.1); // slightly darker at bottom
    @endphp
    <section class="hero-pattern py-24 text-on-primary-container relative overflow-hidden" style="background-image: linear-gradient(rgba(var(--color-primary-rgb) / {{ $opacity }}), rgba(var(--color-primary-rgb) / {{ $opacityGradient }})), url('{{ asset('hero_background.png') }}'); background-size: cover; background-position: center;">
        <div class="max-w-container-max mx-auto px-margin relative z-10 flex flex-col md:flex-row items-center gap-stack-lg">
            <div class="w-full text-center flex flex-col items-center">
                <h1 class="font-headline-xl text-headline-xl mb-stack-md text-white">{{ config('app.hero_title', 'Find Your Next Career at CareerRC3ID') }}</h1>
                <p class="font-body-lg text-body-lg text-primary-fixed mb-stack-lg opacity-90 max-w-2xl">
                    {{ config('app.hero_subtitle', 'Join a global team of innovators, engineers, and creatives. We are building the future of precision technology and we need your talent to help us lead the way.') }}
                </p>
                <!-- Search/Filter Bar -->
                <div class="bg-white/95 backdrop-blur-md p-2 md:p-3 rounded-2xl shadow-2xl border border-white/20 flex flex-col md:flex-row gap-2 max-w-3xl ring-1 ring-black/5 mx-auto transform transition-all hover:scale-[1.01] duration-300">
                    <div class="flex-1 flex items-center px-4 border-r border-surface-border/50">
                        <span class="material-symbols-outlined text-outline mr-2">search</span>
                        <input wire:model="searchQuery" wire:keydown.enter="searchJobs" class="w-full border-none focus:ring-0 text-body-md font-body-md bg-transparent text-on-surface" placeholder="Job title or keywords" type="text"/>
                    </div>
                    <div class="flex-1 flex items-center px-stack-md border-r border-surface-border hidden md:flex">
                        <span class="material-symbols-outlined text-outline mr-2">business_center</span>
                        <select wire:model="searchDepartment" wire:change="searchJobs" class="w-full border-none focus:ring-0 text-body-md font-body-md bg-transparent text-on-surface appearance-none">
                            <option value="All Departments">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button wire:click="searchJobs" class="bg-primary text-white px-8 py-3 rounded-xl font-label-md text-label-md hover:bg-primary/90 transition-all shadow-md hover:shadow-lg active:scale-95">
                        Search Jobs
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Jobs -->
    <section class="py-24 bg-surface">
        <div class="max-w-container-max mx-auto px-margin">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-stack-sm">Featured Opportunities</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">Hand-picked roles for exceptional talent like you.</p>
                </div>
            </div>
            
            <div class="space-y-12">
                @forelse($groupedJobs as $date => $jobs)
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <h3 class="font-headline-sm text-headline-sm text-primary font-semibold bg-primary/10 px-4 py-1.5 rounded-full inline-block border border-primary/20">{{ $date }}</h3>
                            <div class="h-px bg-surface-border flex-1"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
                            @foreach($jobs as $job)
                                @php
                                    $isExpired = $job->deadline_date && now()->startOfDay()->gt($job->deadline_date);
                                @endphp
                                <!-- Job Card -->
                                <a href="{{ $isExpired ? 'javascript:void(0)' : route('jobs.apply', $job) }}" class="bg-surface-bg border border-surface-border rounded-2xl p-stack-md {{ $isExpired ? 'opacity-70 cursor-not-allowed' : 'kanban-shadow hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-primary/5 hover:border-primary/40 group cursor-pointer' }} transition-all duration-300 block relative overflow-hidden">
                                    <!-- Animated Top Border -->
                                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-info opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <div class="flex justify-between items-start mb-stack-md">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-primary/5 p-3 rounded-xl border border-primary/10 group-hover:bg-primary/10 transition-colors">
                                                <span class="material-symbols-outlined text-primary">work</span>
                                            </div>
                                            <div class="relative" x-data="{ shareOpen: false }" @click.outside="shareOpen = false">
                                                <button 
                                                    type="button"
                                                    @click.prevent="shareOpen = !shareOpen"
                                                    class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-variant hover:text-primary transition-colors"
                                                    title="Bagikan Tautan"
                                                >
                                                    <span class="material-symbols-outlined text-[20px]">share</span>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="shareOpen" 
                                                     x-transition.opacity.duration.200ms
                                                     class="absolute left-0 top-full mt-2 w-44 bg-surface-bg border border-surface-border rounded-lg kanban-shadow py-2 z-20"
                                                     style="display: none;"
                                                >
                                                    <button type="button"
                                                       @click.prevent="window.open('https://api.whatsapp.com/send?text={{ urlencode('Cek lowongan ' . $job->title . ' di CareerRC3ID: ' . route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;"
                                                       class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-surface-variant transition-colors text-body-sm text-on-surface">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WA" class="w-4 h-4"> WhatsApp
                                                    </button>
                                                    <button type="button"
                                                       @click.prevent="window.open('https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;"
                                                       class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-surface-variant transition-colors text-body-sm text-on-surface">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/LinkedIn_logo_initials.png" alt="LinkedIn" class="w-4 h-4"> LinkedIn
                                                    </button>
                                                    <button type="button"
                                                       @click.prevent="
                                                            navigator.clipboard.writeText('Cek lowongan {{ $job->title }} di CareerRC3ID: {{ route('jobs.apply', $job) }}');
                                                            $dispatch('notify', { message: 'Teks disalin! Buka IG untuk membagikan.' });
                                                            window.open('https://instagram.com', '_blank'); 
                                                            shareOpen = false;
                                                       "
                                                       class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-surface-variant transition-colors text-body-sm text-on-surface">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram" class="w-4 h-4"> Instagram
                                                    </button>
                                                    <button type="button"
                                                       @click.prevent="window.open('https://twitter.com/intent/tweet?text={{ urlencode('Cek lowongan ' . $job->title . ' di CareerRC3ID!') }}&url={{ urlencode(route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;"
                                                       class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-surface-variant transition-colors text-body-sm text-on-surface">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/c/ce/X_logo_2023.svg" alt="X" class="w-4 h-4"> X / Twitter
                                                    </button>
                                                    <div class="h-px bg-surface-border my-1"></div>
                                                    <button type="button" 
                                                       @click.prevent="
                                                            navigator.clipboard.writeText('{{ route('jobs.apply', $job) }}');
                                                            $dispatch('notify', { message: 'Link lowongan disalin!' });
                                                            shareOpen = false;
                                                       "
                                                       class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-surface-variant transition-colors text-body-sm text-on-surface">
                                                       <span class="material-symbols-outlined text-[16px]">link</span> Copy Link
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @if($isExpired)
                                            <span class="bg-surface-variant text-secondary border border-surface-border text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter">Ditutup</span>
                                        @else
                                            <span class="bg-success/10 text-success text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter">Active</span>
                                        @endif
                                    </div>
                                    <h3 class="font-headline-md text-headline-md mb-2 group-hover:text-primary transition-colors">{{ $job->title }}</h3>
                                    <div class="flex flex-wrap gap-2 mb-stack-md">
                                        <span class="inline-flex items-center text-body-sm text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[16px] mr-1">location_on</span> {{ $job->location }}
                                        </span>
                                        <span class="inline-flex items-center text-body-sm text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[16px] mr-1">schedule</span> {{ $job->work_type }}
                                        </span>
                                    </div>
                                    <p class="font-body-md text-body-md text-on-surface-variant mb-stack-lg line-clamp-2">
                                        {{ strip_tags($job->description) ?: 'Join our team in the ' . $job->department . ' department.' }}
                                    </p>
                                    <div class="flex items-center justify-between pt-stack-md border-t border-surface-border">
                                        <span class="font-label-md text-label-md text-on-surface-variant">{{ $job->department }}</span>
                                        @if($isExpired)
                                            <span class="text-error font-label-md text-label-md flex items-center">
                                                Telah ditutup pada {{ \Carbon\Carbon::parse($job->deadline_date)->translatedFormat('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-primary font-label-md text-label-md group-hover:translate-x-1 transition-transform flex items-center">
                                                Apply <span class="material-symbols-outlined text-[16px] ml-1">chevron_right</span>
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 bg-surface-bg border border-surface-border rounded-xl border-dashed">
                        <span class="material-symbols-outlined text-[48px] mb-2 text-secondary opacity-50" data-icon="inbox">inbox</span>
                        <p class="font-body-lg text-secondary">No open positions at the moment. Check back later!</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-12">
                {{ $jobsPaginator->links() }}
            </div>
        </div>
    </section>
</div>
