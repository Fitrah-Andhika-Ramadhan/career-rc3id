<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Job;
use App\Models\Application;
use App\Models\PipelineStage;
use App\Models\ActiveVisitor;
use Carbon\Carbon;

new
#[Layout('layouts.admin')]
class extends Component
{
    public function with()
    {
        $totalJobs = Job::where('status', 'published')->count();
        $totalApps = Application::count();
        
        // New apps this week
        $appsThisWeek = Application::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        
        $activeVisitorsCount = ActiveVisitor::where('last_activity', '>=', Carbon::now()->subMinutes(5))->count();
        $activeVisitors = ActiveVisitor::where('last_activity', '>=', Carbon::now()->subMinutes(5))
                            ->orderBy('last_activity', 'desc')
                            ->take(10)
                            ->get();
        
        $recentApps = Application::with(['candidate', 'job', 'stage'])
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                        
        $hiredStage = PipelineStage::where('name', 'like', '%Hired%')->orWhere('name', 'like', '%Diterima%')->first();
        $hiredCount = $hiredStage ? Application::where('pipeline_stage_id', $hiredStage->id)->count() : 0;

        return [
            'totalJobs' => $totalJobs,
            'totalApps' => $totalApps,
            'appsThisWeek' => $appsThisWeek,
            'hiredCount' => $hiredCount,
            'recentApps' => $recentApps,
            'activeVisitorsCount' => $activeVisitorsCount,
            'activeVisitors' => $activeVisitors,
        ];
    }
};
?>

