<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JobPostingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('job_postings')->delete();
        
        \DB::table('job_postings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'title' => 'Senior Product Designer',
                'department' => 'Design',
                'location' => 'Jakarta, Indonesia',
                'work_type' => 'Full-time',
                'description' => 'Join our elite product team...',
                'status' => 'published',
                'created_at' => '2026-07-31 03:39:46',
                'updated_at' => '2026-07-31 03:39:46',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'title' => 'Sekretaris Manajer',
                'department' => 'Manajemen / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => 'Dibutuhkan 1 Orang.

Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).

Penempatan: Bandung
Batas Pendaftaran: 14 Agustus 2026
Narahubung: rekrut.rc3id@gmail.com

Jangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:08',
                'updated_at' => '2026-07-31 04:46:08',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'title' => 'Staf Administrasi',
                'department' => 'Administrasi / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => 'Dibutuhkan 1 Orang.

Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).

Penempatan: Bandung
Batas Pendaftaran: 14 Agustus 2026
Narahubung: rekrut.rc3id@gmail.com

Jangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:08',
                'updated_at' => '2026-07-31 04:46:08',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'title' => 'Staf Keuangan',
                'department' => 'Keuangan / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => 'Dibutuhkan 1 Orang.

Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).

Penempatan: Bandung
Batas Pendaftaran: 14 Agustus 2026
Narahubung: rekrut.rc3id@gmail.com

Jangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:08',
                'updated_at' => '2026-07-31 04:46:08',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'title' => 'Sekretaris Manager',
                'department' => 'Manajemen / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => '',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:42',
                'updated_at' => '2026-08-03 07:26:02',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
            'custom_fields' => '[{"id":"field_6a703c8d0eaae","type":"section","label":"IDENTITAS DIRI","description":"Informasi dasar pelamar"},{"id":"field_6a703c8d0eab1","type":"text","label":"Nama Lengkap","required":true},{"id":"field_6a703c8d0eab3","type":"text","label":"Email","required":true},{"id":"field_6a703c8d0eab4","type":"date","label":"Tanggal lahir","required":false},{"id":"field_6a703c8d0eab5","type":"text","label":"Nomor telepon","required":true},{"id":"field_6a703c8d0eab6","type":"section","label":"PENGALAMAN KERJA","description":""},{"id":"field_6a703c8d0eab7","type":"radio","label":"Riwayat Pekerjaan","required":true,"options":["Administrasi Sumber Daya Manusia","HR Generalist","Fresh Graduate"]},{"id":"field_6a703c8d0eab8","type":"textarea","label":"Deskripsi singkat pengalaman kerja","required":false},{"id":"field_6a703c8d0eab9","type":"section","label":"LAINNYA","description":""},{"id":"field_6a703c8d0eaba","type":"textarea","label":"Apakah Anda pernah terlibat dalam penyusunan Struktur dan Skala Upah (SSU) di perusahaan sebelumnya? Bagaimana prosesnya?","required":true},{"id":"field_6a703c8d0eabb","type":"textarea","label":"Ceritakan pengalaman Anda saat harus menangani konflik interpersonal antara karyawan dan atasannya. Bagaimana cara Anda menengahi konflik tersebut?","required":true}]',
            ),
            5 => 
            array (
                'id' => 6,
                'title' => '',
                'department' => 'Administrasi / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => '',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:42',
                'updated_at' => '2026-08-03 06:17:18',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => '[{"id":"field_6a7024e309e3e","type":"section","label":"IDENTITAS DIRI","description":""},{"id":"field_6a7024e309e46","type":"text","label":"Nama Lengkap","required":true},{"id":"field_6a7024e309e47","type":"text","label":"Email","required":true},{"id":"field_6a7024e309e48","type":"date","label":"Tanggal lahir","required":false},{"id":"field_6a7024e309e49","type":"text","label":"Nomor telepon","required":true}]',
            ),
            6 => 
            array (
                'id' => 7,
                'title' => 'Staf Keuangan',
                'department' => 'Keuangan / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => 'Dibutuhkan 1 Orang.

Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).

Penempatan: Bandung
Batas Pendaftaran: 14 Agustus 2026
Narahubung: rekrut.rc3id@gmail.com

Jangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:42',
                'updated_at' => '2026-07-31 04:46:42',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'title' => 'Staf Kebersihan & Kurir',
                'department' => 'Operasional / ATTIS',
                'location' => 'Bandung',
                'work_type' => 'Full Time',
                'description' => 'Dibutuhkan 1 Orang.

Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).

Penempatan: Bandung
Batas Pendaftaran: 14 Agustus 2026
Narahubung: rekrut.rc3id@gmail.com

Jangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!',
                'status' => 'published',
                'created_at' => '2026-07-31 04:46:42',
                'updated_at' => '2026-07-31 04:46:42',
                'one_response_per_person' => 0,
                'deadline_date' => NULL,
                'closed_message' => NULL,
                'custom_fields' => NULL,
            ),
        ));
        
        
    }
}