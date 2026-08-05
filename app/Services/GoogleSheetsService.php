<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use App\Models\Setting;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    /**
     * Get an authenticated Google Client.
     */
    public function getClient()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(url('/google/callback'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope(\Google\Service\Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        
        $token = Setting::where('key', 'google_oauth_token')->value('value');
        if ($token) {
            $client->setAccessToken(json_decode($token, true));
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    Setting::updateOrCreate(
                        ['key' => 'google_oauth_token'],
                        ['value' => json_encode($client->getAccessToken())]
                    );
                } else {
                    throw new \Exception('Google OAuth token expired and no refresh token found.');
                }
            }
        } else {
            throw new \Exception('No Google OAuth token found.');
        }

        return $client;
    }

    /**
     * Create a new Google Spreadsheet for the given Job.
     */
    public function createSpreadsheetForJob(Job $job)
    {
        if ($job->google_spreadsheet_id) {
            return $job->google_spreadsheet_id; // Already created
        }

        $client = $this->getClient();
        $service = new Sheets($client);
        
        $spreadsheet = new Sheets\Spreadsheet([
            'properties' => [
                'title' => 'ATS Responses - ' . $job->title
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet);
        $spreadsheetId = $spreadsheet->spreadsheetId;

        // Share the spreadsheet
        try {
            $driveService = new \Google\Service\Drive($client);
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'writer',
            ]);
            $driveService->permissions->create($spreadsheetId, $permission);
        } catch (\Exception $e) {
            Log::error('Failed to share spreadsheet: ' . $e->getMessage());
        }

        // Add Headers
        $headers = $this->getHeaders($job);
        $body = new Sheets\ValueRange(['values' => [$headers]]);
        
        $colCount = count($headers);
        $endCol = '';
        $temp = $colCount;
        while ($temp > 0) {
            $modulo = ($temp - 1) % 26;
            $endCol = chr(65 + $modulo) . $endCol;
            $temp = (int)(($temp - $modulo) / 26);
        }
        $range = 'Sheet1!A1:' . $endCol . '1';

        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        $job->google_spreadsheet_id = $spreadsheetId;
        $job->save();

        return $spreadsheetId;
    }

    /**
     * Get the headers dynamically from Job custom fields and application basic fields.
     */
    public function getHeaders(Job $job): array
    {
        $headerRow = ['Tanggal Lamaran', 'Nama Lengkap', 'Email', 'No. HP', 'Status Lamaran'];
        $fields = is_array($job->custom_fields) ? $job->custom_fields : json_decode($job->custom_fields, true) ?? [];
        
        foreach ($fields as $field) {
            if (in_array($field['type'] ?? 'text', ['title', 'section', 'image', 'video'])) continue;
            // Skip fields that are already in the basic identity to avoid duplication
            $label = strtolower($field['label']);
            if (str_contains($label, 'nama') || str_contains($label, 'name') ||
                str_contains($label, 'email') || str_contains($label, 'surel') ||
                str_contains($label, 'telepon') || str_contains($label, 'phone') || str_contains($label, 'hp')) {
                continue;
            }
            $headerRow[] = $field['label'];
        }
        return $headerRow;
    }

    /**
     * Get the data row for a specific application.
     */
    public function getApplicationRow(Application $application, Job $job, array $headers): array
    {
        $candidate = $application->candidate;
        
        // Base fields
        $row = [
            $application->created_at->format('Y-m-d H:i:s'),
            $candidate->name ?? '-',
            $candidate->email ?? '-',
            $candidate->phone ?? '-',
            $application->pipelineStage->name ?? '-',
        ];

        // Parse custom notes to extract answers (since we didn't save answers as JSON, we parse from notes)
        // Alternatively, if passed during submission, we can use the array. But for sync all, we parse from notes.
        $notes = $application->notes()->latest()->first()?->note ?? '';
        $customAnswers = [];
        $lines = explode("\n", $notes);
        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $val) = explode(': ', $line, 2);
                $customAnswers[trim($key)] = trim($val);
            }
        }

        // Add custom fields in the order of headers (skipping the first 5 base fields)
        for ($i = 5; $i < count($headers); $i++) {
            $headerLabel = $headers[$i];
            $row[] = $customAnswers[$headerLabel] ?? '-';
        }

        return $row;
    }

    /**
     * Append a single candidate to the Google Sheet.
     */
    public function syncCandidateToSheet(Job $job, Application $application)
    {
        if (!$job->google_spreadsheet_id) {
            return false;
        }

        try {
            $client = $this->getClient();
            $service = new Sheets($client);
            $spreadsheetId = $job->google_spreadsheet_id;

            $headers = $this->getHeaders($job);
            $row = $this->getApplicationRow($application, $job, $headers);

            $body = new Sheets\ValueRange([
                'values' => [$row]
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $service->spreadsheets_values->append(
                $spreadsheetId,
                'Sheet1!A2',
                $body,
                $params
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets Sync Error (Append): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync ALL candidates to the Google Sheet (Overwrite).
     */
    public function syncAllCandidatesToSheet(Job $job)
    {
        if (!$job->google_spreadsheet_id) {
            throw new \Exception('No Google Spreadsheet connected to this Job.');
        }

        try {
            $client = $this->getClient();
            $service = new Sheets($client);
            $spreadsheetId = $job->google_spreadsheet_id;

            $headers = $this->getHeaders($job);
            $applications = $job->applications()->with('candidate', 'pipelineStage', 'notes')->orderBy('created_at', 'asc')->get();

            $values = [$headers]; // Add headers first
            foreach ($applications as $app) {
                $values[] = $this->getApplicationRow($app, $job, $headers);
            }

            // 1. Clear the sheet
            $clearBody = new Sheets\ClearValuesRequest();
            $service->spreadsheets_values->clear(
                $spreadsheetId,
                'Sheet1!A1:Z',
                $clearBody
            );

            // 2. Update with all data
            $body = new Sheets\ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $service->spreadsheets_values->update(
                $spreadsheetId,
                'Sheet1!A1',
                $body,
                $params
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets Sync Error (Sync All): ' . $e->getMessage());
            throw $e;
        }
    }
}
