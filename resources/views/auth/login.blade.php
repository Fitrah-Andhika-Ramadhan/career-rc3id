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
            background: linear-gradient(145deg, #0f0f14 0%, #1a1a2e 40%, #16213e 70%, #0f3460 100%);
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 1024px) {
            .brand-panel { display: flex; }
        }

        /* Animated orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: orb-float 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #c0392b, #922b21);
            top: -80px; left: -80px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 280px; height: 280px;
            background: radial-gradient(circle, #1a5276, #154360);
            bottom: -60px; right: -60px;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 200px; height: 200px;
            background: radial-gradient(circle, #922b21, #7b241c);
            bottom: 30%; left: 15%;
            animation-delay: -2s;
            opacity: 0.25;
        }

        @keyframes orb-float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -20px) scale(1.05); }
            66% { transform: translate(-15px, 15px) scale(0.95); }
        }

        /* Grid pattern overlay */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Floating geometric shapes */
        .geo-shape {
            position: absolute;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            animation: geo-rotate 20s linear infinite;
        }
        .geo-1 { width: 120px; height: 120px; top: 12%; right: 18%; animation-delay: 0s; }
        .geo-2 { width: 70px; height: 70px; top: 60%; left: 10%; animation-delay: -7s; transform: rotate(30deg); }
        .geo-3 { width: 50px; height: 50px; bottom: 18%; right: 25%; animation-delay: -14s; }

        @keyframes geo-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .brand-content {
            position: relative;
            z-index: 10;
            padding: 48px;
            text-align: center;
            color: white;
        }

        .brand-logo-wrap {
            width: 88px; height: 88px;
            margin: 0 auto 32px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .brand-logo-wrap .material-symbols-outlined {
            font-size: 40px;
            color: #e74c3c;
        }

        .brand-title {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.1;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.5);
            max-width: 340px;
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
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 100px;
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand-pill .material-symbols-outlined {
            font-size: 14px;
            color: #e74c3c;
        }

        /* ─── RIGHT FORM PANEL ─── */
        .form-panel {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: #f4f5f7;
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
            box-shadow:
                0 0 0 1px rgba(0,0,0,0.05),
                0 4px 6px -1px rgba(0,0,0,0.05),
                0 20px 40px -10px rgba(0,0,0,0.08);
        }

        .form-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .form-logo {
            width: 52px; height: 52px;
            margin: 0 auto 18px;
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(192,57,43,0.35);
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
            color: #c0392b;
        }

        /* ─── Form Fields ─── */
        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
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
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #111827;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .field-input:focus {
            background: #fff;
            border-color: #c0392b;
            box-shadow: 0 0 0 3px rgba(192,57,43,0.1);
        }

        .field-input::placeholder {
            color: #d1d5db;
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
            color: #6b7280;
            font-weight: 500;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #c0392b;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: #c0392b; }

        /* ─── Submit Button ─── */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            color: white;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 14px rgba(192,57,43,0.35);
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
        }

        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(192,57,43,0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Error */
        .field-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 6px;
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
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="grid-overlay"></div>
            <div class="geo-shape geo-1"></div>
            <div class="geo-shape geo-2"></div>
            <div class="geo-shape geo-3"></div>

            <div class="brand-content">
                <div class="brand-logo-wrap">
                    <span class="material-symbols-outlined">rocket_launch</span>
                </div>
                <h1 class="brand-title">Elevate Your<br>Talent Pipeline</h1>
                <p class="brand-subtitle">
                    Streamline hiring, discover top talent, and build your dream team — all in one precision-built HR portal.
                </p>
                <div class="brand-pills">
                    <div class="brand-pill">
                        <span class="material-symbols-outlined">verified_user</span>
                        Secure Access
                    </div>
                    <div class="brand-pill">
                        <span class="material-symbols-outlined">group</span>
                        Team Collaboration
                    </div>
                    <div class="brand-pill">
                        <span class="material-symbols-outlined">analytics</span>
                        Smart Analytics
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
