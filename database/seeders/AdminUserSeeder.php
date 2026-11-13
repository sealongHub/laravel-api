<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'long26@gmail.com';
        $existing = User::where('email', $email)->first();
        if ($existing) {
            // ensure profile.type is super_admin
            $existing->load('profiles');
            if ($existing->profiles) {
                $existing->profiles()->update(['type' => 'super_admin']);
            } else {
                $existing->profiles()->create(['phone' => 'N/A', 'address' => 'N/A', 'imagee' => null, 'type' => 'super_admin']);
            }
            return;
        }

        $user = User::create([
            'name' => 'Lee Sheerlong',
            'email' => $email,
            'password' => Hash::make('Seaxlong@01'),
        ]);

        $user->profiles()->create([
            'phone' => '012345678',
            'address' => 'Sheerlong HQ',
            'imagee' => null,
            'type' => 'super_admin',
        ]);
    }
}
