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
        $headerRow = ['ID', 'Nama Kandidat', 'Email', 'Telepon', 'Posisi Dilamar', 'Departemen', 'Status', 'Tanggal Melamar'];
        $fields = is_array($job->custom_fields) ? $job->custom_fields : json_decode($job->custom_fields, true) ?? [];
        
        foreach ($fields as $field) {
            if (in_array($field['type'] ?? 'text', ['title', 'section', 'image', 'video'])) continue;
            // Always include custom fields to match exactly what is in the form builder
            $headerRow[] = $field['label'] ?? 'Custom Field';
        }
        return $headerRow;
    }

    /**
     * Get the data row for a specific application.
     */
    public function getApplicationRow(Application $application, Job $job, array $headers): array
    {
        $candidate = $application->candidate;
        
        // Base fields (first 8 columns)
        $row = [
            $application->id,
            $candidate->name ?? '-',
            $candidate->email ?? '-',
            $candidate->phone ?? '-',
            $job->title ?? '-',
            $job->department ?? '-',
            $application->stage->name ?? '-',
            $application->created_at->format('Y-m-d H:i:s'),
        ];

        // Parse custom notes to extract answers
        $notes = $application->notes()->latest()->first()?->note ?? '';
        $customAnswers = [];
        $lines = explode("\n", $notes);
        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $val) = explode(': ', $line, 2);
                $customAnswers[trim($key)] = trim($val);
            }
        }

        $mediaItems = $application->getMedia('documents');

        // Add custom fields in the order of headers (skipping the first 8 base fields)
        for ($i = 8; $i < count($headers); $i++) {
            $headerLabel = $headers[$i];
            $val = $customAnswers[$headerLabel] ?? '-';
            
            if (is_string($val) && str_starts_with($val, 'Berkas dilampirkan: ')) {
                $filename = trim(str_replace('Berkas dilampirkan: ', '', $val));
                // Find matching media
                $matchedMedia = $mediaItems->first(function ($media) use ($filename) {
                    return $media->file_name === $filename || $media->name === $filename;
                });
                
                if ($matchedMedia) {
                    $val = route('media.download', $matchedMedia->uuid);
                }
            }
            
            $row[] = $val;
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
            
            // Always update headers in case the user added new fields to the form
            $headerBody = new Sheets\ValueRange(['values' => [$headers]]);
            $colCount = count($headers);
            $endCol = '';
            $temp = $colCount;
            while ($temp > 0) {
                $modulo = ($temp - 1) % 26;
                $endCol = chr(65 + $modulo) . $endCol;
                $temp = (int)(($temp - $modulo) / 26);
            }
            $range = 'Sheet1!A1:' . $endCol . '1';
            
            try {
                $service->spreadsheets_values->update(
                    $spreadsheetId,
                    $range,
                    $headerBody,
                    ['valueInputOption' => 'RAW']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to update Google Sheets headers: ' . $e->getMessage());
            }

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
            $applications = $job->applications()->with('candidate', 'stage', 'media', 'notes')->orderBy('created_at', 'asc')->get();

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

    /**
     * Upload a file to Google Drive and return the public webViewLink.
     */
    public function uploadFileToDrive($filePath, $fileName, $mimeType)
    {
        try {
            $client = $this->getClient();
            $driveService = new \Google\Service\Drive($client);
            
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $fileName
            ]);
            
            $content = file_get_contents($filePath);
            $file = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);
            
            // Set permission to anyone with link can view
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $driveService->permissions->create($file->id, $permission);
            
            return $file->webViewLink;
        } catch (\Exception $e) {
            Log::error('Failed to upload file to Google Drive: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a master Google Spreadsheet for ALL Jobs with multiple tabs.
     */
    public function createAndSyncMasterSpreadsheet()
    {
        $client = $this->getClient();
        $service = new Sheets($client);
        
        $jobs = Job::whereHas('applications')->get();
        if ($jobs->isEmpty()) {
            throw new \Exception('Belum ada data pelamar untuk diekspor.');
        }

        $spreadsheet = new Sheets\Spreadsheet([
            'properties' => [
                'title' => 'ATS Master Export - ' . date('Y-m-d H:i')
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet);
        $spreadsheetId = $spreadsheet->spreadsheetId;

        try {
            $driveService = new \Google\Service\Drive($client);
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'writer',
            ]);
            $driveService->permissions->create($spreadsheetId, $permission);
        } catch (\Exception $e) {
            Log::error('Failed to share master spreadsheet: ' . $e->getMessage());
        }

        $requests = [];
        $first = true;
        foreach ($jobs as $index => $job) {
            $sheetName = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $job->title), 0, 31) ?: 'Sheet' . ($index + 1);
            if ($first) {
                $requests[] = new Sheets\Request([
                    'updateSheetProperties' => [
                        'properties' => ['sheetId' => 0, 'title' => $sheetName],
                        'fields' => 'title'
                    ]
                ]);
                $first = false;
            } else {
                $requests[] = new Sheets\Request([
                    'addSheet' => [
                        'properties' => ['title' => $sheetName]
                    ]
                ]);
            }
        }

        if (!empty($requests)) {
            $batchUpdateRequest = new Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }

        foreach ($jobs as $index => $job) {
            $sheetName = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $job->title), 0, 31) ?: 'Sheet' . ($index + 1);
            $headers = $this->getHeaders($job);
            
            $applications = $job->applications()->with('candidate', 'stage', 'media', 'notes')->orderBy('created_at', 'asc')->get();
            
            // Add 'Departemen' column header explicitly to match Excel Export
            $headerRow = array_merge(['ID', 'Departemen'], array_slice($headers, 1));
            
            $values = [$headerRow];
            foreach ($applications as $app) {
                $row = $this->getApplicationRow($app, $job, $headers);
                // Insert departemen into row array
                $values[] = array_merge([$app->id, $app->job->department ?? '-'], array_slice($row, 1));
            }

            $body = new Sheets\ValueRange(['values' => $values]);
            $service->spreadsheets_values->update(
                $spreadsheetId,
                "'" . $sheetName . "'!A1",
                $body,
                ['valueInputOption' => 'USER_ENTERED']
            );
        }

        Setting::updateOrCreate(
            ['key' => 'master_google_spreadsheet_id'],
            ['value' => $spreadsheetId]
        );

        return $spreadsheetId;
    }
}
