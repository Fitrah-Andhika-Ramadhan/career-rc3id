<?php

namespace App\Exports;

use App\Models\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CandidatesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Application::with(['candidate', 'job', 'stage'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Candidate Name',
            'Email',
            'Phone',
            'Date of Birth',
            'Job Applied',
            'Current Stage',
            'Application Date'
        ];
    }

    public function map($application): array
    {
        return [
            $application->id,
            $application->candidate->name,
            $application->candidate->email,
            $application->candidate->phone,
            $application->candidate->dob,
            $application->job->title,
            $application->stage->name,
            $application->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
