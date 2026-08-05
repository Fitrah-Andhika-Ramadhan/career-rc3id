<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use App\Models\Setting;

class GoogleIntegrationController extends Controller
{
    private function getClient()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(url('/google/callback'));
        $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
        $client->addScope(\Google\Service\Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        // Load existing token if available
        $token = Setting::where('key', 'google_oauth_token')->value('value');
        if ($token) {
            $client->setAccessToken(json_decode($token, true));
        }
        
        return $client;
    }

    public function auth()
    {
        // Only admins can connect
        if (!auth()->user()->can('access settings')) {
            abort(403, 'Unauthorized action.');
        }

        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        // Only admins can connect
        if (!auth()->user()->can('access settings')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->has('code')) {
            $client = $this->getClient();
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
                if (!isset($token['error'])) {
                    Setting::updateOrCreate(
                        ['key' => 'google_oauth_token'],
                        ['value' => json_encode($token)]
                    );
                    return redirect('/admin/custom-form')->with('success', 'Berhasil terhubung ke Google Sheets!');
                }
            } catch (\Exception $e) {
                return redirect('/admin/custom-form')->with('error', 'Gagal menghubungkan: ' . $e->getMessage());
            }
        }
        return redirect('/admin/custom-form')->with('error', 'Otentikasi dibatalkan.');
    }
}
