<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * External API endpoint for exchange rates
     * ExchangeRate-API: https://www.exchangerate-api.com/
     * Free tier: 1500 requests/month with API key
     */
    protected string $apiUrl = 'https://v6.exchangerate-api.com/v6';

    /**
     * Get API key from configuration
     */
    protected function getApiKey(): ?string
    {
        return config('services.exchangerate.key') ?? env('EXCHANGE_RATE_API_KEY');
    }

    /**
     * Get fallback UGX rate from configuration
     */
    protected function getFallbackUgxRate(): float
    {
        return (float) (config('services.exchangerate.fallback_ugx_rate') ?? env('FALLBACK_UGX_RATE', 3700.00));
    }

    /**
     * Fetch and update exchange rates from external API
     * Uses USD as base currency
     * Falls back to configured rate if API is unavailable
     */
    public function updateRates(): bool
    {
        // Try API first if key is configured
        if ($this->getApiKey()) {
            if ($this->fetchFromApi()) {
                return true;
            }
            Log::warning('ExchangeRate-API failed, trying fallback...');
        } else {
            Log::info('No API key configured, using fallback rates');
        }

        // Use fallback rates
        return $this->useFallbackRates();
    }

    /**
     * Fetch rates from ExchangeRate-API
     */
    protected function fetchFromApi(): bool
    {
        $apiKey = $this->getApiKey();
        $url = "{$this->apiUrl}/{$apiKey}/latest/USD";

        try {
            $response = Http::withOptions(['verify' => false])->get($url);

            if (!$response->successful()) {
                $data = $response->json();
                $status = $response->status();
                
                // Handle rate limit exceeded
                if ($status === 429 || ($data['error']['code'] ?? 0) === 104) {
                    Log::warning('ExchangeRate-API rate limit exceeded');
                    return false;
                }
                
                // Handle invalid key
                if ($status === 401 || ($data['error']['code'] ?? 0) === 101) {
                    Log::error('ExchangeRate-API: Invalid API key', [
                        'response' => $data,
                    ]);
                    return false;
                }

                Log::error('ExchangeRate-API request failed', [
                    'status' => $status,
                    'response' => $data,
                ]);
                return false;
            }

            $data = $response->json();

            if (empty($data['conversion_rates'])) {
                Log::error('ExchangeRate-API: Missing conversion rates in response', ['data' => $data]);
                return false;
            }

            $rates = $data['conversion_rates'];

            // Update USD (always 1.0)
            $this->updateCurrency('USD', 'US Dollar', '$', 1.0);

            // Update UGX if available
            if (isset($rates['UGX'])) {
                $this->updateCurrency('UGX', 'Ugandan Shilling', 'USh', (float) $rates['UGX']);
                
                Log::info('Exchange rates updated from API', [
                    'usd' => 1.0,
                    'ugx' => $rates['UGX'],
                    'source' => 'ExchangeRate-API',
                ]);
                return true;
            } else {
                Log::warning('ExchangeRate-API: UGX not in response, using fallback', [
                    'available_currencies' => array_keys($rates),
                ]);
                $this->useFallbackRates();
                return true;
            }

        } catch (\Exception $e) {
            Log::error('ExchangeRate-API request exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Use fallback rates from configuration
     */
    protected function useFallbackRates(): bool
    {
        $ugxRate = $this->getFallbackUgxRate();

        // Update USD (always 1.0)
        $this->updateCurrency('USD', 'US Dollar', '$', 1.0);

        // Update UGX with fallback rate
        $this->updateCurrency('UGX', 'Ugandan Shilling', 'USh', $ugxRate);

        Log::info('Exchange rates updated using fallback values', [
            'usd' => 1.0,
            'ugx' => $ugxRate,
            'source' => 'Fallback Configuration',
        ]);

        return true;
    }

    /**
     * Update or create a currency record
     */
    protected function updateCurrency(string $code, string $name, string $symbol, float $rate): void
    {
        Currency::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'symbol' => $symbol,
                'exchange_rate' => $rate,
                'base_currency' => 'USD',
            ]
        );
    }

    /**
     * Get current exchange rate between two currencies
     */
    public function getRate(string $from, string $to): ?float
    {
        $fromCurrency = Currency::where('code', strtoupper($from))->first();
        $toCurrency = Currency::where('code', strtoupper($to))->first();

        if (!$fromCurrency || !$toCurrency) {
            return null;
        }

        return $toCurrency->exchange_rate / $fromCurrency->exchange_rate;
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): ?float
    {
        $rate = $this->getRate($from, $to);
        if ($rate === null) {
            return null;
        }
        return $amount * $rate;
    }
}
