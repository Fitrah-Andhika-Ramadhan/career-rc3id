<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Job;
use App\Models\Candidate;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationSubmitted;
use App\Mail\NewApplicationNotification;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithFileUploads;

    public Job $job;

    public bool $isSubmitted = false;
    public bool $isClosed = false;

    // Step 1: Base Identity
    public $full_name = '';
    public $email = '';
    public $phone = '';
    public $dob = '';

    // Terms
    public $terms = false;

    // Custom Fields
    public array $customFields = [];
    public array $customAnswers = [];

    // Multi-Step Logic
    public int $currentStep = 0;
    public array $pages = [];
    public int $totalPages = 0;

    public function mount(Job $job)
    {
        if ($job->status !== 'published') {
            if (!auth()->check() || !auth()->user()->can('access custom form')) {
                abort(404, 'Job not found or closed.');
            }
        }

        if ($job->deadline_date && now()->startOfDay()->gt($job->deadline_date)) {
            $this->isClosed = true;
        }

        $this->job = $job;
        $raw = $job->custom_fields ?? '[]';
        $this->customFields = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
        
        if (empty($this->customFields)) {
            $this->customFields = [
                ['id' => uniqid('field_'), 'type' => 'section', 'label' => 'IDENTITAS DIRI', 'description' => ''],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nama Lengkap', 'required' => true],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Email', 'required' => true],
                ['id' => uniqid('field_'), 'type' => 'date', 'label' => 'Tanggal lahir', 'required' => false],
                ['id' => uniqid('field_'), 'type' => 'text', 'label' => 'Nomor telepon', 'required' => true],
            ];
        }

        // Build pages
        $this->pages = [];
        $this->pages[0] = [
            'title' => '',
            'description' => '',
            'fields' => []
        ];

        $pageIndex = 0;
        foreach ($this->customFields as $field) {
            $this->customAnswers[$field['id']] = $field['type'] === 'checkbox' ? [] : '';
            
            if (($field['type'] ?? 'text') === 'section') {
                if (empty($this->pages[$pageIndex]['fields']) && empty($this->pages[$pageIndex]['title'])) {
                    // if it's the very first section and page is empty, use it for page 0
                    $this->pages[$pageIndex]['title'] = $field['label'] ?: 'Bagian Baru';
                    $this->pages[$pageIndex]['description'] = $field['description'] ?? '';
                } else {
                    $pageIndex++;
                    $this->pages[$pageIndex] = [
                        'title' => $field['label'] ?: 'Bagian Baru',
                        'description' => $field['description'] ?? '',
                        'fields' => []
                    ];
                }
            } else {
                $this->pages[$pageIndex]['fields'][] = $field;
            }
        }
        $this->totalPages = count($this->pages);
    }

    public function extractIdentityVariables()
    {
        $this->full_name = '';
        $this->email = '';
        $this->phone = '';
        $this->dob = '';
        
        foreach ($this->customFields as $field) {
            if (in_array($field['type'] ?? 'text', ['title', 'section', 'image', 'video'])) continue;
            
            $label = strtolower($field['label']);
            $val = $this->customAnswers[$field['id']] ?? '';
            
            if (!$val) continue;

            if (str_contains($label, 'nama') || str_contains($label, 'name')) {
                if (!$this->full_name) $this->full_name = $val;
            }
            elseif (str_contains($label, 'email') || str_contains($label, 'surel')) {
                if (!$this->email) $this->email = $val;
            }
            elseif (str_contains($label, 'telepon') || str_contains($label, 'phone') || str_contains($label, 'hp')) {
                if (!$this->phone) $this->phone = $val;
            }
            elseif (str_contains($label, 'lahir') || str_contains($label, 'dob') || str_contains($label, 'birth')) {
                if (!$this->dob) $this->dob = $val;
            }
        }
        
        if (!$this->email || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->email = uniqid('applicant_') . '@example.com'; // Fallback
        }
        if (!$this->full_name) {
            $this->full_name = 'Anonymous Applicant';
        }
    }

    public function nextStep()
    {
        $rules = [];
        $messages = [
            'customAnswers.*.required' => 'Field ini wajib diisi.',
            'customAnswers.*.file' => 'File tidak valid.',
        ];

        // Custom fields validation for current step
        if (isset($this->pages[$this->currentStep]['fields'])) {
            foreach ($this->pages[$this->currentStep]['fields'] as $field) {
                if (in_array($field['type'] ?? 'text', ['title', 'image', 'video', 'section'])) continue;
                
                $rule = ($field['required'] ?? false) ? 'required' : 'nullable';
                if (($field['type'] ?? 'text') === 'file') {
                    $rule .= '|file|max:10240';
                } elseif (($field['type'] ?? 'text') === 'checkbox') {
                    $rule .= '|array';
                } else {
                    $rule .= '|string';
                }
                $rules["customAnswers.{$field['id']}"] = $rule;
            }
        }

        if (count($rules) > 0) {
            $this->validate($rules, $messages);
        }
        
        // Extract identity variables dynamically
        $this->extractIdentityVariables();

        // Check if candidate already applied (if restriction is enabled)
        $restrictOneApply = filter_var(env('RESTRICT_ONE_APPLY', true), FILTER_VALIDATE_BOOLEAN);
        if ($restrictOneApply && !str_starts_with($this->email, 'applicant_')) {
            $existingCandidate = Candidate::where('email', $this->email)->first();
            if ($existingCandidate) {
                $alreadyApplied = Application::where('candidate_id', $existingCandidate->id)
                    ->where('job_id', $this->job->id)
                    ->exists();
                if ($alreadyApplied) {
                    $emailFieldId = null;
                    foreach ($this->customFields as $f) {
                        if (str_contains(strtolower($f['label']), 'email') || str_contains(strtolower($f['label']), 'surel')) {
                            $emailFieldId = $f['id']; break;
                        }
                    }
                    if ($emailFieldId) {
                        $this->addError("customAnswers.{$emailFieldId}", 'Anda sudah pernah melamar untuk posisi ini sebelumnya.');
                    }
                    return; // Halt if already applied
                }
            }
        }

        if ($this->currentStep < $this->totalPages - 1) {
            $this->currentStep++;
            $this->dispatch('scroll-to-top');
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 0) {
            $this->currentStep--;
            $this->dispatch('scroll-to-top');
        }
    }

    public function submit()
    {
        if ($this->isClosed) return;

        // Run validation for the last step just in case
        $this->nextStep();
        if ($this->getErrorBag()->any()) {
            return;
        }

        $this->validate(['terms' => 'accepted']);

        // Run DB transaction — save data only
        $candidate   = null;
        $application = null;

        DB::transaction(function () use (&$candidate, &$application) {
            // Find or create candidate
            $candidate = Candidate::firstOrCreate(
                ['email' => $this->email],
                [
                    'name'  => $this->full_name,
                    'phone' => $this->phone,
                ]
            );

            // Create Application
            $application = Application::create([
                'candidate_id'      => $candidate->id,
                'job_id'            => $this->job->id,
                'pipeline_stage_id' => 1, // Applied
            ]);

            // Save application notes + custom fields (from all pages)
            $customNotes = "";
            if (!empty($this->customFields)) {
                $customNotes .= "\n\n--- Pertanyaan Kustom ---\n";
                foreach ($this->customFields as $field) {
                    if (($field['type'] ?? 'text') === 'title' || ($field['type'] ?? 'text') === 'section') continue;
                    
                    $answer = $this->customAnswers[$field['id']] ?? '-';
                    if (is_array($answer)) {
                        $answer = implode(', ', $answer);
                    } elseif ($field['type'] === 'file' && $answer instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $application->addMedia($answer->getRealPath())
                                   ->usingName($answer->getClientOriginalName())
                                   ->toMediaCollection('documents');
                        $answer = "Berkas dilampirkan: " . $answer->getClientOriginalName();
                    }
                    $customNotes .= "{$field['label']}: {$answer}\n";
                }
            }

            $application->notes()->create([
                'note' => "Date of Birth: {$this->dob}\n" . $customNotes,
            ]);

            // Record stage history
            $application->stageHistories()->create([
                'new_stage_id' => 1,
            ]);
        });

        // ── Send emails AFTER transaction committed ──────────────────
        try {
            Mail::to($candidate->email)->queue(new ApplicationSubmitted($candidate, $this->job));
        } catch (\Exception $e) {
            \Log::warning('[EMAIL] Failed to queue applicant confirmation: ' . $e->getMessage());
        }

        $hrEmail = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', '');
        $cnlEmail = env('MAIL_CNL_ADDRESS', 'cl.rc3id@unpad.ac.id');
        
        if ($hrEmail && filter_var($hrEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $mail = Mail::to($hrEmail);
                if ($cnlEmail && filter_var($cnlEmail, FILTER_VALIDATE_EMAIL) && $cnlEmail !== $hrEmail) {
                    $mail->cc($cnlEmail);
                }
                $mail->queue(new NewApplicationNotification($candidate, $this->job, $application));
            } catch (\Exception $e) {
                \Log::error('[EMAIL] Failed to send HR notification: ' . $e->getMessage());
            }
        }

        try {
            $admins = \App\Models\User::role(['Super Admin', 'Admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewApplicationNotification($application));
        } catch (\Exception $e) {
            \Log::error('[NOTIFICATION] Failed to send database notification: ' . $e->getMessage());
        }

        $this->isSubmitted = true;
    }
};
?>

<div class="bg-[#f0ebf8] min-h-screen w-full font-sans">
    <div class="max-w-[770px] mx-auto px-4 sm:px-6 py-8">
    @if($isClosed)
        <!-- Closed Message -->
        <div class="mb-6 text-left">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-secondary hover:text-primary transition-colors font-semibold text-sm">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Lowongan
            </a>
        </div>
        
        <div class="text-center py-stack-lg bg-surface-bg border border-surface-border rounded-xl form-card mt-8">
            <div class="w-20 h-20 bg-error/10 text-error rounded-full flex items-center justify-center mx-auto mb-stack-md">
                <span class="material-symbols-outlined text-[48px]" data-icon="block">block</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">Pendaftaran Ditutup</h2>
            <p class="font-body-lg text-body-lg text-secondary mb-stack-lg px-margin">
                {{ $job->closed_message ?: 'Lowongan ini telah ditutup dan tidak lagi menerima lamaran.' }}
            </p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('home') }}" class="px-6 py-3 rounded-lg bg-surface-container-high text-on-surface font-label-md text-label-md shadow-sm hover:bg-surface-variant transition-all">Lihat Lowongan Lainnya</a>
            </div>
        </div>
    @elseif(!$isSubmitted)
        <!-- Back Button -->
        <div class="mb-6 text-left">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-secondary hover:text-primary transition-colors font-semibold text-sm">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Lowongan
            </a>
        </div>
        
        <!-- Hero Section -->
        <section class="mb-4 text-left bg-surface-bg border border-surface-border border-t-[8px] border-t-primary rounded-xl px-6 py-8 shadow-sm">
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-4">{{ $job->title }}</h1>
            <div class="prose prose-sm md:prose-base dark:prose-invert max-w-none prose-a:text-primary hover:prose-a:text-primary-fixed text-left mb-6">
                {!! nl2br($job->description) ?? 'Join our team.' !!}
            </div>
            
            <div class="flex items-center gap-2 pt-4 border-t border-surface-border/60">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-surface-container-low text-secondary font-label-sm text-label-sm">
                    <span class="material-symbols-outlined text-sm" data-icon="work">work</span>
                    {{ $job->department }} · {{ $job->work_type }}
                </div>
            </div>
            <div class="text-error text-sm mt-4">* Indicates required question</div>
        </section>

        <!-- Multi-Step Form Container -->
        <div class="w-full">
            @if($totalPages > 1)
                <!-- Progress Bar -->
                <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-4 shadow-sm flex flex-col gap-2 mb-4">
                    <div class="flex justify-between items-center text-sm font-semibold text-secondary">
                        <span>Langkah {{ $currentStep + 1 }} dari {{ $totalPages }}</span>
                        <span>{{ round((($currentStep + 1) / $totalPages) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-300" style="width: {{ (($currentStep + 1) / $totalPages) * 100 }}%"></div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="space-y-4 pb-12">
                
                @if(isset($pages[$currentStep]))
                    @if($currentStep > 0)
                        <!-- Step Header -->
                        <div class="bg-surface-bg border border-surface-border border-t-[8px] border-t-primary rounded-xl px-6 py-6 shadow-sm">
                            <h2 class="font-headline-lg text-headline-lg mb-2">{{ $pages[$currentStep]['title'] }}</h2>
                            @if(!empty($pages[$currentStep]['description']))
                                <p class="font-body-md text-body-md text-secondary">{{ $pages[$currentStep]['description'] }}</p>
                            @endif
                        </div>
                    @elseif($currentStep === 0)
                        <!-- Step Header for Step 0 (Optional, usually just text in GForms) -->
                        <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                            <h2 class="font-headline-sm text-headline-sm uppercase">{{ $pages[$currentStep]['title'] }}</h2>
                        </div>
                    @endif



                    <!-- Custom Fields for Current Step -->
                    @if(count($pages[$currentStep]['fields']) > 0)
                        @foreach($pages[$currentStep]['fields'] as $field)
                            @if(($field['type'] ?? 'text') === 'title')
                                <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                                    <h4 class="font-headline-sm text-headline-sm text-on-surface mb-1">{{ $field['label'] }}</h4>
                                    @if(!empty($field['description']))
                                        <p class="font-body-sm text-body-sm text-secondary">{{ $field['description'] }}</p>
                                    @endif
                                </div>
                            @elseif(($field['type'] ?? 'text') === 'image')
                                <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                                    <label class="font-headline-sm text-headline-sm text-on-surface block">{{ $field['label'] }}</label>
                                    @if(!empty($field['url']))
                                        <img src="{{ $field['url'] }}" class="max-w-full h-auto rounded-xl border border-surface-border mt-3" style="max-height: 400px" alt="{{ $field['label'] }}" onerror="this.src='https://placehold.co/600x400?text=Invalid+Image+URL'">
                                    @endif
                                </div>
                            @elseif(($field['type'] ?? 'text') === 'video')
                                <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                                    <label class="font-headline-sm text-headline-sm text-on-surface block">{{ $field['label'] }}</label>
                                    @if(!empty($field['url']))
                                        <div class="w-full aspect-video rounded-xl overflow-hidden border border-surface-border mt-3">
                                            <iframe class="w-full h-full" src="{{ str_replace('watch?v=', 'embed/', $field['url']) }}" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                                    <label class="font-label-md text-label-md text-on-surface-variant block mb-4">
                                        {{ $field['label'] }} @if($field['required']) <span class="text-error">*</span> @endif
                                    </label>

                                    @if($field['type'] === 'text')
                                        <input wire:model.blur="customAnswers.{{ $field['id'] }}" type="text" placeholder="Jawaban Anda"
                                            class="w-full px-4 py-3 border-b border-surface-border focus:border-primary hover:border-outline-variant bg-transparent transition-all outline-none" 
                                            @if($field['required']) required @endif />
                                    
                                    @elseif($field['type'] === 'number')
                                        <input wire:model.blur="customAnswers.{{ $field['id'] }}" type="number" placeholder="Jawaban Anda"
                                            class="w-full max-w-[300px] px-4 py-3 border-b border-surface-border focus:border-primary hover:border-outline-variant bg-transparent transition-all outline-none" 
                                            @if($field['required']) required @endif />
                                    
                                    @elseif($field['type'] === 'date')
                                        <input wire:model.blur="customAnswers.{{ $field['id'] }}" type="date" 
                                            class="w-full max-w-[200px] px-4 py-3 border-b border-surface-border focus:border-primary hover:border-outline-variant bg-transparent transition-all outline-none" 
                                            @if($field['required']) required @endif />
                                            
                                    @elseif($field['type'] === 'textarea')
                                        <textarea wire:model.blur="customAnswers.{{ $field['id'] }}" rows="3" placeholder="Jawaban Anda"
                                            class="w-full px-4 py-3 border-b border-surface-border focus:border-primary hover:border-outline-variant bg-transparent transition-all outline-none resize-none" 
                                            @if($field['required']) required @endif></textarea>
                                            
                                    @elseif($field['type'] === 'select')
                                        <select wire:model.blur="customAnswers.{{ $field['id'] }}" 
                                            class="w-full max-w-[300px] px-4 py-3 border border-surface-border rounded-md focus:border-primary hover:border-outline-variant bg-transparent transition-all outline-none" 
                                            @if($field['required']) required @endif>
                                            <option value="">Pilih opsi...</option>
                                            @foreach($field['options'] as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                        
                                    @elseif($field['type'] === 'radio')
                                        <div class="flex flex-col gap-3">
                                            @foreach($field['options'] as $opt)
                                            <label class="flex items-center gap-3 text-body-md cursor-pointer group">
                                                <input wire:model="customAnswers.{{ $field['id'] }}" type="radio" value="{{ $opt }}" 
                                                    class="w-5 h-5 text-primary focus:ring-primary border-outline-variant group-hover:border-primary transition-colors" 
                                                    @if($field['required']) required @endif />
                                                <span>{{ $opt }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                        
                                    @elseif($field['type'] === 'checkbox')
                                        <div class="flex flex-col gap-3">
                                            @foreach($field['options'] as $opt)
                                            <label class="flex items-center gap-3 text-body-md cursor-pointer group">
                                                <input wire:model="customAnswers.{{ $field['id'] }}" type="checkbox" value="{{ $opt }}" 
                                                    class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant group-hover:border-primary transition-colors" />
                                                <span>{{ $opt }}</span>
                                            </label>
                                            @endforeach
                                        </div>

                                    @elseif($field['type'] === 'file')
                                        <input wire:model="customAnswers.{{ $field['id'] }}" type="file" 
                                            class="w-full max-w-sm border border-surface-border p-2 rounded-lg bg-surface-container-low text-sm" 
                                            @if($field['required']) required @endif />
                                        <div wire:loading wire:target="customAnswers.{{ $field['id'] }}" class="text-sm text-primary flex items-center gap-1 mt-2">
                                            <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Mengupload...
                                        </div>
                                    @endif

                                    @error("customAnswers.{$field['id']}") 
                                        <span class="text-error text-sm block mt-2">{{ $message }}</span> 
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    @endif

                    <!-- Terms & Actions -->
                    @if($currentStep === $totalPages - 1)
                        <div class="bg-surface-bg border border-surface-border rounded-xl px-6 py-6 shadow-sm">
                            <div class="flex items-start gap-3">
                                <input wire:model="terms" type="checkbox" class="mt-1 w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" required/>
                                <label class="font-body-sm text-body-sm text-secondary pt-0.5">
                                    I consent to the processing of my personal data for recruitment purposes.
                                </label>
                            </div>
                        </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center mt-6">
                        <div>
                            @if($currentStep > 0)
                                <button type="button" wire:click="previousStep"
                                    class="px-6 py-3 rounded-lg bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-all">
                                    Kembali
                                </button>
                            @endif
                        </div>
                        
                        <div>
                            @if($currentStep < $totalPages - 1)
                                <button type="button" wire:click="nextStep"
                                    class="px-8 py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md shadow-md hover:bg-primary-container transition-all flex items-center gap-2">
                                    Berikutnya <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                </button>
                            @else
                                <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="submit"
                                    class="px-8 py-3 rounded-lg bg-success text-on-primary font-label-md text-label-md shadow-md hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="submit" class="material-symbols-outlined text-[18px]">send</span>
                                    <span wire:loading wire:target="submit" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                    <span wire:loading.remove wire:target="submit">Kirim Lamaran</span>
                                    <span wire:loading wire:target="submit">Sedang Memproses...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- Loading Overlay Popup --}}
        <div wire:loading wire:target="submit"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-surface-bg rounded-2xl shadow-2xl p-10 flex flex-col items-center gap-5 max-w-sm w-full mx-4 border border-surface-border">
                <div class="relative w-20 h-20">
                    <svg class="animate-spin w-20 h-20 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[28px]" style="font-variation-settings:'FILL' 1">work</span>
                    </span>
                </div>
                <div class="text-center">
                    <h3 class="font-headline-md text-on-surface font-semibold mb-2">Mengirim Lamaran Anda...</h3>
                    <p class="text-secondary text-body-sm">Mohon tunggu, kami sedang memproses data dan dokumen Anda. Jangan tutup halaman ini.</p>
                </div>
                <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                    <div class="h-full bg-primary rounded-full animate-pulse" style="width: 70%;"></div>
                </div>
            </div>
        </div>
    @else
        <!-- Success Message -->
        <div class="text-center py-stack-lg bg-surface-bg border border-surface-border rounded-xl form-card">
            <div class="w-20 h-20 bg-success/10 text-success rounded-full flex items-center justify-center mx-auto mb-stack-md">
                <span class="material-symbols-outlined text-[48px]" data-icon="check_circle">check_circle</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">Lamaran Berhasil Terkirim!</h2>
            <p class="font-body-lg text-body-lg text-secondary mb-stack-lg px-margin">
                Terima kasih telah melamar, {{ $full_name }}. Tim HR kami akan meninjau lamaran Anda untuk posisi {{ $job->title }} dan akan segera menghubungi Anda.
            </p>
            <div class="flex justify-center gap-4">
                <a href="/" class="px-6 py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md shadow-md hover:bg-primary-container transition-all">Kembali ke Halaman Utama</a>
            </div>
        </div>
    @endif
</div>
</div>