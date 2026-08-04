<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Job;
use App\Models\Candidate;
use App\Models\Application;
use Illuminate\Support\Str;

new
#[Layout('layouts.admin')]
class extends Component
{
    public $jobs;
    public $showModal = false;
    public $showBulkModal = false;
    public $isEdit = false;
    public $jobId = null;
    
    public $bulkJobsText = '';

    // Form fields
    public $title = '';
    public $department = '';
    public $work_type = '';
    public $location = '';
    public $description = '';
    public $status = 'published';
    public $deadline_date = '';

    public function mount()
    {
        $this->loadJobs();
    }

    public function loadJobs()
    {
        $this->jobs = Job::withCount('applications')->orderBy('created_at', 'desc')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEdit = true;
        $this->jobId = $id;
        
        $job = Job::find($id);
        $this->title = $job->title;
        $this->department = $job->department;
        $this->work_type = $job->work_type;
        $this->location = $job->location;
        $this->description = $job->description;
        $this->status = $job->status;
        $this->deadline_date = $job->deadline_date ? \Carbon\Carbon::parse($job->deadline_date)->format('Y-m-d') : '';

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'work_type' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'deadline_date' => 'nullable|date',
        ]);

        if ($this->isEdit) {
            $job = Job::find($this->jobId);
            $job->update([
                'title' => $this->title,
                'department' => $this->department,
                'work_type' => $this->work_type,
                'location' => $this->location,
                'description' => $this->description,
                'status' => $this->status,
                'deadline_date' => $this->deadline_date ?: null,
            ]);
            $savedJobId = $this->jobId;
        } else {
            $job = Job::create([
                'title' => $this->title,
                'department' => $this->department,
                'work_type' => $this->work_type,
                'location' => $this->location,
                'description' => $this->description,
                'status' => $this->status,
                'deadline_date' => $this->deadline_date ?: null,
            ]);
            $savedJobId = $job->id;
        }

        $this->showModal = false;
        $this->loadJobs();
        
        $this->dispatch('job-saved', ['jobId' => $savedJobId]);
        $this->dispatch('notify', 'Berhasil menyimpan lowongan!');
    }

    public function saveBulk()
    {
        if (empty(trim($this->bulkJobsText))) {
            return;
        }
        
        $lines = explode("\n", str_replace("\r", "", $this->bulkJobsText));
        $count = 0;
        foreach ($lines as $line) {
            $title = trim($line);
            if (!empty($title)) {
                Job::create([
                    'title' => $title,
                    'department' => 'Uncategorized',
                    'work_type' => 'Full-time',
                    'location' => 'Remote',
                    'description' => 'Silakan edit deskripsi lowongan ini.',
                    'status' => 'draft',
                ]);
                $count++;
            }
        }
        
        $this->showBulkModal = false;
        $this->bulkJobsText = '';
        $this->loadJobs();
        $this->dispatch('notify', 'Berhasil menambahkan ' . $count . ' lowongan sekaligus!');
    }

    public function toggleHide($id)
    {
        $job = Job::find($id);
        $job->status = $job->status === 'published' ? 'closed' : 'published';
        $job->save();
        $this->loadJobs();
    }

    public function delete($id)
    {
        Job::find($id)->delete();
        $this->loadJobs();
    }

    public function resetForm()
    {
        $this->title = '';
        $this->department = '';
        $this->work_type = '';
        $this->location = '';
        $this->description = '';
        $this->status = 'published';
        $this->deadline_date = '';
        $this->jobId = null;
    }

    public function generateDummyData()
    {
        $jobs = Job::where('status', 'published')->get();
        if ($jobs->isEmpty()) return;

        foreach (range(1, 10) as $i) {
            $candidate = Candidate::create([
                'name' => 'Dummy Candidate ' . Str::random(4),
                'email' => 'dummy' . Str::random(5) . '@example.com',
                'phone' => '0812' . rand(10000000, 99999999),
            ]);

            $application = Application::create([
                'candidate_id' => $candidate->id,
                'job_id' => $jobs->random()->id,
                'pipeline_stage_id' => rand(1, 3), // applied, screening, interview
            ]);

            $application->notes()->create([
                'note' => "Date of Birth: 2000-01-01\n" .
                          "Latest Education: S1\n" .
                          "Major: Teknik Informatika\n" .
                          "University: Universitas Dummy\n" .
                          "Graduation Year: 2022\n" .
                          "Work History: Fresh Graduate\n" .
                          "Description: Dummy applicant generated by system.",
            ]);
        }

        $this->loadJobs();
        session()->flash('message', '10 Dummy Candidates generated successfully!');
    }
};
?>

