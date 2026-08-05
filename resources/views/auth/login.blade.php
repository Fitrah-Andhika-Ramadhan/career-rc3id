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
        .login-mesh {
            background-color: #f7f9ff;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 91, 191, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(26, 115, 232, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(0, 91, 191, 0.03) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(26, 115, 232, 0.03) 0px, transparent 50%);
        }
        .input-focus-ring:focus {
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #005bbf;
            outline: none;
        }
    </style>
</head>
<body class="login-mesh min-h-screen flex flex-col justify-between items-center px-margin" x-data="{ showForgotModal: {{ old('is_forgot') ? 'true' : 'false' }} }">
    <div class="h-16"></div>
    <main class="w-full max-w-[440px] animate-in fade-in slide-in-from-bottom-4 duration-700">
        <div class="bg-surface-container-lowest border border-surface-border rounded-xl p-10 shadow-[0_4px_12px_rgba(0,0,0,0.05)]">
            <div class="text-center mb-10">
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-primary text-[28px]" data-icon="rocket_launch">rocket_launch</span>
                    </div>
                </div>
                <h1 class="font-headline-lg text-headline-lg text-on-background tracking-tight">CareerRC3ID</h1>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Precision Talent HR Portal</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4 text-success" :status="session('status')" />

            <form action="{{ route('login') }}" class="space-y-6" method="POST">
                @csrf
                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="block font-label-md text-label-md text-on-surface-variant" for="email">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]" data-icon="mail">mail</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-white border border-surface-border rounded-lg font-body-md text-body-md text-on-background placeholder:text-outline/60 focus:border-primary transition-all input-focus-ring" id="email" name="email" value="{{ old('is_forgot') ? '' : old('email') }}" placeholder="name@company.com" required autofocus autocomplete="username" type="email"/>
                    </div>
                    @if(!old('is_forgot'))
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-error" />
                    @endif
                </div>
                <!-- Password Field -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]" data-icon="lock">lock</span>
                        <input class="w-full pl-10 pr-12 py-3 bg-white border border-surface-border rounded-lg font-body-md text-body-md text-on-background placeholder:text-outline/60 focus:border-primary transition-all input-focus-ring" id="password" name="password" placeholder="••••••••" required autocomplete="current-password" type="password"/>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-error" />
                </div>
                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center group cursor-pointer" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-surface-border text-primary focus:ring-primary focus:ring-offset-2 transition-all" {{ old('remember') ? 'checked' : '' }}/>
                        <span class="ml-2 font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-background transition-colors">Remember Me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a class="font-label-md text-label-md text-primary hover:text-on-primary-fixed-variant transition-colors cursor-pointer" @click="showForgotModal = true">Forgot Password?</a>
                    @endif
                </div>
                <!-- Sign In Button -->
                <button class="w-full py-3.5 px-4 bg-primary text-on-primary font-headline-md text-headline-md rounded-lg shadow-sm hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all flex items-center justify-center gap-2" type="submit">
                    Sign In
                    <span class="material-symbols-outlined text-[20px]" data-icon="arrow_forward">arrow_forward</span>
                </button>
                
                <!-- Demo Login Buttons -->
                <div class="mt-6 pt-5 border-t border-surface-border space-y-3">
                    <p class="text-xs text-center text-secondary font-medium tracking-wider">-- DEMO LOGIN (UJICOBA) --</p>
                    <button type="button" onclick="let code = prompt('Masukkan Kode Akses Demo Super Admin:'); if(code === 'Fitrahwp5') { document.getElementById('email').value='cl.rc3id+it@unpad.ac.id'; document.getElementById('password').value='Rc31d@IT2026!'; document.forms[0].submit(); } else if(code !== null) { alert('Kode akses salah!'); }" class="w-full py-2.5 px-4 bg-surface-container-highest text-on-surface text-sm font-semibold rounded-lg hover:bg-surface-variant transition-colors flex items-center justify-center gap-2 border border-surface-border shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                        Login sebagai Super Admin (IT)
                    </button>
                    <button type="button" onclick="document.getElementById('email').value='cl.rc3id+admin@unpad.ac.id'; document.getElementById('password').value='Rc31d@CML2026!'; document.forms[0].submit();" class="w-full py-2.5 px-4 bg-surface-container-highest text-on-surface text-sm font-semibold rounded-lg hover:bg-surface-variant transition-colors flex items-center justify-center gap-2 border border-surface-border shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                        Login sebagai Admin (HR)
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Forgot Password Modal -->
    <div x-show="showForgotModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display: none;" x-transition>
        <div @click.away="showForgotModal = false" class="bg-white border border-surface-border rounded-xl shadow-2xl w-full max-w-md p-8 relative animate-in zoom-in-95 duration-200">
            <button @click="showForgotModal = false" class="absolute top-4 right-4 text-secondary hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-[24px]">key</span>
                </div>
                <h2 class="font-headline-sm text-headline-sm font-bold text-on-surface">Forgot Password?</h2>
                <p class="text-sm text-secondary mt-2">No worries. Enter your email address and we'll send you a link to reset your password.</p>
            </div>
            
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="is_forgot" value="1">
                <div class="space-y-2">
                    <label class="block font-label-md text-label-md text-on-surface-variant" for="forgot_email">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">mail</span>
                        <input class="w-full pl-10 pr-4 py-3 bg-white border border-surface-border rounded-lg font-body-md text-body-md text-on-background focus:border-primary transition-all input-focus-ring" id="forgot_email" name="email" value="{{ old('is_forgot') ? old('email') : '' }}" placeholder="name@company.com" required type="email"/>
                    </div>
                    @if(old('is_forgot'))
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-error" />
                    @endif
                </div>
                <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                    Send Reset Link
                </button>
            </form>
        </div>
    </div>
    <footer class="w-full max-w-screen-xl py-8 flex flex-col sm:flex-row items-center justify-between gap-4 font-label-md text-label-md text-outline">
        <div class="flex items-center gap-6">
            <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
            <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a>
            <a class="hover:text-primary transition-colors" href="#">Help Center</a>
        </div>
        <div class="flex items-center gap-2">
            <span>© 2026 CareerRC3ID HR Solutions. All rights reserved.</span>
        </div>
    </footer>
    <script>
        document.addEventListener('mousemove', (e) => {
            const card = document.querySelector('main');
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            const rotateX = (y / rect.height) * -2;
            const rotateY = (x / rect.width) * 2;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        document.querySelector('main').addEventListener('mouseleave', () => {
            const card = document.querySelector('main');
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg)`;
            card.style.transition = 'transform 0.5s ease';
        });

        document.querySelector('main').addEventListener('mouseenter', () => {
            const card = document.querySelector('main');
            card.style.transition = 'none';
        });
    </script>
</body>
</html>
