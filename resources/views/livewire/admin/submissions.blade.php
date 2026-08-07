<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Application;
use App\Models\Job;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

new
#[Layout('layouts.admin')]
class extends Component
{
    use WithPagination;

    public $search = '';
    #[\Livewire\Attributes\Url]
    public $jobId = '';
    public $stageFilter = '';
    public $showModal = false;
    public $selectedApplication = null;
    public $embedded = false;
    public $showSheetsModal = false;
    public $googleSheetsWebhookUrl = '';

    #[\Livewire\Attributes\On('open-sheets-modal')]
    public function openSheetsModal()
    {
        $this->showSheetsModal = true;
    }

    public function updateStage($appId, $newStageId)
    {
        $app = Application::find($appId);
        if ($app) {
            $app->pipeline_stage_id = $newStageId;
            $app->save();
        }
    }

    public function mount($initialJobId = null, $embedded = false)
    {
        $this->embedded = $embedded;
        if ($initialJobId) {
            $this->jobId = $initialJobId;
        } elseif (request()->query('jobId')) {
            $this->jobId = request()->query('jobId');
        }
        
        try {
            $this->googleSheetsWebhookUrl = \App\Models\Setting::where('key', 'google_sheets_webhook_url')->value('value') ?? '';
        } catch (\Exception $e) {
            $this->googleSheetsWebhookUrl = '';
        }
    }

