@extends('layouts.admin')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-surface-bg border border-surface-border shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-surface-bg border border-surface-border shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-surface-bg border border-surface-border shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-on-surface">
                                {{ __('Two-Factor Authentication (2FA)') }}
                            </h2>
                            <p class="mt-1 text-sm text-secondary">
                                {{ __('Add additional security to your account using two-factor authentication.') }}
                            </p>
                        </header>
                        <div class="mt-6 flex items-center gap-4">
                            <a href="{{ route('2fa.setup') }}" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-on-primary uppercase tracking-widest hover:bg-primary-hover active:bg-primary-active focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Manage 2FA') }}
                            </a>
                            @if(Auth::user()->google2fa_secret)
                                <p class="text-sm text-success flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">check_circle</span> Aktif</p>
                            @else
                                <p class="text-sm text-error flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">cancel</span> Belum Aktif</p>
                            @endif
                        </div>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-surface-bg border border-surface-border shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
