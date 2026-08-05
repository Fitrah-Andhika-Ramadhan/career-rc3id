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
    public $showKanbanModal = false;
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
        if (!$this->jobId) return;
        
        $job = Job::find($this->jobId);
        if (!$job || $job->google_spreadsheet_id) return;

        $tokenStr = \App\Models\Setting::where('key', 'google_oauth_token')->value('value');
        if (!$tokenStr) return;

        try {
            $client = new \Google\Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setAccessToken(json_decode($tokenStr, true));

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    \App\Models\Setting::updateOrCreate(
                        ['key' => 'google_oauth_token'],
                        ['value' => json_encode($client->getAccessToken())]
                    );
                }
            }

            $service = new \Google\Service\Sheets($client);
            
            // Create Spreadsheet
            $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
                'properties' => [
                    'title' => 'ATS Responses - ' . $job->title
                ]
            ]);
            $spreadsheet = $service->spreadsheets->create($spreadsheet);
            
            // Add Header Row
            $values = [
                ["ID", "Nama Kandidat", "Email", "Telepon", "LinkedIn", "Portfolio", "Tanggal Melamar"]
            ];
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => 'RAW'
            ];
            
            $service->spreadsheets_values->update(
                $spreadsheet->spreadsheetId,
                'Sheet1!A1:G1',
                $body,
                $params
            );

            // Save ID
            $job->google_spreadsheet_id = $spreadsheet->spreadsheetId;
            $job->save();

            $this->dispatch('notify', ['message' => 'Spreadsheet berhasil dibuat!', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Gagal membuat Spreadsheet: ' . $e->getMessage(), 'type' => 'error']);
        }
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

    public function exportCsv()
    {
        $query = Application::with(['candidate', 'job', 'stage']);
        if ($this->jobId) $query->where('job_id', $this->jobId);
        $applications = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=candidates-" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($applications) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['ID', 'Nama Kandidat', 'Email', 'Telepon', 'Posisi Dilamar', 'Departemen', 'Status', 'Tanggal Melamar']);
            foreach ($applications as $app) {
                fputcsv($file, [
                    $app->id,
                    $app->candidate->name,
                    $app->candidate->email,
                    $app->candidate->phone ?? '-',
                    $app->job->title,
                    $app->job->department ?? '-',
                    $app->stage->name,
                    $app->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'candidates-' . date('Y-m-d') . '.csv', $headers);
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
        $csvContent .= "ID,Nama Kandidat,Email,Telepon,Posisi Dilamar,Departemen,Status,Tanggal Melamar\n";
        foreach ($applications as $app) {
            $csvContent .= implode(',', [
                $app->id,
                '"' . $app->candidate->name . '"',
                $app->candidate->email,
                $app->candidate->phone ?? '-',
                '"' . $app->job->title . '"',
                '"' . ($app->job->department ?? '-') . '"',
                $app->stage->name,
                $app->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
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

        return response()->download($zipPath, 'ATS-Export-' . date('Y-m-d') . '.zip')->deleteFileAfterSend(true);
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
            @can('access settings')
            <button wire:click="$set('showSheetsModal', true)"
                class="px-4 py-2 bg-success/10 text-success border border-success/20 rounded-lg font-label-md flex items-center gap-2 hover:bg-success/20 shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">table_view</span>
                <span>Google Sheets</span>
            </button>
            @endcan
            <button wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                class="px-4 py-2 bg-surface-container text-on-surface border border-surface-border rounded-lg font-label-md flex items-center gap-2 hover:bg-surface-variant shadow-sm transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="exportCsv" class="material-symbols-outlined text-[18px]">table_chart</span>
                <span wire:loading wire:target="exportCsv" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting...</span>
            </button>
            <button wire:click="exportZip" wire:loading.attr="disabled" wire:target="exportZip"
                class="px-4 py-2 bg-primary text-white rounded-lg font-label-md flex items-center gap-2 hover:opacity-90 shadow-sm transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="exportZip" class="material-symbols-outlined text-[18px]">folder_zip</span>
                <span wire:loading wire:target="exportZip" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                <span wire:loading.remove wire:target="exportZip">Export ZIP (ATS)</span>
                <span wire:loading wire:target="exportZip">Packing...</span>
            </button>
            @can('manage kanban')
            <button wire:click="$set('showKanbanModal', true)" class="px-4 py-2 bg-info text-white rounded-lg font-label-md flex items-center gap-2 hover:opacity-90 shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">view_kanban</span>
                Buka Papan Kanban
            </button>
            @endcan
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
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
                    <div class="prose text-body-md whitespace-pre-wrap text-on-surface-variant text-sm leading-relaxed">{{ $selectedApplication->notes->first()->note }}</div>
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
                            <a href="{{ $media->getUrl() }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
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
                            <a href="{{ $media->getUrl() }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
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
                            <a href="{{ $media->getUrl() }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-sm font-semibold">
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
    {{-- Kanban Modal --}}
    @if($showKanbanModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-md"
         x-data="{}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="$wire.set('showKanbanModal', false)" class="bg-surface-bg rounded-2xl shadow-2xl w-full h-[95vh] flex flex-col relative overflow-hidden mx-4"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95">
             
            <div class="px-6 py-4 flex justify-between items-center bg-white border-b border-surface-border sticky top-0 z-10 shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center text-info">
                        <span class="material-symbols-outlined">view_kanban</span>
                    </div>
                    <div>
                        <h2 class="font-headline-md text-lg font-bold text-on-surface leading-tight">
                            Kanban Pipeline (HR)
                        </h2>
                        <p class="text-xs text-secondary">{{ $jobId ? 'Filter: ' . ($jobs->firstWhere('id', $jobId)?->title ?? '') : 'Menampilkan Semua Lowongan' }}</p>
                    </div>
                </div>
                <button wire:click="$set('showKanbanModal', false)" class="text-secondary hover:text-error hover:bg-error/10 transition-colors w-9 h-9 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6 flex-1 overflow-x-auto bg-[#f8fafc] flex gap-4 h-full">
                @php
                    $stages = \App\Models\PipelineStage::orderBy('order')->get();
                @endphp
                @foreach($stages as $stage)
                <div class="w-[300px] flex-shrink-0 flex flex-col bg-surface-container-lowest rounded-xl shadow-sm border border-surface-border overflow-hidden h-full">
                    <div class="p-3 bg-surface-container border-b border-surface-border flex justify-between items-center">
                        <h3 class="font-bold text-sm text-on-surface">{{ $stage->name }}</h3>
                        <span class="bg-surface-variant text-on-surface-variant text-xs px-2 py-0.5 rounded-full font-semibold">
                            {{ $applications->where('pipeline_stage_id', $stage->id)->count() }}
                        </span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3">
                        @foreach($applications->where('pipeline_stage_id', $stage->id) as $app)
                        <div class="bg-surface-bg border border-surface-border p-3 rounded-lg shadow-sm hover:shadow-md transition-all group">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-semibold text-sm text-on-surface">{{ $app->candidate->name }}</p>
                                <button wire:click="viewDetails({{ $app->id }})" class="text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                </button>
                            </div>
                            <p class="text-xs text-secondary truncate">{{ $app->job->title }}</p>
                            <p class="text-[10px] text-secondary/70 mt-1">{{ $app->created_at->diffForHumans() }}</p>
                            
                            <div class="mt-3 flex gap-2 justify-between border-t border-surface-border pt-2">
                                @if($stage->id > 1)
                                <button wire:click="updateStage({{ $app->id }}, {{ $stage->id - 1 }})" class="text-[10px] flex items-center gap-1 text-secondary hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">arrow_back</span> Prev
                                </button>
                                @else
                                <div></div>
                                @endif
                                
                                @if($stage->id < $stages->count())
                                <button wire:click="updateStage({{ $app->id }}, {{ $stage->id + 1 }})" class="text-[10px] flex items-center gap-1 text-primary hover:text-info transition-colors font-semibold">
                                    Next <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
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
                                        <span class="text-sm" style="color: #202124;">Create a new spreadsheet</span>
                                        <input type="text" readonly value="{{ $currentJob ? $currentJob->title . ' (Responses)' : 'Job Application (Responses)' }}" class="border-b outline-none px-0 py-1 text-sm w-full sm:w-[220px] bg-transparent" style="border-color: #dadce0; color: #202124; ">
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
</div>

