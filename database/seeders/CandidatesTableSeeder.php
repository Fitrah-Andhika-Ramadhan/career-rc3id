<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CandidatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('candidates')->delete();
        
        \DB::table('candidates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'fitrah',
                'email' => 'fitrahramadhan@gmail.com',
                'phone' => '0812',
                'address' => NULL,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'TEST WEBSITE CAREER RC3ID',
                'email' => 'fitrahramadhan310@gmail.com',
                'phone' => '081289886013',
                'address' => NULL,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
        ));
        
        
    }
}