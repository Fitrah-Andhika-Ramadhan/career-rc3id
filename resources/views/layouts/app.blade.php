<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CareerRC3ID') }} | HR Portal</title>
    
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
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
<body class="bg-surface font-body-md text-on-surface flex min-h-screen">
    <!-- Side Navigation Bar -->
    <aside class="flex flex-col h-full sticky top-0 h-screen w-64 border-r border-surface-border dark:border-outline-variant bg-surface-container-lowest dark:bg-on-background z-50">
        <div class="px-margin py-stack-lg">
            <span class="font-headline-md text-headline-md font-bold text-primary dark:text-primary-fixed-dim">CareerRC3ID</span>
            <p class="font-label-md text-label-md text-secondary-fixed-variant">HR Portal</p>
        </div>
        <nav class="flex-1 px-stack-sm space-y-1">
            <a class="{{ request()->routeIs('dashboard') ? 'bg-primary-fixed dark:bg-on-primary-fixed-variant text-on-primary-fixed dark:text-primary-fixed font-semibold border-r-4 border-primary' : 'text-secondary dark:text-secondary-fixed-dim hover:bg-surface-container dark:hover:bg-surface-variant' }} flex items-center gap-stack-md px-margin py-stack-md transition-colors duration-200" href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
                <span class="font-body-md text-body-md">Dashboard</span>
            </a>
            <!-- Jobs -->
            <a class="text-secondary dark:text-secondary-fixed-dim hover:bg-surface-container dark:hover:bg-surface-variant flex items-center gap-stack-md px-margin py-stack-md transition-colors duration-200" href="#">
                <span class="material-symbols-outlined" data-icon="work">work</span>
                <span class="font-body-md text-body-md">Jobs</span>
            </a>
            <!-- Candidates -->
            <a class="text-secondary dark:text-secondary-fixed-dim hover:bg-surface-container dark:hover:bg-surface-variant flex items-center gap-stack-md px-margin py-stack-md transition-colors duration-200" href="#">
                <span class="material-symbols-outlined" data-icon="group">group</span>
                <span class="font-body-md text-body-md">Candidates</span>
            </a>
        </nav>
        <div class="p-margin">
            <button class="w-full py-stack-md px-margin bg-primary text-on-primary rounded-xl font-semibold flex items-center justify-center gap-stack-sm hover:opacity-90 active:scale-95 transition-all">
                <span class="material-symbols-outlined" data-icon="add">add</span>
                Post New Job
            </button>
        </div>
        <div class="mt-auto border-t border-surface-border p-stack-sm">
            <a class="flex items-center gap-stack-md px-margin py-stack-md text-secondary hover:bg-surface-container rounded-lg" href="{{ route('profile.edit') }}">
                <span class="material-symbols-outlined" data-icon="settings">settings</span>
                <span class="font-body-md text-body-md">Settings</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-stack-md px-margin py-stack-md text-error hover:bg-error-container rounded-lg transition-colors">
                    <span class="material-symbols-outlined" data-icon="logout">logout</span>
                    <span class="font-body-md text-body-md">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Canvas -->
    <main class="flex-1 flex flex-col bg-surface min-w-0">
        <!-- Top Navigation Bar -->
        <header class="flex justify-between items-center px-margin h-16 sticky top-0 z-40 bg-surface-bg dark:bg-on-background border-b border-surface-border">
            <div class="flex items-center flex-1 max-w-xl">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-surface-border rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Search jobs, candidates, or teams..." type="text"/>
                </div>
            </div>
            <div class="flex items-center gap-stack-md ml-margin">
                <a href="{{ route('home') }}" target="_blank" title="Lihat Website" class="p-2 text-secondary hover:bg-surface-container hover:text-primary rounded-full relative transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined" data-icon="public">public</span>
                </a>
                <button class="p-2 text-secondary hover:bg-surface-container rounded-full relative transition-colors">
                    <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
                </button>
                <div class="h-8 w-px bg-surface-border mx-2"></div>
                <div class="flex items-center gap-stack-sm cursor-pointer hover:bg-surface-container p-1 rounded-full pr-3 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                    <span class="font-label-md text-label-md hidden sm:block">{{ Auth::user()->name ?? 'Admin' }}</span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
