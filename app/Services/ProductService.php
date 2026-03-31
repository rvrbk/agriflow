<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductProperty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @param array $data
     * @return void
     */
    public function store(array $data): void
    {
        $user = Auth::user();

        foreach ($data as $row) {
            $product = null;
        
            if (isset($row['uuid'])) {
                $product = Product::where('uuid', $row['uuid'])->first();
            }

            if (!$product) {
                $product = new Product();

                $product->uuid = Str::uuid();
            }

            $this->storeTranslations($product, $row['translations']);

            $product->save();

            $this->storeProperties($product, $row['properties']);
        }
    }

    /**
     * @param Product $product
      * @param array $translations
     * @return void
     */
    private function storeTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $translation) {
            foreach ($translation as $property => $localizations) {
                foreach ($localizations as $localization) {
                    foreach ($localization as $code => $value) {
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