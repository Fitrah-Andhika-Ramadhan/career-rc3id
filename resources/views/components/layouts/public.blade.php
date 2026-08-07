<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>{{ $title ?? 'CareerRC3ID | Find Your Next Opportunity' }}</title>
    
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9ff;
        }
        .glass-header {
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.9);
        }
        .kanban-shadow {
            box-shadow: 0px 4px 12px rgba(0,0,0,0.05);
        }
        .hero-pattern {
            background-color: #005bbf;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 24px 24px;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
        .step-transition { transition: all 0.3s ease-in-out; }
        .form-card { box-shadow: 0px 4px 12px rgba(0,0,0,0.05); }
        input:focus, textarea:focus { outline: 2px solid #005bbf; outline-offset: 1px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #adc7ff; border-radius: 10px; }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-fade-in-up { animation: fadeInUp 0.8s ease-out forwards; opacity: 0; }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 6s ease-in-out 3s infinite; }
        .bg-animated-gradient {
            background-size: 200% 200%;
            animation: gradientMove 15s ease infinite;
        }
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
        .stagger-5 { animation-delay: 0.5s; }
    </style>
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
        input:focus, textarea:focus { outline: 2px solid var(--color-primary); outline-offset: 1px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(var(--color-primary-rgb) / 0.5); border-radius: 10px; }
        
        /* Restore list styles that Tailwind resets */
        [contenteditable="true"] ul, .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        [contenteditable="true"] ol, .prose ol { list-style-type: decimal; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        [contenteditable="true"] li, .prose li { margin-bottom: 0.25rem; }
    </style>
    @livewireStyles
</head>
<body class="text-on-surface custom-scrollbar">
    <!-- TopNavBar (Shared Component Identity) -->
    <header class="bg-surface-bg sticky top-0 z-50 border-b border-surface-border">
        <div class="flex justify-between items-center px-margin py-stack-md w-full max-w-container-max mx-auto">
            <a href="/" class="flex items-center gap-3 transition-opacity hover:opacity-90">
                @php
                    $logoPath = file_exists(public_path('logo.svg')) ? 'logo.svg' : (file_exists(public_path('logo.png')) ? 'logo.png' : '');
                @endphp
                @if($logoPath)
                    <img src="{{ asset($logoPath) }}?v={{ filemtime(public_path($logoPath)) }}" alt="{{ env('APP_NAME', 'CareerRC3ID') }} Logo" class="h-12 w-auto object-contain">
                @else
                    <span class="font-headline-md text-headline-md text-primary font-bold">{{ env('APP_NAME', 'CareerRC3ID') }}</span>
                @endif
            </a>

            <div class="flex items-center gap-4">
                <!-- Language Switcher -->
                <div class="flex bg-surface-container-low rounded-lg p-1 border border-surface-border">
                    <a href="{{ route('lang.switch', 'id') }}" class="px-2 py-1 rounded text-xs font-bold transition-colors {{ App::getLocale() === 'id' ? 'bg-primary text-white shadow-sm' : 'text-secondary hover:text-on-surface' }}">ID</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 rounded text-xs font-bold transition-colors {{ App::getLocale() === 'en' ? 'bg-primary text-white shadow-sm' : 'text-secondary hover:text-on-surface' }}">EN</a>
                </div>
                
                <a href="{{ url('/magic-login') }}" class="px-4 py-2 bg-primary/10 text-primary rounded-lg font-label-md font-bold hover:bg-primary/20 transition-colors">Demo Login</a>
                <a href="{{ route('login') }}" class="hidden font-label-md text-label-md text-secondary hover:text-primary transition-colors">Admin Login</a>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-surface-container-low border-t border-surface-border">
        <div class="flex flex-col md:flex-row justify-between items-center px-margin py-stack-lg w-full max-w-container-max mx-auto gap-stack-md">
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <div class="font-headline-sm text-headline-sm text-on-surface font-bold mb-1">{{ env('APP_NAME', 'CareerRC3ID') }}</div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">© {{ date('Y') }} CareerRC3ID -Fitt Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
