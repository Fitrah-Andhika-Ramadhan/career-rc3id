<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MediaTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('media')->delete();
        
        \DB::table('media')->insert(array (
            0 => 
            array (
                'id' => 1,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 1,
                'uuid' => '5d23a313-71cc-4af0-a823-35d69b8db4c3',
                'collection_name' => 'resumes',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => '9hgDKf5mveMOgnUd2ZaYUFD7DhRleoMgu79AZpws.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            1 => 
            array (
                'id' => 2,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 1,
                'uuid' => 'ea37f8bf-b4fc-4b48-a752-ce3c787e8c5d',
                'collection_name' => 'ijazah',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => 'pveijkd0U2PBNvvCz9eh7HQfgQF0mY3pxsmAd9mo.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 2,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            2 => 
            array (
                'id' => 3,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 1,
                'uuid' => '2f6a8dca-3f10-4dc4-8d12-5756770a0f05',
                'collection_name' => 'documents',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => 'PGJE0p05vBvZtQ8MFFvbxZlAnzw81Ed9hMUvPrL9.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 3,
                'created_at' => '2026-07-31 12:52:52',
                'updated_at' => '2026-07-31 12:52:52',
            ),
            3 => 
            array (
                'id' => 4,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 2,
                'uuid' => '1cb66b37-07c6-4018-850c-81773a12890f',
                'collection_name' => 'resumes',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => '1uAHLga5WeLut8OQYL5yv9ZBVD8pHWklqcwjzo8t.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
            4 => 
            array (
                'id' => 5,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 2,
                'uuid' => '4bec0956-2751-4b72-b1c4-ee0d55c5ba9b',
                'collection_name' => 'ijazah',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => 'ghjyVq4lAvHtU6cWfKN2qblnCPuJ7qoPdjL4fqeF.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 2,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
            5 => 
            array (
                'id' => 6,
                'model_type' => 'App\\Models\\Application',
                'model_id' => 2,
                'uuid' => '8e1944da-62b8-4aef-abee-96eaf8cca7c0',
                'collection_name' => 'documents',
                'name' => 'DOKUMEN TEST WEBSITE CAREER RC3IDSYSTEM.pdf',
                'file_name' => 'Zeu1ndby6EFuqGNfidxIgBeX0AYiCUNygU1A6sY9.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 80373,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 3,
                'created_at' => '2026-07-31 12:59:01',
                'updated_at' => '2026-07-31 12:59:01',
            ),
        ));
        
        
    }
}