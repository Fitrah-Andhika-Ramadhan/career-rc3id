<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

new
#[Layout('layouts.admin')]
class extends Component
{
    use WithFileUploads;

    public $app_name = '';
    public $app_subtitle = '';
    public $restrict_one_apply = true;
    public $primary_color = '#005bbf';
    public $hero_overlay_opacity = '0.8';
    public $super_admin_reset_email = '';
    public $footer_text = '';

    public $mail_mailer = '';
    public $mail_host = '';
    public $mail_port = '';
    public $mail_username = '';
    public $mail_password = '';
    public $mail_encryption = '';
    public $mail_from_address = '';
    public $mail_notification_addresses = '';
    public $mail_include_full_data = false;
    public $mail_hr_greeting = '';
    public $logo; // temp upload
    public string $currentLogo = '';
    public $favicon; // temp upload
    public string $currentFavicon = '';
    
    public $hero_title = '';
    public $hero_subtitle = '';
    public $hero_background; // temp upload
    public string $currentHeroBg = '';

    public function mount()
    {
        if (!auth()->user()->can('access settings')) {
            abort(403, 'Unauthorized action.');
        }

        $this->app_name = env('APP_NAME', 'CareerRC3ID');
        $this->app_subtitle = env('APP_SUBTITLE', 'HR Portal');
        $this->restrict_one_apply = filter_var(env('RESTRICT_ONE_APPLY', true), FILTER_VALIDATE_BOOLEAN);
        $this->primary_color = env('PRIMARY_COLOR', '#005bbf');
        $this->hero_overlay_opacity = env('HERO_OVERLAY_OPACITY', '0.8');
        $this->super_admin_reset_email = env('SUPER_ADMIN_RESET_EMAIL', 'fitrahramadhan310@gmail.com');
        $this->footer_text = env('FOOTER_TEXT', 'recruitment portal. All rights reserved.');

        $this->mail_mailer = env('MAIL_MAILER', 'smtp');
        $this->mail_host = env('MAIL_HOST', '127.0.0.1');
        $this->mail_port = env('MAIL_PORT', '2525');
        $this->mail_username = env('MAIL_USERNAME', '');
        $this->mail_password = env('MAIL_PASSWORD', '');
        $this->mail_encryption = env('MAIL_ENCRYPTION', 'tls');
        $this->mail_from_address = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $this->mail_notification_addresses = env('MAIL_NOTIFICATION_ADDRESSES', 'cl.rc3id@unpad.ac.id');
        $this->mail_include_full_data = filter_var(env('MAIL_INCLUDE_FULL_DATA', false), FILTER_VALIDATE_BOOLEAN);
        $this->mail_hr_greeting = env('MAIL_HR_GREETING', '');
        $this->hero_title = env('HERO_TITLE', 'Find Your Next Career at CareerRC3ID');
        $this->hero_subtitle = env('HERO_SUBTITLE', 'Join a global team of innovators, engineers, and creatives. We are building the future of precision technology and we need your talent to help us lead the way.');

        // Load current logo
        $this->currentLogo = file_exists(public_path('logo.svg'))
            ? 'logo.svg'
            : (file_exists(public_path('logo.png')) ? 'logo.png'
            : (file_exists(public_path('logo.jpg')) ? 'logo.jpg' : ''));

        // Load current favicon
        $this->currentFavicon = file_exists(public_path('favicon.svg'))
            ? 'favicon.svg'
            : (file_exists(public_path('favicon.ico')) ? 'favicon.ico'
            : (file_exists(public_path('favicon.png')) ? 'favicon.png' : ''));

        $this->currentHeroBg = file_exists(public_path('hero_background.png'))
            ? 'hero_background.png' : '';
    }

