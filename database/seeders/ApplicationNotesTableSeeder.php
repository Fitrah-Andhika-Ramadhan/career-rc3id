<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApplicationNotesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('application_notes')->delete();
        
        \DB::table('application_notes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'application_id' => 1,
                'user_id' => NULL,
                'note' => 'Date of Birth: 2000-12-03
Latest Education: S1
Major: si
University: test
Graduation Year: 2025
Work History: Fresh Graduate
Description: test',
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            1 => 
            array (
                'id' => 2,
                'application_id' => 2,
                'user_id' => NULL,
                'note' => 'Date of Birth: 2000-12-03
Latest Education: S1
Major: si
University: TEST WEBSITE CAREER RC3ID
Graduation Year: 2025
Work History: Fresh Graduate
Description: TEST WEBSITE CAREER RC3ID',
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
            2 => 
            array (
                'id' => 3,
                'application_id' => 3,
                'user_id' => NULL,
                'note' => 'Date of Birth: 2000-12-03
',
                'created_at' => '2026-08-02 05:39:44',
                'updated_at' => '2026-08-02 05:39:44',
            ),
        ));
        
        
    }
}