<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Blaaiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function store(Request $request, Blaaiz $blaaiz): JsonResponse
    {
        $payout = $blaaiz->payouts()->initiate($request->validate([
            'wallet_id' => ['required', 'string'],
            'customer_id' => ['required', 'string'],
            'method' => ['required', 'string'],
            'from_currency_id' => ['required', 'string'],
            'to_currency_id' => ['required', 'string'],
            'from_amount' => ['nullable', 'numeric'],
            'to_amount' => ['nullable', 'numeric'],
            'bank_id' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
        ]));

        return response()->json($payout);
    }
}
