<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Home Visit', 'icon' => 'home', 'key' => 'home_visit', 'visible' => true],
            ['name' => 'Hospital Doctors', 'icon' => 'hospital-user', 'key' => 'hospital_doctor', 'visible' => true],
            ['name' => 'Family Dr/Abroad Hospitals', 'icon' => 'user-md', 'key' => 'family', 'visible' => true],
            ['name' => 'Online Consultation', 'icon' => 'laptop-medical', 'key' => 'online', 'visible' => true],
        ];

        Service::insert($data);
    }
}
