<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.claude.api_key', '');
        $this->model = config('services.claude.model', 'claude-sonnet-4-20250514');
    }

    /**
     * Send a chat request to Claude API.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'][0]['text'] ?? null;
            }

            Log::warning('Claude API error', [
                'provider' => $this->getName(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Claude API exception', [
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
        return 'claude';
    }

    /**
     * Get approximate cost per 1K tokens (for prioritization).
     * Claude Sonnet: ~$3 per 1M input tokens = $0.003 per 1K
     */
    public function getCostPer1KTokens(): float
    {
        return 0.003;
    }
}
