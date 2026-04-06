<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Facades\Blaaiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $customer = Blaaiz::customers()->create($request->validate([
            'first_name' => ['required_without:business_name', 'string'],
            'last_name' => ['required_without:business_name', 'string'],
            'business_name' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            'email' => ['required', 'email'],
            'country' => ['required', 'string', 'size:2'],
            'id_type' => ['required', 'string'],
            'id_number' => ['required', 'string'],
        ]));

        return response()->json($customer);
    }
}
