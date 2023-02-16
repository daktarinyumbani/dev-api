<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Specialty;
use App\Models\User;
use App\Traits\Users;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ServiceProvidersSeeder extends Seeder
{
    use Users;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //make users
//        $users = User::factory()->count(10)->create();

        $services = Service::all();

        //make service providers
        $x= 0;

        while($x<=10) {
            $serviceProviders = ServiceProvider::factory()
                ->state(new Sequence(
                    ['board_status' => 'approved'],
                    ['board_status' => 'registered']
                ))
                ->state(new Sequence(
                    ['available' => true],
                    ['available' => false]
                ))
                ->state(new Sequence(
                    ['active' => true],
                    ['active' => false]
                ))
                ->state(new Sequence(
                    ['specialty_id' => Specialty::all()->random()]
                ))
                ->for(User::factory())
                ->create();

            $x++;
        }
        //attach the services
        ServiceProvider::all()->each(function ($serviceProvider) use($services) {
            $serviceProvider->services()->attach(
                $services->random((rand(1,4)))->pluck('id')->toArray()
            );
        });

//        $user = $this->getOrCreateUser([
//            'first_name' => 'James',
//            'last_name' => 'Doe',
//            'phone' => '255198765432',
//            'password' => 'Test@123'
//        ]);
//        ServiceProvider::create([
//            'specialty_id' => 1,
//            'user_id' => $user->id,
//            'qualification' => 'Qualified doctor',
//            'reg_number' => '1313ADCA24',
//            'board_status' => 'approved',
//            'current_hospital' => 'Aga Khan',
//            'bio' => 'A qualified doctor',
//            'available' => 1,
//            'address' => 'Kwa Mwalimu',
//            'latitude' => -6.760142792710726,
//            'longitude' => 39.25232050830577,
//            'cost' => 50000.00,
//            'active' => 1,
//        ]);
    }
}
