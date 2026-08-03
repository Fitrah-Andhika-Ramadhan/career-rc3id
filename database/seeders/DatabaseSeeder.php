<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            JobPostingsTableSeeder::class,
            PipelineStagesTableSeeder::class,
            CandidatesTableSeeder::class,
            ApplicationsTableSeeder::class,
            ApplicationNotesTableSeeder::class,
            ApplicationStageHistoriesTableSeeder::class,
            MediaTableSeeder::class
        ]);
        $this->call(ApplicationsTableSeeder::class);
        $this->call(ApplicationNotesTableSeeder::class);
        $this->call(ApplicationStageHistoriesTableSeeder::class);
        $this->call(MediaTableSeeder::class);
    }
}
