<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ollama Provider for local LLM inference.
 *
 * Ollama allows running uncensored models locally (like Mistral, Llama2-uncensored, etc.)
 * Perfect for 18+ creative writing content that commercial APIs refuse to generate.
 */
class OllamaProvider implements AIProviderInterface
{
    private string $baseUrl;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url', 'http://ollama:11434');
        $this->model = config('services.ollama.model', 'mistral');
        $this->timeout = config('services.ollama.timeout', 120);
    }

    /**
     * Send a chat request to local Ollama API.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            // Ollama uses OpenAI-compatible chat format
            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/api/chat",
                [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'stream' => false,
                    'options' => [
                        'num_predict' => $maxTokens,
                        'temperature' => 0.8,
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                return $data['message']['content'] ?? null;
            }

            Log::warning('Ollama API error', [
                'provider' => $this->getName(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Ollama API exception', [
                'provider' => $this->getName(),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if Ollama is running and model is available.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if (! $response->successful()) {
                return false;
            }

            // Check if our model is available
            $models = $response->json('models', []);
            foreach ($models as $model) {
                if (str_starts_with($model['name'] ?? '', $this->model)) {
                    return true;
                }
            }

            // Model not found but Ollama is running
            Log::info("Ollama running but model '{$this->model}' not found. Available: ".
                implode(', ', array_column($models, 'name')));

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'ollama';
    }

    /**
     * Get cost per 1K tokens (free for local).
     */
    public function getCostPer1KTokens(): float
    {
        return 0.0; // Free - runs locally
    }

    /**
     * Check if Ollama service is reachable (without checking model).
     */
    public function isServiceRunning(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get list of available models.
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) {
                return array_column($response->json('models', []), 'name');
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Pull a model from Ollama registry.
     */
    public function pullModel(string $modelName): bool
    {
        try {
            $response = Http::timeout(600)->post("{$this->baseUrl}/api/pull", [
                'name' => $modelName,
                'stream' => false,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to pull Ollama model', [
                'model' => $modelName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
