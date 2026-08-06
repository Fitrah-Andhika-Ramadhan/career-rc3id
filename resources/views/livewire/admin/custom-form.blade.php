<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

new
#[Layout('layouts.admin')]
class extends Component
{
    // ── Selected job ──────────────────────────────────────────────
    #[Url]
    public $selectedJobId = '';
    public $selectedJob   = null;

    // ── Form field builder state ───────────────────────────────────
    public array $fields = [];         // current list of custom fields
    public $editingIndex = null;
    public string $newOptionText   = ''; // temporary for adding an option inline

    public string $saveMessage = '';
    public string $currentTab = 'questions';
    public int $responsesCount = 0;
    public bool $oneResponsePerPerson = false;
    public $deadlineDate = null;
    public string $closedMessage = '';
    
    // ── History tracking (Undo/Redo) ───────────────────────────────
    public array $historyFields = [];
    public int $historyIndex = -1;
    public bool $isUndoRedo = false;
    
    // ── Form Header ────────────────────────────────────────────────
    public string $jobTitle = '';
    public string $jobDescription = '';
    public bool $importModalOpen = false;
    public $availableJobsForImport = [];
    public string $primaryColor = '#005bbf';
    public bool $aiModalOpen = false;
    public string $aiPrompt = '';

    // ── Persisted config per job (stored in DB as JSON in job column) ──
    
    public function mount()
    {
        $this->primaryColor = env('PRIMARY_COLOR', '#005bbf');
        $jobs = Job::orderBy('created_at', 'desc')->get();
        if ($jobs->isNotEmpty()) {
            if (!$this->selectedJobId) {
                $this->selectedJobId = $jobs->first()->id;
            }
            $this->loadFields();
        }
    }

    public function updatedSelectedJobId()
    {
        $this->loadFields();
        $this->saveMessage = '';
    }

    public function loadFields()
    {
        if (!$this->selectedJobId) {
            $this->fields = [];
            $this->responsesCount = 0;
            $this->oneResponsePerPerson = false;
            $this->editingIndex = null;
            return;
        }
        $this->selectedJob = Job::find($this->selectedJobId);
        $raw = $this->selectedJob?->custom_fields ?? '[]';
        $this->fields = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
        
        if (empty($this->fields)) {
            $this->fields = [
                ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'IDENTITAS DIRI', 'description' => ''],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nama Lengkap', 'required' => true],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Email', 'required' => true],
                ['id' => uniqid('field_'), 'type' => 'date', 'label' => 'Tanggal lahir', 'required' => false],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nomor telepon', 'required' => true],
            ];
            $this->saveForm(); // Save the seeded fields immediately
        }
        
        $this->oneResponsePerPerson = (bool) $this->selectedJob?->one_response_per_person;
        $this->deadlineDate = $this->selectedJob?->deadline_date?->format('Y-m-d');
        $this->closedMessage = $this->selectedJob?->closed_message ?? 'Lowongan ini telah ditutup dan tidak lagi menerima lamaran.';
        
        $this->jobTitle = $this->selectedJob?->title ?? '';
        $this->jobDescription = $this->selectedJob?->description ?? '';
        
        $this->responsesCount = \App\Models\Application::where('job_id', $this->selectedJobId)->count();
        $this->editingIndex = null;
        
        $this->historyFields = [];
        $this->historyIndex = -1;
        $this->pushHistory();
    }

    public function updatedOneResponsePerPerson($value)
    {
        if ($this->selectedJobId) {
            Job::where('id', $this->selectedJobId)->update(['one_response_per_person' => $value]);
        }
    }

    public function updatedDeadlineDate($value)
    {
        if ($this->selectedJobId) {
            Job::where('id', $this->selectedJobId)->update(['deadline_date' => $value ?: null]);
        }
    }

    public function updatedClosedMessage($value)
    {
        if ($this->selectedJobId) {
            Job::where('id', $this->selectedJobId)->update(['closed_message' => $value]);
        }
    }
    
    // ── History Tracking ───────────────────────────────────────────
    public function pushHistory()
    {
        if ($this->isUndoRedo) return;
        
        if ($this->historyIndex < count($this->historyFields) - 1) {
            $this->historyFields = array_slice($this->historyFields, 0, $this->historyIndex + 1);
        }
        $this->historyFields[] = $this->fields;
        $this->historyIndex++;
        
        if (count($this->historyFields) > 20) {
            array_shift($this->historyFields);
            $this->historyIndex--;
        }
    }

    public function undo()
    {
        if ($this->historyIndex > 0) {
            $this->isUndoRedo = true;
            $this->historyIndex--;
            $this->fields = $this->historyFields[$this->historyIndex];
            $this->isUndoRedo = false;
            $this->saveForm();
        }
    }

    public function redo()
    {
        if ($this->historyIndex < count($this->historyFields) - 1) {
            $this->isUndoRedo = true;
            $this->historyIndex++;
            $this->fields = $this->historyFields[$this->historyIndex];
            $this->isUndoRedo = false;
            $this->saveForm();
        }
    }

    public function updateThemeColor($color)
    {
        $this->primaryColor = $color;
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        $key = 'PRIMARY_COLOR';
        if (preg_match('/^' . $key . '=/m', $str)) {
            $str = preg_replace('/^' . $key . '=.*/m', $key . '=' . $color, $str);
        } else {
            $str .= "\n" . $key . '=' . $color;
        }
        file_put_contents($envFile, $str);
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $this->dispatch('theme-updated', ['color' => $color]);
    }

    // ── Inline Edit Helpers ────────────────────────────────────────
    
    public function loadStandardTemplate()
    {
        $this->fields = [
            // IDENTITAS DIRI
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'IDENTITAS DIRI', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nama Lengkap', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Email', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'date', 'label' => 'Tanggal lahir', 'required' => false],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nomor telepon', 'required' => true],

            // PENDIDIKAN DAN REGISTRASI
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'PENDIDIKAN DAN REGISTRASI', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'radio', 'label' => 'Pendidikan Terakhir', 'required' => true, 'options' => ['D3', 'D4', 'S1', 'S2']],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Jurusan', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Universitas', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Tahun Lulus', 'required' => true],

            // PENGALAMAN KERJA
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'PENGALAMAN KERJA', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'checkbox', 'label' => 'Riwayat Pekerjaan', 'required' => true, 'options' => ['Administrasi Sumber Daya Manusia', 'HR Generalist', 'Fresh Graduate']],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Deskripsi singkat pengalaman kerja', 'required' => false],

            // DOKUMEN PENDUKUNG
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'DOKUMEN PENDUKUNG', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan Upload CV dan Surat lamaran', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan Upload Ijazah dan Transkrip nilai', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan upload berkas pendukung lainnya (Motivation letter, Pelatihan, dll)', 'required' => false],
        ];
        $this->editingIndex = null;
        $this->pushHistory();
        $this->saveForm();
        $this->dispatch('notify', ['message' => 'Template Standar berhasil dimuat!', 'type' => 'success']);
    }

    public function addBlankField()
    {
        $this->fields[] = [
            'id'       => uniqid('field_'),
            'type'     => 'text',
            'label'    => '',
            'required' => false,
            'options'  => [],
        ];
        $this->editingIndex = count($this->fields) - 1;
        $this->pushHistory();
        $this->dispatch('notify', 'Pertanyaan baru ditambahkan');
    }

    public function addTitleField()
    {
        $this->fields[] = [
            'id'       => uniqid('field_'),
            'type'     => 'title',
            'label'    => '',
            'description' => '',
            'required' => false,
            'options'  => [],
        ];
        $this->editingIndex = count($this->fields) - 1;
        $this->pushHistory();
        $this->dispatch('notify', 'Judul / Teks ditambahkan');
    }

    public function addImageField()
    {
        $this->fields[] = [
            'id'       => uniqid('field_'),
            'type'     => 'image',
            'label'    => '',
            'url'      => '',
            'required' => false,
            'options'  => [],
        ];
        $this->editingIndex = count($this->fields) - 1;
        $this->pushHistory();
        $this->dispatch('notify', 'Gambar ditambahkan');
    }

    public function addVideoField()
    {
        $this->fields[] = [
            'id'       => uniqid('field_'),
            'type'     => 'video',
            'label'    => '',
            'url'      => '',
            'required' => false,
            'options'  => [],
        ];
        $this->editingIndex = count($this->fields) - 1;
        $this->pushHistory();
        $this->dispatch('notify', 'Video ditambahkan');
    }

    public function addSectionField()
    {
        $this->fields[] = [
            'id'       => uniqid('field_'),
            'type'     => 'section',
            'label'    => 'Bagian Baru',
            'description' => '',
            'required' => false,
            'options'  => [],
        ];
        $this->editingIndex = count($this->fields) - 1;
        $this->pushHistory();
        $this->dispatch('notify', 'Bagian (Section) baru ditambahkan');
    }

    public function generateStaticTemplate()
    {
        $this->fields = [
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'IDENTITAS DIRI', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nama Lengkap', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Email', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'date', 'label' => 'Tanggal lahir', 'required' => false],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nomor telepon', 'required' => true],
            
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'PENDIDIKAN DAN REGISTRASI', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'radio', 'label' => 'Pendidikan Terakhir', 'required' => true, 'options' => ['D3', 'D4', 'S1', 'S2']],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Jurusan', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Universitas', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Tahun Lulus', 'required' => true],

            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'PENGALAMAN KERJA', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'checkbox', 'label' => 'Riwayat Pekerjaan', 'required' => true, 'options' => ['Administrasi Sumber Daya Manusia', 'HR Generalist', 'Fresh Graduate']],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Deskripsi singkat pengalaman kerja', 'required' => false],
            
            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'DOKUMEN PENDUKUNG', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan Upload CV dan Surat lamaran', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan Upload Ijazah dan Transkrip nilai', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'file', 'label' => 'Silakan upload berkas pendukung lainnya (Motivation letter, Pelatihan, dll)', 'required' => false],

            ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'LAINNYA', 'description' => ''],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Boleh ceritakan pengalaman Anda selama ini? Hal apa yang paling berat / dirasa paling memuaskan dalam pekerjaan Anda?', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Seberapa teliti Anda mengenai masalah kebersihan? Apakah pengalaman sebelumnya di fasilitas kesehatan?', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Masalah apa yang paling sering Anda temui? Misalnya, bagian apa dari kantor yang biasanya paling kotor / paling sering terlewat untuk dibersihkan?', 'required' => true],
            ['id' => uniqid('field_'), 'type' => 'textarea', 'label' => 'Apakah Anda bisa mengendarai motor? Punya motor? Apakah Anda bersedia untuk terkadang membantu pengiriman logistik/sampel/belanja jika sewaktu-waktu dibutuhkan?', 'required' => true],
        ];
        
        $this->pushHistory();
        $this->aiModalOpen = false;
        $this->dispatch('notify', '✨ Template Standar HR berhasil di-generate! Silakan edit dan klik "Simpan Form".');
    }

    public function generateAITemplate()
    {
        if (empty(trim($this->aiPrompt))) {
            $this->addError('aiPrompt', 'Prompt instruksi tidak boleh kosong.');
            return;
        }

        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            $this->dispatch('notify', 'Kunci API Gemini belum dikonfigurasi di server (.env).');
            return;
        }

        $systemInstruction = "You are an expert HR form builder. Your task is to generate a JSON array of form fields based on the user's prompt. 
