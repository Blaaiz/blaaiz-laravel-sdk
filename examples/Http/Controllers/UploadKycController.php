<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Facades\Blaaiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadKycController extends Controller
{
    public function store(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'file_category' => ['required', 'in:identity,identity_back,proof_of_address,liveness_check'],
        ]);

        $result = Blaaiz::customers()->uploadFileComplete($customerId, [
            'file' => $validated['file']->getRealPath(),
            'file_category' => $validated['file_category'],
            'filename' => $validated['file']->getClientOriginalName(),
            'content_type' => $validated['file']->getMimeType(),
        ]);

        return response()->json($result);
    }
}
