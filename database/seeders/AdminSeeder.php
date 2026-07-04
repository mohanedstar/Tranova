<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@trinova.com',
            'password' => Hash::make('admin123'),
            'phone' => '0590000000',
            'role' => 'admin',
        ]);
    }
}
