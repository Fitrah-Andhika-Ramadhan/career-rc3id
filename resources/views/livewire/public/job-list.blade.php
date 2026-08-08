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
    public $searchDepartment = 'All Departments / Projects';

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

        if ($this->searchDepartment !== 'All Departments / Projects') {
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
        $opacity = env('HERO_OVERLAY_OPACITY', '0.85');
        $opacityGradient = min(1, floatval($opacity) + 0.1);
    @endphp
    <section class="relative py-28 lg:py-36 overflow-hidden bg-black" style="background-image: linear-gradient(rgba(0,0,0, {{ $opacity }}), rgba(0,0,0, {{ $opacityGradient }})), url('{{ asset('hero_background.png') }}'); background-size: cover; background-position: center;">
        <!-- Floating Ambient Shapes -->
        <div class="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-10 right-10 w-80 h-80 bg-black/10 rounded-full blur-3xl animate-float-delayed"></div>
        
        <div class="max-w-container-max mx-auto px-margin relative z-10 flex flex-col items-center">
            
            <!-- Floating Badges -->
            <div class="flex gap-4 mb-6 animate-fade-in-up stagger-1">
                <span class="bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">public</span> {{ __('Global Team') }}
                </span>
                <span class="bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">rocket_launch</span> {{ __('Innovators') }}
                </span>
            </div>

            <div class="w-full text-center flex flex-col items-center">
                <h1 class="font-headline-xl text-5xl lg:text-7xl mb-stack-md text-white font-extrabold tracking-tight animate-fade-in-up stagger-2 drop-shadow-sm">
                    {{ __(config('app.hero_title', 'Find Your Next Career')) }}
                </h1>
                <p class="font-body-lg text-lg lg:text-xl text-white/90 mb-12 max-w-3xl animate-fade-in-up stagger-3 font-medium">
                    {{ __(config('app.hero_subtitle', 'Join a global team of innovators, engineers, and creatives. We are building the future of precision technology and we need your talent to help us lead the way.')) }}
                </p>
                
                <!-- Modern Search/Filter Bar -->
                <div class="bg-white/80 backdrop-blur-xl p-3 md:p-4 rounded-[2rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] border border-white/50 flex flex-col md:flex-row gap-3 max-w-4xl w-full mx-auto transform transition-all focus-within:scale-[1.02] focus-within:bg-white/95 focus-within:shadow-[0_25px_50px_-12px_rgba(var(--color-primary-rgb),0.25)] duration-500 animate-fade-in-up stagger-4">
                    <div class="flex-1 flex items-center px-4 md:border-r border-surface-border/50 transition-colors">
                        <span class="material-symbols-outlined text-primary mr-3 text-[28px]">search</span>
                        <input 
                            wire:model.live.debounce.300ms="searchQuery" 
                            wire:keydown.enter="searchJobs"
                            onkeydown="if(event.key === 'Enter') document.getElementById('job-listings').scrollIntoView({ behavior: 'smooth' })"
                            x-on:input.debounce.800ms="if($event.target.value.trim() !== '') document.getElementById('job-listings').scrollIntoView({ behavior: 'smooth' })"
                            class="w-full border-none focus:ring-0 text-body-lg font-body-lg bg-transparent text-on-surface placeholder:text-on-surface-variant/70" 
                            placeholder="Job title or keywords..." 
                            type="text"/>
                    </div>
                    
                    <button onclick="document.getElementById('job-listings').scrollIntoView({ behavior: 'smooth' })" wire:click="searchJobs" class="bg-primary text-white px-10 py-4 rounded-full font-label-lg text-label-lg hover:bg-primary/90 transition-all shadow-lg hover:shadow-primary/30 hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-2">
                        <span>{{ __('Search Jobs') }}</span>
                        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                    </button>
                </div>

                <!-- Dynamic Category Pills -->
                <div class="mt-8 flex flex-wrap justify-center gap-3 animate-fade-in-up stagger-5 max-w-4xl mx-auto">
                    <button onclick="document.getElementById('job-listings').scrollIntoView({ behavior: 'smooth' })" wire:click="$set('searchDepartment', 'All Departments / Projects'); searchJobs()" 
                            class="px-5 py-2 rounded-full text-sm font-semibold transition-all backdrop-blur-md border shadow-sm hover:-translate-y-0.5 {{ $searchDepartment === 'All Departments / Projects' ? 'bg-white text-primary border-white shadow-primary/20' : 'bg-white/10 text-white border-white/30 hover:bg-white/20' }}">
                        {{ __('All Departments / Projects') }}
                    </button>
                    @foreach($departments as $dept)
                        <button onclick="document.getElementById('job-listings').scrollIntoView({ behavior: 'smooth' })" wire:click="$set('searchDepartment', '{{ $dept }}'); searchJobs()" 
                                class="px-5 py-2 rounded-full text-sm font-semibold transition-all backdrop-blur-md border shadow-sm hover:-translate-y-0.5 {{ $searchDepartment === $dept ? 'bg-white text-primary border-white shadow-primary/20' : 'bg-white/10 text-white border-white/30 hover:bg-white/20' }}">
                            {{ $dept }}
                        </button>
                    @endforeach
                </div>

            </div>
        </div>
    </section>

    <!-- Featured Jobs -->
    <section id="job-listings" class="py-24 bg-surface">
        <div class="max-w-container-max mx-auto px-margin">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-stack-sm">{{ __('Featured Opportunities') }}</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Hand-picked roles for exceptional talent like you.') }}</p>
                </div>
            </div>
            
            <div class="space-y-12">
                @forelse($groupedJobs as $date => $jobs)
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <h3 class="font-headline-sm text-headline-sm text-primary font-semibold bg-primary/10 px-4 py-1.5 rounded-full inline-block border border-primary/20">{{ $date }}</h3>
                            <div class="h-px bg-surface-border flex-1"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-gutter">
                            @foreach($jobs as $job)
                                @php
                                    $isExpired = $job->deadline_date && now()->startOfDay()->gt($job->deadline_date);
                                @endphp
                                <!-- Alternative Job Card Design 4 (Dark Mode / Neon Glow Style) -->
                                <a href="{{ $isExpired ? 'javascript:void(0)' : route('jobs.apply', $job) }}" 
                                   x-data="{ shown: false }" 
                                   x-intersect.once="shown = true"
                                   :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                                   class="group cursor-pointer block relative overflow-hidden transition-all duration-500 rounded-3xl bg-[#0a0a0a] p-6 lg:p-8 {{ $isExpired ? 'opacity-70 cursor-not-allowed border border-white/10' : 'hover:-translate-y-2 hover:shadow-[0_10px_40px_rgba(var(--color-primary-rgb),0.3)] border border-white/10 hover:border-primary/50' }}">
                                    
                                    <!-- Neon Hover Effect Background -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                                    
                                    <div class="relative z-10 flex flex-col h-full">
                                        <div class="flex justify-between items-start mb-6">
                                            <!-- Dark Icon Container -->
                                            <div class="bg-white/5 border border-white/10 p-4 rounded-2xl group-hover:bg-primary/20 group-hover:border-primary/50 transition-all duration-300">
                                                <span class="material-symbols-outlined text-white group-hover:text-primary transition-colors text-[28px]">rocket_launch</span>
                                            </div>
                                            
                                            <!-- Share Menu -->
                                            <div class="relative" x-data="{ shareOpen: false }" @click.outside="shareOpen = false">
                                                <button type="button" @click.prevent="shareOpen = !shareOpen" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 text-white/70 hover:text-white hover:bg-white/20 transition-all flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[18px]">share</span>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="shareOpen" class="absolute right-0 top-full mt-2 w-48 bg-[#1a1a1a] border border-white/10 rounded-xl shadow-2xl py-2 z-20" style="display: none;">
                                                    <button type="button" @click.prevent="window.open('https://api.whatsapp.com/send?text={{ urlencode('Cek lowongan ' . $job->title . ' di CareerRC3ID: ' . route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-body-sm text-white/90">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WA" class="w-4 h-4"> WhatsApp
                                                    </button>
                                                    <button type="button" @click.prevent="window.open('https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-body-sm text-white/90">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/LinkedIn_logo_initials.png" alt="LinkedIn" class="w-4 h-4"> LinkedIn
                                                    </button>
                                                    <button type="button" @click.prevent="navigator.clipboard.writeText('Cek lowongan {{ $job->title }} di CareerRC3ID: {{ route('jobs.apply', $job) }}'); $dispatch('notify', { message: 'Teks disalin! Buka IG untuk membagikan.' }); window.open('https://instagram.com', '_blank'); shareOpen = false;" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-body-sm text-white/90">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram" class="w-4 h-4"> Instagram
                                                    </button>
                                                    <button type="button" @click.prevent="window.open('https://twitter.com/intent/tweet?text={{ urlencode('Cek lowongan ' . $job->title . ' di CareerRC3ID!') }}&url={{ urlencode(route('jobs.apply', $job)) }}', '_blank'); shareOpen = false;" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-body-sm text-white/90">
                                                       <img src="https://upload.wikimedia.org/wikipedia/commons/c/ce/X_logo_2023.svg" alt="X" class="w-4 h-4"> X / Twitter
                                                    </button>
                                                    <div class="h-px bg-white/10 my-1"></div>
                                                    <button type="button" @click.prevent="navigator.clipboard.writeText('{{ route('jobs.apply', $job) }}'); $dispatch('notify', { message: 'Link lowongan disalin!' }); shareOpen = false;" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-body-sm text-white/90">
                                                       <span class="material-symbols-outlined text-[16px]">link</span> Copy Link
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Badges -->
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            @if($isExpired)
                                                <span class="bg-white/5 border border-white/10 text-white/50 text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-wider">{{ __('Ditutup') }}</span>
                                            @else
                                                <span class="bg-primary/20 border border-primary/30 text-primary-light text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-wider flex items-center gap-1.5" style="color: #60a5fa;">
                                                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse shadow-[0_0_8px_#60a5fa]"></span>
                                                    {{ __('Active') }}
                                                </span>
                                            @endif
                                            <span class="bg-white/5 border border-white/10 text-white/70 text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-wider">
                                                {{ $job->department }}
                                            </span>
                                        </div>
                                        
                                        <!-- Title -->
                                        <h3 class="font-headline-md text-xl lg:text-[24px] leading-tight mb-4 font-bold text-white group-hover:text-primary transition-colors flex-1">{{ $job->title }}</h3>
                                        
                                        <!-- Details -->
                                        <div class="flex flex-wrap gap-x-4 gap-y-2 mb-6">
                                            <span class="inline-flex items-center text-[12px] font-medium text-white/60">
                                                <span class="material-symbols-outlined text-[16px] mr-1 text-white/40">location_on</span> {{ $job->location }}
                                            </span>
                                            <span class="inline-flex items-center text-[12px] font-medium text-white/60">
                                                <span class="material-symbols-outlined text-[16px] mr-1 text-white/40">schedule</span> {{ $job->work_type }}
                                            </span>
                                        </div>
                                        
                                        <!-- Description -->
                                        <p class="font-body-md text-sm text-white/50 mb-8 line-clamp-2 leading-relaxed">
                                            {{ strip_tags($job->description) ?: 'Join our team in the ' . $job->department . ' department.' }}
                                        </p>
                                        
                                        <!-- Footer / Action Area -->
                                        <div class="pt-5 mt-auto border-t border-white/10 flex items-center justify-between">
                                            @if($isExpired)
                                                <span class="text-error font-semibold text-[12px]">
                                                    {{ __('Tutup pada') }} {{ \Carbon\Carbon::parse($job->deadline_date)->translatedFormat('d M Y') }}
                                                </span>
                                            @else
                                                <span class="text-white/40 text-[12px] group-hover:text-white/70 transition-colors">
                                                    Gabung bersama RC3ID
                                                </span>
                                                <div class="flex items-center gap-2 text-primary font-bold text-sm group-hover:translate-x-2 transition-transform duration-300">
                                                    {{ __('Lamar') }}
                                                    <span class="material-symbols-outlined text-[20px]">arrow_right_alt</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 bg-surface-bg border border-surface-border rounded-xl border-dashed">
                        <span class="material-symbols-outlined text-[48px] mb-2 text-secondary opacity-50" data-icon="inbox">inbox</span>
                        <p class="font-body-lg text-secondary">{{ __('No open positions at the moment. Check back later!') }}</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-12">
                {{ $jobsPaginator->links() }}
            </div>
        </div>
    </section>
</div>
