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
            'type' => ['required', 'in:individual,business'],
            'email' => ['required', 'email'],
            'country' => ['required', 'string', 'size:2'],

            // Individual customers identify with personal ID fields.
            'first_name' => ['required_if:type,individual', 'prohibited_if:type,business', 'string'],
            'last_name' => ['required_if:type,individual', 'prohibited_if:type,business', 'string'],
            'id_type' => ['required_if:type,individual', 'prohibited_if:type,business', 'string'],
            'id_number' => ['required_if:type,individual', 'prohibited_if:type,business', 'string'],

            // Business customers identify with registration details.
            'business_name' => ['required_if:type,business', 'prohibited_if:type,individual', 'string'],
            'registration_number' => ['required_if:type,business', 'prohibited_if:type,individual', 'string'],
            'incorporation_country' => ['required_if:type,business', 'prohibited_if:type,individual', 'string', 'size:2'],
        ]));

        return response()->json($customer);
    }
}
