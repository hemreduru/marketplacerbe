<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Emre Duru',
            'email' => 'hemreduru@gmail.com',
            'username' => 'hemreduru',
            'password' => Hash::make('emre2000'),
        ]);

        $this->call(MarketplaceSeeder::class);

        // Add Trendyol Credentials
        $trendyol = \App\Models\Marketplace::where('slug', 'trendyol')->first();
        if ($trendyol) {
            \App\Models\UserMarketplaceCredential::create([
                'user_id' => $user->id,
                'marketplace_id' => $trendyol->id,
                'api_key' => 'PICixzvGypfjiBfTVz0z',
                'api_secret' => '95HaBdU0zMsWoPxYMywQ',
                'additional_credentials' => ['seller_id' => '342591'],
                'is_active' => true,
            ]);
        }
    }
}
