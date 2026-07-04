<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'شركة تك كورب',
                'email' => 'hr@techcorp.com',
                'phone' => '022111111',
                'provider_data' => [
                    'organization_name' => 'Tech Corp',
                    'organization_type' => 'company',
                    'address' => 'شارع النصر، غزة',
                    'city' => 'غزة',
                    'country' => 'فلسطين',
                    'website' => 'https://techcorp.com',
                    'description' => 'شركة رائدة في تطوير البرمجيات',
                    'is_verified' => true,
                ]
            ],
            [
                'name' => 'مستشفى الشفاء',
                'email' => 'training@shifa hospital.com',
                'phone' => '022222222',
                'provider_data' => [
                    'organization_name' => 'مستشفى الشفاء',
                    'organization_type' => 'hospital',
                    'address' => 'شارع الوحدة، غزة',
                    'city' => 'غزة',
                    'country' => 'فلسطين',
                    'website' => 'https://shifahospital.com',
                    'description' => 'أكبر مستشفى في فلسطين',
                    'is_verified' => true,
                ]
            ],
        ];

        foreach ($providers as $providerData) {
            $user = User::create([
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'password' => Hash::make('password123'),
                'phone' => $providerData['phone'],
                'role' => 'provider',
            ]);

            Provider::create([
                'user_id' => $user->id,
                'organization_name' => $providerData['provider_data']['organization_name'],
                'organization_type' => $providerData['provider_data']['organization_type'],
                'address' => $providerData['provider_data']['address'],
                'city' => $providerData['provider_data']['city'],
                'country' => $providerData['provider_data']['country'],
                'website' => $providerData['provider_data']['website'],
                'description' => $providerData['provider_data']['description'],
                'is_verified' => $providerData['provider_data']['is_verified'],
            ]);
        }
    }
}
