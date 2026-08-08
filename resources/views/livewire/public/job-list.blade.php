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
                                <!-- Job Card -->
                                <a href="{{ $isExpired ? 'javascript:void(0)' : route('jobs.apply', $job) }}" 
                                   x-data="{ shown: false }" 
                                   x-intersect.once="shown = true"
                                   :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                                   class="group cursor-pointer block relative overflow-hidden transition-all duration-500 rounded-[1.5rem] bg-white p-6 lg:p-8 {{ $isExpired ? 'opacity-70 cursor-not-allowed' : 'hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(var(--color-primary-rgb),0.15)] shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-surface-border hover:border-primary/20' }}">
                                    
                                    <!-- Animated Background Glow -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>

                                    <!-- Top Border Glow -->
                                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-primary via-info to-primary opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-[length:200%_auto] animate-gradientMove pointer-events-none"></div>
                                    
                                    <div class="flex justify-between items-start mb-6 relative z-10">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-gradient-to-br from-primary/10 to-primary/5 p-4 rounded-[1.2rem] border border-primary/20 group-hover:shadow-lg group-hover:shadow-primary/20 group-hover:scale-110 transition-all duration-300">
                                                <span class="material-symbols-outlined text-primary text-[28px] drop-shadow-sm">work</span>
                                            </div>
                                            <div class="relative" x-data="{ shareOpen: false }" @click.outside="shareOpen = false">
                                                <button 
                                                    type="button"
                                                    @click.prevent="shareOpen = !shareOpen"
                                                    class="p-2.5 rounded-full text-secondary hover:bg-surface-variant hover:text-primary transition-colors hover:shadow-sm"
                                                    title="Bagikan Tautan"
                                                >
                                                    <span class="material-symbols-outlined text-[20px]">share</span>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="shareOpen" 
                                                     x-transition.opacity.duration.200ms
                                                     class="absolute left-0 top-full mt-2 w-48 bg-surface-bg border border-surface-border rounded-xl shadow-xl py-2 z-20"
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
                                            <span class="bg-surface-variant text-secondary border border-surface-border text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest shadow-sm">{{ __('Ditutup') }}</span>
                                        @else
                                            <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-widest shadow-sm flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                                {{ __('Active') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="font-headline-md text-xl lg:text-2xl mb-3 group-hover:text-primary font-bold transition-colors relative z-10">{{ $job->title }}</h3>
                                    
                                    <div class="flex flex-wrap gap-2 mb-4 relative z-10">
                                        <span class="inline-flex items-center text-xs font-semibold text-on-surface-variant bg-surface-container-low px-2.5 py-1 rounded-md border border-surface-border shadow-sm">
                                            <span class="material-symbols-outlined text-[14px] mr-1 text-primary">location_on</span> {{ $job->location }}
                                        </span>
                                        <span class="inline-flex items-center text-xs font-semibold text-on-surface-variant bg-surface-container-low px-2.5 py-1 rounded-md border border-surface-border shadow-sm">
                                            <span class="material-symbols-outlined text-[14px] mr-1 text-primary">schedule</span> {{ $job->work_type }}
                                        </span>
                                    </div>
                                    
                                    <p class="font-body-md text-sm text-secondary mb-8 line-clamp-3 leading-relaxed relative z-10">
                                        {{ strip_tags($job->description) ?: 'Join our team in the ' . $job->department . ' department.' }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between pt-6 border-t border-surface-border/60 relative z-10">
                                        <span class="font-bold text-[11px] text-primary/80 bg-primary/5 border border-primary/10 px-3 py-1.5 rounded-lg uppercase tracking-wider">{{ $job->department }}</span>
                                        
                                        @if($isExpired)
                                            <span class="text-error font-semibold text-xs flex items-center bg-error/5 px-3 py-1.5 rounded-lg border border-error/10">
                                                <span class="material-symbols-outlined text-[14px] mr-1">event_busy</span>
                                                {{ \Carbon\Carbon::parse($job->deadline_date)->translatedFormat('d M Y') }}
                                            </span>
                                        @else
                                            <span class="font-bold text-white bg-primary shadow-md shadow-primary/30 px-5 py-2.5 rounded-xl flex items-center gap-2 group-hover:bg-primary/90 group-hover:-translate-y-0.5 transition-all duration-300">
                                                {{ __('Apply Now') }} 
                                                <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform duration-300">arrow_forward</span>
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
