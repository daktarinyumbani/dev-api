<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Doctor/Lab', 'key' => 'general'],
            ['name' => 'Nurse/Physiotherapy', 'key' => 'nursing'],
            ['name' => 'Specialists', 'key' => 'specialist'],
            ['name' => 'Super Specialists', 'key' => 'super_specialist']
        ];

        Category::insert($data);

        //TODO add the specialties here
        $specialtiesData = [
            ['name' => 'General', 'category_id' => 1, 'icon' => 'user-md'],
            ['name' => 'Nurse', 'category_id' => 2, 'icon' => 'user-nurse'],
            ['name' => 'Physiotherapist', 'category_id' => 2, 'icon' => 'walking'],
            ['name' => 'OBS & GY', 'category_id' => 3, 'icon' => 'user-md'],
            ['name' => 'Dermatologist', 'category_id' => 3, 'icon' => 'allergies'],
            ['name' => 'Internal Medicine', 'category_id' => 3, 'icon' => 'capsules'],
            ['name' => 'Surgery', 'category_id' => 3, 'icon' => 'cut'],
            ['name' => 'Pediatrics', 'category_id' => 3, 'icon' => 'user-md'],
            ['name' => 'Plastic Surgeon', 'category_id' => 3, 'icon' => 'user-md'],
            ['name' => 'Orthopedics', 'category_id' => 3, 'icon' => 'user-md'],
            ['name' => 'Urology', 'category_id' => 3, 'icon' => 'user-md'],
            ['name' => 'Neurology', 'category_id' => 3, 'icon' => 'atom'],
            ['name' => 'ENT', 'category_id' => 3, 'icon' => 'head-side-virus'],
            ['name' => 'Ophthalmologist', 'category_id' => 3, 'icon' => 'eye'],
            ['name' => 'Cardiology', 'category_id' => 4, 'icon' => 'heartbeat'],
            ['name' => 'Neurology', 'category_id' => 4, 'icon' => 'user-md'],
            ['name' => 'Orthopedics', 'category_id' => 4, 'icon' => 'user-md'],
        ];

        Specialty::insert($specialtiesData);
    }
}
