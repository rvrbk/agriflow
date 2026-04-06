<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;

class TranslationController extends Controller
{
    /**
     * @param string $locale
     * @return JsonResponse
     */
    public function show(string $locale): JsonResponse
    {
        $supportedLocales = ['en', 'lg', 'sw'];

        if (!in_array($locale, $supportedLocales, true)) {
            abort(404);
        }

        $translations = Lang::get('ui', [], $locale);

        return response()->json(is_array($translations) ? $translations : []);
    }
}
