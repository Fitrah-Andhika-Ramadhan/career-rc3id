@extends('layouts.admin')

@section('content')
    <section class="p-margin max-w-container-max mx-auto w-full space-y-stack-lg">
        <!-- Welcome Header & Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-stack-md items-end">
            <div class="md:col-span-8">
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-stack-sm">Active Job Postings</h1>
                <p class="text-on-surface-variant font-body-lg text-body-lg max-w-2xl">Manage your open roles and candidate pipelines. You have <span class="font-bold text-primary">12 active jobs</span> and 48 new applicants today.</p>
            </div>
            <div class="md:col-span-4 flex justify-end gap-stack-sm">
                <button class="px-margin py-stack-md border border-surface-border rounded-lg font-semibold flex items-center gap-stack-sm hover:bg-surface-container transition-all">
                    <span class="material-symbols-outlined" data-icon="filter_list">filter_list</span>
                    Filter
                </button>
                <button class="px-margin py-stack-md border border-surface-border rounded-lg font-semibold flex items-center gap-stack-sm hover:bg-surface-container transition-all">
                    <span class="material-symbols-outlined" data-icon="download">download</span>
                    Export
                </button>
            </div>
        </div>

        <!-- Bento Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-stack-md">
            <div class="bg-surface-container-lowest p-stack-lg rounded-xl border border-surface-border shadow-sm">
                <div class="flex justify-between items-start mb-stack-md">
                    <div class="p-2 bg-primary-fixed rounded-lg text-primary">
                        <span class="material-symbols-outlined" data-icon="group_add">group_add</span>
                    </div>
                    <span class="text-success font-label-md text-label-md flex items-center">+12% <span class="material-symbols-outlined text-[14px]" data-icon="trending_up">trending_up</span></span>
                </div>
                <p class="text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">Total Applicants</p>
                <h3 class="text-headline-lg font-headline-lg">1,248</h3>
            </div>
            <div class="bg-surface-container-lowest p-stack-lg rounded-xl border border-surface-border shadow-sm">
                <div class="flex justify-between items-start mb-stack-md">
                    <div class="p-2 bg-tertiary-fixed rounded-lg text-tertiary">
                        <span class="material-symbols-outlined" data-icon="hourglass_empty">hourglass_empty</span>
                    </div>
                    <span class="text-on-surface-variant font-label-md text-label-md">Avg. Role</span>
                </div>
                <p class="text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">Time to Hire</p>
                <h3 class="text-headline-lg font-headline-lg">18 Days</h3>
            </div>
            <div class="bg-surface-container-lowest p-stack-lg rounded-xl border border-surface-border shadow-sm">
                <div class="flex justify-between items-start mb-stack-md">
                    <div class="p-2 bg-secondary-container rounded-lg text-secondary">
                        <span class="material-symbols-outlined" data-icon="mail">mail</span>
                    </div>
                    <span class="px-2 py-0.5 bg-error-container text-on-error-container rounded-full font-label-sm text-label-sm">Action Required</span>
                </div>
                <p class="text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">New Resumes</p>
                <h3 class="text-headline-lg font-headline-lg">48</h3>
            </div>
            <div class="bg-surface-container-lowest p-stack-lg rounded-xl border border-surface-border shadow-sm">
                <div class="flex justify-between items-start mb-stack-md">
                    <div class="p-2 bg-primary-container rounded-lg text-on-primary-container">
                        <span class="material-symbols-outlined" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    </div>
                    <span class="text-on-surface-variant font-label-md text-label-md">Past 30d</span>
                </div>
                <p class="text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">Roles Closed</p>
                <h3 class="text-headline-lg font-headline-lg">9</h3>
            </div>
        </div>
    </section>
@endsection
