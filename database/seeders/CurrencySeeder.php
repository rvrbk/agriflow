<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // USD is the base currency with exchange rate of 1
        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'base_currency' => 'USD',
            ]
        );

        // UGX exchange rate (approximate, update as needed)
        // As of April 2026, ~1 USD = 3700 UGX
        Currency::updateOrCreate(
            ['code' => 'UGX'],
            [
                'name' => 'Ugandan Shilling',
                'symbol' => 'USh',
                'exchange_rate' => 3700.000000,
                'base_currency' => 'USD',
            ]
        );
    }
}