    public function uploadLogo()
    {
        $this->validate(['logo' => 'required|file|mimes:svg,png,jpg,jpeg,webp|max:2048']);

        $extension = $this->logo->getClientOriginalExtension();
        $filename  = 'logo.' . $extension;

        // Clean up old logo files first
        $oldFiles = ['logo.svg', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp'];
        foreach ($oldFiles as $oldFile) {
            if (File::exists(public_path($oldFile))) {
                File::delete(public_path($oldFile));
            }
        }

        // Copy directly to public/
        File::copy($this->logo->getRealPath(), public_path($filename));

        $this->currentLogo = $filename;
        $this->logo = null;

        session()->flash('logo_message', 'Logo Sidebar berhasil diupload dan diterapkan!');
    }

    public function uploadFavicon()
    {
        $this->validate(['favicon' => 'required|file|mimes:svg,ico,png,jpg,jpeg,webp|max:1024']);

        $extension = $this->favicon->getClientOriginalExtension();
        $filename  = 'favicon.' . $extension;

        // Clean up old favicon files
        $oldFiles = ['favicon.svg', 'favicon.ico', 'favicon.png', 'favicon.jpg', 'favicon.jpeg', 'favicon.webp'];
        foreach ($oldFiles as $oldFile) {
            if (File::exists(public_path($oldFile))) {
                File::delete(public_path($oldFile));
            }
        }

        // Copy directly to public/
        File::copy($this->favicon->getRealPath(), public_path($filename));

        $this->currentFavicon = $filename;
        $this->favicon = null;

        session()->flash('favicon_message', 'Favicon Tab Browser berhasil diupload dan diterapkan!');
    }

    public function uploadHeroBg()
    {
        $this->validate(['hero_background' => 'required|file|mimes:png,jpg,jpeg,webp|max:5120']);

        $filename = 'hero_background.png';

        if (File::exists(public_path($filename))) {
            File::delete(public_path($filename));
        }

        File::copy($this->hero_background->getRealPath(), public_path($filename));

        $this->currentHeroBg = $filename;
        $this->hero_background = null;

        session()->flash('hero_message', 'Background Hero berhasil diperbarui!');
    }

    public function saveGeneral()
    {
        $this->validate([
            'app_name' => 'required|string|max:255',
            'app_subtitle' => 'required|string|max:255',
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string',
            'restrict_one_apply' => 'boolean',
            'primary_color' => 'required|string|size:7', // expects #RRGGBB
            'hero_overlay_opacity' => 'required|numeric|min:0|max:1',
            'super_admin_reset_email' => 'nullable|email',
            'footer_text' => 'required|string|max:255',
        ]);

        $this->updateEnv([
            'APP_NAME' => $this->app_name,
            'APP_SUBTITLE' => $this->app_subtitle,
            'HERO_TITLE' => $this->hero_title,
            'HERO_SUBTITLE' => $this->hero_subtitle,
            'RESTRICT_ONE_APPLY' => $this->restrict_one_apply ? 'true' : 'false',
            'PRIMARY_COLOR' => $this->primary_color,
            'HERO_OVERLAY_OPACITY' => $this->hero_overlay_opacity,
            'SUPER_ADMIN_RESET_EMAIL' => $this->super_admin_reset_email,
            'FOOTER_TEXT' => $this->footer_text,
        ]);

        Artisan::call('config:clear');
        session()->flash('message', 'General settings updated successfully!');
    }

    public function saveEmail()
    {
        $this->validate([
            'mail_mailer' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
        ]);

        $this->updateEnv([
            'MAIL_MAILER' => $this->mail_mailer,
            'MAIL_HOST' => $this->mail_host,
            'MAIL_PORT' => $this->mail_port,
            'MAIL_USERNAME' => $this->mail_username,
            'MAIL_PASSWORD' => $this->mail_password,
            'MAIL_ENCRYPTION' => $this->mail_encryption,
            'MAIL_FROM_ADDRESS' => $this->mail_from_address,
        ]);

        Artisan::call('config:clear');
        session()->flash('message', 'Email settings updated successfully!');
    }

    public function saveNotification()
    {
        $this->validate([
            'mail_notification_addresses' => 'nullable|string',
            'mail_include_full_data' => 'boolean',
            'mail_hr_greeting' => 'nullable|string',
        ]);

        $this->updateEnv([
            'MAIL_NOTIFICATION_ADDRESSES' => $this->mail_notification_addresses,
            'MAIL_INCLUDE_FULL_DATA' => $this->mail_include_full_data ? 'true' : 'false',
            'MAIL_HR_GREETING' => $this->mail_hr_greeting,
        ]);

        Artisan::call('config:clear');
        session()->flash('message', 'Aturan kelola data kandidat berhasil disimpan!');
    }
    public function testEmail()
    {
        $this->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'nullable|string',
            'mail_username' => 'required|string',
            'mail_password' => 'required|string',
            'mail_from_address' => 'required|email',
        ]);

