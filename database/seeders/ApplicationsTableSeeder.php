<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApplicationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('applications')->delete();
        
        \DB::table('applications')->insert(array (
            0 => 
            array (
                'id' => 1,
                'candidate_id' => 1,
                'job_id' => 6,
                'pipeline_stage_id' => 1,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            1 => 
            array (
                'id' => 2,
                'candidate_id' => 2,
                'job_id' => 6,
                'pipeline_stage_id' => 1,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
            2 => 
            array (
                'id' => 3,
                'candidate_id' => 2,
                'job_id' => 5,
                'pipeline_stage_id' => 1,
                'created_at' => '2026-08-02 05:39:44',
                'updated_at' => '2026-08-02 05:39:44',
            ),
        ));
        
        
    }
}