    public function createSpreadsheetForJob()
    {
        try {
            $service = new \App\Services\GoogleSheetsService();
            
            if ($this->jobId) {
                $job = Job::find($this->jobId);
                if (!$job) return;
                
                if (!$job->google_spreadsheet_id) {
                    $service->createSpreadsheetForJob($job);
                    $job = Job::find($this->jobId);
                }
                $url = 'https://docs.google.com/spreadsheets/d/' . $job->google_spreadsheet_id;
            } else {
                $spreadsheetId = $service->createAndSyncMasterSpreadsheet();
                $url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId;
            }
            
            $this->showSheetsModal = false;
            $this->dispatch('show-sheets-sweetalert', [
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Gagal membuat Spreadsheet: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    public function syncToGoogleSheets()
    {
        try {
            $service = new \App\Services\GoogleSheetsService();
            
            if ($this->jobId) {
                $job = Job::find($this->jobId);
                if (!$job || !$job->google_spreadsheet_id) return;
                
                $service->syncAllCandidatesToSheet($job);
                $url = 'https://docs.google.com/spreadsheets/d/' . $job->google_spreadsheet_id;
            } else {
                $spreadsheetId = $service->createAndSyncMasterSpreadsheet();
                $url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId;
            }
            
            $this->dispatch('show-sheets-sweetalert', [
                'url' => $url,
                'syncSuccess' => true,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('sync-complete');
            $this->dispatch('notify', ['message' => 'Gagal sinkronisasi: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    public function openGoogleSheets()
    {
        if ($this->jobId) {
            $job = Job::find($this->jobId);
            if ($job && $job->google_spreadsheet_id) {
                $url = 'https://docs.google.com/spreadsheets/d/' . $job->google_spreadsheet_id;
                $this->dispatch('show-sheets-sweetalert', ['url' => $url]);
                return;
            }
        } else {
            $masterId = \App\Models\Setting::where('key', 'master_google_spreadsheet_id')->value('value');
            if ($masterId) {
                $url = 'https://docs.google.com/spreadsheets/d/' . $masterId;
                $this->dispatch('show-sheets-sweetalert', ['url' => $url]);
                return;
            }
        }
        
        $this->showSheetsModal = true;
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingJobId() { $this->resetPage(); }
    public function updatingStageFilter() { $this->resetPage(); }

    public function selectJob($id)
    {
        $this->jobId = ($this->jobId == $id) ? '' : $id;
        $this->resetPage();
    }

    public function viewDetails($id)
    {
        $this->selectedApplication = Application::with(['candidate', 'job', 'stage', 'media', 'notes'])->find($id);
        $this->showModal = true;
    }

    public function exportExcel()
    {
        // Require OpenSpout classes
        // Since we may not have aliased them, we use the fully qualified namespaces
        if (!class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            $this->dispatch('notify', ['message' => 'Library Excel belum siap, mohon tunggu sebentar atau muat ulang halaman.', 'type' => 'warning']);
            return;
        }

        $fileName = 'kandidat-' . date('Y-m-d') . '.xlsx';
        
        // OpenSpout setup
        $options = new \OpenSpout\Writer\XLSX\Options();
        $writer = new \OpenSpout\Writer\XLSX\Writer($options);
        
        // We will write to a temp file and then stream it
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->openToFile($tempFile);
        
        $googleService = new \App\Services\GoogleSheetsService();
        $sheetCount = 0;

        if ($this->jobId) {
            // Export Single Job
            $job = Job::find($this->jobId);
            $applications = Application::with(['candidate', 'job', 'stage', 'media', 'notes'])->where('job_id', $job->id)->get();
            
            $sheet = $writer->getCurrentSheet();
            $sheetName = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $job->title), 0, 31) ?: 'Sheet1';
            $sheet->setName($sheetName);
            
            $csvHeaders = $googleService->getHeaders($job);
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge(['ID', 'Departemen'], $csvHeaders)));
            
            foreach ($applications as $app) {
                $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge([$app->id, $app->job->department ?? '-'], $row)));
            }
        } else {
            // Export All Jobs
            $jobs = Job::whereHas('applications')->get();
            foreach ($jobs as $job) {
                if ($sheetCount > 0) {
                    $writer->addNewSheetAndMakeItCurrent();
                }
                
                $sheet = $writer->getCurrentSheet();
                // Sheet names must be <= 31 chars and no special chars
                $sheetName = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $job->title), 0, 31) ?: 'Sheet' . ($sheetCount + 1);
                $sheet->setName($sheetName);
                
                $applications = Application::with(['candidate', 'job', 'stage', 'media', 'notes'])->where('job_id', $job->id)->get();
                $csvHeaders = $googleService->getHeaders($job);
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge(['ID', 'Departemen'], $csvHeaders)));
                
                foreach ($applications as $app) {
                    $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_merge([$app->id, $app->job->department ?? '-'], $row)));
                }
                
                $sheetCount++;
            }
            
            if ($jobs->isEmpty()) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Belum ada data pelamar.']));
            }
        }

        $writer->close();
        
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportZip()
    {
        $query = Application::with(['candidate', 'job', 'stage', 'media', 'notes']);
        if ($this->jobId) $query->where('job_id', $this->jobId);
        $applications = $query->get();

        $zipPath = storage_path('app/temp/applications-' . date('YmdHis') . '.zip');
        @mkdir(dirname($zipPath), 0755, true);

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Add CSV summary
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM
        $googleService = new \App\Services\GoogleSheetsService();
        
        if ($this->jobId) {
            $job = Job::find($this->jobId);
            $csvHeaders = $googleService->getHeaders($job);
            
            // Build CSV Headers manually to handle escaping
            $escapedHeaders = array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, array_merge(['ID', 'Departemen'], $csvHeaders));
            $csvContent .= implode(';', $escapedHeaders) . "\n";
            
            foreach ($applications as $app) {
                $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
                $fullRow = array_merge([$app->id, $app->job->department ?? '-'], $row);
                $escapedRow = array_map(function($v) { return '"' . str_replace('"', '""', $v) . '"'; }, $fullRow);
                $csvContent .= implode(';', $escapedRow) . "\n";
            }
        } else {
            $jobsWithApps = Job::whereHas('applications')->get();
            foreach ($jobsWithApps as $job) {
                $jobApps = $applications->where('job_id', $job->id);
                
                $csvContent .= "\"=== Lowongan: " . str_replace('"', '""', $job->title) . " ===\"\n";
                $csvHeaders = $googleService->getHeaders($job);
                $escapedHeaders = array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, array_merge(['ID', 'Departemen'], $csvHeaders));
                $csvContent .= implode(';', $escapedHeaders) . "\n";
                
                foreach ($jobApps as $app) {
                    $row = $googleService->getApplicationRow($app, $job, $csvHeaders);
                    $fullRow = array_merge([$app->id, $app->job->department ?? '-'], $row);
                    $escapedRow = array_map(function($v) { return '"' . str_replace('"', '""', $v) . '"'; }, $fullRow);
                    $csvContent .= implode(';', $escapedRow) . "\n";
                }
                $csvContent .= "\n";
            }
        }
        
        $zip->addFromString('rekap_pelamar.csv', $csvContent);

        // Add CV/resumes per Department folder → candidate subfolder
        $counter = 1;
        foreach ($applications as $app) {
            $departmentName = !empty($app->job->department) ? $app->job->department : 'Uncategorized';
            // Clean folder names
            $deptFolder      = \Illuminate\Support\Str::slug($departmentName);
            $candidateFolder = $counter . '_' . \Illuminate\Support\Str::slug($app->candidate->name);
            $basePath        = "{$deptFolder}/{$candidateFolder}";
            
            $zip->addEmptyDir($basePath);

            $notes = $app->notes->pluck('note')->join("\n\n");
            
            $pdf = Pdf::loadView('exports.candidate-form-pdf', [
                'candidate' => $app->candidate,
                'job' => $app->job,
                'application' => $app,
                'notes' => $notes,
            ]);
            
            $pdfFileName = 'Data_Form_' . \Illuminate\Support\Str::slug($app->candidate->name) . '.pdf';
            $zip->addFromString($basePath . '/' . $pdfFileName, $pdf->output());

            // Export all attached media
            foreach ($app->getMedia() as $media) {
                $filePath = $media->getPath();
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, "{$basePath}/{$media->file_name}");
                }
            }
            
            $counter++;
        }

        $zip->close();

        return response()->download($zipPath, 'Export-' . date('Y-m-d') . '.zip')->deleteFileAfterSend(true);
    }

    public function delete($id)
    {
        Application::find($id)?->delete();
    }

    public function with()
    {
        $query = Application::with(['candidate', 'job', 'stage']);

        if ($this->search) {
            $query->whereHas('candidate', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->jobId) {
            $query->where('job_id', $this->jobId);
        }

        if ($this->stageFilter) {
            $query->where('pipeline_stage_id', $this->stageFilter);
        }

        $jobs = Job::withCount('applications')->orderBy('created_at', 'desc')->get();

        return [
            'applications' => $query->orderBy('created_at', 'desc')->paginate(10),
            'jobs'         => $jobs,
            'totalAll'     => Application::count(),
        ];
    }
};
?>

