<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Facades\Blaaiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadKycFromS3Controller extends Controller
{
    public function store(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'file_category' => ['required', 'in:identity,proof_of_address,liveness_check'],
        ]);

        $disk = Storage::disk('s3');
        $path = $validated['path'];

        if (! $disk->exists($path)) {
            abort(404, 'S3 file not found.');
        }

        $result = Blaaiz::customers()->uploadFileComplete($customerId, [
            'file' => $disk->get($path),
            'file_category' => $validated['file_category'],
            'filename' => basename($path),
            'content_type' => $disk->mimeType($path) ?: null,
        ]);

        return response()->json($result);
    }
}
