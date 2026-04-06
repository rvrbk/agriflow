<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * @param float $latitude
     * @param float $longitude
     * @return array<string, string|null>
     */
    public function reverse(float $latitude, float $longitude): array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'AgriFlow/1.0',
            ])
            ->acceptJson()
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'addressdetails' => 1,
            ]);

        if (!$response->successful()) {
            return [
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => null,
            ];
        }

        $payload = $response->json();
        $address = $payload['address'] ?? [];

        $line = trim(implode(' ', array_filter([
            $address['house_number'] ?? null,
            $address['road'] ?? null,
        ])));

        return [
            'address' => $line !== '' ? $line : ($payload['display_name'] ?? null),
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
            'state' => $address['state'] ?? $address['region'] ?? $address['state_district'] ?? null,
            'country' => isset($address['country_code']) ? strtoupper((string) $address['country_code']) : null,
        ];
    }
}
