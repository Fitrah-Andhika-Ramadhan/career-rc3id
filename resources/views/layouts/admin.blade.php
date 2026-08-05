<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ config('app.name', 'TalentStream') }} | Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet"/>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Dynamic Favicon --}}
    @php
        $faviconPath = '';
        foreach (['favicon.svg', 'favicon.ico', 'favicon.png', 'favicon.jpg', 'favicon.jpeg', 'favicon.webp'] as $f) {
            if (file_exists(public_path($f))) {
                $faviconPath = $f;
                break;
            }
        }
        $mime = match(pathinfo($faviconPath, PATHINFO_EXTENSION)) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/x-icon'
        };
    @endphp
    @if($faviconPath)
        <link rel="icon" type="{{ $mime }}" href="{{ asset($faviconPath) }}?v={{ filemtime(public_path($faviconPath)) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    @php
        $primaryHex = env('PRIMARY_COLOR', '#005bbf');
        $primaryHex = ltrim($primaryHex, '#');
        $r = hexdec(substr($primaryHex, 0, 2));
        $g = hexdec(substr($primaryHex, 2, 2));
        $b = hexdec(substr($primaryHex, 4, 2));
    @endphp
    <style>
        :root {
            --color-primary: #{{ $primaryHex }};
            --color-primary-rgb: {{ $r }} {{ $g }} {{ $b }};
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #DADCE0;
        }
    </style>
    @livewireStyles
