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
    public array $otherAnswers = [];

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
                
                // Validate 'Other' input
                if (($field['type'] ?? 'text') === 'radio' && ($this->customAnswers[$field['id']] ?? '') === '__other__') {
                    $rules["otherAnswers.{$field['id']}"] = 'required|string';
                }
                if (($field['type'] ?? 'text') === 'checkbox' && is_array($this->customAnswers[$field['id']] ?? null) && in_array('__other__', $this->customAnswers[$field['id']])) {
                    $rules["otherAnswers.{$field['id']}"] = 'required|string';
                }
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
                        if (in_array('__other__', $answer)) {
                            $otherText = $this->otherAnswers[$field['id']] ?? '';
                            $answer = array_map(function($a) use ($otherText) {
                                return $a === '__other__' ? $otherText : $a;
                            }, $answer);
                        }
                        $answer = implode(', ', $answer);
                    } elseif ($answer === '__other__') {
                        $answer = $this->otherAnswers[$field['id']] ?? '';
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
            Mail::to($candidate->email)->send(new ApplicationSubmitted($candidate, $this->job));
        } catch (\Exception $e) {
            \Log::warning('[EMAIL] Failed to queue applicant confirmation: ' . $e->getMessage());
        }

        $notificationEmailsStr = env('MAIL_NOTIFICATION_ADDRESSES', 'cl.rc3id@unpad.ac.id');
        
        if ($notificationEmailsStr) {
            $emails = array_filter(array_map('trim', explode(',', $notificationEmailsStr)), function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            
            $validEmails = array_values($emails);
            
            if (count($validEmails) > 0) {
                try {
                    $mail = Mail::to($validEmails[0]);
                    if (count($validEmails) > 1) {
                        $mail->cc(array_slice($validEmails, 1));
                    }
                    $mail->send(new NewApplicationNotification($candidate, $this->job, $application));
                } catch (\Exception $e) {
                    \Log::error('[EMAIL] Failed to send HR notification: ' . $e->getMessage());
                }
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

<div class="bg-surface-container-lowest min-h-screen w-full font-sans relative pb-20">
    {{-- Elegant background gradient --}}
    <div class="absolute top-0 left-0 w-full h-[320px] bg-primary/5 z-0 pointer-events-none"></div>
    <div class="absolute top-0 left-0 w-full h-[320px] bg-gradient-to-b from-primary/10 to-surface-container-lowest z-0 pointer-events-none"></div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 relative z-10">
    @if($isClosed)
        <!-- Closed Message (Card style) -->
        <div class="mb-8">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 text-secondary hover:text-primary transition-colors font-semibold text-sm bg-surface-bg/50 backdrop-blur-sm px-4 py-2 rounded-full border border-surface-border shadow-sm">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                {{ __('Kembali ke Daftar Lowongan') }}
            </a>
        </div>
        
        <div class="text-center p-10 bg-surface-bg border border-surface-border rounded-2xl shadow-lg mt-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-error"></div>
            <div class="w-24 h-24 bg-error/10 text-error rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-[48px]" data-icon="block">block</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-3">{{ __('Pendaftaran Ditutup') }}</h2>
            <p class="font-body-lg text-body-lg text-secondary mb-8 px-4">
                {{ $job->closed_message ?: __('Mohon maaf, lowongan ini telah ditutup dan tidak lagi menerima lamaran baru.') }}
            </p>
            <div class="flex justify-center">
                <a href="{{ route('home') }}" wire:navigate class="px-8 py-3 rounded-xl bg-primary text-on-primary font-semibold text-sm shadow-md hover:bg-primary-container transition-all">{{ __('Lihat Lowongan Lainnya') }}</a>
            </div>
        </div>
    @elseif(!$isSubmitted)
        <!-- Back Button -->
        <div class="mb-8">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 text-secondary hover:text-primary transition-colors font-semibold text-sm bg-surface-bg/50 backdrop-blur-sm px-4 py-2 rounded-full border border-surface-border shadow-sm">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                {{ __('Kembali ke Daftar Lowongan') }}
            </a>
        </div>
        
        <!-- Modern Hero Section -->
        <section class="mb-8 text-center sm:text-left flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center shrink-0 border border-primary/20 shadow-sm">
                <span class="material-symbols-outlined text-primary text-[32px]">work</span>
            </div>
            <div>
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-3 font-bold">{{ $job->title }}</h1>
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-bg border border-surface-border text-secondary text-xs font-semibold shadow-sm">
                        <span class="material-symbols-outlined text-[14px]">domain</span> {{ $job->department }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-bg border border-surface-border text-secondary text-xs font-semibold shadow-sm">
                        <span class="material-symbols-outlined text-[14px]">schedule</span> {{ $job->work_type }}
                    </span>
                    @if($job->location)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-bg border border-surface-border text-secondary text-xs font-semibold shadow-sm">
                        <span class="material-symbols-outlined text-[14px]">location_on</span> {{ $job->location }}
                    </span>
                    @endif
                </div>
                <div class="prose prose-sm dark:prose-invert max-w-none text-secondary">
                    {!! nl2br($job->description) ?? __('Silakan lengkapi form pendaftaran di bawah ini dengan data yang sebenarnya.') !!}
                </div>
            </div>
        </section>

        <!-- Multi-Step Form Container -->
        <div class="w-full">
            @if($totalPages > 1)
                <!-- Modern Stepper UI -->
                <div class="mb-10 flex items-center justify-center max-w-xl mx-auto">
                    @for($i = 0; $i < $totalPages; $i++)
                        <div class="flex items-center">
                            <div class="flex flex-col items-center relative">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 z-10 border-2 {{ $i < $currentStep ? 'bg-primary border-primary text-on-primary' : ($i === $currentStep ? 'bg-surface-bg border-primary text-primary shadow-[0_0_0_4px_rgba(var(--color-primary-rgb),0.1)]' : 'bg-surface-bg border-surface-border text-secondary') }}">
                                    @if($i < $currentStep)
                                        <span class="material-symbols-outlined text-[20px]">check</span>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="absolute top-12 text-[11px] font-semibold whitespace-nowrap {{ $i <= $currentStep ? 'text-primary' : 'text-secondary' }} hidden sm:block">
                                    {{ $i === 0 ? __('Mulai') : ($i === $totalPages - 1 ? __('Selesai') : __('Langkah') . ' ' . ($i + 1)) }}
                                </span>
                            </div>
                            
                            @if($i < $totalPages - 1)
                                <div class="w-12 sm:w-24 h-[2px] mx-2 transition-all duration-300 {{ $i < $currentStep ? 'bg-primary' : 'bg-surface-border' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            @endif

            <form wire:submit.prevent="submit">
                {{-- Main Form Card --}}
                <div class="bg-surface-bg rounded-2xl shadow-xl shadow-surface-border/50 border border-surface-border overflow-hidden">
                    
                    {{-- Card Header --}}
                    @if(isset($pages[$currentStep]))
                        <div class="bg-surface-container-lowest border-b border-surface-border px-6 sm:px-10 py-8 text-center sm:text-left relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1.5 bg-primary"></div>
                            <h2 class="font-headline-md text-headline-md text-on-surface mb-2 font-bold">{{ $pages[$currentStep]['title'] ?: __('Lengkapi Data') }}</h2>
                            @if(!empty($pages[$currentStep]['description']))
                                <p class="text-sm text-secondary">{{ $pages[$currentStep]['description'] }}</p>
                            @endif
                            @if($currentStep === 0)
                                <p class="text-error text-xs mt-4 font-medium flex items-center justify-center sm:justify-start gap-1">
                                    <span class="material-symbols-outlined text-[14px]">info</span> {{ __('* Wajib diisi') }}
                                </p>
                            @endif
                        </div>

                        {{-- Card Body (Questions) --}}
                        <div class="px-6 sm:px-10 py-8 space-y-8 bg-surface-bg">
                            @if(count($pages[$currentStep]['fields']) > 0)
                                @foreach($pages[$currentStep]['fields'] as $field)
                                    @if(($field['type'] ?? 'text') === 'title')
                                        <div class="border-l-4 border-primary pl-4 py-2 my-2 bg-primary/5 rounded-r-lg">
                                            <h4 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ $field['label'] }}</h4>
                                            @if(!empty($field['description']))
                                                <p class="text-sm text-secondary mt-1">{{ $field['description'] }}</p>
                                            @endif
                                        </div>
                                    @elseif(($field['type'] ?? 'text') === 'image')
                                        <div class="my-4">
                                            <label class="font-label-lg text-label-lg text-on-surface block mb-3 font-semibold">{{ $field['label'] }}</label>
                                            @if(!empty($field['url']))
                                                <img src="{{ $field['url'] }}" class="max-w-full rounded-xl border border-surface-border shadow-sm" alt="{{ $field['label'] }}">
                                            @endif
                                        </div>
                                    @elseif(($field['type'] ?? 'text') === 'video')
                                        <div class="my-4">
                                            <label class="font-label-lg text-label-lg text-on-surface block mb-3 font-semibold">{{ $field['label'] }}</label>
                                            @if(!empty($field['url']))
                                                <div class="w-full aspect-video rounded-xl overflow-hidden border border-surface-border shadow-sm">
                                                    <iframe class="w-full h-full" src="{{ str_replace('watch?v=', 'embed/', $field['url']) }}" frameborder="0" allowfullscreen></iframe>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Modern Input Group --}}
                                        <div class="group mb-8">
                                            <label class="font-label-md text-label-md text-on-surface block mb-3 font-semibold">
                                                {{ $field['label'] }} @if($field['required']) <span class="text-error">*</span> @endif
                                            </label>

                                            @if($field['type'] === 'text' || $field['type'] === 'number' || $field['type'] === 'date')
                                                <div class="relative">
                                                    <input wire:model.blur="customAnswers.{{ $field['id'] }}" type="{{ $field['type'] }}" placeholder="{{ __('Ketik jawaban Anda di sini...') }}"
                                                        class="w-full px-4 py-3 bg-surface-container-lowest border border-surface-border rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary hover:border-outline-variant transition-all outline-none text-on-surface shadow-sm" 
                                                        @if($field['required']) required @endif />
                                                </div>
                                            @elseif($field['type'] === 'textarea')
                                                <textarea wire:model.blur="customAnswers.{{ $field['id'] }}" rows="4" placeholder="{{ __('Ketik jawaban Anda di sini...') }}"
                                                    class="w-full px-4 py-3 bg-surface-container-lowest border border-surface-border rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary hover:border-outline-variant transition-all outline-none resize-none text-on-surface shadow-sm" 
                                                    @if($field['required']) required @endif></textarea>
                                                    
                                            @elseif($field['type'] === 'select')
                                                <select wire:model.blur="customAnswers.{{ $field['id'] }}" 
                                                    class="w-full px-4 py-3 bg-surface-container-lowest border border-surface-border rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary hover:border-outline-variant transition-all outline-none text-on-surface shadow-sm appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20stroke%3D%22%23666%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22M6%209l6%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_1rem_center]" 
                                                    @if($field['required']) required @endif>
                                                    <option value="">{{ __('— Pilih salah satu —') }}</option>
                                                    @foreach($field['options'] as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                                
                                            @elseif($field['type'] === 'radio')
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                                    @foreach($field['options'] as $opt)
                                                    <label class="relative flex items-center p-4 border border-surface-border rounded-xl cursor-pointer hover:bg-surface-container-lowest hover:border-primary transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:shadow-sm">
                                                        <input wire:model="customAnswers.{{ $field['id'] }}" type="radio" value="{{ $opt }}" class="w-5 h-5 text-primary focus:ring-primary border-outline-variant" @if($field['required']) required @endif />
                                                        <span class="ml-3 text-sm font-medium text-on-surface">{{ $opt }}</span>
                                                    </label>
                                                    @endforeach
                                                    @if(isset($field['allow_other']) && $field['allow_other'])
                                                    <div class="relative flex items-center p-4 border border-surface-border rounded-xl hover:bg-surface-container-lowest hover:border-primary transition-all {{ (isset($customAnswers[$field['id']]) && $customAnswers[$field['id']] === '__other__') ? 'border-primary bg-primary/5 shadow-sm' : '' }}">
                                                        <label class="flex items-center cursor-pointer w-full">
                                                            <input wire:model.live="customAnswers.{{ $field['id'] }}" type="radio" value="__other__" class="w-5 h-5 text-primary focus:ring-primary border-outline-variant" @if($field['required']) required @endif />
                                                            <span class="ml-3 text-sm font-medium text-on-surface whitespace-nowrap">{{ __('Lainnya:') }}</span>
                                                            <input wire:model.blur="otherAnswers.{{ $field['id'] }}" type="text" class="ml-3 flex-1 bg-transparent border-b border-surface-border focus:border-primary focus:ring-0 px-1 py-0.5 text-sm outline-none w-full" placeholder="{{ __('Ketik di sini...') }}" @if((isset($customAnswers[$field['id']]) && $customAnswers[$field['id']] === '__other__') && $field['required']) required @endif />
                                                        </label>
                                                    </div>
                                                    @endif
                                                </div>
                                                
                                            @elseif($field['type'] === 'checkbox')
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                                    @foreach($field['options'] as $opt)
                                                    <label class="relative flex items-center p-4 border border-surface-border rounded-xl cursor-pointer hover:bg-surface-container-lowest hover:border-primary transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:shadow-sm">
                                                        <input wire:model="customAnswers.{{ $field['id'] }}" type="checkbox" value="{{ $opt }}" class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant" />
                                                        <span class="ml-3 text-sm font-medium text-on-surface">{{ $opt }}</span>
                                                    </label>
                                                    @endforeach
                                                    @if(isset($field['allow_other']) && $field['allow_other'])
                                                    <div class="relative flex items-center p-4 border border-surface-border rounded-xl hover:bg-surface-container-lowest hover:border-primary transition-all {{ (isset($customAnswers[$field['id']]) && is_array($customAnswers[$field['id']]) && in_array('__other__', $customAnswers[$field['id']])) ? 'border-primary bg-primary/5 shadow-sm' : '' }}">
                                                        <label class="flex items-center cursor-pointer w-full">
                                                            <input wire:model.live="customAnswers.{{ $field['id'] }}" type="checkbox" value="__other__" class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant" />
                                                            <span class="ml-3 text-sm font-medium text-on-surface whitespace-nowrap">{{ __('Lainnya:') }}</span>
                                                            <input wire:model.blur="otherAnswers.{{ $field['id'] }}" type="text" class="ml-3 flex-1 bg-transparent border-b border-surface-border focus:border-primary focus:ring-0 px-1 py-0.5 text-sm outline-none w-full" placeholder="{{ __('Ketik di sini...') }}" @if((isset($customAnswers[$field['id']]) && is_array($customAnswers[$field['id']]) && in_array('__other__', $customAnswers[$field['id']])) && $field['required']) required @endif />
                                                        </label>
                                                    </div>
                                                    @endif
                                                </div>

                                            @elseif($field['type'] === 'file')
                                                <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-surface-border border-dashed rounded-xl bg-surface-container-lowest hover:bg-surface-container-low transition-colors relative overflow-hidden group">
                                                    <div class="space-y-1 text-center">
                                                        <span class="material-symbols-outlined text-[40px] text-secondary group-hover:text-primary transition-colors">cloud_upload</span>
                                                        <div class="flex text-sm text-secondary justify-center">
                                                            <label class="relative cursor-pointer rounded-md font-medium text-primary hover:text-primary-fixed focus-within:outline-none">
                                                                <span>{{ __('Pilih File') }}</span>
                                                                <input wire:model="customAnswers.{{ $field['id'] }}" type="file" class="sr-only" @if($field['required']) required @endif>
                                                            </label>
                                                            <p class="pl-1">{{ __('atau seret ke sini') }}</p>
                                                        </div>
                                                        <p class="text-xs text-secondary">{{ __('PDF, DOCX, JPG hingga 10MB') }}</p>
                                                        
                                                        @if(isset($customAnswers[$field['id']]) && $customAnswers[$field['id']] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                                            <div class="mt-4 px-4 py-2.5 bg-success/10 text-success rounded-lg text-sm font-medium flex items-center justify-center gap-2 border border-success/20 shadow-sm">
                                                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                                                {{ $customAnswers[$field['id']]->getClientOriginalName() }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div wire:loading wire:target="customAnswers.{{ $field['id'] }}" class="text-sm text-primary flex items-center gap-2 mt-3 bg-primary/10 px-4 py-2 rounded-lg font-medium inline-block">
                                                    <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> {{ __('Mengunggah dokumen...') }}
                                                </div>
                                            @endif

                                            @error("customAnswers.{$field['id']}") 
                                                <span class="text-error text-xs font-semibold block mt-2 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span> {{ $message }}</span> 
                                            @enderror
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            <!-- Terms & Actions (Last Step Only) -->
                            @if($currentStep === $totalPages - 1)
                                <div class="mt-8 pt-6 border-t border-surface-border">
                                    <label class="relative flex items-start p-5 border border-surface-border rounded-xl cursor-pointer hover:bg-surface-container-lowest transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:shadow-sm">
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input wire:model="terms" type="checkbox" class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant" required/>
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-semibold text-on-surface block mb-1">{{ __('Persetujuan Data Pribadi') }}</span>
                                            <span class="text-secondary leading-relaxed">{{ __('Saya menyatakan bahwa data yang saya berikan adalah benar, dan saya menyetujui pemrosesan data ini untuk keperluan rekrutmen perusahaan sesuai dengan kebijakan yang berlaku.') }}</span>
                                        </div>
                                    </label>
                                </div>
                            @endif
                        </div>

                        {{-- Card Footer (Navigation Buttons) --}}
                        <div class="bg-surface-container-lowest border-t border-surface-border px-6 sm:px-10 py-6 flex flex-col-reverse sm:flex-row justify-between items-center gap-4">
                            @if($currentStep > 0)
                                <button type="button" wire:click="previousStep"
                                    class="w-full sm:w-auto px-6 py-3 rounded-xl border border-surface-border bg-surface-bg text-on-surface font-semibold text-sm hover:bg-surface-container transition-all">
                                    {{ __('Kembali') }}
                                </button>
                            @else
                                <div></div>
                            @endif
                            
                            @if($currentStep < $totalPages - 1)
                                <button type="button" wire:click="nextStep"
                                    class="w-full sm:w-auto px-8 py-3 rounded-xl bg-primary text-on-primary font-semibold text-sm shadow-md hover:bg-primary-container hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                                    {{ __('Lanjutkan') }} <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                </button>
                            @else
                                <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="submit"
                                    class="w-full sm:w-auto px-8 py-3 rounded-xl bg-success text-white font-semibold text-sm shadow-md hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="submit" class="material-symbols-outlined text-[18px]">send</span>
                                    <span wire:loading wire:target="submit" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                    <span wire:loading.remove wire:target="submit">{{ __('Kirim Lamaran Sekarang') }}</span>
                                    <span wire:loading wire:target="submit">{{ __('Sedang Memproses...') }}</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- Loading Overlay Popup --}}
        <div wire:loading.flex wire:target="submit"
             class="fixed inset-0 z-[9999] items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-surface-bg rounded-2xl shadow-2xl p-10 flex flex-col items-center gap-5 max-w-sm w-full mx-4 border border-surface-border">
                <div class="relative w-24 h-24">
                    <svg class="animate-spin w-24 h-24 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[32px]" style="font-variation-settings:'FILL' 1">cloud_upload</span>
                    </span>
                </div>
                <div class="text-center">
                    <h3 class="font-headline-md text-on-surface font-semibold mb-2">{{ __('Mengirim Lamaran Anda...') }}</h3>
                    <p class="text-secondary text-sm">{{ __('Mohon tunggu, kami sedang mengamankan data dan mengunggah dokumen Anda ke sistem.') }}</p>
                </div>
                <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                    <div class="h-full bg-primary rounded-full animate-pulse" style="width: 80%;"></div>
                </div>
            </div>
        </div>
    @else
        <!-- Success Message -->
        <div class="text-center p-12 bg-surface-bg border border-surface-border rounded-2xl shadow-xl mt-10 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-success"></div>
            <div class="w-24 h-24 bg-success/10 text-success rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-[48px]" data-icon="check_circle">task_alt</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-3 font-bold">{{ __('Lamaran Berhasil Terkirim! 🎉') }}</h2>
            <p class="font-body-lg text-body-lg text-secondary mb-10 max-w-xl mx-auto leading-relaxed">
                {{ __('Terima kasih atas antusiasme Anda, ') }}<span class="font-semibold text-on-surface">{{ $full_name }}</span>. 
                {{ __('Tim HR kami akan segera meninjau berkas Anda untuk posisi ') }}<span class="font-semibold text-on-surface">{{ $job->title }}</span>. 
                {{ __('Pantau terus email Anda (') }}<span class="text-primary">{{ $email }}</span>{{ __(') untuk info selanjutnya.') }}
            </p>
            <div class="flex justify-center">
                <a href="/" class="px-8 py-3 rounded-xl bg-primary text-on-primary font-semibold text-sm shadow-md hover:bg-primary-container hover:shadow-lg transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">home</span>
                    {{ __('Kembali ke Beranda') }}
                </a>
            </div>
        </div>
    @endif
    </div>
</div>