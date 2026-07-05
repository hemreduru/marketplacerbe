<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Models\UserSetting;
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
        $this->call(LanguageSeeder::class);
        $this->call(PlanSeeder::class);

        $user = User::factory()->create([
            'name' => 'Emre Duru',
            'email' => 'hemreduru@gmail.com',
            'username' => 'hemreduru',
            'password' => Hash::make('emre2000'),
        ]);

        $turkish = Language::where('code', 'tr')->first();
        $settings = UserSetting::create([
            'user_id' => $user->id,
            'preferred_language_id' => $turkish?->id,
            'dark_mode' => false,
        ]);
        $user->update(['settings_id' => $settings->id]);

        $this->call(MarketplaceSeeder::class);

        // Add Trendyol Credentials
        $trendyol = Marketplace::where('slug', 'trendyol')->first();
        if ($trendyol) {
            UserMarketplaceCredential::create([
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
