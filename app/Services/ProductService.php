<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductProperty;
use Illuminate\Support\Str;

class ProductService
{
    public const DELETE_DELETED = 'deleted';
    public const DELETE_NOT_FOUND = 'not_found';
    public const DELETE_LINKED_TO_HARVEST = 'linked_to_harvest';

    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        $rows = $this->normalizeRows($data);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $product = null;
        
            if (isset($row['uuid'])) {
                $product = Product::where('uuid', $row['uuid'])->first();
            }

            if (!$product) {
                $product = new Product();

                $product->uuid = Str::uuid();
            }

            $this->storeTranslations($product, $row);

            if (array_key_exists('unit', $row) && $row['unit']) {
                $product->unit = $row['unit'];
            }

            $product->save();

            if (array_key_exists('properties', $row) && is_array($row['properties'])) {
                $this->storeProperties($product, $row['properties']);
            }
        }
    }

    /**
     * Normalize inbound payload to a list of product row arrays.
     *
     * @param array $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $data): array
    {
        if (isset($data['name']) || isset($data['unit']) || isset($data['uuid'])) {
            return [$data];
        }

        $rows = [];

        foreach ($data as $row) {
            if (is_string($row)) {
                $decoded = json_decode($row, true);

                if (is_array($decoded)) {
                    $row = $decoded;
                }
            }

            if (!is_array($row)) {
                continue;
            }

            if (isset($row['name']) || isset($row['unit']) || isset($row['uuid'])) {
                $rows[] = $row;
                continue;
            }

            if ($row !== [] && array_is_list($row)) {
                foreach ($row as $nestedRow) {
                    if (is_array($nestedRow) && (isset($nestedRow['name']) || isset($nestedRow['unit']) || isset($nestedRow['uuid']))) {
                        $rows[] = $nestedRow;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @param string $uuid
     * @return string
     */
    public function deleteByUuid(string $uuid): string
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return self::DELETE_NOT_FOUND;
        }

        $hasHarvestLinks = Inventory::query()
            ->where('product_id', $product->id)
            ->whereNotNull('batch_id')
            ->exists();

        if ($hasHarvestLinks) {
            return self::DELETE_LINKED_TO_HARVEST;
        }

        ProductProperty::where('product_id', $product->id)->delete();
        $product->delete();

        return self::DELETE_DELETED;
    }

    /**
     * @param Product $product
      * @param array $translations
     * @return void
     */
    private function storeTranslations(Product $product, array $row): void
    {
        if (isset($row['name']) && is_string($row['name']) && $row['name'] !== '') {
            $product->setTranslation('name', 'en', $row['name']);
            return;
        }

        $translations = $row['translations'] ?? [];

        if (!is_array($translations)) {
            return;
        }

        foreach ($translations as $translation) {
            if (!is_array($translation)) {
                continue;
            }

            foreach ($translation as $property => $localizations) {
                if (!is_array($localizations)) {
                    continue;
                }

                foreach ($localizations as $localization) {
                    if (!is_array($localization)) {
                        continue;
                    }

                    foreach ($localization as $code => $value) {
                        if (!is_string($value) || $value === '') {
                            continue;
                        }

                        $product->setTranslation($property, $code, $value);
                    }
                }
            }
        }
    }

    /**
     * @param Product $product
        * @param array $productProperties
     * @return void
     */
    private function storeProperties(Product $product, array $productProperties): void
    {
        ProductProperty::where('product_id', $product->id)->delete();

        foreach ($productProperties as $productPropertyRow) {
            if (!is_array($productPropertyRow)) {
                continue;
            }

            $productProperty = new ProductProperty();

            $productProperty->product_id = $product->id;
            $productProperty->type = $productPropertyRow['type'];
            $productProperty->sequence = $productPropertyRow['sequence'];

            foreach (['key', 'value'] as $property) {
                $localizations = $productPropertyRow[$property] ?? [];

                if (!is_array($localizations)) {
                    continue;
                }

                foreach ($localizations as $localization) {
                    if (!is_array($localization)) {
                        continue;
                    }

                    foreach ($localization as $code => $value) {
                        if (!is_string($value) || $value === '') {
                            continue;
                        }

                        $productProperty->setTranslation($property, $code, $value);
                    }
                }
            }

            $productProperty->save();
        }
    }
}