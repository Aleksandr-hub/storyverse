<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Send a chat request to Google Gemini API.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(
                "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemPrompt],
                        ],
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $userMessage],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $maxTokens,
                        'temperature' => 0.7,
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::warning('Gemini API error', [
                'provider' => $this->getName(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'provider' => $this->getName(),
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if the provider is configured and available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'gemini';
    }

    /**
     * Get approximate cost per 1K tokens (for prioritization).
     * Gemini 1.5 Flash has generous free tier (60 req/min, 1M tokens/day)
     * Paid: ~$0.075 per 1M tokens = $0.000075 per 1K
     */
    public function getCostPer1KTokens(): float
    {
        return 0.000075;
    }
}