        // Set config on the fly to test without writing to .env (which kills local server)
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->mail_host,
            'mail.mailers.smtp.port' => $this->mail_port,
            'mail.mailers.smtp.encryption' => $this->mail_encryption,
            'mail.mailers.smtp.username' => $this->mail_username,
            'mail.mailers.smtp.password' => $this->mail_password,
            'mail.from.address' => $this->mail_from_address,
            'mail.from.name' => config('app.name'),
        ]);

        try {
            // Collect all emails to test
            $testEmails = [$this->mail_from_address];
            if (!empty($this->mail_notification_addresses)) {
                $additional = array_filter(array_map('trim', explode(',', $this->mail_notification_addresses)), function($e) {
                    return filter_var($e, FILTER_VALIDATE_EMAIL);
                });
                $testEmails = array_merge($testEmails, $additional);
            }
            $testEmails = array_unique($testEmails);

            foreach ($testEmails as $email) {
                Mail::to($email)->send(new \App\Mail\TestEmail($email));
            }
            
            $emailsStr = implode(', ', $testEmails);
            session()->flash('message', "Test Email sent successfully to: {$emailsStr}! (JANGAN LUPA KLIK 'SAVE SETTINGS' UNTUK MENYIMPAN)");
        } catch (\Exception $e) {
            session()->flash('error', 'Test Email failed: ' . $e->getMessage());
        }
    }

    private function updateEnv($data = array())
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $contents = File::get($envPath);
            foreach ($data as $key => $value) {
                // Ensure value is quoted if it contains spaces
                $value = preg_match('/\s/', $value) ? '"' . $value . '"' : $value;
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $contents)) {
                    $contents = preg_replace($pattern, "{$key}={$value}", $contents);
                } else {
                    $contents .= "\n{$key}={$value}";
                }
            }
            File::put($envPath, $contents);
        }
    }
};
?>

