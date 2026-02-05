<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Send a chat request to the AI provider.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): ?string;

    /**
     * Check if the provider is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Get approximate cost per 1K tokens (for prioritization).
     */
    public function getCostPer1KTokens(): float;
}