Each field is an object. Available types: 'section', 'title', 'text', 'textarea', 'date', 'file', 'radio', 'checkbox', 'select'.
Fields like text, textarea, date, file can have 'label' (string) and 'required' (boolean).
Fields like radio, checkbox, select must have 'label' (string), 'required' (boolean), and 'options' (array of strings).
Fields like section and title must have 'label' (string) and optionally 'description' (string).
Output MUST be a valid JSON array only, without markdown wrapping or backticks.
Always include an 'id' for each field using a unique string (e.g. 'field_xxx').
Start the form with standard 'Identitas Diri' fields if appropriate for the prompt.
Example output format:
[
    {\"id\": \"field_1a\", \"type\": \"section\", \"label\": \"IDENTITAS DIRI\", \"description\": \"\"},
    {\"id\": \"field_1b\", \"type\": \"text\", \"label\": \"Nama Lengkap\", \"required\": true}
]";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-latest:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $this->aiPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                
                if ($content) {
                    // Extract only the JSON array part from the response
                    preg_match('/\[.*\]/s', $content, $matches);
                    if (!empty($matches[0])) {
                        $jsonContent = $matches[0];
                        $parsed = json_decode($jsonContent, true);
                        if (is_array($parsed)) {
                            $this->fields = $parsed;
                            $this->pushHistory();
                            $this->aiModalOpen = false;
                            $this->aiPrompt = '';
                            $this->dispatch('notify', '✨ Form berhasil dirancang oleh AI! Silakan periksa dan Simpan.');
                            return;
                        }
                    }
                }
            }
            
            $errorDetail = $response->json('error.message') ?? $response->body();
            \Log::error('Gemini API Error: ' . $response->body());
            $this->dispatch('notify', 'Error API: ' . substr($errorDetail, 0, 100));
        } catch (\Exception $e) {
            \Log::error('Gemini API Exception: ' . $e->getMessage());
            $this->dispatch('notify', 'Terjadi kesalahan sistem saat menghubungi server AI: ' . substr($e->getMessage(), 0, 100));
        }
    }

    public function openImportModal()
    {
        $this->availableJobsForImport = Job::where('id', '!=', $this->selectedJobId)->orderBy('created_at', 'desc')->get();
        $this->importModalOpen = true;
    }

    public function importQuestions($fromJobId)
    {
        $job = Job::find($fromJobId);
        if ($job) {
            $raw = $job->custom_fields ?? '[]';
            $importedFields = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
            
            // Generate new IDs for imported fields to prevent collision
            foreach ($importedFields as &$field) {
                $field['id'] = uniqid('field_');
            }
            
            $this->fields = array_merge($this->fields, $importedFields);
            $this->importModalOpen = false;
            $this->saveMessage = 'Pertanyaan berhasil diimpor!';
            $this->pushHistory();
            $this->saveForm(); // auto save
        }
    }

    public function editField($index)
    {
        $this->editingIndex = $index;
    }

    public function addOption($fieldIndex)
    {
        $opt = trim($this->newOptionText);
        if ($opt) {
            if (!isset($this->fields[$fieldIndex]['options'])) {
                $this->fields[$fieldIndex]['options'] = [];
            }
            if (!in_array($opt, $this->fields[$fieldIndex]['options'])) {
                $this->fields[$fieldIndex]['options'][] = $opt;
            }
        }
        $this->newOptionText = '';
        $this->pushHistory();
    }

    public function removeOption($fieldIndex, $optIndex)
    {
        if (isset($this->fields[$fieldIndex]['options'])) {
            array_splice($this->fields[$fieldIndex]['options'], $optIndex, 1);
            $this->pushHistory();
        }
    }

    public function removeField($index)
    {
        array_splice($this->fields, $index, 1);
        if ($this->editingIndex === $index) {
            $this->editingIndex = null;
        } elseif ($this->editingIndex > $index) {
            $this->editingIndex--;
        }
        $this->pushHistory();
    }

    public function duplicateField($index)
    {
        if (isset($this->fields[$index])) {
            $clonedField = $this->fields[$index];
            $clonedField['id'] = uniqid('field_'); // Give it a new unique ID
            $clonedField['label'] = $clonedField['label'] . ' (Copy)';
            
            // Insert the cloned field right after the duplicated one
            array_splice($this->fields, $index + 1, 0, [$clonedField]);
            
            // Adjust editing index if necessary
            if ($this->editingIndex !== null && $this->editingIndex > $index) {
                $this->editingIndex++;
            }
            $this->pushHistory();
        }
    }

    // ── Move field up/down/reorder ─────────────────────────────────
    public function moveUp($index)
    {
        if ($index > 0) {
            [$this->fields[$index - 1], $this->fields[$index]] =
                [$this->fields[$index], $this->fields[$index - 1]];
            $this->pushHistory();
        }
    }

    public function moveDown($index)
    {
        if ($index < count($this->fields) - 1) {
            [$this->fields[$index], $this->fields[$index + 1]] =
                [$this->fields[$index + 1], $this->fields[$index]];
        }
    }

    public function reorder($oldIndex, $newIndex)
    {
        if ($oldIndex === null || $newIndex === null || $oldIndex === $newIndex) return;
        
        $item = $this->fields[$oldIndex];
        array_splice($this->fields, $oldIndex, 1);
        array_splice($this->fields, $newIndex, 0, [$item]);
        
        $this->editingIndex = null;
    }

    // ── Save to DB ────────────────────────────────────────────────
    public function toggleEmailNotifications()
    {
        $job = Job::find($this->selectedJobId);
        if ($job) {
            $settingKey = 'job_'.$job->id.'_email_notif';
            $currentSetting = \App\Models\Setting::where('key', $settingKey)->first();
            $newValue = $currentSetting && $currentSetting->value === '1' ? '0' : '1';
            
            \App\Models\Setting::updateOrCreate(
                ['key' => $settingKey],
                ['value' => $newValue]
            );
            
            $msg = $newValue === '1' ? 'Notifikasi email untuk lamaran baru DIAKTIFKAN.' : 'Notifikasi email untuk lamaran baru DINONAKTIFKAN.';
            $this->dispatch('notify', ['message' => $msg, 'type' => 'success']);
        }
    }

    public function exportExcel()
    {
        if (!class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            $this->dispatch('notify', 'Library Excel belum siap, muat ulang halaman.');
            return;
        }

        $fileName = 'kandidat-' . date('Y-m-d') . '.xlsx';
        $options = new \OpenSpout\Writer\XLSX\Options();
        $writer = new \OpenSpout\Writer\XLSX\Writer($options);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->openToFile($tempFile);
        
        $googleService = new \App\Services\GoogleSheetsService();
        $job = Job::find($this->selectedJobId);
        if (!$job) return;

        $applications = Application::with(['candidate', 'job', 'stage', 'notes'])->where('job_id', $job->id)->get();
        $sheet = $writer->getCurrentSheet();
        $sheetName = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $job->title), 0, 31) ?: 'Sheet1';
        $sheet->setName($sheetName);
        
        $csvHeaders = $googleService->getHeaders($job);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge(['ID', 'Departemen'], $csvHeaders)));
        
        foreach ($applications as $app) {
            $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge([$app->id, $app->job->department ?? '-'], $row)));
        }

        $writer->close();
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportCsv()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=candidates-" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            $googleService = new \App\Services\GoogleSheetsService();
            $job = Job::find($this->selectedJobId);
            if (!$job) return;

            $applications = Application::with(['candidate', 'job', 'stage', 'notes'])->where('job_id', $job->id)->get();
            $csvHeaders = $googleService->getHeaders($job);
            fputcsv($file, array_merge(['ID', 'Departemen'], $csvHeaders), ';');
            
            foreach ($applications as $app) {
                $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
                fputcsv($file, array_merge([$app->id, $app->job->department ?? '-'], $row), ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'candidates-' . date('Y-m-d') . '.csv', $headers);
    }
    
    public function unlinkGoogleSheets()
    {
        $job = Job::find($this->selectedJobId);
        if ($job) {
            $job->google_spreadsheet_id = null;
            $job->save();
            $this->dispatch('notify', 'Google Sheets form telah diputuskan (Unlinked).');
        }
    }
    
    public function deleteAllResponses()
    {
        $job = Job::find($this->selectedJobId);
        if ($job) {
            Application::where('job_id', $job->id)->delete();
            $this->dispatch('notify', 'Seluruh response telah dihapus secara permanen.');
            // Refresh response count
            $this->responsesCount = 0;
        }
    }
    public function syncToGoogleSheets()
    {
        if (!$this->selectedJobId) return;
        $job = Job::find($this->selectedJobId);
        if (!$job || !$job->google_spreadsheet_id) return;
        
        try {
            $service = new \App\Services\GoogleSheetsService();
            $service->syncAllCandidatesToSheet($job);
            // After sync, redispatch sweetalert so user can open or re-sync
            $this->dispatch('show-sheets-sweetalert', [
                'url' => 'https://docs.google.com/spreadsheets/d/' . $job->google_spreadsheet_id,
                'syncSuccess' => true,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    public function openGoogleSheets()
    {
        if ($this->selectedJobId) {
            $job = Job::find($this->selectedJobId);
            if ($job && $job->google_spreadsheet_id) {
                $url = 'https://docs.google.com/spreadsheets/d/' . $job->google_spreadsheet_id;
                $this->dispatch('show-sheets-sweetalert', ['url' => $url]);
            }
        }
    }
    public function saveForm()
    {
        if ($this->selectedJobId) {
            Job::where('id', $this->selectedJobId)->update([
                'title' => $this->jobTitle,
                'description' => $this->jobDescription,
                'custom_fields' => json_encode($this->fields),
                'one_response_per_person' => $this->oneResponsePerPerson
            ]);
            $this->saveMessage = 'Form berhasil disimpan!';
            
            // Dispatch event to open the preview in a new tab
            $jobInstance = Job::find($this->selectedJobId);
            $this->dispatch('form-saved', route('jobs.apply', $jobInstance ?: $this->selectedJobId));
        }
    }

    public function with()
    {
        return [
            'jobs' => Job::orderBy('created_at', 'desc')->get(),
        ];
    }
};
?>

<div class="flex-1 overflow-y-auto h-[calc(100vh-64px)] flex flex-col bg-slate-50"
     x-data="{}"
     @keydown.window.ctrl.z.exact="if(!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $wire.undo(); }"
     @keydown.window.ctrl.y.exact="if(!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $wire.redo(); }"
     @keydown.window.ctrl.shift.z.exact="if(!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $wire.redo(); }"
     @keydown.window.meta.z.exact="if(!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $wire.undo(); }"
     @keydown.window.meta.shift.z.exact="if(!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { $event.preventDefault(); $wire.redo(); }">

    @if(!$selectedJobId)
    {{-- Default View when no job selected --}}
    <div class="p-margin max-w-2xl mx-auto w-full mt-10">
        <div class="text-center mb-10">
            <h2 class="font-headline-lg text-headline-lg text-on-background">Pilih Lowongan</h2>
            <p class="text-on-surface-variant mt-2">Silakan pilih lowongan terlebih dahulu untuk mengelola form, melihat jawaban, dan mengatur setting.</p>
        </div>
        <div class="bg-surface-bg border border-surface-border rounded-xl p-stack-xl shadow-sm">
            <select wire:model.live="selectedJobId"
                class="w-full bg-surface-container-low border border-surface-border rounded-lg px-4 py-4 text-on-surface focus:ring-2 focus:ring-primary transition-all text-lg font-semibold cursor-pointer">
                <option value="">— Pilih Lowongan —</option>
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->title }} ({{ $job->department ?? '-' }}) — {{ $job->status }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @else

    {{-- Google Forms Style Top Nav --}}
    <div class="bg-surface-bg border-b border-surface-border sticky top-0 z-30 pt-4 px-4 flex flex-col shadow-sm">
        <div class="w-full flex items-center justify-between mb-4 px-2 lg:px-6">
            <div class="flex-1">
                @php $activeJob = $jobs->firstWhere('id', $selectedJobId); @endphp
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.jobs.index') }}" wire:navigate class="text-secondary hover:bg-surface-container rounded-full p-2 transition-colors flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    </a>
                    <h1 class="font-headline-sm text-headline-sm text-on-surface truncate font-semibold">{{ $selectedJob->title ?? 'Form Lamaran' }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-1 sm:gap-2" x-data="{ menuOpen: false, publishModalOpen: false, themeModalOpen: false }">
                <button @click="themeModalOpen = true" class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center" title="Customize Theme">
                    <span class="material-symbols-outlined text-[20px]">palette</span>
                </button>
                <a href="{{ route('jobs.apply', \App\Models\Job::find($selectedJobId)) }}" target="_blank" title="Preview"
                   class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                </a>
                <button wire:click="undo" @if($historyIndex <= 0) disabled @endif class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed" title="Undo">
                    <span class="material-symbols-outlined text-[20px]">undo</span>
                </button>
                <button wire:click="redo" @if($historyIndex >= count($historyFields) - 1) disabled @endif class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed" title="Redo">
                    <span class="material-symbols-outlined text-[20px]">redo</span>
                </button>
                <button @click="Swal.fire({title: 'Link Form Pendaftaran', input: 'text', inputValue: '{{ route('jobs.apply', \App\Models\Job::find($selectedJobId)) }}', customClass: {input: 'bg-surface-container-low border-surface-border text-on-surface'}, confirmButtonText: 'Tutup', confirmButtonColor: 'var(--color-primary, #005bbf)'})" 
                   class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center" title="Get link">
                    <span class="material-symbols-outlined text-[20px]">link</span>
                </button>
                <button class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center" title="Add collaborators" onclick="alert('Fitur kolaborator sedang dalam tahap pengembangan.')">
                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                </button>
                
                <button @click="publishModalOpen = true" 
                   class="ml-2 inline-flex items-center gap-2 px-6 py-2 bg-[#673ab7] hover:bg-[#5e35b1] text-white rounded-md font-semibold text-sm transition-all shadow-sm">
                    Publish
                </button>
                
                {{-- Publish Modal --}}
                <div x-show="publishModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                    <div @click.outside="publishModalOpen = false" class="bg-surface-bg border border-surface-border rounded-xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col mx-4 relative" style="max-height: 90vh;">
                        
                        {{-- Modal Header --}}
                        <div class="px-6 py-5 border-b border-surface-border flex items-center justify-between">
                            <h2 class="text-xl font-normal text-on-surface">Publish form</h2>
                        </div>
                        
                        {{-- Modal Body --}}
                        <div class="p-6">
                            <div class="flex gap-4">
                                <span class="material-symbols-outlined text-secondary text-[24px]">person_add</span>
                                <div class="flex-1">
                                    <h3 class="text-on-surface font-semibold mb-3">Responders</h3>
                                    
                                    <div class="flex items-start justify-between gap-4 bg-surface-container-low p-3 rounded-lg">
                                        <div class="flex gap-3">
                                            <div class="w-8 h-8 rounded-full bg-success/20 flex items-center justify-center text-success flex-shrink-0 mt-0.5">
                                                <span class="material-symbols-outlined text-[18px]">public</span>
                                            </div>
                                            <div>
                                                <p class="text-on-surface text-sm font-medium">Anyone with the link</p>
                                                <a href="#" class="text-primary text-sm hover:underline">Restrict to 'Admin Only'</a>
                                            </div>
                                        </div>
                                        <button class="px-4 py-1.5 border border-surface-border rounded-full text-sm font-medium text-primary hover:bg-surface-container transition-colors">
                                            Manage
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="h-px bg-surface-border my-6 ml-10"></div>
                            
                            <div class="flex gap-4 items-center">
                                <span class="material-symbols-outlined text-secondary text-[24px]">notifications_off</span>
                                <p class="text-on-surface text-sm">Nobody will be notified when publishing the form</p>
                            </div>
                        </div>
                        
                        {{-- Modal Footer --}}
                        <div class="px-6 py-4 flex items-center justify-end gap-2 border-t border-surface-border bg-surface-container-lowest">
                            <button @click="publishModalOpen = false" class="px-4 py-2 text-primary hover:bg-surface-container rounded-md font-medium text-sm transition-colors">
                                Dismiss
                            </button>
                            <button @click="Swal.fire({title: 'Link Form Pendaftaran', input: 'text', inputValue: '{{ route('jobs.apply', \App\Models\Job::find($selectedJobId)) }}', customClass: {input: 'bg-surface-container-low border-surface-border text-on-surface'}, confirmButtonText: 'Tutup', confirmButtonColor: 'var(--color-primary, #005bbf)'}); publishModalOpen = false;" class="px-6 py-2 bg-[#007b5e] hover:bg-[#00664d] text-white rounded-md font-medium text-sm transition-colors">
                                Publish
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Theme Modal --}}
                <div x-show="themeModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                    <div @click.outside="themeModalOpen = false" class="bg-surface-bg border border-surface-border rounded-xl shadow-xl w-full max-w-sm overflow-hidden flex flex-col mx-4">
                        <div class="px-6 py-4 border-b border-surface-border flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-on-surface flex items-center gap-2">
                                <span class="material-symbols-outlined">palette</span> Tema Form
                            </h2>
                            <button @click="themeModalOpen = false" class="text-secondary hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-secondary mb-4">Pilih warna utama form ini. (Warna ini akan mengubah tema utama sistem).</p>
                            
                            <div class="grid grid-cols-5 gap-3 mb-4">
                                @php
                                    $colors = [
                                        '#005bbf' => 'Biru',
                                        '#673ab7' => 'Ungu',
                                        '#007b5e' => 'Hijau',
                                        '#d93025' => 'Merah',
                                        '#f29900' => 'Kuning',
                                        '#e91e63' => 'Pink',
                                        '#00bcd4' => 'Cyan',
                                        '#ff5722' => 'Oranye',
                                        '#607d8b' => 'Biru Abu',
                                        '#333333' => 'Gelap'
                                    ];
                                @endphp
                                @foreach($colors as $hex => $name)
                                    <button wire:click="updateThemeColor('{{ $hex }}')" title="{{ $name }}"
                                        class="w-10 h-10 rounded-full border-2 transition-transform hover:scale-110 flex items-center justify-center"
                                        :class="$wire.primaryColor === '{{ $hex }}' ? 'border-primary shadow-md scale-110' : 'border-transparent'"
                                        style="background-color: {{ $hex }}">
                                        <span x-show="$wire.primaryColor === '{{ $hex }}'" class="material-symbols-outlined text-white text-[20px]">check</span>
                                    </button>
                                @endforeach
                            </div>
                            
                            <div class="flex items-center gap-3 mt-6">
                                <input type="color" wire:model.live="primaryColor" wire:change="updateThemeColor($event.target.value)" class="w-10 h-10 rounded cursor-pointer border-0 p-0">
                                <span class="text-sm text-on-surface font-medium">Custom Color</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- More Options Dropdown --}}
                <div class="relative ml-1">
                    <button @click="menuOpen = !menuOpen" @click.outside="menuOpen = false" 
                        class="p-2 text-secondary hover:bg-surface-container hover:text-on-surface rounded-full transition-colors flex items-center justify-center" title="More">
                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                    </button>
                    
                    <div x-show="menuOpen" x-transition.opacity.duration.200ms style="display: none;"
                        class="absolute right-0 top-full mt-1 w-56 bg-surface-bg border border-surface-border rounded-lg shadow-lg py-2 z-50">
                        <button onclick="alert('Fitur salin form sedang dalam pengembangan.')" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">content_copy</span> Make a copy
                        </button>
                        <button onclick="alert('Fitur hapus form sedang dalam pengembangan.')" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">delete</span> Move to trash
                        </button>
                        <div class="h-px bg-surface-border my-2"></div>
                        <button onclick="alert('Fitur Pre-fill form sedang dalam pengembangan.')" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">edit_document</span> Pre-fill form
                        </button>
                        <button onclick="alert('Fitur Embed HTML sedang dalam pengembangan.')" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">code</span> Embed HTML
                        </button>
                        <button onclick="window.print()" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">print</span> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex justify-center gap-2 pb-4">
            <button wire:click="$set('currentTab', 'questions')"
                class="px-6 py-2 text-sm font-medium transition-all rounded-full border 
                {{ $currentTab === 'questions' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface-bg text-secondary border-surface-border hover:bg-surface-container' }}">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">format_list_bulleted</span> Questions</div>
            </button>
            <button wire:click="$set('currentTab', 'responses')"
                class="px-6 py-2 text-sm font-medium transition-all rounded-full border flex items-center gap-2
                {{ $currentTab === 'responses' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface-bg text-secondary border-surface-border hover:bg-surface-container' }}">
                <span class="material-symbols-outlined text-[18px]">analytics</span> Responses
                <span class="px-2 py-0.5 rounded-full text-[11px] leading-none {{ $currentTab === 'responses' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">{{ $responsesCount }}</span>
            </button>
            <button wire:click="$set('currentTab', 'settings')"
                class="px-6 py-2 text-sm font-medium transition-all rounded-full border
                {{ $currentTab === 'settings' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface-bg text-secondary border-surface-border hover:bg-surface-container' }}">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">settings</span> Settings</div>
            </button>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="p-margin max-w-6xl mx-auto w-full flex-1 flex gap-8">
        
        @if($currentTab === 'questions')
        
        {{-- Left Sidebar Toolbox (ATS Style instead of Google Forms right floating) --}}
        <div class="hidden xl:block w-64 flex-shrink-0 pt-4">
            <div class="bg-white border border-surface-border shadow-sm rounded-xl p-4 sticky top-[140px]">
                <h3 class="text-xs font-bold text-secondary uppercase tracking-wider mb-4">Form Elements</h3>
                <div class="flex flex-col gap-2">
                    <button wire:click="addBlankField" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">add_box</span> Add Question
                    </button>
                    <button wire:click="addTitleField" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">title</span> Add Text Block
                    </button>
                    <button wire:click="addSectionField" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">view_agenda</span> Add Section
                    </button>
                    <button wire:click="openImportModal" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">upload_file</span> Import Fields
                    </button>
                    <button type="button" @click="$dispatch('show-template-confirm')" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">dynamic_form</span> Standard Template
                    </button>
                    
                    <div class="h-px bg-surface-border my-2"></div>
                    
                    <h3 class="text-xs font-bold text-secondary uppercase tracking-wider mb-2 mt-2">Media</h3>
                    <button wire:click="addImageField" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">image</span> Add Image
                    </button>
                    <button wire:click="addVideoField" class="w-full px-4 py-2 text-sm text-left text-on-surface hover:text-primary hover:bg-primary/5 rounded-lg border border-transparent hover:border-primary/20 transition-all flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px] text-primary">smart_display</span> Add Video
                    </button>
                    
                    <div class="h-px bg-surface-border my-2"></div>
                    
                    <button @click="$wire.set('aiModalOpen', true)" class="w-full mt-2 px-4 py-2.5 text-sm text-center text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 rounded-lg shadow-sm transition-all flex items-center justify-center gap-2 font-medium">
                        <span class="material-symbols-outlined text-[18px]">auto_awesome</span> Generate with AI
                    </button>
                </div>
            </div>
        </div>

        {{-- TAB: QUESTIONS (Main Canvas) --}}
        <div class="flex-1 max-w-3xl w-full mt-4 space-y-6 relative pb-24">

            {{-- Form Header (Editable) - Modern ATS Card Style --}}
            <div class="bg-white border border-surface-border rounded-xl shadow-sm overflow-hidden mb-6 relative">
                <div class="p-8">
                    <input type="text" wire:model.blur="jobTitle" class="w-full font-headline-md text-headline-md font-bold text-on-surface mb-3 bg-transparent border-none focus:ring-0 p-0 focus:outline-none" placeholder="Job Title / Form Name">
                    <textarea wire:model.blur="jobDescription" class="w-full text-on-surface-variant bg-transparent border-none focus:ring-0 p-0 resize-none focus:outline-none leading-relaxed" rows="3" placeholder="Add a description for this application form..."></textarea>
                </div>
            </div>



            {{-- Form Fields Builder Loop --}}
            <div x-data="{ draggingIndex: null, dragOverIndex: null }">
            @forelse($fields as $i => $field)
                <div x-data="{ isDraggable: false }"
                     :draggable="isDraggable"
                     @dragstart="draggingIndex = {{ $i }}; $event.dataTransfer.effectAllowed = 'move';"
                     @dragenter.prevent="if(draggingIndex !== null) dragOverIndex = {{ $i }}"
                     @dragover.prevent
                     @drop.prevent="if(draggingIndex !== null && dragOverIndex !== null) { $wire.reorder(draggingIndex, dragOverIndex); } draggingIndex = null; dragOverIndex = null; isDraggable = false;"
                     @dragend="draggingIndex = null; dragOverIndex = null; isDraggable = false;"
                     :class="{ 'opacity-40 scale-[0.98]': draggingIndex === {{ $i }}, 'border-t-2 border-primary': dragOverIndex === {{ $i }} && draggingIndex !== {{ $i }}, 'pt-2': dragOverIndex === {{ $i }} && draggingIndex !== {{ $i }} }"
                     class="transition-all duration-200 mb-4 group/field">
                    
                    @if($editingIndex === $i)
                        {{-- EDIT MODE (Active Card) --}}
                        <div @click.outside="$set('editingIndex', null)" class="bg-white border-2 border-primary/20 rounded-xl shadow-sm p-6 relative transition-all z-10" id="card-{{$i}}">
                            
                            {{-- Drag handle --}}
                            <div @mousedown="isDraggable = true" @mouseup="isDraggable = false" @mouseleave="isDraggable = false" 
                                 class="absolute top-2 left-1/2 -translate-x-1/2 text-surface-border hover:text-secondary cursor-move flex items-center justify-center py-1 select-none">
                                <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
                            </div>

                            <div class="flex flex-col md:flex-row items-start gap-4 mb-4 mt-4">
                                <div class="flex-1 w-full bg-surface-container-lowest border-b border-surface-border focus-within:border-primary focus-within:border-b-2 transition-all p-4 rounded-t-md group relative">
                                    <input type="text" wire:model.live.debounce.300="fields.{{ $i }}.label" placeholder="Pertanyaan" class="w-full font-body-lg text-body-lg bg-transparent border-none focus:ring-0 p-0 mb-1">
                                    
                                    @if(in_array(($fields[$i]['type'] ?? 'text'), ['title', 'section']))
                                        <input type="text" wire:model.live.debounce.300="fields.{{ $i }}.description" placeholder="Deskripsi (opsional)" class="w-full text-sm bg-transparent border-none focus:ring-0 p-0 mt-2 text-secondary">
                                    @endif
                                    @if(in_array(($fields[$i]['type'] ?? 'text'), ['image', 'video']))
                                        <input type="text" wire:model.live.debounce.300="fields.{{ $i }}.url" placeholder="Masukkan URL {{ ($fields[$i]['type'] == 'image') ? 'Gambar' : 'YouTube Video' }}" class="w-full text-sm bg-transparent border-none focus:ring-0 p-0 mt-2 text-primary font-mono bg-surface-container-low px-2 py-1 rounded">
                                    @endif
                                    
                                    {{-- Mock formatting toolbar --}}
                                    <div class="hidden group-focus-within:flex items-center gap-2 text-secondary border-t border-surface-border pt-3 mt-2">
                                        <button onclick="alert('Fitur format Rich-text sedang dalam pengembangan.')" class="p-1 hover:bg-surface-container rounded font-bold w-6 h-6 flex items-center justify-center">B</button>
                                        <button onclick="alert('Fitur format Rich-text sedang dalam pengembangan.')" class="p-1 hover:bg-surface-container rounded italic w-6 h-6 flex items-center justify-center">I</button>
                                        <button onclick="alert('Fitur format Rich-text sedang dalam pengembangan.')" class="p-1 hover:bg-surface-container rounded underline w-6 h-6 flex items-center justify-center">U</button>
                                        <button onclick="alert('Fitur format Rich-text sedang dalam pengembangan.')" class="p-1 hover:bg-surface-container rounded w-6 h-6 flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">link</span></button>
                                        <button onclick="alert('Fitur format Rich-text sedang dalam pengembangan.')" class="p-1 hover:bg-surface-container rounded w-6 h-6 flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">format_clear</span></button>
                                    </div>
                                    
                                    {{-- Image icon --}}
                                    <div onclick="alert('Fitur tambah gambar sedang dalam tahap pengembangan.')" class="absolute right-3 top-3 text-secondary hover:bg-surface-container p-2 rounded-full cursor-pointer transition-colors" title="Add image">
                                        <span class="material-symbols-outlined text-[22px]">image</span>
                                    </div>
                                </div>
                                
                                {{-- Field Type Dropdown (AlpineJS for custom options) --}}
                                <div x-data="{ open: false }" class="relative w-full md:w-56 mt-2 md:mt-0 flex-shrink-0">
                                    <button @click="open = !open" @click.outside="open = false" class="w-full bg-surface-bg border border-surface-border rounded-md px-4 py-3 text-sm focus:ring-2 focus:ring-primary flex items-center justify-between hover:bg-surface-container-lowest transition-colors shadow-sm">
                                        <div class="flex items-center gap-3 text-on-surface">
                                            @php
                                                $currType = $fields[$i]['type'] ?? 'text';
                                                $typeIcon = match($currType) {
                                                    'textarea' => 'notes',
                                                    'radio' => 'radio_button_checked',
                                                    'checkbox' => 'check_box',
                                                    'select' => 'arrow_drop_down_circle',
                                                    'file' => 'cloud_upload',
                                                    'date' => 'calendar_today',
                                                    'number' => 'pin',
                                                    default => 'short_text'
                                                };
                                                $typeLabel = match($currType) {
                                                    'textarea' => 'Paragraph',
                                                    'radio' => 'Multiple choice',
                                                    'checkbox' => 'Checkboxes',
                                                    'select' => 'Dropdown',
                                                    'file' => 'File upload',
                                                    'date' => 'Date',
                                                    'number' => 'Number',
                                                    'title' => 'Title and description',
                                                    'image' => 'Image',
                                                    'video' => 'Video',
                                                    'section' => 'Section',
                                                    default => 'Short answer'
                                                };
                                            @endphp
                                            <span class="material-symbols-outlined text-secondary text-[22px]">{{ $typeIcon }}</span>
                                            <span class="font-semibold">{{ $typeLabel }}</span>
                                        </div>
                                        <span class="material-symbols-outlined text-secondary text-[22px]">arrow_drop_down</span>
                                    </button>
                                    
                                    <div x-show="open" x-transition.opacity.duration.200ms style="display: none;" class="absolute right-0 top-full mt-1 w-64 bg-surface-bg border border-surface-border rounded-lg shadow-lg py-2 z-50 max-h-80 overflow-y-auto">
                                        <div wire:click="$set('fields.{{ $i }}.type', 'text')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">short_text</span> Short answer
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'textarea')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">notes</span> Paragraph
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'radio')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">radio_button_checked</span> Multiple choice
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'checkbox')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">check_box</span> Checkboxes
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'select')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">arrow_drop_down_circle</span> Dropdown
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'file')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">cloud_upload</span> File upload
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'date')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">calendar_today</span> Date
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'title')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">match_case</span> Title and description
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'image')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">image</span> Image
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'video')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">smart_display</span> Video
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'section')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">view_stream</span> Section
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'radio')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">radio_button_checked</span> Multiple choice
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'checkbox')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">check_box</span> Checkboxes
                                        </div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'select')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">arrow_drop_down_circle</span> Dropdown
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'file')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">cloud_upload</span> File upload
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'date')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">calendar_today</span> Date
                                        </div>
                                        <div class="h-px bg-surface-border my-2"></div>
                                        <div wire:click="$set('fields.{{ $i }}.type', 'title')" @click="open = false" class="px-5 py-2 hover:bg-primary/5 cursor-pointer flex items-center gap-4 text-on-surface text-sm">
                                            <span class="material-symbols-outlined text-secondary text-[22px]">match_case</span> Title and description
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Options Editing for Radio/Checkbox/Select --}}
                            @if(in_array($fields[$i]['type'] ?? 'text', ['radio', 'checkbox', 'select']))
                                <div class="space-y-3 mb-6 ml-1 mt-6">
                                    @foreach($fields[$i]['options'] ?? [] as $optIndex => $opt)
                                        <div class="flex items-center gap-3 group">
                                            <span class="material-symbols-outlined text-surface-border text-[20px]">
                                                {{ ($fields[$i]['type'] ?? 'radio') === 'radio' ? 'radio_button_unchecked' : (($fields[$i]['type'] ?? 'checkbox') === 'checkbox' ? 'check_box_outline_blank' : 'format_list_numbered') }}
                                            </span>
                                            <input type="text" wire:model.live.debounce.300="fields.{{ $i }}.options.{{ $optIndex }}" class="flex-1 bg-transparent border-b border-transparent focus:border-surface-border hover:border-surface-border focus:ring-0 px-0 py-1 text-sm transition-colors text-on-surface">
                                            <button wire:click="removeOption({{ $i }}, {{ $optIndex }})" class="text-secondary hover:text-error opacity-0 group-hover:opacity-100 transition-opacity">
                                                <span class="material-symbols-outlined text-[20px]">close</span>
                                            </button>
                                        </div>
                                    @endforeach
                                    
                                    {{-- Add option input --}}
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-surface-border text-[20px]">
                                            {{ ($fields[$i]['type'] ?? 'radio') === 'radio' ? 'radio_button_unchecked' : (($fields[$i]['type'] ?? 'checkbox') === 'checkbox' ? 'check_box_outline_blank' : 'format_list_numbered') }}
                                        </span>
                                        <form wire:submit.prevent="addOption({{ $i }})" class="flex-1 flex gap-2 items-center">
                                            <input type="text" wire:model="newOptionText" placeholder="Add option" class="flex-1 bg-transparent border-b border-transparent focus:border-surface-border hover:border-surface-border focus:ring-0 px-0 py-1 text-sm text-secondary transition-colors outline-none">
                                            <button type="submit" class="hidden">Add</button>
                                        </form>
                                        @if(!isset($fields[$i]['allow_other']) || !$fields[$i]['allow_other'])
                                            <span class="text-secondary text-sm mx-1">atau</span>
                                            <button wire:click="$set('fields.{{ $i }}.allow_other', true)" class="text-primary text-sm font-semibold hover:underline bg-primary/10 px-3 py-1 rounded-md">tambah "Lainnya"</button>
                                        @endif
                                    </div>
                                    @if(isset($fields[$i]['allow_other']) && $fields[$i]['allow_other'])
                                        <div class="flex items-center gap-3 group mt-3">
                                            <span class="material-symbols-outlined text-surface-border text-[20px]">
                                                {{ ($fields[$i]['type'] ?? 'radio') === 'radio' ? 'radio_button_unchecked' : (($fields[$i]['type'] ?? 'checkbox') === 'checkbox' ? 'check_box_outline_blank' : 'format_list_numbered') }}
                                            </span>
                                            <span class="flex-1 px-0 py-1 text-sm text-secondary border-b border-surface-border">Lainnya... (pelamar akan mengetik sendiri)</span>
                                            <button wire:click="$set('fields.{{ $i }}.allow_other', false)" class="text-secondary hover:text-error opacity-0 group-hover:opacity-100 transition-opacity">
                                                <span class="material-symbols-outlined text-[20px]">close</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @elseif(in_array(($fields[$i]['type'] ?? 'text'), ['title', 'image', 'video', 'section']))
                                <div class="mb-2 ml-1 mt-6 hidden">
                                </div>
                            @else
                                <div class="mb-6 ml-1 mt-6">
                                    <p class="text-secondary text-sm border-b border-surface-border border-dotted inline-block pb-1">
                                        {{ ($fields[$i]['type'] ?? 'text') === 'textarea' ? 'Long answer text' : 'Short answer text' }}
                                    </p>
                                </div>
                            @endif

                            {{-- Footer Actions --}}
                            <div class="flex flex-wrap items-center justify-end gap-1 pt-4 border-t border-surface-border mt-8 text-secondary">
                                <button wire:click="duplicateField({{ $i }})" class="p-2.5 hover:text-on-surface hover:bg-surface-container rounded-full transition-colors flex items-center justify-center" title="Duplicate">
                                    <span class="material-symbols-outlined text-[22px]">content_copy</span>
                                </button>
                                <button wire:click="removeField({{ $i }})" class="p-2.5 hover:text-on-surface hover:bg-surface-container rounded-full transition-colors flex items-center justify-center mr-1" title="Delete">
                                    <span class="material-symbols-outlined text-[24px]">delete</span>
                                </button>
                                
                                @if(!in_array(($fields[$i]['type'] ?? 'text'), ['title', 'image', 'video', 'section']))
                                    <div class="w-px h-8 bg-surface-border mx-2 hidden sm:block"></div>
                                    <label class="flex items-center gap-3 cursor-pointer mr-2 py-2 px-1">
                                        <span class="text-sm text-on-surface">Required</span>
                                        <div class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                                            <input type="checkbox" wire:model.live="fields.{{ $i }}.required" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer checked:right-0 checked:border-primary transition-all z-10" />
                                            <div class="toggle-label block overflow-hidden h-5 rounded-full bg-surface-container-high cursor-pointer transition-colors"></div>
                                        </div>
                                    </label>
                                @endif
                                
                                <div x-data="{ cardMenuOpen: false }" class="relative">
                                    <button type="button" @click="cardMenuOpen = !cardMenuOpen" @click.outside="cardMenuOpen = false" class="p-2.5 hover:text-on-surface hover:bg-surface-container rounded-full transition-colors flex items-center justify-center ml-1" title="More options">
                                        <span class="material-symbols-outlined text-[22px]">more_vert</span>
                                    </button>
                                    <div x-show="cardMenuOpen" x-transition.opacity.duration.200ms style="display: none;"
                                        class="absolute right-0 bottom-full mb-2 w-48 bg-surface-bg border border-surface-border rounded-lg shadow-lg py-2 z-50">
                                        <button type="button" onclick="alert('Fitur deskripsi sedang dalam pengembangan.')" class="w-full text-left px-4 py-2 hover:bg-surface-container-low text-sm flex items-center gap-3 text-on-surface">
                                            <span class="material-symbols-outlined text-secondary text-[20px]">description</span> Description
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- VIEW MODE (Inactive Card) --}}
                        <div wire:click="editField({{ $i }})" class="bg-white border border-surface-border hover:border-primary/30 rounded-xl shadow-sm p-6 relative cursor-pointer group transition-all duration-200">
                            
                            {{-- Drag handle (only visible on hover) --}}
                            <div class="absolute top-2 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-surface-border flex gap-1 items-center z-10">
                                <button wire:click.stop="moveUp({{ $i }})" class="hover:text-primary"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></button>
                                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'wght' 200">drag_indicator</span>
                                <button wire:click.stop="moveDown({{ $i }})" class="hover:text-primary"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></button>
                            </div>
                            
                            <div class="flex items-start gap-2 mt-2">
                                <div class="flex-1">
                                    @if(($field['type'] ?? 'text') === 'section')
                                        <div class="border-t-4 border-primary pt-4 mt-2">
                                            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">{{ $field['label'] ?: 'Bagian tanpa judul' }}</h2>
                                            <p class="text-sm text-secondary">{{ $field['description'] ?? '' }}</p>
                                        </div>
                                    @elseif(($field['type'] ?? 'text') === 'title')
                                        <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1">{{ $field['label'] ?: 'Judul' }}</h3>
                                        <p class="text-sm text-secondary">{{ $field['description'] ?? '' }}</p>
                                    @elseif(($field['type'] ?? 'text') === 'image')
                                        <p class="font-body-lg text-body-lg text-on-surface mb-4 font-semibold">{{ $field['label'] ?: 'Gambar' }}</p>
                                        @if(!empty($field['url']))
                                            <img src="{{ $field['url'] }}" class="max-w-full h-auto rounded-lg border border-surface-border mt-2 mb-2" style="max-height: 200px" alt="Preview" onerror="this.src='https://placehold.co/600x400?text=Invalid+Image+URL'">
                                        @else
                                            <div class="w-full h-32 bg-surface-container-low border border-surface-border border-dashed rounded-lg flex items-center justify-center text-secondary">
                                                <span class="material-symbols-outlined mr-2">image</span> URL Gambar belum diisi
                                            </div>
                                        @endif
                                    @elseif(($field['type'] ?? 'text') === 'video')
                                        <p class="font-body-lg text-body-lg text-on-surface mb-4 font-semibold">{{ $field['label'] ?: 'Video' }}</p>
                                        @if(!empty($field['url']))
                                            <div class="w-full bg-surface-container-low border border-surface-border rounded-lg flex items-center justify-center text-primary py-8">
                                                <span class="material-symbols-outlined text-[48px]">play_circle</span>
                                                <span class="ml-2">Video Tersedia</span>
                                            </div>
                                        @else
                                            <div class="w-full h-32 bg-surface-container-low border border-surface-border border-dashed rounded-lg flex items-center justify-center text-secondary">
                                                <span class="material-symbols-outlined mr-2">smart_display</span> URL Video belum diisi
                                            </div>
                                        @endif
                                    @elseif(($field['type'] ?? 'text') === 'section')
                                        <div class="bg-primary text-on-primary px-6 py-4 -mx-6 -mt-6 mb-4 rounded-t-xl flex items-center justify-between">
                                            <h3 class="font-headline-sm">{{ $field['label'] ?: 'Bagian Baru' }}</h3>
                                            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold">Bagian</span>
                                        </div>
                                        @if(!empty($field['description']))
                                            <p class="text-sm text-secondary mb-4">{{ $field['description'] }}</p>
                                        @endif
                                    @else
                                        <p class="font-body-lg text-body-lg text-on-surface mb-4 font-semibold">
                                            {{ $field['label'] ?: 'Pertanyaan tanpa judul' }}
                                            @if($field['required'] ?? false) <span class="text-error ml-1">*</span> @endif
                                        </p>
                                        
                                        @if(in_array($field['type'] ?? 'text', ['radio', 'checkbox']))
                                            <div class="space-y-3">
                                                @foreach($field['options'] ?? [] as $opt)
                                                    <div class="flex items-center gap-3">
                                                        <span class="material-symbols-outlined text-surface-border text-[20px]">
                                                            {{ ($field['type'] ?? 'radio') === 'radio' ? 'radio_button_unchecked' : 'check_box_outline_blank' }}
                                                        </span>
                                                        <span class="text-sm text-on-surface-variant">{{ $opt }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif(($field['type'] ?? 'text') === 'select')
                                            <div class="border border-surface-border rounded-lg px-3 py-2 flex items-center justify-between w-full max-w-sm bg-surface-container-lowest text-secondary">
                                                <span class="text-sm">Pilih opsi</span>
                                                <span class="material-symbols-outlined">arrow_drop_down</span>
                                            </div>
                                        @elseif(($field['type'] ?? 'text') === 'textarea')
                                            <div class="border-b border-surface-border border-dotted pb-1 w-full text-secondary text-sm italic">
                                                Teks jawaban panjang
                                            </div>
                                        @else
                                            <div class="border-b border-surface-border border-dotted pb-1 w-1/2 text-secondary text-sm italic">
                                                Teks jawaban singkat
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-surface-bg border border-surface-border border-dashed rounded-xl p-12 text-center shadow-sm">
                    <span class="material-symbols-outlined text-[48px] block mb-2 text-surface-container-highest mx-auto">dynamic_form</span>
                    <p class="font-semibold text-lg text-on-surface">Belum ada field kustom</p>
                    <p class="text-sm mt-1 text-secondary">Klik "Tambah Pertanyaan" untuk mulai membuat form pendaftaran.</p>
                    <button wire:click="addBlankField" class="mt-6 inline-flex items-center gap-2 px-6 py-2 bg-primary text-on-primary rounded-lg font-semibold hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[20px]">add</span> Tambah Pertanyaan
                    </button>
                </div>
            @endforelse

            {{-- Bottom Toolbar (Mobile & inline fallback) --}}
            @if(count($fields) > 0)
            <div class="flex items-center justify-center gap-4 mt-8 xl:hidden">
                <button wire:click="addBlankField" class="inline-flex items-center gap-2 px-6 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-full text-sm font-semibold transition-colors shadow-sm border border-surface-border">
                    <span class="material-symbols-outlined text-[20px]">add</span> Tambah Pertanyaan
                </button>
            </div>
            @endif
            
        </div>
        @elseif($currentTab === 'responses')
        {{-- TAB: RESPONSES --}}
        <div class="mt-4 bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
            <div class="bg-surface-container-lowest p-6 border-b border-surface-border flex justify-between items-center">
                <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $responsesCount }} Responses</h2>
                
                <div class="flex items-center gap-2">
                    @php
                        $currentJob = \App\Models\Job::find($selectedJobId);
                        $hasSpreadsheet = $currentJob && $currentJob->google_spreadsheet_id;
                    @endphp
                    
                    @if($hasSpreadsheet)
                        <button type="button" wire:click="openGoogleSheets"
                           title="Pengaturan Google Sheets"
                           class="inline-flex items-center justify-center p-2 bg-success text-white rounded-lg hover:opacity-90 transition-opacity shadow-sm">
                           <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">table_view</span>
                        </button>
                    @else
                        <button wire:click="$dispatch('open-sheets-modal')"
                           title="Hubungkan ke Google Sheets"
                           class="inline-flex items-center justify-center p-2 bg-success text-white rounded-lg hover:opacity-90 transition-opacity shadow-sm">
                           <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">table_view</span>
                        </button>
                    @endif
                    
                    @if($responsesCount > 0)
                    <a href="{{ route('admin.submissions.index') }}?jobId={{ $selectedJobId }}" 
                       title="View in Submissions"
                       class="inline-flex items-center justify-center p-2 text-secondary hover:bg-surface-container rounded-full transition-colors">
                       <span class="material-symbols-outlined text-[20px]">table_chart</span>
                    </a>
                    @endif
                    
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false" class="p-2 text-secondary hover:bg-surface-container rounded-full transition-colors flex items-center justify-center" title="More options">
                            <span class="material-symbols-outlined text-[20px]">more_vert</span>
                        </button>
                        
                        <div x-show="open" x-transition.opacity.duration.200ms style="display: none;" class="absolute right-0 top-full mt-1 w-[280px] bg-surface-bg border border-surface-border rounded-md shadow-lg z-50 py-2">
                            @php
                                $isEmailNotifActive = \App\Models\Setting::where('key', 'job_'.$selectedJobId.'_email_notif')->value('value') === '1';
                            @endphp
                            <button wire:click="toggleEmailNotifications" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] {{ $isEmailNotifActive ? 'text-primary' : 'text-transparent' }}">check</span>
                                Get email notifications for new responses
                            </button>
                            
                            @if($hasSpreadsheet)
                            <button wire:click="openGoogleSheets" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-transparent">check</span>
                                Select destination for responses
                            </button>
                            @else
                            <button wire:click="$dispatch('open-sheets-modal')" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-transparent">check</span>
                                Select destination for responses
                            </button>
                            @endif

                            <button wire:click="unlinkGoogleSheets" wire:confirm="Anda yakin ingin memutus form ini dari Spreadsheet?" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-secondary">link_off</span> Unlink form
                            </button>
                            <hr class="my-2 border-surface-border">
                            <button wire:click="exportExcel" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-secondary">download</span> Download responses (.xlsx)
                            </button>
                            <button wire:click="exportCsv" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-secondary">download</span> Download responses (.csv)
                            </button>
                            <button onclick="window.print()" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-secondary">print</span> Print all responses
                            </button>
                            <hr class="my-2 border-surface-border">
                            <button wire:click="deleteAllResponses" wire:confirm="PERINGATAN: Semua lamaran dan data pelamar pada loker ini akan dihapus permanen. Lanjutkan?" class="w-full text-left px-4 py-2.5 text-sm text-on-surface hover:bg-error/10 transition-colors flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] text-secondary">delete</span> Delete all responses
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-0">
                {{-- Embed the submissions view for this specific job --}}
                @livewire('admin.submissions', ['initialJobId' => $selectedJobId, 'embedded' => true], key('submissions-'.$selectedJobId))
            </div>
        </div>

        @elseif($currentTab === 'settings')
        {{-- TAB: SETTINGS --}}
        <div class="mt-4 max-w-3xl mx-auto space-y-4 pb-24">
            
            <h2 class="font-headline-sm text-headline-sm text-on-surface px-2 mb-2">Settings</h2>
            
            {{-- Setting Item (Responses) --}}
            <div x-data="{ expanded: false }" class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden mb-4">
                <div @click="expanded = !expanded" class="px-6 py-5 flex items-center justify-between cursor-pointer hover:bg-surface-container-lowest transition-colors">
                    <div>
                        <h4 class="font-body-lg text-body-lg text-on-surface font-semibold">Responses</h4>
                        <p class="text-sm text-secondary mt-1">Manage how responses are collected and protected</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </div>
                
                <div x-show="expanded" x-transition.opacity.duration.200ms style="display: none;" class="px-6 pb-6 pt-2 border-t border-surface-border/50 bg-surface-container-lowest space-y-6">
                    <!-- Collect email addresses -->
                    <div class="flex items-center justify-between mt-4">
                        <div>
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Collect email addresses</h4>
                        </div>
                        <div>
                            <select class="border border-surface-border rounded-md px-3 py-1.5 bg-surface-bg text-sm text-on-surface focus:ring-primary focus:border-primary" disabled>
                                <option>Do not collect</option>
                                <option>Verified</option>
                                <option selected>Responder input</option>
                            </select>
                        </div>
                    </div>

                    <!-- Send responders a copy -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Send responders a copy of their response</h4>
                            <p class="text-xs text-secondary mt-0.5">Requires Collect email addresses</p>
                        </div>
                        <div>
                            <select class="border border-surface-border rounded-md px-3 py-1.5 bg-surface-bg text-sm text-on-surface focus:ring-primary focus:border-primary" disabled>
                                <option selected>Off</option>
                                <option>When requested</option>
                                <option>Always</option>
                            </select>
                        </div>
                    </div>

                    <!-- Allow response editing -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Allow response editing</h4>
                            <p class="text-xs text-secondary mt-0.5">Responses can be changed after being submitted</p>
                        </div>
                        <div>
                            <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                <input type="checkbox" class="sr-only peer" disabled>
                                <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="border-t border-surface-border/50 pt-4">
                        <p class="text-xs font-semibold text-secondary uppercase tracking-wider mb-4">Requires Sign In</p>
                        
                        <!-- Limit to 1 response -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-body-md text-body-md text-on-surface font-semibold">Limit to 1 response</h4>
                            </div>
                            <div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="oneResponsePerPerson" class="sr-only peer">
                                    <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Setting Item (Presentation) --}}
            <div x-data="{ expanded: false }" class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden mb-4">
                <div @click="expanded = !expanded" class="px-6 py-5 flex items-center justify-between cursor-pointer hover:bg-surface-container-lowest transition-colors">
                    <div>
                        <h4 class="font-body-lg text-body-lg text-on-surface font-semibold">Presentation</h4>
                        <p class="text-sm text-secondary mt-1">Manage how the form and responses are presented</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </div>
                
                <div x-show="expanded" x-transition.opacity.duration.200ms style="display: none;" class="px-6 pb-6 pt-2 border-t border-surface-border/50 bg-surface-container-lowest space-y-6">
                    
                    <p class="text-xs font-semibold text-secondary uppercase tracking-wider mb-4 mt-4">Form Presentation</p>

                    <!-- Show progress bar -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Show progress bar</h4>
                        </div>
                        <div>
                            <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                <input type="checkbox" class="sr-only peer" disabled>
                                <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Shuffle question order -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Shuffle question order</h4>
                        </div>
                        <div>
                            <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                <input type="checkbox" class="sr-only peer" disabled>
                                <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-surface-border/50 pt-4">
                        <p class="text-xs font-semibold text-secondary uppercase tracking-wider mb-4">After Submission</p>
                        
                        <!-- Confirmation message -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-body-md text-body-md text-on-surface font-semibold">Confirmation message</h4>
                                <p class="text-xs text-secondary mt-0.5 italic">Thank you for your interest.<br>Someone will be contacting you shortly.</p>
                            </div>
                            <div>
                                <button class="text-primary text-sm font-semibold hover:underline" disabled>Edit</button>
                            </div>
                        </div>

                        <!-- Show link to submit another response -->
                        <div class="flex items-center justify-between mt-6">
                            <div>
                                <h4 class="font-body-md text-body-md text-on-surface font-semibold">Show link to submit another response</h4>
                            </div>
                            <div>
                                <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                    <input type="checkbox" class="sr-only peer" checked disabled>
                                    <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                        </div>

                        <!-- View results summary -->
                        <div class="flex items-center justify-between mt-6">
                            <div>
                                <h4 class="font-body-md text-body-md text-on-surface font-semibold">View results summary</h4>
                                <p class="text-xs text-secondary mt-0.5">Share <a href="#" class="text-primary hover:underline">results summary</a> with respondents. <a href="#" class="text-primary hover:underline">Important details</a></p>
                            </div>
                            <div>
                                <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                    <input type="checkbox" class="sr-only peer" disabled>
                                    <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-surface-border/50 pt-4">
                        <p class="text-xs font-semibold text-secondary uppercase tracking-wider mb-4">Restrictions:</p>
                        
                        <!-- Disable autosave -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-body-md text-body-md text-on-surface font-semibold">Disable autosave for all respondents</h4>
                            </div>
                            <div>
                                <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                                    <input type="checkbox" class="sr-only peer" disabled>
                                    <div class="w-11 h-6 bg-surface-container-high rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Setting Item (Deadline & Status) --}}
            <div x-data="{ expanded: true }" class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden mb-4">
                <div @click="expanded = !expanded" class="px-6 py-5 flex items-center justify-between cursor-pointer hover:bg-surface-container-lowest transition-colors">
                    <div>
                        <h4 class="font-body-lg text-body-lg text-on-surface font-semibold">Form Deadline</h4>
                        <p class="text-sm text-secondary mt-1">Set a deadline to automatically close the form</p>
                    </div>
                    <span class="material-symbols-outlined text-secondary transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </div>
                
                <div x-show="expanded" x-transition.opacity.duration.200ms class="px-6 pb-6 pt-2 border-t border-surface-border/50 bg-surface-container-lowest space-y-6">
                    <div class="flex items-center justify-between mt-4">
                        <div class="flex-1">
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Close Form on Date</h4>
                            <p class="text-xs text-secondary mt-0.5">Form will be closed and stop accepting responses after this date.</p>
                        </div>
                        <div class="ml-4">
                            <input type="date" wire:model.live="deadlineDate" class="border border-surface-border rounded-md px-3 py-1.5 bg-surface-bg text-sm text-on-surface focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="border-t border-surface-border/50 pt-4 mt-4">
                        <div class="mb-2">
                            <h4 class="font-body-md text-body-md text-on-surface font-semibold">Message for respondents</h4>
                            <p class="text-xs text-secondary mt-0.5">This message will be shown when the form is closed.</p>
                        </div>
                        <input type="text" wire:model.live.debounce.500ms="closedMessage" class="w-full border border-surface-border rounded-md px-3 py-2 bg-surface-bg text-sm text-on-surface focus:ring-primary focus:border-primary border-b-2 focus:border-b-primary focus:border-t-surface-border focus:border-l-surface-border focus:border-r-surface-border" placeholder="This form is no longer accepting responses.">
                    </div>
                </div>
            </div>

        </div>
        @endif
        
        @script
        <script>
            $wire.on('theme-updated', (event) => {
                let color = event[0].color;
                let hexToRgb = (hex) => {
                    let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                    return result ? `${parseInt(result[1], 16)} ${parseInt(result[2], 16)} ${parseInt(result[3], 16)}` : null;
                };
                document.documentElement.style.setProperty('--color-primary', color);
                let rgb = hexToRgb(color);
                if (rgb) {
                    document.documentElement.style.setProperty('--color-primary-rgb', rgb);
                }
            });

            Livewire.on('form-saved', (url) => {
                // Automatically open the preview page in a new tab when saved
                window.open(url[0], '_blank');
            });
            
            $wire.on('show-template-confirm', () => {
                Swal.fire({
                    title: 'Gunakan Template Standar?',
                    text: 'Memuat template standar akan menimpa seluruh pertanyaan saat ini. Anda yakin?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007b83',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Muat Template',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $wire.loadStandardTemplate();
                    }
                });
            });
            
            $wire.on('show-sheets-sweetalert', (data) => {
                const url = data[0].url;
                const syncSuccess = data[0].syncSuccess ?? false;

                Swal.fire({
                    title: syncSuccess ? '✅ Sinkronisasi Berhasil!' : 'Google Sheets Terhubung',
                    text: syncSuccess
                        ? 'Semua data sudah masuk ke Google Sheets. Apa yang ingin Anda lakukan selanjutnya?'
                        : 'Apa yang ingin Anda lakukan dengan Spreadsheet ini?',
                    icon: syncSuccess ? 'success' : 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#1a73e8',
                    denyButtonColor: '#0f9d58',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '📊 Buka Spreadsheet',
                    denyButtonText: '🔄 Sinkronisasi Ulang',
                    cancelButtonText: 'Tutup'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(url, '_blank');
                    } else if (result.isDenied) {
                        Swal.fire({
                            title: 'Menyinkronkan Data...',
                            html: 'Mohon tunggu, jangan tutup halaman ini.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $wire.syncToGoogleSheets();
                    }
                });
            });
        </script>
        @endscript

        {{-- Save Bar (Always Visible) --}}
        <div class="fixed bottom-0 left-0 right-0 lg:left-64 bg-surface-bg border-t border-surface-border p-4 flex items-center justify-between shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-40">
            <div class="flex items-center gap-4 max-w-3xl mx-auto w-full px-4">
                <div class="flex-1">
                    @if($saveMessage)
                    <span class="text-success text-sm font-semibold flex items-center gap-1" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">check_circle</span>
                        {{ $saveMessage }}
                    </span>
                    @endif
                </div>
                <button wire:click="saveForm" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-8 py-2.5 bg-success text-white rounded-lg font-semibold text-sm hover:bg-opacity-90 transition-all shadow-sm disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveForm" class="material-symbols-outlined text-[18px]">save</span>
                    <span wire:loading wire:target="saveForm" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    Simpan Form
                </button>
            </div>
        </div>
        
    </div>
    @endif
    
    {{-- AI Prompt Modal --}}
    @if($aiModalOpen)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-surface-bg rounded-xl max-w-lg w-full shadow-xl relative overflow-hidden" @click.outside="$wire.set('aiModalOpen', false)">
            <div class="bg-primary p-6 text-on-primary flex justify-between items-center">
                <h3 class="font-headline-sm text-lg font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">auto_awesome</span> AI Form Generator
                </h3>
                <button wire:click="$set('aiModalOpen', false)" class="text-on-primary opacity-80 hover:opacity-100">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="p-6">
                <div class="hidden">
                    <p class="text-sm text-secondary mb-4">
                        Ketikkan instruksi Anda, dan biarkan AI merancang kuesioner form secara otomatis. <br/>
                        <span class="text-error font-semibold text-xs mt-1 block">PERHATIAN: Membuat form via AI akan menggantikan/menimpa semua pertanyaan Anda saat ini!</span>
                    </p>
                    
                    <textarea wire:model="aiPrompt" rows="4" 
                        placeholder="Contoh: Buatkan kuesioner untuk lowongan IT Support. Tambahkan pertanyaan tentang pemahaman jaringan dasar dan sistem operasi." 
                        class="w-full bg-surface-container-lowest border border-surface-border rounded-xl p-4 focus:ring-2 focus:ring-primary focus:border-primary outline-none text-on-surface resize-none mb-2"></textarea>
                    @error('aiPrompt') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-between items-center mt-6">
                    <button wire:click="generateStaticTemplate" class="px-4 py-2 rounded-lg font-semibold text-primary hover:bg-primary/10 transition-colors text-sm border border-primary">
                        Template Standar
                    </button>
                    <div class="flex justify-end gap-2">
                        <button wire:click="$set('aiModalOpen', false)" class="px-4 py-2 rounded-lg font-semibold text-secondary hover:bg-surface-container transition-colors text-sm border border-surface-border">
                            Batal
                        </button>
                        <button wire:click="generateAITemplate" wire:loading.attr="disabled" class="hidden px-5 py-2 bg-primary hover:opacity-90 text-on-primary rounded-lg font-semibold text-sm transition-opacity shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="generateAITemplate" class="material-symbols-outlined text-[18px]">auto_awesome</span>
                            <span wire:loading wire:target="generateAITemplate" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                            <span wire:loading.remove wire:target="generateAITemplate">Generate (AI)</span>
                            <span wire:loading wire:target="generateAITemplate">Berpikir...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Import Modal --}}
    @if($importModalOpen)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-surface-bg rounded-xl max-w-lg w-full p-6 shadow-xl relative" @click.outside="$wire.set('importModalOpen', false)">
            <button wire:click="$set('importModalOpen', false)" class="absolute top-4 right-4 text-secondary hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
            <h3 class="font-headline-sm text-headline-sm mb-6">Import Pertanyaan</h3>
            
            <div class="max-h-80 overflow-y-auto pr-2 space-y-2">
                @forelse($availableJobsForImport as $job)
                    <div wire:click="importQuestions({{ $job->id }})" class="p-4 border border-surface-border rounded-lg hover:border-primary hover:bg-primary/5 cursor-pointer transition-colors flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">work</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-on-surface">{{ $job->title }}</h4>
                            <p class="text-xs text-secondary">{{ $job->department }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-secondary text-center py-8">Belum ada lowongan lain untuk di-import.</p>
                @endforelse
            </div>
        </div>
    </div>
    </div>
    @endif
    
    <!-- Alpine Toast Notification -->
    <div x-data="{ show: false, message: '' }" 
         @notify.window="message = $event.detail; show = true; setTimeout(() => show = false, 3000)"
         x-show="show" 
         x-transition.opacity.duration.300ms
         class="fixed bottom-4 left-4 bg-surface-bg border border-surface-border text-on-surface px-4 py-3 rounded-lg shadow-lg z-50 text-sm flex items-center gap-3 kanban-shadow"
         style="display: none;">
        <span class="material-symbols-outlined text-[20px] text-primary">info</span>
        <span x-text="message" class="font-medium"></span>
    </div>
</div>
