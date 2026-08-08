<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login | CareerRC3ID HR Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f4f5f7;
        }

        .page-wrapper {
            display: flex;
            flex: 1;
            min-height: calc(100vh - 60px);
        }

        /* ─── LEFT BRANDING PANEL ─── */
        .brand-panel {
            display: none;
            width: 50%;
            position: relative;
            overflow: hidden;
            background-color: #000000;
            background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1600&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            align-items: center;
            justify-content: center;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.65) 100%);
            z-index: 0;
        }

        @media (min-width: 1024px) {
            .brand-panel { display: flex; }
        }

        /* Dynamic Minimalist Grid & Graphics */
        .grid-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-image:
                linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translateY(0); }
            100% { transform: translateY(60px); }
        }

        .abstract-circle {
            position: absolute;
            z-index: 1;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.1);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation: pulseCircle 8s infinite alternate ease-in-out;
        }
        .circle-1 { width: 300px; height: 300px; animation-delay: 0s; }
        .circle-2 { width: 500px; height: 500px; border-style: dashed; opacity: 0.6; animation-delay: 2s; animation-direction: reverse; }
        .circle-3 { width: 750px; height: 750px; opacity: 0.3; animation-delay: 4s; }

        @keyframes pulseCircle {
            0% { transform: translate(-50%, -50%) scale(0.95) rotate(0deg); }
            100% { transform: translate(-50%, -50%) scale(1.05) rotate(15deg); }
        }

        .brand-content {
            position: relative;
            z-index: 10;
            padding: 48px;
            text-align: center;
            color: white;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .brand-logo-wrap {
            width: 88px; height: 88px;
            margin: 0 auto 32px;
            background: #111;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            transition: transform 0.3s ease;
        }
        
        .brand-panel:hover .brand-logo-wrap {
            transform: rotate(-10deg) scale(1.1);
        }

        .brand-logo-wrap .material-symbols-outlined {
            font-size: 40px;
            color: #fff;
        }

        .brand-title {
            font-size: 56px;
            font-weight: 900;
            letter-spacing: -2px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #ffffff, #666666, #ffffff);
            background-size: 200% auto;
            color: #ffffff;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shine 5s linear infinite;
        }
        
        @keyframes shine {
            to { background-position: 200% center; }
        }

        .brand-subtitle {
            font-size: 18px;
            color: #d4d4d4;
            max-width: 400px;
            margin: 0 auto 40px;
            line-height: 1.7;
        }

        .brand-pills {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .brand-pill {
            padding: 6px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            font-size: 12px;
            color: #d4d4d4;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand-pill .material-symbols-outlined {
            font-size: 14px;
            color: #fff;
        }

        /* ─── RIGHT FORM PANEL ─── */
        .form-panel {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: #ffffff;
        }

        @media (min-width: 1024px) {
            .form-panel { width: 50%; }
        }

        .form-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            padding: 44px 40px;
            border: 1px solid #eaeaea;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
        }

        .form-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .form-logo {
            width: 52px; height: 52px;
            margin: 0 auto 18px;
            background: #111827;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }

        .form-logo .material-symbols-outlined {
            font-size: 26px;
            color: white;
        }

        .form-title {
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .form-tagline {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #6b7280;
        }

        /* ─── Form Fields ─── */
        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #9ca3af;
            pointer-events: none;
        }

        .field-input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            background: #fafafa;
            border: 1.5px solid #eaeaea;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #111827;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .field-input:focus {
            background: #fff;
            border-color: #111827;
            box-shadow: 0 0 0 3px rgba(17,24,39,0.1);
        }

        .field-input::placeholder {
            color: #a3a3a3;
        }

        /* ─── Remember / Forgot ─── */
        .row-inline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #4b5563;
            font-weight: 500;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #111827;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            text-decoration: underline;
            text-decoration-color: transparent;
            cursor: pointer;
            transition: text-decoration-color 0.2s;
        }

        .forgot-link:hover { text-decoration-color: #111827; }

        /* ─── Submit Button ─── */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #111827;
            color: white;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .btn-login:hover {
            background: #000000;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Error */
        .field-error {
            font-size: 12px;
            color: #000000;
            margin-top: 6px;
            font-weight: 600;
        }

        /* ─── FOOTER ─── */
        .site-footer {
            height: 60px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            font-size: 12px;
            color: #9ca3af;
        }

        .footer-brand {
            font-weight: 900;
            color: #111827;
            font-size: 13px;
            letter-spacing: -0.3px;
        }

        .footer-links {
            display: flex;
            gap: 28px;
        }

        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .footer-links a:hover { color: #111827; }

        .footer-copy { font-weight: 400; }

        @media (max-width: 640px) {
            .footer-links, .footer-brand { display: none; }
            .site-footer { justify-content: center; }
        }

        /* ─── Forgot Modal ─── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            padding: 16px;
        }

        .modal-box {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            padding: 36px;
            position: relative;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px; height: 32px;
            border: none;
            background: #f3f4f6;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            transition: background 0.2s, color 0.2s;
        }

        .modal-close:hover { background: #fee2e2; color: #dc2626; }

        .modal-icon {
            width: 52px; height: 52px;
            background: #fee2e2;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .modal-icon .material-symbols-outlined { font-size: 26px; color: #c0392b; }

        .modal-title {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin-bottom: 6px;
        }

        .modal-desc {
            font-size: 13px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body x-data="{ showForgotModal: {{ old('is_forgot') ? 'true' : 'false' }} }">

    <div class="page-wrapper">

        <!-- ═══ LEFT PANEL ═══ -->
        <div class="brand-panel">
            <div class="grid-overlay"></div>
            <div class="abstract-circle circle-1"></div>
            <div class="abstract-circle circle-2"></div>
            <div class="abstract-circle circle-3"></div>

            <div class="brand-content">
                <h1 class="brand-title">CAREER RC3ID</h1>
                <p class="brand-subtitle">
                    Precision Talent HR Portal.<br>Modern, secure, and streamlined.
                </p>
                <div class="brand-pills">
                    <div class="brand-pill">
                        <span class="material-symbols-outlined">verified_user</span>
                        Secure Access
                    </div>
                    <div class="brand-pill">
                        <span class="material-symbols-outlined">bolt</span>
                        Lightning Fast
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ RIGHT PANEL ═══ -->
        <div class="form-panel">
            <div class="form-card">

                <!-- Header -->
                <div class="form-header">
                    <div class="form-logo">
                        <span class="material-symbols-outlined">rocket_launch</span>
                    </div>
                    <h1 class="form-title">CareerRC3ID</h1>
                    <p class="form-tagline">Precision Talent HR Portal</p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" style="font-size:14px;color:#16a34a;text-align:center;" :status="session('status')" />

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <!-- Email -->
                    <div class="field-group">
                        <label class="field-label" for="email">Email Address</label>
                        <div class="field-wrap">
                            <span class="field-icon material-symbols-outlined">mail</span>
                            <input
                                class="field-input"
                                id="email" name="email" type="email"
                                value="{{ old('is_forgot') ? '' : old('email') }}"
                                placeholder="name@company.com"
                                required autofocus autocomplete="username"
                            />
                        </div>
                        @if(!old('is_forgot'))
                            <x-input-error :messages="$errors->get('email')" class="field-error" />
                        @endif
                    </div>

                    <!-- Password -->
                    <div class="field-group">
                        <label class="field-label" for="password">Password</label>
                        <div class="field-wrap">
                            <span class="field-icon material-symbols-outlined">lock</span>
                            <input
                                class="field-input"
                                id="password" name="password" type="password"
                                placeholder="Enter your password"
                                required autocomplete="current-password"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="field-error" />
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="row-inline">
                        <label class="remember-label" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}/>
                            Remember Me
                        </label>
                        @if (Route::has('password.request'))
                            <a class="forgot-link" @click.prevent="showForgotModal = true" href="#">Forgot Password?</a>
                        @endif
                    </div>

                    <!-- Submit -->
                    <button class="btn-login" type="submit">Sign In</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ FOOTER ═══ -->
    <footer class="site-footer">
        <div class="footer-brand">CareerRC3ID</div>
        <div class="footer-links">
            <a href="#">Terms of Service</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Help Center</a>
        </div>
        <div class="footer-copy">© 2026 CareerRC3ID -Fitt Solutions. All rights reserved.</div>
    </footer>

    <!-- ═══ FORGOT PASSWORD MODAL ═══ -->
    <div class="modal-overlay" x-show="showForgotModal" x-transition style="display:none;">
        <div class="modal-box" @click.away="showForgotModal = false">
            <button class="modal-close" @click="showForgotModal = false">
                <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            </button>

            <div class="modal-icon">
                <span class="material-symbols-outlined">key</span>
            </div>
            <h2 class="modal-title">Forgot Password?</h2>
            <p class="modal-desc">No worries. Enter your email and we'll send you a reset link right away.</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <input type="hidden" name="is_forgot" value="1">

                <div class="field-group">
                    <label class="field-label" for="forgot_email">Email Address</label>
                    <div class="field-wrap">
                        <span class="field-icon material-symbols-outlined">mail</span>
                        <input
                            class="field-input"
                            id="forgot_email" name="email" type="email"
                            value="{{ old('is_forgot') ? old('email') : '' }}"
                            placeholder="name@company.com"
                            required
                        />
                    </div>
                    @if(old('is_forgot'))
                        <x-input-error :messages="$errors->get('email')" class="field-error" />
                    @endif
                </div>

                <button class="btn-login" type="submit">Send Reset Link</button>
            </form>
        </div>
    </div>

</body>
</html>
