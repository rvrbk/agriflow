<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CountryService
{
    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function list(): array
    {
        return Cache::remember('countries.code_name_list', now()->addDay(), function () {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://restcountries.com/v3.1/all', [
                    'fields' => 'name,cca2',
                ]);

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json())
                ->map(function (array $country): ?array {
                    $code = strtoupper((string) ($country['cca2'] ?? ''));
                    $name = (string) ($country['name']['common'] ?? '');

                    if ($code === '' || $name === '') {
                        return null;
                    }

                    return [
                        'code' => $code,
                        'name' => $name,
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }
}