<div>
    <!-- Welcome Header & Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-stack-md items-end mb-stack-lg">
        <div class="md:col-span-8">
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-stack-sm">Active Job Postings</h1>
            <p class="text-on-surface-variant font-body-lg text-body-lg max-w-2xl">Manage your open roles and candidate pipelines. You have <span class="font-bold text-primary">{{ $jobs->where('status', 'published')->count() }} active jobs</span>.</p>
        </div>
        <div class="md:col-span-4 flex justify-end gap-stack-sm">
            @if(auth()->check() && auth()->user()->hasRole('Super Admin'))
            <button wire:click="generateDummyData" class="px-margin py-stack-md bg-secondary-container text-on-secondary-container rounded-lg font-semibold flex items-center gap-stack-sm hover:opacity-90 transition-all">
                <span class="material-symbols-outlined" data-icon="science">science</span>
                Generate Dummy
            </button>
            @endif
        </div>
    </div>
    
    @if (session()->has('message'))
        <div class="mb-stack-lg p-stack-md bg-success/10 text-success border border-success/20 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <!-- Template Gallery (Start a new form) -->
    <div class="bg-surface-container-lowest -mx-6 px-6 py-6 mb-8 border-y border-surface-border">
        <h2 class="font-body-lg text-body-lg text-on-surface mb-4">Start a new job posting</h2>
        <div class="flex gap-4 overflow-x-auto pb-2">
            <!-- Blank Form Card -->
            <div wire:click="create" class="cursor-pointer flex flex-col gap-2 group w-[150px] shrink-0">
                <div class="h-[115px] bg-white border border-surface-border rounded-lg flex items-center justify-center hover:border-primary transition-all shadow-sm">
                    <span class="material-symbols-outlined text-primary text-[48px] group-hover:scale-110 transition-transform">add</span>
                </div>
                <span class="font-label-md text-label-md text-on-surface-variant font-medium group-hover:text-primary transition-colors">Blank job</span>
            </div>
            
            <!-- Bulk Add Card -->
            <div wire:click="$set('showBulkModal', true)" class="cursor-pointer flex flex-col gap-2 group w-[150px] shrink-0">
                <div class="h-[115px] bg-white border border-surface-border rounded-lg flex items-center justify-center hover:border-primary transition-all shadow-sm">
                    <span class="material-symbols-outlined text-primary text-[48px] group-hover:scale-110 transition-transform">list_alt_add</span>
                </div>
                <span class="font-label-md text-label-md text-on-surface-variant font-medium group-hover:text-primary transition-colors">Bulk Add Jobs</span>
            </div>
        </div>
    </div>

    <!-- Jobs Grid Section -->
    <div class="mb-stack-md flex justify-between items-center">
        <h2 class="font-headline-md text-headline-md text-on-surface">Your Job Postings</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5 pb-10">
        @foreach($jobs as $job)
        <div class="bg-surface-bg border border-surface-border rounded-lg hover:border-primary/40 hover:shadow-md transition-all group flex flex-col h-[240px]">
            {{-- Top Thumbnail Area --}}
            <a href="#" wire:click.prevent="edit({{ $job->id }})" class="h-[140px] bg-primary/5 rounded-t-lg flex items-center justify-center p-4 border-b border-surface-border relative">
                {{-- Mock document visual --}}
                <div class="w-full h-full bg-white rounded shadow-sm border border-gray-200 p-2 flex flex-col gap-1 overflow-hidden opacity-90 group-hover:opacity-100 transition-opacity">
                    <div class="h-2 bg-primary/20 rounded w-1/2 mb-2"></div>
                    <div class="h-1 bg-gray-100 rounded w-full"></div>
                    <div class="h-1 bg-gray-100 rounded w-3/4"></div>
                    <div class="h-1 bg-gray-100 rounded w-5/6"></div>
                    <div class="mt-auto h-4 bg-gray-100 rounded w-full"></div>
                </div>
                {{-- Status badge --}}
                <div class="absolute top-2 right-2 flex gap-1">
                    @if($job->status === 'published')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-success/10 text-success border border-success/20">Active</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-variant text-secondary border border-surface-border">{{ ucfirst($job->status) }}</span>
                    @endif
                </div>
            </a>
            
            {{-- Bottom Info Area --}}
            <div class="p-3 bg-surface-bg rounded-b-lg flex-1 flex flex-col justify-between relative">
                <a href="#" wire:click.prevent="edit({{ $job->id }})" class="block">
                    <h3 class="font-semibold text-sm text-on-surface truncate hover:text-primary transition-colors" title="{{ $job->title }}">{{ $job->title }}</h3>
                </a>
                
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center gap-2 text-secondary text-xs truncate">
                        <span class="material-symbols-outlined text-primary text-[16px]" style="font-variation-settings: 'FILL' 1;">list_alt</span>
                        <span class="truncate">{{ $job->applications_count }} responses</span>
                    </div>
                    
                    {{-- 3 dots menu --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click.stop="open = !open" class="p-1.5 text-secondary hover:bg-surface-container rounded-full transition-colors focus:outline-none">
                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                        </button>
                        
                        {{-- Dropdown menu --}}
                        <div x-show="open" 
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;" 
                             class="absolute right-0 bottom-full mb-1 w-44 bg-white border border-gray-200 rounded-lg shadow-xl z-[999] py-1">
                            <a href="{{ route('jobs.apply', $job) }}" target="_blank" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">visibility</span> Preview
                            </a>
                            <button type="button" @click="open = false; Swal.fire({title: 'Link Form Pendaftaran', input: 'text', inputValue: '{{ route('jobs.apply', $job) }}', customClass: {input: 'bg-surface-container-low border-surface-border text-on-surface'}, confirmButtonText: 'Tutup', confirmButtonColor: 'var(--color-primary, #005bbf)'})" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">link</span> Get link
                            </button>
                            <a href="{{ route('admin.custom-form') }}?selectedJobId={{ $job->id }}" wire:navigate class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">dynamic_form</span> Form Builder
                            </a>
                            <hr class="my-1 border-gray-100">
                            <button type="button" @click="open = false; confirmDelete('Hapus lowongan ini?', () => $wire.delete({{ $job->id }}))" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Form -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-margin border-b border-surface-border flex justify-between items-center">
                <h2 class="font-headline-md text-headline-md">{{ $isEdit ? 'Edit Job' : 'Post New Job' }}</h2>
                <button wire:click="$set('showModal', false)" class="text-secondary hover:text-on-surface">
                    <span class="material-symbols-outlined" data-icon="close">close</span>
                </button>
            </div>
            <form wire:submit="save" class="p-margin space-y-stack-md">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Job Title</label>
                        <input wire:model="title" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                        @error('title') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Department / Project</label>
                        <input wire:model="department" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div class="space-y-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Work Type</label>
                        <input wire:model="work_type" type="text" placeholder="e.g. Full-time, Remote" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div class="space-y-2 col-span-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Location</label>
                        <input wire:model="location" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label class="font-label-md text-label-md text-on-surface-variant">Status</label>
                        <select wire:model="status" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="space-y-2 col-span-2 sm:col-span-1">
                        <label class="font-label-md text-label-md text-on-surface-variant">Deadline Date</label>
                        <input wire:model="deadline_date" type="date" class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary">
                        <p class="text-xs text-secondary mt-1">Leave empty for no deadline.</p>
                        @error('deadline_date') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="space-y-2 col-span-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Job Description / Requirements</label>
                        <textarea wire:model="description" rows="5" placeholder="Tuliskan deskripsi pekerjaan atau kualifikasi di sini..." class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary"></textarea>
                        @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                </div>
                <div class="flex justify-between items-center mt-6 pt-6 border-t border-surface-border">
                    <div>
                        @if($jobId)
                            <a href="{{ url('/admin/custom-form?selectedJobId=' . $jobId) }}" class="inline-flex items-center gap-2 px-4 py-2 text-primary hover:bg-primary/10 rounded-lg font-label-md transition-colors tooltip-trigger" title="Atur Form Pendaftaran">
                                <span class="material-symbols-outlined text-[20px]">dynamic_form</span>
                                <span class="hidden sm:inline">Form Builder</span>
                            </a>
                        @endif
                    </div>
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('showModal', false)" class="px-6 py-2 rounded-lg font-label-md border hover:bg-surface-container transition-colors">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 transition-opacity">Save Job</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Bulk Add Modal -->
    @if($showBulkModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-margin border-b border-surface-border flex justify-between items-center bg-primary/5">
                <h2 class="font-headline-md text-headline-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">list_alt_add</span>
                    Bulk Add Jobs
                </h2>
                <button wire:click="$set('showBulkModal', false)" class="text-secondary hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined" data-icon="close">close</span>
                </button>
            </div>
            <form wire:submit="saveBulk" class="p-margin space-y-stack-md">
                <div class="space-y-3">
                    <p class="font-body-sm text-secondary">
                        Ketikkan (atau *paste*) daftar nama lowongan yang ingin Anda tambahkan sekaligus. Setiap baris baru akan dihitung sebagai satu lowongan (draft).
                    </p>
                    <textarea wire:model="bulkJobsText" rows="10" placeholder="Contoh:&#10;Software Engineer&#10;Data Analyst&#10;Product Manager" class="w-full px-4 py-3 border rounded-lg focus:ring-primary focus:border-primary text-body-md" required></textarea>
                </div>
                
                <div class="flex justify-end gap-4 mt-6 pt-6 border-t border-surface-border">
                    <button type="button" wire:click="$set('showBulkModal', false)" class="px-6 py-2 rounded-lg font-label-md border hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 transition-opacity flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        Simpan Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @script
    <script>
        $wire.on('job-saved', (event) => {
            // Livewire 3 event data is usually the first item in the array if dispatched as an array
            let jobId = event[0].jobId;
            Swal.fire({
                title: 'Berhasil Disimpan!',
                text: 'Apakah Anda ingin langsung mengatur pertanyaan form pendaftaran untuk lowongan ini?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-primary, #005bbf)',
                cancelButtonColor: '#74777F',
                confirmButtonText: '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">dynamic_form</span> Ya, Atur Form</span>',
                cancelButtonText: 'Nanti Saja',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'font-headline-sm text-on-surface',
                    htmlContainer: 'font-body-md text-on-surface-variant'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/custom-form?selectedJobId=' + jobId;
                }
            });
        });
    </script>
    @endscript
</div>
