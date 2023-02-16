<?php

namespace Database\Seeders;

use App\Models\Ambulance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class AmbulancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $x = 0;
        while($x<=10) {
            Ambulance::factory()
                ->state(new Sequence(
                    ['board_status' => 'approved'],
                    ['board_status' => 'registered']
                ))
                ->state(new Sequence(
                    ['type' => 'air'],
                    ['type' => 'car']
                ))
                ->state(new Sequence(
                    ['available' => true],
                    ['available' => false]
                ))
                ->state(new Sequence(
                    ['active' => true],
                    ['active' => false]
                ))
                ->for(User::factory())
                ->create();

            $x++;
        }
    }
}
