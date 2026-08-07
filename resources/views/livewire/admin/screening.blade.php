<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Application;
use App\Models\Job;
use Livewire\WithPagination;

new
#[Layout('layouts.admin')]
class extends Component
{
    use WithPagination;

    #[\Livewire\Attributes\Url]
    public $jobId = '';
    public $showModal = false;
    public $selectedApplication = null;

    public function updateStage($appId, $newStageId)
    {
        $app = Application::find($appId);
        if ($app) {
            $app->pipeline_stage_id = $newStageId;
            $app->save();
        }
    }

    public function mount($initialJobId = null)
    {
        if ($initialJobId) {
            $this->jobId = $initialJobId;
        } elseif (request()->query('jobId')) {
            $this->jobId = request()->query('jobId');
        }
    }

    public function updatingJobId() { $this->resetPage(); }

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

    public function with(): array
    {
        $query = Application::with(['candidate', 'job', 'stage']);
        if ($this->jobId) {
            $query->where('job_id', $this->jobId);
        }
        
        $jobs = Job::withCount('applications')->orderBy('created_at', 'desc')->get();

        return [
            'applications' => $query->orderBy('created_at', 'desc')->get(),
            'jobs'         => $jobs,
        ];
    }
};
?>

<div>
    {{-- Page Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-display-sm font-bold text-on-surface">Screening Pipeline</h1>
            <p class="text-body-md text-secondary mt-1">Kelola dan sortir kandidat dalam alur Kanban.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative min-w-[250px]">
                <select wire:model.live="jobId" class="w-full bg-surface-container-lowest border border-surface-border text-on-surface text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 appearance-none">
                    <option value="">Semua Lowongan</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}">{{ $job->title }} ({{ $job->applications_count }} pelamar)</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-secondary">
                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Board Full Page --}}
    <div class="bg-[#f8fafc] flex gap-4 overflow-x-auto overflow-y-hidden rounded-xl border border-surface-border p-4" style="height: calc(100vh - 180px); min-height: 500px;">
        @php
            $stages = \App\Models\PipelineStage::orderBy('order')->get();
        @endphp
        @foreach($stages as $stage)
        <div class="w-[300px] flex-shrink-0 flex flex-col bg-surface-container-lowest rounded-xl shadow-sm border border-surface-border overflow-hidden h-full">
            <div class="p-3 bg-surface-container border-b border-surface-border flex justify-between items-center shrink-0">
                <h3 class="font-bold text-sm text-on-surface">{{ $stage->name }}</h3>
                <span class="bg-surface-variant text-on-surface-variant text-xs px-2 py-0.5 rounded-full font-semibold">
                    {{ $applications->where('pipeline_stage_id', $stage->id)->count() }}
                </span>
            </div>
            
            <div class="p-3 flex-1 overflow-y-auto space-y-3 custom-scrollbar">
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
</div>
