<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update exchange rates from external API';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $this->info('Starting exchange rate update...');

        $success = $exchangeRateService->updateRates();

        if ($success) {
            $this->info('Exchange rates updated successfully!');
            return 0;
        }

        $this->error('Failed to update exchange rates. Check logs for details.');
        return 1;
    }
}
