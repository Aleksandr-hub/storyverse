<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload an image file.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
            'type' => 'required|in:avatar,cover,illustration',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $user = $request->user();

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;

        // Determine path based on type
        $path = match ($type) {
            'avatar' => 'avatars/'.$user->id,
            'cover' => 'covers',
            'illustration' => 'illustrations',
            default => 'uploads',
        };

        // Store file
        $storedPath = $file->storeAs($path, $filename, 'public');

        // Generate full URL
        $url = Storage::disk('public')->url($storedPath);

        return response()->json([
            'message' => 'Файл завантажено успішно',
            'url' => $url,
            'path' => $storedPath,
        ], 201);
    }

    /**
     * Delete an uploaded file.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Security: only allow deleting from allowed directories
        $allowedPrefixes = ['avatars/', 'covers/', 'illustrations/'];
        $isAllowed = false;

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            return response()->json([
                'message' => 'Недозволений шлях',
            ], 403);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            return response()->json([
                'message' => 'Файл видалено',
            ]);
        }

        return response()->json([
            'message' => 'Файл не знайдено',
        ], 404);
    }
}
