<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;

class DummyJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $descriptionTemplate = "Bergabunglah langsung dalam inovasi riset kesehatan bersama RC3ID. Saat ini, kami mencari kandidat terbaik untuk mengisi posisi pada penelitian Asymptomatic TB Transmission in Indonesia and South Africa (ATTIS).\n\nPenempatan: Bandung\nBatas Pendaftaran: 14 Agustus 2026\nNarahubung: rekrut.rc3id@gmail.com\n\nJangan lewatkan kesempatan ini dan mari menjadi bagian dari RC3ID!";

        $jobs = [
            [
                'title' => 'Sekretaris Manajer',
                'department' => 'Manajemen / ATTIS',
                'work_type' => 'Full Time',
                'location' => 'Bandung',
                'description' => "Dibutuhkan 1 Orang.\n\n" . $descriptionTemplate,
                'status' => 'published',
            ],
            [
                'title' => 'Staf Administrasi',
                'department' => 'Administrasi / ATTIS',
                'work_type' => 'Full Time',
                'location' => 'Bandung',
                'description' => "Dibutuhkan 1 Orang.\n\n" . $descriptionTemplate,
                'status' => 'published',
            ],
            [
                'title' => 'Staf Keuangan',
                'department' => 'Keuangan / ATTIS',
                'work_type' => 'Full Time',
                'location' => 'Bandung',
                'description' => "Dibutuhkan 1 Orang.\n\n" . $descriptionTemplate,
                'status' => 'published',
            ],
            [
                'title' => 'Staf Kebersihan & Kurir',
                'department' => 'Operasional / ATTIS',
                'work_type' => 'Full Time',
                'location' => 'Bandung',
                'description' => "Dibutuhkan 1 Orang.\n\n" . $descriptionTemplate,
                'status' => 'published',
            ],
        ];

        foreach ($jobs as $job) {
            Job::create($job);
        }
    }
}
