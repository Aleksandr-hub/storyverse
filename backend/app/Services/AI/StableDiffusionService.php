<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stable Diffusion WebUI API Service for local image generation.
 *
 * This service connects to a local Stable Diffusion WebUI instance running with --api flag.
 * Perfect for generating 18+ images that commercial APIs (DALL-E, Midjourney) refuse to create.
 */
class StableDiffusionService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.stable_diffusion.base_url', 'http://stable-diffusion:7860');
        $this->timeout = config('services.stable_diffusion.timeout', 180);
    }

    /**
     * Generate image from text prompt.
     *
     * @param string $prompt The image description
     * @param string $negativePrompt What to avoid in the image
     * @param int $width Image width (default 512)
     * @param int $height Image height (default 512)
     * @param int $steps Number of sampling steps (more = better quality but slower)
     * @param float $cfgScale How closely to follow the prompt (7-12 is typical)
     * @return array|null Returns ['url' => 'path/to/image.png', 'base64' => '...'] or null on failure
     */
    public function generate(
        string $prompt,
        string $negativePrompt = '',
        int $width = 512,
        int $height = 512,
        int $steps = 30,
        float $cfgScale = 7.5
    ): ?array {
        if (!$this->isAvailable()) {
            Log::warning('Stable Diffusion service is not available');
            return null;
        }

        try {
            // Default negative prompt for better quality
            if (empty($negativePrompt)) {
                $negativePrompt = 'low quality, blurry, distorted, deformed, ugly, bad anatomy';
            }

            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/sdapi/v1/txt2img",
                [
                    'prompt' => $prompt,
                    'negative_prompt' => $negativePrompt,
                    'width' => $width,
                    'height' => $height,
                    'steps' => $steps,
                    'cfg_scale' => $cfgScale,
                    'sampler_name' => 'Euler a',
                    'batch_size' => 1,
                    'n_iter' => 1,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $imageBase64 = $data['images'][0] ?? null;

                if (!$imageBase64) {
                    Log::warning('Stable Diffusion returned no images');
                    return null;
                }

                // Save image to storage
                $filename = 'illustrations/' . Str::uuid() . '.png';
                $imageData = base64_decode($imageBase64);
                Storage::disk('public')->put($filename, $imageData);

                return [
                    'url' => Storage::disk('public')->url($filename),
                    'path' => $filename,
                    'base64' => $imageBase64,
                ];
            }

            Log::warning('Stable Diffusion API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Stable Diffusion exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate image variations/improvements using img2img.
     *
     * @param string $prompt The image description
     * @param string $imageBase64 Base64 encoded source image
     * @param float $denoisingStrength How much to change (0=no change, 1=complete redraw)
     * @return array|null
     */
    public function generateVariation(
        string $prompt,
        string $imageBase64,
        float $denoisingStrength = 0.5,
        string $negativePrompt = ''
    ): ?array {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            if (empty($negativePrompt)) {
                $negativePrompt = 'low quality, blurry, distorted, deformed, ugly, bad anatomy';
            }

            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/sdapi/v1/img2img",
                [
                    'prompt' => $prompt,
                    'negative_prompt' => $negativePrompt,
                    'init_images' => [$imageBase64],
                    'denoising_strength' => $denoisingStrength,
                    'steps' => 30,
                    'cfg_scale' => 7.5,
                    'sampler_name' => 'Euler a',
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $imageBase64Result = $data['images'][0] ?? null;

                if (!$imageBase64Result) {
                    return null;
                }

                $filename = 'illustrations/' . Str::uuid() . '.png';
                $imageData = base64_decode($imageBase64Result);
                Storage::disk('public')->put($filename, $imageData);

                return [
                    'url' => Storage::disk('public')->url($filename),
                    'path' => $filename,
                    'base64' => $imageBase64Result,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Stable Diffusion img2img exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if Stable Diffusion WebUI is running and accessible.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/sdapi/v1/sd-models");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models.
     */
    public function getModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/sdapi/v1/sd-models");

            if ($response->successful()) {
                return collect($response->json())
                    ->pluck('title')
                    ->toArray();
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get available samplers.
     */
    public function getSamplers(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/sdapi/v1/samplers");

            if ($response->successful()) {
                return collect($response->json())
                    ->pluck('name')
                    ->toArray();
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Change the active model.
     */
    public function setModel(string $modelName): bool
    {
        try {
            $response = Http::timeout(60)->post(
                "{$this->baseUrl}/sdapi/v1/options",
                ['sd_model_checkpoint' => $modelName]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to change SD model', [
                'model' => $modelName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get progress of current generation.
     */
    public function getProgress(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/sdapi/v1/progress");

            if ($response->successful()) {
                return $response->json();
            }

            return ['progress' => 0, 'eta_relative' => 0];
        } catch (\Exception $e) {
            return ['progress' => 0, 'eta_relative' => 0];
        }
    }

    /**
     * Interrupt current generation.
     */
    public function interrupt(): bool
    {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/sdapi/v1/interrupt");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
