<?php

namespace App\Services;

use App\Models\Corporation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CorporationService
{
    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        foreach ($data as $row) {
            $corporation = null;

            if (isset($row['uuid'])) {
                $corporation = Corporation::where('uuid', $row['uuid'])->first();
            }

            if (!$corporation) {
                $corporation = new Corporation();

                $corporation->uuid = Str::uuid();
            }

            $corporation->name = $row['name'];
            $corporation->location = isset($row['location']) ? json_encode($row['location']) : null;
            $corporation->address = $row['address'] ?? null;
            $corporation->city = $row['city'] ?? null;
            $corporation->state = $row['state'] ?? null;
            $corporation->country = $row['country'] ?? null;

            $corporation->save();
        }
    }
}