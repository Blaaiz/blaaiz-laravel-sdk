<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Facades\Blaaiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlaaizWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $event = Blaaiz::webhooks()->constructEvent(
            $request->getContent(),
            $request->header('X-Blaaiz-Signature', ''),
            $request->header('X-Blaaiz-Timestamp', ''),
            (string) config('blaaiz.webhook_secret')
        );

        return response()->json([
            'received' => true,
            'event' => $event,
        ]);
    }
}