</head>
<body class="bg-surface font-body-md text-on-surface flex min-h-screen" x-data="{ mobileMenuOpen: false, showGuidelineModal: false }">

    <!-- Mobile Sidebar Overlay -->
    <div x-show="mobileMenuOpen" 
         x-transition.opacity 
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-black/50 z-40 md:hidden" style="display: none;"></div>

    <!-- Side Navigation Bar -->
    <aside class="group/aside flex flex-col h-full fixed md:sticky top-0 h-screen w-64 md:w-20 md:hover:w-64 border-r border-surface-border dark:border-outline-variant bg-surface-container-lowest dark:bg-on-background z-50 transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap -translate-x-full md:translate-x-0"
           :class="mobileMenuOpen ? 'translate-x-0 shadow-2xl' : ''">
        <div class="py-6 flex items-center px-6">
            @php
                $sidebarLogo = '';
                foreach (['logo.svg','logo.png','logo.jpg','logo.jpeg','logo.webp'] as $f) {
                    if (file_exists(public_path($f))) { $sidebarLogo = $f; break; }
                }
            @endphp
            @if($sidebarLogo)
                <img src="{{ asset($sidebarLogo) }}?v={{ filemtime(public_path($sidebarLogo)) }}"
                     alt="Logo" class="h-10 w-auto max-w-none object-left object-contain flex-shrink-0 transition-all duration-300">
            @else
                <div class="w-10 h-10 rounded bg-primary flex items-center justify-center flex-shrink-0 transition-all duration-300">
                    <span class="material-symbols-outlined text-white text-[24px]">apartment</span>
                </div>
            @endif
        </div>
        <nav class="flex-1 px-3 space-y-1 mt-4" x-data>
            @can('access dashboard')
            <a class="{{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container-lowest hover:text-on-surface' }} flex items-center px-3 py-3 rounded-xl transition-all duration-200 group" href="{{ route('dashboard') }}" wire:navigate>
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-secondary group-hover:text-primary' }}" data-icon="dashboard">dashboard</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Dashboard</span>
            </a>
            @else
            <button type="button" @click="$dispatch('open-permission-modal', { permissionName: 'access dashboard', permissionLabel: 'Dashboard' })" class="w-full text-left flex items-center px-3 py-3 rounded-xl transition-all duration-200 group opacity-90 text-secondary hover:bg-surface-container-lowest hover:text-on-surface">
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center text-secondary group-hover:text-primary">lock</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Dashboard</span>
            </button>
            @endcan
            @can('access jobs')
            <a class="{{ request()->routeIs('admin.jobs*') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container-lowest hover:text-on-surface' }} flex items-center px-3 py-3 rounded-xl transition-all duration-200 group mt-1" href="{{ route('admin.jobs.index') ?? '#' }}" wire:navigate>
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center {{ request()->routeIs('admin.jobs*') ? 'text-primary' : 'text-secondary group-hover:text-primary' }}" data-icon="work">work</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Jobs</span>
            </a>
            @else
            <button type="button" @click="$dispatch('open-permission-modal', { permissionName: 'access jobs', permissionLabel: 'Jobs Management' })" class="w-full text-left flex items-center px-3 py-3 rounded-xl transition-all duration-200 group mt-1 opacity-90 text-secondary hover:bg-surface-container-lowest hover:text-on-surface">
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center text-secondary group-hover:text-primary">lock</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Jobs</span>
            </button>
            @endcan
            @can('access submissions')
            <a class="{{ request()->routeIs('admin.submissions*') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container-lowest hover:text-on-surface' }} flex items-center px-3 py-3 rounded-xl transition-all duration-200 group mt-1" href="{{ route('admin.submissions.index') }}" wire:navigate>
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center {{ request()->routeIs('admin.submissions*') ? 'text-primary' : 'text-secondary group-hover:text-primary' }}" data-icon="group">group</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Submissions</span>
            </a>
            @else
            <button type="button" @click="$dispatch('open-permission-modal', { permissionName: 'access submissions', permissionLabel: 'Submissions' })" class="w-full text-left flex items-center px-3 py-3 rounded-xl transition-all duration-200 group mt-1 opacity-90 text-secondary hover:bg-surface-container-lowest hover:text-on-surface">
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center text-secondary group-hover:text-primary">lock</span>
                <span class="text-sm ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Submissions</span>
            </button>
            @endcan
        </nav>
        <div class="mt-auto border-t border-surface-border p-3"
             x-data="{ openSettings: {{ request()->routeIs('admin.users*') || request()->routeIs('admin.roles*') || request()->routeIs('admin.settings') || request()->routeIs('admin.backup') || request()->routeIs('admin.permission-requests') ? 'true' : 'false' }} }">
             
            <!-- Settings Toggle Button -->
            <button @click="openSettings = !openSettings" class="w-full flex items-center px-3 py-3 text-secondary hover:bg-surface-container rounded-lg transition-colors overflow-hidden">
                <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center" data-icon="settings">settings</span>
                <span class="font-body-md text-body-md font-semibold ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Settings</span>
                <span class="material-symbols-outlined ml-auto transition-transform duration-200 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100" :class="openSettings ? 'rotate-180' : ''">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="openSettings" x-collapse class="pl-2 py-1 space-y-1 overflow-hidden opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">
                @can('access settings')
                <a class="flex items-center px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container' }}" href="{{ route('admin.settings') }}">
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center">tune</span>
                    <span class="ml-3">System Settings</span>
                </a>
                @else
                <button type="button" @click="$dispatch('open-permission-modal', { permissionName: 'access settings', permissionLabel: 'System Settings' })" class="w-full text-left flex items-center px-3 py-2 text-sm rounded-lg text-secondary hover:bg-surface-container opacity-90 transition-colors group">
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center">lock</span>
                    <span class="ml-3">System Settings</span>
                </button>
                @endcan

                @if(auth()->user()->hasRole('Super Admin'))
                @php
                    $pendingRequests = \App\Models\PermissionRequest::where('status', 'pending')->count();
                @endphp
                <a class="{{ request()->routeIs('admin.permission-requests') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container-lowest hover:text-on-surface' }} flex items-center justify-between px-3 py-2 rounded-lg transition-all duration-200 group" href="{{ route('admin.permission-requests') }}" wire:navigate>
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center {{ request()->routeIs('admin.permission-requests') ? 'text-primary' : 'text-secondary group-hover:text-primary' }}">key</span>
                        <span class="text-sm ml-3">Permission Requests</span>
                    </div>
                    @if($pendingRequests > 0)
                        <span class="bg-error text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $pendingRequests }}</span>
                    @endif
                </a>
                @else
                <a class="{{ request()->routeIs('admin.permission-requests') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container-lowest hover:text-on-surface' }} flex items-center px-3 py-2 rounded-lg transition-all duration-200 group" href="{{ route('admin.permission-requests') }}" wire:navigate>
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center {{ request()->routeIs('admin.permission-requests') ? 'text-primary' : 'text-secondary group-hover:text-primary' }}">key</span>
                    <span class="text-sm ml-3">My Requests</span>
                </a>
                @endif
                
                @can('access backup')
                <a class="flex items-center px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.backup') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container' }}" href="{{ route('admin.backup') }}">
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center">backup</span>
                    <span class="ml-3">Backup Data</span>
                </a>
                @else
                <button type="button" @click="$dispatch('open-permission-modal', { permissionName: 'access backup', permissionLabel: 'Backup Data' })" class="w-full text-left flex items-center px-3 py-2 text-sm rounded-lg text-secondary hover:bg-surface-container opacity-90 transition-colors group">
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center">lock</span>
                    <span class="ml-3">Backup Data</span>
                </button>
                @endcan

                <a class="flex items-center px-3 py-2 text-sm rounded-lg {{ request()->routeIs('profile.edit') ? 'bg-primary/10 text-primary font-semibold' : 'text-secondary hover:bg-surface-container' }}" href="{{ route('profile.edit') }}">
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0 w-8 text-center">person</span>
                    <span class="ml-3">My Profile</span>
                </a>
            </div>

            <!-- Logout -->
            <div class="mt-2 pt-2 border-t border-surface-border">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-3 py-3 text-error hover:bg-error-container rounded-lg transition-colors overflow-hidden">
                        <span class="material-symbols-outlined text-[24px] flex-shrink-0 w-8 text-center" data-icon="logout">logout</span>
                        <span class="font-body-md text-body-md ml-3 opacity-100 md:opacity-0 md:group-hover/aside:opacity-100 transition-opacity duration-300">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Canvas -->
    <main class="flex-1 flex flex-col bg-surface min-w-0">
        <!-- Top Navigation Bar -->
        <header class="flex justify-between items-center px-margin h-16 sticky top-0 z-40 bg-surface-bg dark:bg-on-background border-b border-surface-border">
            <div class="flex items-center flex-1 max-w-xl gap-4">
                <!-- Mobile Sidebar Toggle -->
                <button @click="mobileMenuOpen = true" class="md:hidden p-2 text-secondary hover:bg-surface-container rounded-full transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <!-- Optional Global Search can go here -->
            </div>
            <div class="flex items-center gap-stack-md ml-margin">
                <button type="button" @click="showGuidelineModal = true" title="Baca Buku Panduan (Manual Book)" class="p-2 text-secondary hover:bg-surface-container hover:text-primary rounded-full relative transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined" data-icon="menu_book">menu_book</span>
                </button>
                <a href="{{ route('home') }}" target="_blank" title="Lihat Website" class="p-2 text-secondary hover:bg-surface-container hover:text-primary rounded-full relative transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined" data-icon="public">public</span>
                </a>
                <livewire:admin.notifications-bell />
                <div class="h-8 w-px bg-surface-border mx-2"></div>
                <div class="flex items-center gap-stack-sm p-1 pr-3">
                    <div class="w-8 h-8 rounded-full overflow-hidden border border-surface-border bg-primary text-on-primary flex items-center justify-center font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <span class="font-label-md text-label-md hidden sm:block">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <section class="p-margin max-w-container-max mx-auto w-full space-y-stack-lg">
            {{ $slot ?? '' }}
            @yield('content')
        </section>
    </main>

    @livewireScripts

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session()->has('message'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: "{!! session('message') !!}",
                    icon: 'success',
                    confirmButtonColor: '#0a56d9'
                });
            @endif

            @if(session()->has('success_backup'))
                Swal.fire({
                    title: 'Proses Dimulai',
                    text: "{!! session('success_backup') !!}",
                    icon: 'info',
                    confirmButtonColor: '#0a56d9'
                });
            @endif

            @if(session()->has('error'))
                Swal.fire({
                    title: 'Oops...',
                    text: "{!! session('error') !!}",
                    icon: 'error',
                    confirmButtonColor: '#d93025'
                });
            @endif

            @if(session()->has('logo_message'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: "{!! session('logo_message') !!}",
                    icon: 'success',
                    confirmButtonColor: '#0a56d9'
                });
            @endif
        });
    </script>
    <!-- SweetAlert2 for beautiful popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Override Livewire's default wire:confirm with SweetAlert2
        document.addEventListener('livewire:init', () => {
            Livewire.directive('confirm', ({ el, directive, component, cleanup }) => {
                let content = directive.expression;

                let onClick = e => {
                    // Prevent Livewire's default action
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    Swal.fire({
                        title: 'Konfirmasi',
                        text: content,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--color-primary, #005bbf)',
                        cancelButtonColor: '#74777F',
                        confirmButtonText: '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">check</span> Ya, Lanjutkan</span>',
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'rounded-xl',
                            title: 'font-headline-sm text-on-surface',
                            htmlContainer: 'font-body-md text-on-surface-variant'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Remove wire:confirm to bypass this interceptor
                            el.removeAttribute('wire:confirm');
                            // Trigger the actual Livewire action
                            el.click();
                        }
                    });
                };

                // Use capture: true to intercept the click before Livewire does
                el.addEventListener('click', onClick, { capture: true });

                cleanup(() => {
                    el.removeEventListener('click', onClick, { capture: true });
                });
            });
        });

        window.confirmAction = function(message, callback, iconType = 'warning') {
            let confirmBtnColor = '#005bbf'; // Primary color
            let confirmText = '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">check</span> Ya, Lanjutkan</span>';
            
            if (iconType === 'error' || iconType === 'warning') {
                confirmBtnColor = '#d93025'; // Error color if dangerous
                if (message.toLowerCase().includes('cabut') || message.toLowerCase().includes('tolak')) {
                    confirmBtnColor = '#d93025';
                }
            } else if (iconType === 'question') {
                confirmBtnColor = '#0f9d58'; // Success color for approve
                confirmText = '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">check_circle</span> Setujui</span>';
            }

            Swal.fire({
                title: 'Konfirmasi',
                text: message,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#74777F',
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'font-headline-sm text-on-surface',
                    htmlContainer: 'font-body-md text-on-surface-variant'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    try {
                        callback();
                    } catch (e) {
                        console.error(e);
                    }
                    setTimeout(() => Swal.close(), 1000);
                }
            });
        }

        window.confirmDelete = function(message, callback) {
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#BA1A1A', // Error color matching our theme
                cancelButtonColor: '#74777F',
                confirmButtonText: '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">delete</span> Yes, delete it!</span>',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'font-headline-sm text-on-surface',
                    htmlContainer: 'font-body-md text-on-surface-variant'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    try {
                        callback();
                    } catch (e) {
                        console.error(e);
                    }
                    setTimeout(() => Swal.close(), 1500);
                }
            });
        }
    </script>
    <!-- Guideline Modal -->
    <div x-show="showGuidelineModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-md" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0">
         
        <div @click.away="showGuidelineModal = false" class="bg-surface-bg rounded-xl shadow-lg w-full max-w-3xl max-h-[90vh] flex flex-col relative overflow-hidden mx-4"
             x-transition:enter="transition ease-out duration-300 transform" 
             x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
             x-transition:leave="transition ease-in duration-200 transform" 
             x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
             x-transition:leave-end="opacity-0 translate-y-8 scale-95">
             
            <div class="px-6 py-5 flex justify-between items-center bg-white/95 backdrop-blur-sm sticky top-0 z-10 shrink-0 border-b border-surface-border shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shadow-inner">
                        <span class="material-symbols-outlined text-primary text-[28px]">menu_book</span>
                    </div>
                    <div>
                        <h2 class="font-headline-md text-[20px] md:text-[22px] font-bold text-on-surface leading-tight tracking-tight">
                            Buku Panduan ATS
                        </h2>
                        <p class="text-[13px] text-secondary font-medium mt-0.5">Dokumentasi Resmi & Prosedur Sistem</p>
                    </div>
                </div>
                <button @click="showGuidelineModal = false" class="text-secondary hover:text-error hover:bg-error/10 transition-all w-10 h-10 rounded-full flex items-center justify-center group focus:outline-none focus:ring-2 focus:ring-error/50">
                    <span class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-300">close</span>
                </button>
            </div>
            
            <div class="p-6 md:p-12 overflow-y-auto flex-1 markdown-body bg-[#fafcff]">
                <div class="max-w-3xl mx-auto">
                    {!! \Illuminate\Support\Str::markdown(file_exists(base_path('Manual_Book_RC3ID_ATS.md')) ? file_get_contents(base_path('Manual_Book_RC3ID_ATS.md')) : 'Dokumen tidak ditemukan.') !!}
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .markdown-body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #334155;
            line-height: 1.8;
        }
        .markdown-body h1 { font-size: 2.25rem; font-weight: 800; margin-bottom: 1.5rem; color: #0f172a; letter-spacing: -0.025em; }
        .markdown-body h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1.25rem; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; letter-spacing: -0.015em; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; color: #1e293b; }
        .markdown-body p { margin-bottom: 1.25rem; font-size: 1.05rem; }
        .markdown-body ul { list-style-type: none; padding-left: 1rem; margin-bottom: 1.5rem; }
        .markdown-body ul li { position: relative; padding-left: 1.25rem; margin-bottom: 0.5rem; font-size: 1.05rem; }
        .markdown-body ul li::before { content: '•'; position: absolute; left: 0; color: var(--color-primary, #3b82f6); font-weight: bold; font-size: 1.2em; top: -0.1em; }
        .markdown-body ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 1.5rem; font-size: 1.05rem; }
        .markdown-body ol li { margin-bottom: 0.5rem; padding-left: 0.25rem; }
        .markdown-body a { color: var(--color-primary, #2563eb); text-decoration: none; font-weight: 500; border-bottom: 1px transparent; transition: border-color 0.2s; }
        .markdown-body a:hover { border-bottom: 1px solid var(--color-primary, #2563eb); }
        .markdown-body hr { margin: 2.5rem 0; border: 0; border-top: 1px dashed #cbd5e1; }
        .markdown-body strong { font-weight: 700; color: #0f172a; }
        .markdown-body blockquote { border-left: 4px solid var(--color-primary, #3b82f6); margin-left: 0; margin-bottom: 1.5rem; font-style: italic; color: #475569; background: #f0f7ff; padding: 1.25rem; border-radius: 0 0.75rem 0.75rem 0; font-size: 1.05rem; }
        .markdown-body pre { background-color: #0f172a; color: #f8fafc; padding: 1.25rem; border-radius: 0.75rem; overflow-x: auto; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1); }
        .markdown-body pre code { background: transparent; padding: 0; color: inherit; font-size: inherit; }
        .markdown-body code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background-color: #f1f5f9; color: #db2777; padding: 0.2em 0.4em; border-radius: 0.375rem; font-size: 0.875em; font-weight: 600; border: 1px solid #e2e8f0; }
    </style>
</body>
</html>