<div class="flex-1 overflow-y-auto {{ $embedded ? '' : 'p-margin' }} h-[calc(100vh-64px)]">

    @if(!$embedded)
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-stack-md mb-stack-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">Candidate Submissions</h2>
            <p class="text-on-surface-variant mt-1">Manage and screen all incoming applications from active job vacancies.</p>
        </div>
        <div class="flex items-center gap-stack-sm flex-wrap">
            <button wire:click="openGoogleSheets"
                class="px-4 py-2 bg-success/10 text-success border border-success/20 rounded-lg font-label-md flex items-center gap-2 hover:bg-success/20 shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">table_view</span>
                <span>Google Sheets</span>
            </button>
            <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                class="inline-flex items-center gap-2 px-4 py-2 border border-surface-border text-on-surface hover:bg-surface-container rounded-lg text-sm font-medium transition-colors shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="exportExcel" class="material-symbols-outlined text-[18px]">table_chart</span>
                <span wire:loading wire:target="exportExcel" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Exporting...</span>
            </button>
            <button wire:click="exportZip" wire:loading.attr="disabled" wire:target="exportZip"
                class="px-4 py-2 bg-primary text-white rounded-lg font-label-md flex items-center gap-2 hover:opacity-90 shadow-sm transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="exportZip" class="material-symbols-outlined text-[18px]">folder_zip</span>
                <span wire:loading wire:target="exportZip" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                <span wire:loading.remove wire:target="exportZip">Export ZIP</span>
                <span wire:loading wire:target="exportZip">Packing...</span>
            </button>
        </div>
    </div>

    {{-- Job Cards --}}
    <div class="mb-stack-lg">
        <h3 class="font-label-md text-on-surface-variant uppercase tracking-wider mb-stack-sm text-xs">Lowongan Aktif — Klik untuk filter lamaran</h3>
        <div class="flex flex-wrap gap-stack-md">

            {{-- "All" card --}}
            <button wire:click="selectJob('')"
                class="flex items-center gap-3 px-5 py-4 rounded-xl border-2 transition-all shadow-sm text-left
                    {{ $jobId === '' ? 'border-primary bg-primary/10 shadow-primary/20' : 'border-surface-border bg-surface-bg hover:border-primary/50 hover:shadow-md' }}">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                    {{ $jobId === '' ? 'bg-primary text-on-primary' : 'bg-surface-container text-secondary' }}">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings:'FILL' 1">grid_view</span>
                </div>
                <div>
                    <p class="font-semibold text-on-surface text-sm">Semua Lowongan</p>
                    <p class="text-xs {{ $jobId === '' ? 'text-primary font-bold' : 'text-secondary' }}">{{ $totalAll }} Pelamar</p>
                </div>
            </button>

            {{-- One card per job --}}
            @foreach($jobs as $job)
            @php
                $colors = [
                    'bg-blue-500', 'bg-violet-500', 'bg-emerald-500', 'bg-amber-500',
                    'bg-rose-500', 'bg-cyan-500', 'bg-orange-500', 'bg-teal-500',
                ];
                $color = $colors[$job->id % count($colors)];
            @endphp
            <button wire:click="selectJob({{ $job->id }})"
                class="flex items-center gap-3 px-5 py-4 rounded-xl border-2 transition-all shadow-sm text-left group
                    {{ $jobId == $job->id ? 'border-primary bg-primary/10 shadow-primary/20' : 'border-surface-border bg-surface-bg hover:border-primary/50 hover:shadow-md' }}">
                <div class="w-10 h-10 rounded-full {{ $color }} text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($job->title, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-on-surface text-sm truncate max-w-[140px]" title="{{ $job->title }}">{{ $job->title }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center gap-1 text-xs {{ $jobId == $job->id ? 'text-primary font-bold' : 'text-secondary' }}">
                            <span class="material-symbols-outlined text-[12px]">person</span>
                            {{ $job->applications_count }} Pelamar
                        </span>
                        @if($job->status === 'published')
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-success"></span>
                        @else
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-error"></span>
                        @endif
                    </div>
                </div>
            </button>
            @endforeach

            @if($jobs->isEmpty())
            <div class="flex items-center gap-3 px-5 py-4 rounded-xl border-2 border-dashed border-surface-border text-secondary">
                <span class="material-symbols-outlined">work_off</span>
                <span class="text-sm">Belum ada lowongan aktif. Tambahkan lowongan di menu Jobs.</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Filter Controls --}}
    <div class="bg-surface-bg border border-surface-border rounded-xl p-stack-md mb-stack-md flex flex-wrap items-center gap-stack-md shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-label-sm text-on-surface-variant mb-1">Search Candidate</label>
            <input wire:model.live="search" class="w-full bg-surface-container-low border-surface-border rounded-lg text-body-sm focus:ring-1 focus:ring-primary" placeholder="Search by name or email" type="text"/>
        </div>
    </div>

    {{-- Active filter label --}}
    @if($jobId)
    <div class="mb-stack-sm flex items-center gap-2">
        <span class="text-sm text-secondary">Menampilkan lamaran untuk:</span>
        <span class="inline-flex items-center gap-1.5 bg-primary/10 text-primary text-sm font-semibold px-3 py-1 rounded-full">
            {{ $jobs->firstWhere('id', $jobId)?->title ?? '' }}
            <button wire:click="selectJob('')" class="hover:text-error transition-colors">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </span>
    </div>
    @endif

    {{-- Data Table --}}
    <div class="bg-surface-bg border border-surface-border rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-surface-border">
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Kandidat</th>
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Posisi Dilamar</th>
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Tanggal Melamar</th>
                        <th class="p-stack-md text-left font-label-md text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="p-stack-md text-right font-label-md text-on-surface-variant uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    @forelse($applications as $app)
                    <tr class="hover:bg-primary/5 transition-colors group">
                        <td class="p-stack-md">
                            <div class="flex items-center gap-stack-sm">
                                <div class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($app->candidate->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-on-surface">{{ $app->candidate->name }}</p>
                                    <p class="text-body-sm text-secondary">{{ $app->candidate->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-stack-md">
                            <p class="text-on-surface font-medium text-sm">{{ $app->job->title }}</p>
                            <p class="text-secondary text-xs">{{ $app->job->department ?? '' }}</p>
                        </td>
                        <td class="p-stack-md text-on-surface-variant font-data-tabular text-sm">
                            {{ $app->created_at->format('d M Y') }}<br>
                            <span class="text-xs text-secondary">{{ $app->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="p-stack-md">
                            @php
                                $stageName = $app->stage->name ?? 'Applied';
                                $badgeClass = match(strtolower($stageName)) {
                                    'applied'   => 'bg-blue-100 text-blue-700',
                                    'screening' => 'bg-cyan-100 text-cyan-700',
                                    'interview' => 'bg-amber-100 text-amber-700',
                                    'offer'     => 'bg-emerald-100 text-emerald-700',
                                    'hired'     => 'bg-violet-100 text-violet-700',
                                    'rejected'  => 'bg-red-100 text-red-700',
                                    default     => 'bg-surface-variant text-on-surface-variant',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ $stageName }}
                            </span>
                        </td>
                        <td class="p-stack-md text-right">
                            <div class="flex justify-end gap-1">
                                <button wire:click="viewDetails({{ $app->id }})" class="p-2 hover:bg-surface-container rounded-lg transition-colors text-primary" title="Lihat Detail">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                                <button type="button" x-on:click="confirmDelete('Hapus lamaran ini?', () => $wire.delete({{ $app->id }}))" class="p-2 hover:bg-surface-container rounded-lg transition-colors text-error" title="Hapus">
                                    <span class="material-symbols-outlined text-[20px]" data-icon="delete">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-stack-lg text-center text-secondary">
                            <div class="flex flex-col items-center gap-2 py-8">
                                <span class="material-symbols-outlined text-[48px] text-surface-container-highest">inbox</span>
                                <p class="font-semibold">Belum ada lamaran</p>
                                <p class="text-sm">{{ $jobId ? 'Belum ada pelamar untuk lowongan ini.' : 'Belum ada lamaran yang masuk.' }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-stack-md border-t border-surface-border bg-surface-container-lowest">
            {{ $applications->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    {{-- Application Detail Modal --}}
    @if($showModal && $selectedApplication)
    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-surface-bg rounded-xl shadow-lg w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="p-margin border-b border-surface-border flex justify-between items-center bg-surface-container-lowest">
                <div>
                    <h2 class="font-headline-md text-headline-md">{{ $selectedApplication->candidate->name }}</h2>
                    <p class="text-body-sm text-secondary">Melamar untuk <span class="font-semibold text-primary">{{ $selectedApplication->job->title }}</span> pada {{ $selectedApplication->created_at->format('d M Y') }}</p>
                </div>
                <button wire:click="$set('showModal', false)" class="text-secondary hover:text-on-surface p-1 rounded-lg hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-margin overflow-y-auto flex-1 space-y-stack-lg bg-surface">
                {{-- Contact Info --}}
                <div class="bg-surface-container-lowest p-stack-md rounded-lg border border-surface-border">
                    <h3 class="font-label-md text-primary mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">contact_mail</span> Informasi Kontak
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-label-sm text-secondary">Email</p>
                            <p class="font-body-md">{{ $selectedApplication->candidate->email }}</p>
                        </div>
                        <div>
                            <p class="text-label-sm text-secondary">Telepon</p>
                            <p class="font-body-md">{{ $selectedApplication->candidate->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-label-sm text-secondary">Status Pipeline</p>
                            <p class="font-body-md font-semibold text-primary">{{ $selectedApplication->stage->name }}</p>
                        </div>
                        <div>
                            <p class="text-label-sm text-secondary">Tanggal Melamar</p>
                            <p class="font-body-md">{{ $selectedApplication->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Application Details / Notes --}}
                @if($selectedApplication->notes->count() > 0)
                <div class="bg-surface-container-lowest p-stack-md rounded-lg border border-surface-border">
                    <h3 class="font-label-md text-primary mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">assignment</span> Detail Lamaran
                    </h3>
                    @php
                        $noteText = $selectedApplication->notes->first()->note;
                        $lines = explode("\n", trim($noteText));
                    @endphp
                    <div class="flex flex-col gap-3">
                        @foreach($lines as $line)
                            @if(trim($line) === '--- Pertanyaan Kustom ---')
                                <h4 class="font-semibold text-primary mt-2 mb-2 border-b border-surface-border pb-1">Jawaban Kustom</h4>
                            @elseif(str_contains($line, ':'))
                                @php
                                    $parts = explode(':', $line, 2);
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-1 md:gap-4 border-b border-surface-border border-dashed pb-2 last:border-0">
                                    <span class="text-secondary text-xs font-medium">{{ trim($parts[0]) }}</span>
                                    <span class="text-on-surface text-sm md:col-span-2 font-semibold whitespace-pre-wrap">{{ trim($parts[1]) }}</span>
                                </div>
                            @elseif(trim($line) !== '')
                                <p class="text-sm text-on-surface whitespace-pre-wrap">{{ trim($line) }}</p>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Files --}}
                <div class="bg-surface-container-lowest p-stack-md rounded-lg border border-surface-border">
                    <h3 class="font-label-md text-primary mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">folder_open</span> Dokumen Terlampir
                    </h3>
                    <div class="space-y-2">
                        @foreach($selectedApplication->getMedia('resumes') as $media)
                        <div class="flex items-center justify-between p-3 border border-outline-variant rounded-lg bg-surface-bg hover:border-primary/50 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-error" style="font-variation-settings:'FILL' 1">picture_as_pdf</span>
                                <div>
                                    <p class="text-sm font-medium">CV / Surat Lamaran</p>
                                    <p class="text-xs text-secondary">{{ $media->name }}</p>
                                </div>
                            </div>
                            <a href="{{ route('media.download', $media->uuid) }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span> Buka
                            </a>
                        </div>
                        @endforeach

                        @foreach($selectedApplication->getMedia('ijazah') as $media)
                        <div class="flex items-center justify-between p-3 border border-outline-variant rounded-lg bg-surface-bg hover:border-primary/50 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600" style="font-variation-settings:'FILL' 1">school</span>
                                <div>
                                    <p class="text-sm font-medium">Ijazah & Transkrip Nilai</p>
                                    <p class="text-xs text-secondary">{{ $media->name }}</p>
                                </div>
                            </div>
                            <a href="{{ route('media.download', $media->uuid) }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span> Buka
                            </a>
                        </div>
                        @endforeach

                        @foreach($selectedApplication->getMedia('documents') as $media)
                        <div class="flex items-center justify-between p-3 border border-outline-variant rounded-lg bg-surface-bg hover:border-primary/50 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary" style="font-variation-settings:'FILL' 1">description</span>
                                <div>
                                    <p class="text-sm font-medium">Berkas Pendukung</p>
                                    <p class="text-xs text-secondary">{{ $media->name }}</p>
                                </div>
                            </div>
                            <a href="{{ route('media.download', $media->uuid) }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span> Buka
                            </a>
                        </div>
                        @endforeach

                        @if($selectedApplication->getMedia('resumes')->isEmpty() && $selectedApplication->getMedia('ijazah')->isEmpty() && $selectedApplication->getMedia('documents')->isEmpty())
                            <p class="text-secondary text-sm text-center py-4">Tidak ada dokumen terlampir.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-margin border-t border-surface-border bg-surface-container-lowest flex justify-end gap-3">
                <button wire:click="$set('showModal', false)" class="px-6 py-2 border border-surface-border text-on-surface rounded-lg hover:bg-surface-container transition-all font-label-md">Tutup</button>
            </div>
        </div>
    </div>
    @endif
            {{-- Google Sheets Integration Modal --}}
    @if($showSheetsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full overflow-hidden" style="max-width: 500px;" @click.outside="$wire.set('showSheetsModal', false)">
            <div class="px-6 py-5 flex justify-between items-center">
                <h3 class="text-base font-medium" style="color: #202124;">Select destination for responses</h3>
                <button wire:click="$set('showSheetsModal', false)" class="p-1.5 rounded-full transition-colors flex items-center justify-center" style="color: #5f6368; ">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            
            <div class="px-6 pb-2 pt-2 bg-white">
                @php
                    $isGoogleConnected = \App\Models\Setting::where('key', 'google_oauth_token')->exists();
                    $currentJob = $jobId ? \App\Models\Job::find($jobId) : null;
                @endphp
                
                @if(!$isGoogleConnected)
                    <div class="mb-6 text-sm flex flex-col gap-4" style="color: #3c4043;">
                        <p>You need to authorize Google Sheets first before creating a destination.</p>
                        <a href="{{ route('google.auth') }}" class="px-4 py-2 border rounded-md font-medium text-sm inline-flex items-center w-max transition-colors gap-2" style="border-color: #dadce0; color: #1a73e8; ">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google" class="w-4 h-4">
                            Connect to Google
                        </a>
                    </div>
                @else
                    @if($currentJob && $currentJob->google_spreadsheet_id)
                        <div class="mb-4 text-sm" style="color: #3c4043;">
                            <p class="mb-4">Spreadsheet already exists for this form.</p>
                            <a href="https://docs.google.com/spreadsheets/d/{{ $currentJob->google_spreadsheet_id }}" target="_blank" class="font-medium hover:underline" style="color: #1a73e8;">
                                Open Spreadsheet
                            </a>
                        </div>
                    @else
                        <!-- Google Forms style radio options -->
                        <div class="flex flex-col gap-4 mb-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <div class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center" style="border-color: #007b83;">
                                    <div class="w-2.5 h-2.5 rounded-full" style="background-color: #007b83;"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <input type="text" readonly value="{{ $currentJob ? $currentJob->title . ' (Responses)' : 'Master Export (All Jobs)' }}" class="border-b outline-none px-0 py-1 text-sm w-full sm:w-[220px] bg-transparent" style="border-color: #dadce0; color: #202124; ">
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-start gap-3 cursor-not-allowed opacity-60" title="Coming soon">
                                <div class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center" style="border-color: #dadce0;">
                                </div>
                                <div class="flex-1">
                                    <span class="text-sm" style="color: #202124;">Select existing spreadsheet</span>
                                </div>
                            </label>
                        </div>
                    @endif
                @endif
            </div>
            
            <div class="px-6 py-4 flex justify-end gap-2">
                <button wire:click="$set('showSheetsModal', false)" class="px-4 py-2 text-sm font-medium rounded-md transition-colors" style="color: #5f6368; ">
                    Cancel
                </button>
                @if($isGoogleConnected && (!$currentJob || !$currentJob->google_spreadsheet_id))
                <button wire:click="createSpreadsheetForJob" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium rounded-md transition-colors flex items-center gap-2 disabled:opacity-50" style="color: #007b83; ">
                    <span wire:loading.remove wire:target="createSpreadsheetForJob">Create</span>
                    <span wire:loading wire:target="createSpreadsheetForJob" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
                    <span wire:loading wire:target="createSpreadsheetForJob">Creating...</span>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    @script
    <script>
        // If sync fails, close the loading Swal
        $wire.on('sync-complete', () => {
            Swal.close();
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
                    // Use .then() to close loading if no event fires (safety net)
                    $wire.syncToGoogleSheets().then(() => {
                        // The show-sheets-sweetalert event will handle opening new Swal
                        // This .then() only runs if event hasn't already replaced the Swal
                    }).catch(() => {
                        Swal.close();
                    });
                }
            });
        });
    </script>
    @endscript
</div>
