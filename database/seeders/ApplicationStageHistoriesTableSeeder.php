<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApplicationStageHistoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('application_stage_histories')->delete();
        
        \DB::table('application_stage_histories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'application_id' => 1,
                'old_stage_id' => NULL,
                'new_stage_id' => 1,
                'user_id' => NULL,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            1 => 
            array (
                'id' => 2,
                'application_id' => 2,
                'old_stage_id' => NULL,
                'new_stage_id' => 1,
                'user_id' => NULL,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
            2 => 
            array (
                'id' => 3,
                'application_id' => 3,
                'old_stage_id' => NULL,
                'new_stage_id' => 1,
                'user_id' => NULL,
                'created_at' => '2026-08-02 05:39:44',
                'updated_at' => '2026-08-02 05:39:44',
            ),
        ));
        
        
    }
}