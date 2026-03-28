<?php

namespace App\Services;

use App\Models\Product;
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

                $product->created_by = $user->id;
                $product->uuid = Str::uuid();
            }

            $product->updated_by = $user->id;

            $this->storeTranslations($product, $row['translations']);

            $product->save();
        }
    }

    /**
     * @param Product $product
     * @param array $translations
     * @return void
     */
    private function storeTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $translationrow) {
            foreach ($translationrow as $code => $translation) {
                foreach ($translation as $properties) {
                    foreach ($properties as $property => $value) {
                        $product->setTranslation($property, $code, $value);
                    }
                }
            }
        }
    }
}