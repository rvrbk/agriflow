<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Get all currencies with their exchange rates
     */
    public function index(Request $request)
    {
        $currencies = Currency::orderBy('code')->get();
        return response()->json($currencies);
    }

    /**
     * Get exchange rate between two currencies
     */
    public function rate(Request $request, string $from, string $to)
    {
        $fromCurrency = Currency::where('code', strtoupper($from))->first();
        $toCurrency = Currency::where('code', strtoupper($to))->first();

        if (!$fromCurrency || !$toCurrency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }

        // Both relative to USD base
        $rate = $toCurrency->exchange_rate / $fromCurrency->exchange_rate;

        return response()->json([
            'from' => $fromCurrency->code,
            'to' => $toCurrency->code,
            'rate' => $rate,
        ]);
    }

    /**
     * Convert an amount from one currency to another
     */
    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $fromCurrency = Currency::where('code', strtoupper($request->from))->first();
        $toCurrency = Currency::where('code', strtoupper($request->to))->first();

        if (!$fromCurrency || !$toCurrency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }

        $rate = $toCurrency->exchange_rate / $fromCurrency->exchange_rate;
        $converted = $request->amount * $rate;

        return response()->json([
            'amount' => $request->amount,
            'from' => $fromCurrency->code,
            'to' => $toCurrency->code,
            'rate' => $rate,
            'converted_amount' => $converted,
        ]);
    }

    /**
     * Update exchange rate for a currency
     */
    public function update(Request $request, string $code)
    {
        $request->validate([
            'exchange_rate' => 'required|numeric',
        ]);

        $currency = Currency::where('code', strtoupper($code))->first();

        if (!$currency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }

        $currency->update([
            'exchange_rate' => $request->exchange_rate,
        ]);

        return response()->json($currency);
    }
}
