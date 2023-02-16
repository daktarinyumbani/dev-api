<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password' => Hash::make('Test@123'),
            'phone' => '255012345678',
            'email' => 'admin@daktarinyumbani.co.tz'
        ]);

        $user->assignRole('admin');
    }
}
