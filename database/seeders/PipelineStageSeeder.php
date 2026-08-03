<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            ['name' => 'Applied', 'order' => 1],
            ['name' => 'Screening', 'order' => 2],
            ['name' => 'Interview', 'order' => 3],
            ['name' => 'Offer', 'order' => 4],
            ['name' => 'Hired', 'order' => 5],
            ['name' => 'Rejected', 'order' => 6],
        ];

        foreach ($stages as $stage) {
            \App\Models\PipelineStage::create($stage);
        }
    }
}
