@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-surface-bg border border-surface-border shadow sm:rounded-lg">
            <div class="max-w-xl">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-on-surface">
                            {{ __('Two-Factor Authentication (2FA)') }}
                        </h2>
                        <p class="mt-1 text-sm text-secondary">
                            {{ __('Amankan akun Anda dengan Autentikasi Dua Langkah menggunakan Google Authenticator.') }}
                        </p>
                    </header>

                    @if (session('status'))
                        <div class="mt-4 px-4 py-3 rounded-lg bg-success/10 text-success text-sm border border-success/20 font-medium">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mt-4 px-4 py-3 rounded-lg bg-error/10 text-error text-sm border border-error/20">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($enabled)
                        <div class="mt-6 p-6 bg-success/5 border border-success/20 rounded-xl">
                            <h3 class="text-lg font-semibold text-success flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined">verified_user</span> 2FA Aktif
                            </h3>
                            <p class="text-sm text-secondary mb-6">Autentikasi dua langkah saat ini telah aktif pada akun Anda.</p>
                            
                            <form method="POST" action="{{ route('2fa.disable') }}">
                                @csrf
                                <div class="mb-4">
                                    <label for="password" class="block font-medium text-sm text-on-surface">Konfirmasi Password Anda</label>
                                    <input id="password" type="password" name="password" required class="mt-1 block w-full bg-surface-container border-surface-border text-on-surface rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                </div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-error border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-error-hover active:bg-error-active focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Nonaktifkan 2FA') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-6">
                            <div class="mb-6 p-4 bg-surface-container rounded-xl text-sm text-on-surface">
                                <p class="mb-4 font-semibold">1. Unduh Aplikasi Google Authenticator di HP Anda.</p>
                                <p class="mb-2 font-semibold">2. Pindai (Scan) QR Code di bawah ini:</p>
                                
                                <div class="bg-white p-4 rounded-xl inline-block shadow-sm">
                                    {!! $qrCodeSvg !!}
                                </div>
                                
                                <p class="mt-4 text-xs text-secondary">Atau masukkan kode rahasia ini secara manual: <strong class="text-on-surface font-mono tracking-widest">{{ $secret }}</strong></p>
                            </div>

                            <form method="POST" action="{{ route('2fa.enable') }}" class="mt-6 p-6 bg-surface-container-lowest border border-surface-border rounded-xl shadow-sm">
                                @csrf
                                <div class="mb-4">
                                    <h3 class="text-md font-semibold text-on-surface mb-2">3. Konfirmasi Kode OTP</h3>
                                    <p class="text-sm text-secondary mb-4">Masukkan 6 digit angka dari aplikasi Google Authenticator untuk memverifikasi dan mengaktifkan.</p>
                                    
                                    <input id="otp" type="text" name="otp" required autofocus autocomplete="off" class="block w-full text-center text-2xl tracking-[0.5em] font-mono bg-surface-container border-surface-border text-on-surface rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="000000" maxlength="6">
                                </div>
                                <div class="flex items-center gap-4">
                                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-semibold text-xs text-on-primary uppercase tracking-widest hover:bg-primary-hover active:bg-primary-active focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                                        {{ __('Verifikasi & Aktifkan') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
