<?php

namespace App\Services\AI;

use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MultiProviderAIService
{
    /** @var AIProviderInterface[] */
    private array $providers = [];

    /** @var string[] Provider priority order from config */
    private array $priorityOrder;

    public function __construct()
    {
        // Initialize all providers
        $this->providers = [
            'gemini' => new GeminiProvider,
            'claude' => new ClaudeProvider,
            'openai' => new OpenAIProvider,
            'ollama' => new OllamaProvider,
        ];

        // Default priority: Gemini first (free tier), then Claude, then OpenAI
        $this->priorityOrder = config('services.ai.priority', ['gemini', 'claude', 'openai']);
    }

    /**
     * Send chat request using adult-content providers (Ollama).
     * Use this for 18+ content that commercial APIs refuse to generate.
     */
    public function chatAdult(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        $adultPriority = config('services.ai.adult_priority', ['ollama']);
        $errors = [];

        foreach ($adultPriority as $providerName) {
            if (! isset($this->providers[$providerName])) {
                continue;
            }

            $provider = $this->providers[$providerName];

            if (! $provider->isAvailable()) {
                continue;
            }

            if ($this->isProviderDisabled($provider->getName())) {
                continue;
            }

            Log::info("Trying adult AI provider: {$provider->getName()}");

            $result = $provider->chat($systemPrompt, $userMessage, $maxTokens);

            if ($result !== null) {
                Log::info("Successfully used adult provider: {$provider->getName()}");
                $this->resetProviderErrors($provider->getName());

                return $result;
            }

            $errors[] = $provider->getName();
            $this->incrementProviderErrors($provider->getName());
        }

        Log::error('All adult AI providers failed', ['tried' => $errors]);

        return null;
    }

    /**
     * Send a chat request with automatic fallback between providers.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        $errors = [];

        foreach ($this->getOrderedProviders() as $provider) {
            if (! $provider->isAvailable()) {
                continue;
            }

            // Check if provider is temporarily disabled due to errors
            if ($this->isProviderDisabled($provider->getName())) {
                Log::debug("Provider {$provider->getName()} is temporarily disabled");

                continue;
            }

            Log::info("Trying AI provider: {$provider->getName()}");

            $result = $provider->chat($systemPrompt, $userMessage, $maxTokens);

            if ($result !== null) {
                Log::info("Successfully used provider: {$provider->getName()}");
                // Reset error count on success
                $this->resetProviderErrors($provider->getName());

                return $result;
            }

            // Track the error
            $errors[] = $provider->getName();
            $this->incrementProviderErrors($provider->getName());
            Log::warning("Provider {$provider->getName()} failed, trying next...");
        }

        Log::error('All AI providers failed', ['tried' => $errors]);

        return null;
    }

    /**
     * Get providers in priority order.
     *
     * @return AIProviderInterface[]
     */
    private function getOrderedProviders(): array
    {
        $ordered = [];

        foreach ($this->priorityOrder as $name) {
            if (isset($this->providers[$name])) {
                $ordered[] = $this->providers[$name];
            }
        }

        // Add any providers not in priority list
        foreach ($this->providers as $name => $provider) {
            if (! in_array($name, $this->priorityOrder)) {
                $ordered[] = $provider;
            }
        }

        return $ordered;
    }

    /**
     * Check if a provider is temporarily disabled due to too many errors.
     */
    private function isProviderDisabled(string $providerName): bool
    {
        $errorCount = Cache::get("ai_provider_errors:{$providerName}", 0);

        // Disable provider after 3 consecutive errors for 5 minutes
        return $errorCount >= 3;
    }

    /**
     * Increment error count for a provider.
     */
    private function incrementProviderErrors(string $providerName): void
    {
        $key = "ai_provider_errors:{$providerName}";
        $count = Cache::get($key, 0) + 1;
        // Store error count for 5 minutes
        Cache::put($key, $count, 300);
    }

    /**
     * Reset error count for a provider on success.
     */
    private function resetProviderErrors(string $providerName): void
    {
        Cache::forget("ai_provider_errors:{$providerName}");
    }

    /**
     * Get status of all providers.
     */
    public function getProvidersStatus(): array
    {
        $status = [];

        foreach ($this->providers as $name => $provider) {
            $status[$name] = [
                'available' => $provider->isAvailable(),
                'disabled' => $this->isProviderDisabled($name),
                'cost_per_1k_tokens' => $provider->getCostPer1KTokens(),
            ];
        }

        return $status;
    }

    /**
     * Get list of available provider names.
     */
    public function getAvailableProviders(): array
    {
        $available = [];

        foreach ($this->getOrderedProviders() as $provider) {
            if ($provider->isAvailable() && ! $this->isProviderDisabled($provider->getName())) {
                $available[] = $provider->getName();
            }
        }

        return $available;
    }

    /**
     * Check if any provider is available.
     */
    public function isAvailable(): bool
    {
        return count($this->getAvailableProviders()) > 0;
    }

    /**
     * Get the primary (first available) provider name.
     */
    public function getPrimaryProvider(): ?string
    {
        $available = $this->getAvailableProviders();

        return $available[0] ?? null;
    }

    /**
     * Force use of a specific provider (for testing or user preference).
     */
    public function chatWithProvider(string $providerName, string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string
    {
        if (! isset($this->providers[$providerName])) {
            Log::error("Unknown provider: {$providerName}");

            return null;
        }

        $provider = $this->providers[$providerName];

        if (! $provider->isAvailable()) {
            Log::error("Provider {$providerName} is not available");

            return null;
        }

        return $provider->chat($systemPrompt, $userMessage, $maxTokens);
    }
}
