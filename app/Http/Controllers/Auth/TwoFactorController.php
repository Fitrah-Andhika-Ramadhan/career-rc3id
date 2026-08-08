<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA Setup page (Profile section)
     */
    public function showSetup(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        if ($user->google2fa_secret) {
            return view('profile.two-factor-setup', ['user' => $user, 'enabled' => true]);
        }

        $secret = $google2fa->generateSecretKey();
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        $request->session()->put('2fa_secret_temp', $secret);

        return view('profile.two-factor-setup', [
            'user' => $user,
            'enabled' => false,
            'secret' => $secret,
            'qrCodeSvg' => $qrCodeSvg
        ]);
    }

    /**
     * Enable 2FA after scanning QR code
     */
    public function enable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $secret = $request->session()->get('2fa_secret_temp');
        if (!$secret) {
            return redirect()->back()->withErrors(['error' => 'Sesi kedaluwarsa. Silakan muat ulang halaman.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $request->otp);

        if ($valid) {
            $user = $request->user();
            $user->google2fa_secret = $secret;
            $user->save();

            $request->session()->forget('2fa_secret_temp');

            return redirect()->back()->with('status', '2FA Berhasil diaktifkan.');
        }

        return redirect()->back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->google2fa_secret = null;
        $user->save();

        return redirect()->back()->with('status', '2FA Berhasil dinonaktifkan.');
    }

    /**
     * Show the 2FA verification form during login
     */
    public function showVerifyForm(Request $request)
    {
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-verify');
    }

    /**
     * Verify the 2FA OTP during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('2fa:user:id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            Auth::login($user, $request->session()->get('2fa:user:remember', false));
            $request->session()->forget('2fa:user:id');
            $request->session()->forget('2fa:user:remember');
            
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->back()->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.']);
    }
}