<div wire:poll.10s class="flex-1 overflow-y-auto px-6 py-8 md:px-10 md:py-10 h-[calc(100vh-64px)] space-y-10 bg-surface">
    
    <!-- Header Section (Sleek, Clean) -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold tracking-tight text-on-surface">Dashboard</h1>
            <p class="text-sm font-medium text-secondary">
                Overview of your recruitment activities and pipeline.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.jobs.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-lg shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Post New Job
            </a>
        </div>
    </div>

    <!-- Stats Grid (Minimalist Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        
        <!-- Stat: Active Jobs -->
        <div class="bg-surface-bg rounded-2xl border border-surface-border p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-secondary uppercase tracking-wider">Active Jobs</span>
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[16px]">work</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-semibold tracking-tight text-on-surface">{{ $totalJobs }}</h3>
            </div>
        </div>

        <!-- Stat: Total Applicants -->
        <div class="bg-surface-bg rounded-2xl border border-surface-border p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-secondary uppercase tracking-wider">Applicants</span>
                <div class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-600">
                    <span class="material-symbols-outlined text-[16px]">groups</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-semibold tracking-tight text-on-surface">{{ $totalApps }}</h3>
            </div>
        </div>

        <!-- Stat: New This Week -->
        <div class="bg-surface-bg rounded-2xl border border-surface-border p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-secondary uppercase tracking-wider">This Week</span>
                <div class="w-8 h-8 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                    <span class="material-symbols-outlined text-[16px]">trending_up</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-semibold tracking-tight text-on-surface">{{ $appsThisWeek > 0 ? '+' : '' }}{{ $appsThisWeek }}</h3>
                @if($appsThisWeek > 0)
                <span class="text-xs font-medium text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-full">New</span>
                @endif
            </div>
        </div>

        <!-- Stat: Hired -->
        <div class="bg-surface-bg rounded-2xl border border-surface-border p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-secondary uppercase tracking-wider">Hired</span>
                <div class="w-8 h-8 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-600">
                    <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-semibold tracking-tight text-on-surface">{{ $hiredCount }}</h3>
            </div>
        </div>
        
        <!-- Stat: Realtime Visitors -->
        <div class="bg-surface-bg rounded-2xl border border-surface-border p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden sm:col-span-2 lg:col-span-2 xl:col-span-1">
            <div class="absolute top-0 right-0 w-2.5 h-2.5 mt-4 mr-4 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.8)]"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-semibold text-secondary uppercase tracking-wider">Online Now</span>
                <div class="w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center text-red-600">
                    <span class="material-symbols-outlined text-[16px]">sensors</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-semibold tracking-tight text-on-surface">{{ $activeVisitorsCount }}</h3>
            </div>
        </div>
        
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Recent Activity (Sleek List) -->
        <div class="lg:col-span-2 bg-surface-bg rounded-2xl border border-surface-border shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-surface-border flex justify-between items-center">
                <h2 class="text-base font-semibold text-on-surface">Recent Applications</h2>
                <a href="{{ route('admin.submissions.index') }}" class="text-sm font-medium text-primary hover:text-primary-fixed transition-colors">
                    View all &rarr;
                </a>
            </div>
            
            <div class="flex-1 overflow-x-auto">
                @if($recentApps->count() > 0)
                <table class="w-full text-left border-collapse">
                    <tbody>
                        @foreach($recentApps as $app)
                        <tr class="border-b border-surface-border last:border-0 hover:bg-surface-container-lowest transition-colors group">
                            <td class="py-4 pl-6 pr-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-secondary font-semibold text-sm">
                                        {{ strtoupper(substr($app->candidate->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-on-surface">{{ $app->candidate->name }}</p>
                                        <p class="text-xs text-secondary mt-0.5">{{ $app->candidate->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 hidden sm:table-cell">
                                <p class="text-sm font-medium text-on-surface">{{ $app->job->title }}</p>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-medium bg-surface-container text-on-surface-variant ring-1 ring-inset ring-surface-border">
                                    {{ $app->stage->name ?? 'New' }}
                                </span>
                                <p class="text-[11px] text-secondary mt-1">{{ $app->created_at->diffForHumans() }}</p>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="p-12 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-3">
                        <span class="material-symbols-outlined text-[20px]">inbox</span>
                    </div>
                    <h3 class="text-sm font-medium text-on-surface">No applications</h3>
                    <p class="text-sm text-secondary mt-1">Applications will appear here once candidates apply.</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Right: Quick Actions -->
        <div class="space-y-6">
            <div class="bg-surface-bg rounded-2xl border border-surface-border shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-surface-border">
                    <h2 class="text-base font-semibold text-on-surface">Quick Actions</h2>
                </div>
                <div class="p-3">
                    <a href="{{ route('admin.jobs.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-container-lowest transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-secondary group-hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[20px]">work</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-on-surface">Manage Jobs</p>
                            <p class="text-xs text-secondary mt-0.5">Create or edit job postings</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.submissions.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-container-lowest transition-colors group mt-1">
                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-secondary group-hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[20px]">group</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-on-surface">Review Submissions</p>
                            <p class="text-xs text-secondary mt-0.5">Process candidate applications</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Active Visitors Table -->
            <div class="bg-surface-bg rounded-2xl border border-surface-border shadow-sm overflow-hidden mt-6">
                <div class="px-6 py-5 border-b border-surface-border flex justify-between items-center">
                    <h2 class="text-base font-semibold text-on-surface flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.8)]"></span> Live Traffic
                    </h2>
                </div>
                <div class="p-0 max-h-[300px] overflow-y-auto">
                    @if($activeVisitors->count() > 0)
                        <table class="w-full text-left border-collapse text-sm">
                            <thead class="bg-surface-container-lowest border-b border-surface-border sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 font-medium text-secondary text-xs">Lokasi</th>
                                    <th class="px-4 py-2 font-medium text-secondary text-xs text-right">Aktivitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeVisitors as $visitor)
                                <tr class="border-b border-surface-border last:border-0 hover:bg-surface-container-lowest transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-on-surface">{{ $visitor->city ?? 'Unknown' }}, {{ $visitor->country ?? 'Unknown' }}</span>
                                            <span class="text-[10px] text-secondary font-mono">{{ $visitor->ip_address }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-xs text-secondary">{{ $visitor->last_activity->diffForHumans() }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-6 text-center text-secondary text-sm">
                            Belum ada pengunjung aktif.
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-primary/5 rounded-2xl border border-primary/10 p-6 flex items-start gap-4 mt-6">
                <span class="material-symbols-outlined text-primary mt-1">auto_awesome</span>
                <div>
                    <h3 class="text-sm font-semibold text-on-surface mb-1">Tip of the day</h3>
                    <p class="text-xs text-secondary leading-relaxed">
                        Keep your job descriptions clear and concise. Use the custom form builder to filter candidates early in the process.
                    </p>
                </div>
            </div>
        </div>
        
    </div>
</div>