<div class="flex-1 overflow-y-auto p-margin h-[calc(100vh-64px)]">
    <div class="mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg text-on-background">System Settings</h2>
        <p class="text-on-surface-variant mt-1">Configure global application settings such as email credentials and branding.</p>
    </div>

    {{-- Alerts --}}
    @if (session()->has('message'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('logo_message'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
            {{ session('logo_message') }}
        </div>
    @endif
    @if (session()->has('hero_message'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
            {{ session('hero_message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-stack-lg p-stack-md bg-error/10 text-error border border-error/20 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div x-data="{ activeTab: 'branding' }" class="max-w-3xl">
        {{-- Tab Navigation --}}
        <div class="flex border-b border-surface-border mb-stack-lg overflow-x-auto hide-scrollbar">
            <button @click="activeTab = 'branding'" 
                    :class="activeTab === 'branding' ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-on-surface hover:border-surface-border'"
                    class="px-4 py-3 font-semibold text-sm border-b-2 whitespace-nowrap transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">palette</span> Branding & UI
            </button>
            <button @click="activeTab = 'general'" 
                    :class="activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-on-surface hover:border-surface-border'"
                    class="px-4 py-3 font-semibold text-sm border-b-2 whitespace-nowrap transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">settings</span> General & Access
            </button>
            <button @click="activeTab = 'email'" 
                    :class="activeTab === 'email' ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-on-surface hover:border-surface-border'"
                    class="px-4 py-3 font-semibold text-sm border-b-2 whitespace-nowrap transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">mail</span> Email Configuration
            </button>
            <button @click="activeTab = 'candidate_data'" 
                    :class="activeTab === 'candidate_data' ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-on-surface hover:border-surface-border'"
                    class="px-4 py-3 font-semibold text-sm border-b-2 whitespace-nowrap transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">contact_mail</span> Data Candidate & HR
            </button>
        </div>

        {{-- BRANDING TAB --}}
        <div x-show="activeTab === 'branding'" x-cloak class="space-y-stack-lg">
            {{-- LOGO UPLOAD CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">image</span>
                        Logo Sidebar Admin
                    </h3>
                    <p class="text-sm text-secondary mt-1">Upload logo utama perusahaan. Logo ini akan diterapkan di menu sidebar admin (bentuk memanjang/lebar disarankan).</p>
                </div>
                <div class="p-margin">
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <div class="flex-shrink-0 w-full md:w-auto">
                            <p class="text-label-sm text-secondary mb-2 font-semibold">Logo Saat Ini</p>
                            <div class="w-48 h-32 rounded-xl border-2 border-dashed border-surface-border bg-surface-container flex items-center justify-center overflow-hidden p-2">
                                @if($currentLogo)
                                    <img src="{{ asset($currentLogo) }}?v={{ time() }}" alt="Current Logo" class="w-full h-full object-contain">
                                @else
                                    <span class="material-symbols-outlined text-[40px] text-surface-container-highest">image</span>
                                @endif
                            </div>
                            @if($currentLogo)
                            <p class="text-xs text-success mt-2 text-center font-semibold">✓ Aktif</p>
                            @endif
                        </div>

                        {{-- Upload Form --}}
                        <div class="flex-1 space-y-3">
                            <div>
                                <label class="font-label-md text-on-surface-variant block mb-1">Upload Logo Baru</label>
                                <p class="text-xs text-secondary mb-2">Format: SVG (direkomendasikan), PNG, JPG, WebP. Maks 2MB.</p>
                                <input wire:model="logo" type="file" accept=".svg,.png,.jpg,.jpeg,.webp"
                                    class="w-full border border-surface-border p-2 rounded-lg bg-surface-container-low text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary file:text-sm file:font-semibold hover:file:opacity-90 transition-all">
                                @error('logo') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="logo" class="mt-2 text-primary text-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Mengupload...
                                </div>
                            </div>

                            {{-- Live preview of new upload --}}
                            @if($logo)
                            <div class="flex items-center gap-3 p-3 bg-primary/5 border border-primary/20 rounded-lg">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-24 h-16 object-contain rounded border border-surface-border bg-white p-1">
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Preview Logo Baru</p>
                                    <p class="text-xs text-secondary">{{ $logo->getClientOriginalName() }}</p>
                                </div>
                            </div>
                            @endif

                            <button wire:click="uploadLogo"
                                wire:loading.attr="disabled" wire:target="uploadLogo"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-lg font-semibold text-sm hover:opacity-90 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="uploadLogo" class="material-symbols-outlined text-[18px]">upload</span>
                                <span wire:loading wire:target="uploadLogo" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                <span wire:loading.remove wire:target="uploadLogo">Upload & Terapkan Logo</span>
                                <span wire:loading wire:target="uploadLogo">Mengupload...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FAVICON UPLOAD CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">tab</span>
                        Favicon (Tab Browser)
                    </h3>
                    <p class="text-sm text-secondary mt-1">Upload ikon kotak kecil (biasanya SVG atau ICO) yang akan muncul di tab browser pengguna.</p>
                </div>
                <div class="p-margin">
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <div class="flex-shrink-0 w-full md:w-auto">
                            <p class="text-label-sm text-secondary mb-2 font-semibold">Favicon Saat Ini</p>
                            <div class="w-32 h-32 rounded-xl border-2 border-dashed border-surface-border bg-surface-container flex items-center justify-center overflow-hidden p-2 mx-auto md:mx-0">
                                @if($currentFavicon)
                                    <img src="{{ asset($currentFavicon) }}?v={{ time() }}" alt="Current Favicon" class="w-full h-full object-contain">
                                @else
                                    <span class="material-symbols-outlined text-[32px] text-surface-container-highest">tab</span>
                                @endif
                            </div>
                            @if($currentFavicon)
                            <p class="text-xs text-success mt-2 text-center font-semibold">✓ Aktif</p>
                            @endif
                        </div>

                        {{-- Upload Form --}}
                        <div class="flex-1 space-y-3">
                            <div>
                                <label class="font-label-md text-on-surface-variant block mb-1">Upload Favicon Baru</label>
                                <p class="text-xs text-secondary mb-2">Format: SVG, ICO, PNG. Maks 1MB. (Harus rasio 1:1 persegi)</p>
                                <input wire:model="favicon" type="file" accept=".svg,.ico,.png,.jpg,.jpeg,.webp"
                                    class="w-full border border-surface-border p-2 rounded-lg bg-surface-container-low text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary file:text-sm file:font-semibold hover:file:opacity-90 transition-all">
                                @error('favicon') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="favicon" class="mt-2 text-primary text-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Mengupload...
                                </div>
                            </div>

                            {{-- Live preview of new upload --}}
                            @if($favicon)
                            <div class="flex items-center gap-3 p-3 bg-primary/5 border border-primary/20 rounded-lg">
                                <img src="{{ $favicon->temporaryUrl() }}" alt="Preview" class="w-24 h-16 object-contain rounded border border-surface-border bg-white p-1">
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Preview Favicon Baru</p>
                                    <p class="text-xs text-secondary">{{ $favicon->getClientOriginalName() }}</p>
                                </div>
                            </div>
                            @endif

                            <button wire:click="uploadFavicon"
                                wire:loading.attr="disabled" wire:target="uploadFavicon"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-lg font-semibold text-sm hover:opacity-90 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="uploadFavicon" class="material-symbols-outlined text-[18px]">upload</span>
                                <span wire:loading wire:target="uploadFavicon" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                <span wire:loading.remove wire:target="uploadFavicon">Upload & Terapkan Favicon</span>
                                <span wire:loading wire:target="uploadFavicon">Mengupload...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- HERO BACKGROUND UPLOAD CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">wallpaper</span>
                        Hero Background
                    </h3>
                    <p class="text-sm text-secondary mt-1">Upload gambar latar belakang untuk bagian Hero di halaman Home publik.</p>
                </div>
                <div class="p-margin">
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <div class="flex-shrink-0 w-full md:w-auto">
                            <p class="text-label-sm text-secondary mb-2 font-semibold">Background Saat Ini</p>
                            <div class="w-32 h-20 rounded-xl border-2 border-dashed border-surface-border bg-surface-container flex items-center justify-center overflow-hidden">
                                @if($currentHeroBg)
                                    <img src="{{ asset($currentHeroBg) }}?v={{ time() }}" alt="Hero Background" class="w-full h-full object-cover">
                                @else
                                    <span class="material-symbols-outlined text-[32px] text-surface-container-highest">image</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div>
                                <input wire:model="hero_background" type="file" accept=".png,.jpg,.jpeg,.webp" class="w-full border border-surface-border p-2 rounded-lg bg-surface-container-low text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary file:text-sm file:font-semibold hover:file:opacity-90 transition-all">
                                @error('hero_background') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="hero_background" class="mt-2 text-primary text-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Mengupload...
                                </div>
                            </div>
                            <button wire:click="uploadHeroBg" wire:loading.attr="disabled" wire:target="uploadHeroBg" class="inline-flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-lg font-semibold text-sm hover:opacity-90 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="uploadHeroBg" class="material-symbols-outlined text-[18px]">upload</span>
                                <span wire:loading wire:target="uploadHeroBg" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                <span wire:loading.remove wire:target="uploadHeroBg">Upload Background</span>
                                <span wire:loading wire:target="uploadHeroBg">Mengupload...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GENERAL TAB --}}
        <div x-show="activeTab === 'general'" x-cloak class="space-y-stack-lg" style="display: none;">
            {{-- ACCESS MANAGEMENT CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">security</span>
                        Access Management
                    </h3>
                    <p class="text-sm text-secondary mt-1">Manage system administrators, HR staff, and their permissions.</p>
                </div>
                <div class="p-margin flex flex-col gap-4 md:flex-row">
                    @can('access users')
                    <a href="{{ route('admin.users.index') }}" class="flex-1 border border-surface-border rounded-lg p-4 hover:border-primary/50 hover:bg-surface-container-low transition-colors group flex flex-col sm:flex-row items-start gap-4">
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded-lg flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">manage_accounts</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-headline-sm text-on-surface break-words">Users</h4>
                            <p class="text-xs text-secondary mt-1 break-words">Add, edit, or remove staff accounts.</p>
                        </div>
                    </a>
                    @endcan
                    @can('access roles')
                    <a href="{{ route('admin.roles.index') }}" class="flex-1 border border-surface-border rounded-lg p-4 hover:border-primary/50 hover:bg-surface-container-low transition-colors group flex flex-col sm:flex-row items-start gap-4">
                        <div class="w-12 h-12 bg-[#0ea5e9]/10 text-[#0ea5e9] rounded-lg flex items-center justify-center shrink-0 group-hover:bg-[#0ea5e9] group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">shield_person</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-headline-sm text-on-surface break-words">Roles & Permissions</h4>
                            <p class="text-xs text-secondary mt-1 break-words">Configure access levels for each role.</p>
                        </div>
                    </a>
                    @endcan
                </div>
            </div>
            {{-- GENERAL SETTINGS CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <form wire:submit="saveGeneral">
                    <div class="p-margin border-b border-surface-border">
                        <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">settings_applications</span>
                            General Settings
                        </h3>
                        <p class="text-sm text-secondary mt-1">Configure application branding text.</p>
                    </div>
                    <div class="p-margin space-y-stack-md">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-stack-md">
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">App Name</label>
                                <input wire:model="app_name" type="text" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary" placeholder="CareerRC3ID">
                                @error('app_name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">Portal Subtitle</label>
                                <input wire:model="app_subtitle" type="text" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary" placeholder="HR Portal">
                                @error('app_subtitle') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">Theme Color (Primary)</label>
                                <div class="flex items-center gap-3">
                                    <div class="relative w-10 h-10 rounded-lg overflow-hidden border border-surface-border shadow-sm flex-shrink-0">
                                        <input wire:model="primary_color" type="color" class="absolute -top-4 -left-4 w-20 h-20 cursor-pointer">
                                    </div>
                                    <input wire:model="primary_color" type="text" class="flex-1 px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary uppercase font-mono text-sm" placeholder="#005bbf" maxlength="7">
                                </div>
                                @error('primary_color') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md mt-stack-md">
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">Hero Title</label>
                                <input wire:model="hero_title" type="text" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary" placeholder="Find Your Next Career...">
                                @error('hero_title') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">Hero Subtitle</label>
                                <textarea wire:model="hero_subtitle" rows="3" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary"></textarea>
                                @error('hero_subtitle') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-stack-md mt-stack-md">
                            <div class="space-y-2">
                                <label class="font-label-md text-label-md text-on-surface-variant">Footer Text</label>
                                <input wire:model="footer_text" type="text" class="w-full px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary" placeholder="recruitment portal. All rights reserved.">
                                <p class="text-xs text-secondary mt-1">Teks ini akan muncul di bagian bawah website publik setelah tulisan "© [Tahun] [Nama Aplikasi]".</p>
                                @error('footer_text') <span class="text-error text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-stack-md space-y-2">
                            <label class="font-label-md text-label-md text-on-surface-variant flex items-center justify-between">
                                Hero Background Overlay Opacity 
                                <span class="text-primary font-bold">{{ floatval($hero_overlay_opacity) * 100 }}%</span>
                            </label>
                            <input wire:model.live="hero_overlay_opacity" type="range" min="0" max="1" step="0.05" class="w-full h-2 bg-surface-container-highest rounded-lg appearance-none cursor-pointer accent-primary">
                            <p class="text-xs text-secondary">Atur tingkat transparansi warna gradient di atas gambar hero (0 = Transparan, 1 = Solid).</p>
                            @error('hero_overlay_opacity') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="pt-2 border-t border-surface-border mt-stack-md">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" wire:model="restrict_one_apply" class="sr-only peer">
                                    <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-on-surface block">Batasi 1 Email 1 Lamaran (per Lowongan)</span>
                                    <span class="text-xs text-secondary block">Jika aktif, pelamar tidak bisa melamar posisi yang sama lebih dari sekali.</span>
                                </div>
                            </label>
                        </div>
                        <div class="pt-4 mt-stack-md border-t border-surface-border">
                            <label class="font-label-md text-label-md text-on-surface-variant block mb-2">Super Admin Reset Email</label>
                            <input wire:model="super_admin_reset_email" type="email" class="w-full md:w-1/2 px-4 py-2 border border-surface-border rounded-lg bg-surface-container-low focus:ring-primary focus:border-primary" placeholder="Masukkan email tujuan reset password">
                            <p class="text-xs text-secondary mt-1">Jika ada permintaan reset password untuk Super Admin, link akan dikirimkan ke email ini (bukan ke email login).</p>
                            @error('super_admin_reset_email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" wire:loading.attr="disabled" wire:target="saveGeneral" class="px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 flex items-center gap-2 transition-all">
                                <span wire:loading.remove wire:target="saveGeneral" class="material-symbols-outlined text-[18px]">save</span>
                                <span wire:loading wire:target="saveGeneral" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                <span>Save General Settings</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        {{-- EMAIL TAB --}}
        <div x-show="activeTab === 'email'" x-cloak class="space-y-stack-lg" style="display: none;">
            {{-- EMAIL SETTINGS CARD --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
                <form wire:submit="saveEmail">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">mail</span>
                        Email (SMTP) Configuration
                    </h3>
                </div>
                <div class="p-margin space-y-stack-md">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md">
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Mailer</label>
                    <input wire:model="mail_mailer" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="smtp">
                    @error('mail_mailer') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Host</label>
                    <input wire:model="mail_host" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="smtp.mailtrap.io">
                    @error('mail_host') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Port</label>
                    <input wire:model="mail_port" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="2525">
                    @error('mail_port') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Encryption</label>
                    <input wire:model="mail_encryption" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="tls, ssl">
                    @error('mail_encryption') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Username</label>
                    <input wire:model="mail_username" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary">
                    @error('mail_username') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">Mail Password</label>
                    <input wire:model="mail_password" type="password" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary">
                    @error('mail_password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="font-label-md text-label-md text-on-surface-variant">From Address</label>
                    <input wire:model="mail_from_address" type="email" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="no-reply@careerrc3id.com">
                    @error('mail_from_address') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                </div>
            </div>
            <div class="flex justify-end gap-4 mt-6 pt-6 border-t border-surface-border">
                <button type="button" wire:click="testEmail" wire:loading.attr="disabled" wire:target="testEmail" class="px-6 py-2 bg-surface-container text-on-surface border border-surface-border rounded-lg font-label-md hover:bg-surface-variant flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="testEmail" class="material-symbols-outlined text-[18px]">send</span>
                    <span wire:loading wire:target="testEmail" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span wire:loading.remove wire:target="testEmail">Test Email</span>
                    <span wire:loading wire:target="testEmail">Sending...</span>
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save" class="material-symbols-outlined text-[18px]">save</span>
                    <span wire:loading wire:target="save" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
    </div> {{-- Close EMAIL TAB --}}

        {{-- CANDIDATE DATA & HR TAB --}}
        <div x-show="activeTab === 'candidate_data'" x-cloak class="space-y-stack-lg" style="display: none;">
            <form wire:submit="saveNotification">
            
            {{-- CARD 1: Email Penerima Notifikasi --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden mb-stack-lg">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">group</span>
                        Penerima Notifikasi Lamaran Baru
                    </h3>
                    <p class="text-sm text-secondary mt-1">Atur siapa saja tim HR atau pihak CNL yang akan menerima notifikasi setiap kali ada kandidat baru.</p>
                </div>
                <div class="p-margin space-y-4">
                    <label class="font-label-md text-label-md text-on-surface-variant block mb-2">Daftar Email Penerima (HR & CNL)</label>
                    <input wire:model="mail_notification_addresses" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" placeholder="hr@example.com, admin@example.com">
                    <p class="text-xs text-secondary mt-1">Pisahkan dengan koma jika Anda memasukkan lebih dari satu email.</p>
                    @error('mail_notification_addresses') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- CARD 2: Aturan Pengiriman Data Pelamar --}}
            <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden mb-stack-lg">
                <div class="p-margin border-b border-surface-border">
                    <h3 class="font-headline-md text-headline-md text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings:'FILL' 1">dataset</span>
                        Aturan Kelola Data Kandidat
                    </h3>
                    <p class="text-sm text-secondary mt-1">Tentukan sedetail apa data yang akan langsung dikirimkan melalui email notifikasi ke tim HR.</p>
                </div>
                <div class="p-margin space-y-6">
                    
                    {{-- Toggle Lampiran --}}
                    <div>
                        <label class="flex items-start gap-4 cursor-pointer p-4 border border-surface-border rounded-lg bg-surface-container/30 hover:bg-surface-container-low transition-colors">
                            <div class="relative mt-1">
                                <input type="checkbox" wire:model="mail_include_full_data" class="sr-only peer">
                                <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-on-surface block">Kirim Seluruh File dan Jawaban Kustom Pelamar ke Email</span>
                                <span class="text-xs text-secondary block mt-1">Jika diaktifkan, email notifikasi HR tidak hanya berisi nama pelamar, namun juga melampirkan secara otomatis CV/Dokumen pelamar dan daftar jawaban kustom (seperti domisili, pendidikan, dll) sehingga HR bisa meninjau langsung dari kotak masuk email.</span>
                            </div>
                        </label>
                    </div>

                    {{-- Pesan Kustom --}}
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-2">Teks Pengantar Email HR (Opsional)</label>
                        <textarea wire:model="mail_hr_greeting" rows="3" class="w-full px-4 py-3 border rounded-lg focus:ring-primary focus:border-primary" placeholder="Teks yang akan muncul di bagian paling atas email HR..."></textarea>
                        <p class="text-xs text-secondary mt-1">Anda bisa mengatur kalimat pembuka kustom (contoh: "Halo Tim HRD, mohon segera ditinjau lamaran ini..."). Biarkan kosong untuk menggunakan pesan bawaan sistem.</p>
                        @error('mail_hr_greeting') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveNotification" class="px-8 py-3 bg-primary text-on-primary rounded-lg font-bold hover:opacity-90 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-md">
                    <span wire:loading.remove wire:target="saveNotification" class="material-symbols-outlined text-[20px]">save</span>
                    <span wire:loading wire:target="saveNotification" class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                    <span wire:loading.remove wire:target="saveNotification">Simpan Aturan Data</span>
                    <span wire:loading wire:target="saveNotification">Menyimpan...</span>
                </button>
            </div>
            
            </form>
        </div>
</div>
</div>
</div>
