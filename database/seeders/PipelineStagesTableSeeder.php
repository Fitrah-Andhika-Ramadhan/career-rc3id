<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PipelineStagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('pipeline_stages')->delete();
        
        \DB::table('pipeline_stages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Applied',
                'order' => 1,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Screening',
                'order' => 2,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Interview',
                'order' => 3,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Offer',
                'order' => 4,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Hired',
                'order' => 5,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Rejected',
                'order' => 6,
                'created_at' => '2026-07-31 03:28:04',
                'updated_at' => '2026-07-31 03:28:04',
            ),
        ));
        
        
    }
}