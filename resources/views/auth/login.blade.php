<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login | CareerRC3ID HR Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#f9fafb]" x-data="{ showForgotModal: {{ old('is_forgot') ? 'true' : 'false' }} }">
    
    <!-- Main Content Area -->
    <div class="flex flex-1">
        <!-- Left Panel: Branding / Visuals -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-[#1c1c1c] items-center justify-center overflow-hidden">
            <!-- Subtle bottom glow effect matching the screenshot -->
            <div class="absolute bottom-[-10%] left-1/2 transform -translate-x-1/2 w-full h-[500px]" style="background: radial-gradient(ellipse at bottom, rgba(160, 150, 110, 0.15) 0%, transparent 60%);"></div>
            
            <div class="relative z-10 p-12 text-white flex flex-col items-center text-center">
                <div class="w-20 h-20 bg-[#2d2d2d] rounded-full flex items-center justify-center mb-10 shadow-lg">
                    <span class="material-symbols-outlined text-[32px] text-[#e63946]" data-icon="rocket_launch">rocket_launch</span>
                </div>
                <h1 class="text-[40px] font-bold tracking-tight mb-4 text-white">Elevate Your Talent</h1>
                <p class="text-[17px] text-[#a0a0a0] max-w-md leading-relaxed">Streamline your hiring process, discover top talent, and build your dream team with Precision Talent HR Portal.</p>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-4 py-12 relative bg-[#f9fafb]">
            <main class="w-full max-w-[420px]">
                <div class="bg-white rounded-xl p-10 sm:p-12 shadow-sm border border-gray-100">
                    <div class="text-center mb-8">
                        <h1 class="font-bold text-[28px] text-gray-900 tracking-tight mb-2">CareerRC3ID</h1>
                        <p class="text-[11px] font-bold tracking-widest text-[#8b4545] uppercase">Precision Talent HR Portal</p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4 text-green-600 text-sm text-center" :status="session('status')" />

                    <form action="{{ route('login') }}" class="space-y-6" method="POST">
                        @csrf
                        <!-- Email Field -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2" for="email">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-[20px]" data-icon="mail">mail</span>
                                <input class="w-full pl-11 pr-4 py-3 bg-[#f3f4f6] border border-gray-200 rounded-md text-sm text-gray-900 focus:bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all outline-none" id="email" name="email" value="{{ old('is_forgot') ? '' : old('email') }}" required autofocus autocomplete="username" type="email"/>
                            </div>
                            @if(!old('is_forgot'))
                                <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-sm" />
                            @endif
                        </div>
                        
                        <!-- Password Field -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2" for="password">Password</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-[20px]" data-icon="lock">lock</span>
                                <input class="w-full pl-11 pr-12 py-3 bg-[#f3f4f6] border border-gray-200 rounded-md text-sm text-gray-900 focus:bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all outline-none" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password" type="password"/>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-sm" />
                        </div>
                        
                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between pt-1 pb-2">
                            <label class="flex items-center group cursor-pointer" for="remember_me">
                                <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#b71c1c] focus:ring-[#b71c1c]" {{ old('remember') ? 'checked' : '' }}/>
                                <span class="ml-2 text-sm text-gray-600 font-medium group-hover:text-gray-900 transition-colors">Remember Me</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a class="text-sm font-bold text-gray-900 hover:text-[#b71c1c] transition-colors cursor-pointer" @click="showForgotModal = true">Forgot Password?</a>
                            @endif
                        </div>
                        
                        <!-- Sign In Button -->
                        <button class="w-full py-3.5 px-4 bg-[#b71c1c] text-white font-bold text-[15px] rounded-md shadow-sm hover:bg-[#9b1818] active:scale-[0.99] transition-all text-center" type="submit">
                            Login
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- Full width Footer -->
    <footer class="w-full bg-[#f9fafb] border-t border-gray-200 py-6 px-8 flex flex-col md:flex-row items-center justify-between text-xs text-gray-500">
        <div class="font-black text-black mb-4 md:mb-0 text-[13px] tracking-tight">CareerRC3ID</div>
        <div class="flex items-center gap-8 mb-4 md:mb-0 font-semibold text-gray-600">
            <a class="hover:text-black transition-colors" href="#">Terms of Service</a>
            <a class="hover:text-black transition-colors" href="#">Privacy Policy</a>
            <a class="hover:text-black transition-colors" href="#">Help Center</a>
        </div>
        <div>© 2026 CareerRC3ID -Fitt Solutions. All rights reserved.</div>
    </footer>

    <!-- Forgot Password Modal -->
    <div x-show="showForgotModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display: none;" x-transition>
        <div @click.away="showForgotModal = false" class="bg-white border border-gray-200 rounded-xl shadow-2xl w-full max-w-md p-8 relative">
            <button @click="showForgotModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-[24px]">key</span>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Forgot Password?</h2>
                <p class="text-sm text-gray-500 mt-2">No worries. Enter your email address and we'll send you a link to reset your password.</p>
            </div>
            
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="is_forgot" value="1">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2" for="forgot_email">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-[20px]">mail</span>
                        <input class="w-full pl-11 pr-4 py-3 bg-[#f3f4f6] border border-gray-200 rounded-md text-sm text-gray-900 focus:bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all outline-none" id="forgot_email" name="email" value="{{ old('is_forgot') ? old('email') : '' }}" required type="email"/>
                    </div>
                    @if(old('is_forgot'))
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-sm" />
                    @endif
                </div>
                <button type="submit" class="w-full py-3.5 bg-[#b71c1c] text-white font-bold text-[15px] rounded-md hover:bg-[#9b1818] transition-colors shadow-sm">
                    Send Reset Link
                </button>
            </form>
        </div>
    </div>
</body>
</html>
