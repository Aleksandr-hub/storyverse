<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    private string $apiKey;

    private string $baseUrl = 'https://api.openai.com/v1';

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    /**
     * Send a chat request to OpenAI API.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::warning('OpenAI API error', [
                'provider' => $this->getName(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
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
        return ! empty($this->apiKey);
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * Get approximate cost per 1K tokens (for prioritization).
     * GPT-4o: ~$2.50 per 1M input tokens = $0.0025 per 1K
     */
    public function getCostPer1KTokens(): float
    {
        return 0.0025;
    }
}
