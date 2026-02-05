<?php

namespace App\Services\AI;

use App\Models\Story;
use App\Models\Chapter;

class StoryAIService
{
    public function __construct(
        private readonly MultiProviderAIService $ai
    ) {}

    /**
     * Continue writing a story/chapter.
     */
    public function continueWriting(Story $story, ?Chapter $chapter, string $userPrompt = ''): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story);
        $context = $this->buildContext($story, $chapter);

        $message = $context;
        if (!empty($userPrompt)) {
            $message .= "\n\nІНСТРУКЦІЯ АВТОРА: " . $userPrompt;
        }
        $message .= "\n\nПродовж історію. Напиши наступні 2-3 абзаци.";

        return $this->ai->chat($systemPrompt, $message, 1500);
    }

    /**
     * Get suggestions for the story.
     */
    public function getSuggestions(Story $story, ?Chapter $chapter): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story);
        $context = $this->buildContext($story, $chapter);

        $message = $context;
        $message .= "\n\nДай 3-5 коротких ідей для продовження цієї історії. Формат:\n";
        $message .= "1. [Коротка ідея]\n2. [Коротка ідея]\n...";

        return $this->ai->chat($systemPrompt, $message, 500);
    }

    /**
     * Improve/edit existing text.
     */
    public function improveText(Story $story, string $text, string $instruction): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story);

        $message = "ТЕКСТ ДЛЯ РЕДАГУВАННЯ:\n{$text}\n\n";
        $message .= "ІНСТРУКЦІЯ: {$instruction}\n\n";
        $message .= "Поверни відредагований текст без додаткових коментарів.";

        return $this->ai->chat($systemPrompt, $message, 2000);
    }

    /**
     * Generate a story title.
     */
    public function generateTitle(Story $story): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story);
        $context = $this->buildContext($story, null);

        $message = $context;
        $message .= "\n\nЗапропонуй 5 варіантів назви для цієї історії. ";
        $message .= "Формат - кожна назва з нового рядка, без нумерації.";

        return $this->ai->chat($systemPrompt, $message, 200);
    }

    /**
     * Generate a story description/summary.
     */
    public function generateDescription(Story $story): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story);
        $context = $this->buildContext($story, null);

        $message = $context;
        $message .= "\n\nНапиши короткий опис (анотацію) для цієї історії. ";
        $message .= "2-3 речення, які зацікавлять читача, без спойлерів.";

        return $this->ai->chat($systemPrompt, $message, 300);
    }

    /**
     * Build the system prompt with universe and character context.
     *
     * @param Story $story The story context
     * @param bool $isAdult Whether to build adult content prompt (18+, uncensored)
     */
    private function buildSystemPrompt(Story $story, bool $isAdult = false): string
    {
        $prompt = $this->buildIntroSection($isAdult);
        $prompt .= $this->buildUniverseSection($story, $isAdult);
        $prompt .= $this->buildCharactersSection($story);
        $prompt .= $this->buildStoryMetadataSection($story, $isAdult);

        return $prompt;
    }

    /**
     * Build intro section of system prompt.
     */
    private function buildIntroSection(bool $isAdult): string
    {
        $prompt = "Ти — творчий письменник-помічник для української платформи StoryVerse.\n";
        $prompt .= "ВАЖЛИВО: Завжди пиши українською мовою.\n";

        if ($isAdult) {
            $prompt .= "Ця історія має рейтинг для дорослих (18+). Ти можеш писати:\n";
            $prompt .= "- Еротичні та сексуальні сцени\n";
            $prompt .= "- Ненормативну лексику та матюки\n";
            $prompt .= "- Сцени насильства\n";
            $prompt .= "- Табуйовані теми\n";
            $prompt .= "НЕ ЦЕНЗУРУЙ себе - це офіційний контент для дорослих.\n";
        }

        $prompt .= "Пиши художній текст, уникай кліше, створюй живих персонажів.\n\n";

        return $prompt;
    }

    /**
     * Build universe section of system prompt.
     */
    private function buildUniverseSection(Story $story, bool $isAdult): string
    {
        $story->loadMissing(['universe']);

        if (!$story->universe) {
            return '';
        }

        $section = "=== ВСЕСВІТ ===\n";
        $section .= "Назва: {$story->universe->name}\n";

        if ($story->universe->description) {
            $section .= "Опис: {$story->universe->description}\n";
        }

        if (!$isAdult) {
            $section .= "Дотримуйся канону цього всесвіту.\n";
        }

        return $section . "\n";
    }

    /**
     * Build characters section of system prompt.
     */
    private function buildCharactersSection(Story $story): string
    {
        $story->loadMissing(['characters']);

        if ($story->characters->isEmpty()) {
            return '';
        }

        $section = "=== ПЕРСОНАЖІ ===\n";

        foreach ($story->characters as $character) {
            $section .= "• {$character->name}";

            if ($character->pivot?->role) {
                $section .= " ({$character->pivot->role})";
            }

            if ($character->description) {
                $section .= ": {$character->description}";
            }

            $section .= "\n";
        }

        return $section . "\n";
    }

    /**
     * Build story metadata section of system prompt.
     */
    private function buildStoryMetadataSection(Story $story, bool $isAdult): string
    {
        $section = "=== ІСТОРІЯ ===\n";
        $section .= "Назва: {$story->title}\n";

        if ($story->description) {
            $section .= "Опис: {$story->description}\n";
        }

        $section .= "Рейтинг: " . ($isAdult ? '18+ (без цензури)' : $story->rating) . "\n";

        return $section;
    }

    /**
     * Build context from existing story content.
     */
    private function buildContext(Story $story, ?Chapter $chapter): string
    {
        $context = "=== ПОПЕРЕДНІЙ ТЕКСТ ===\n";

        if ($chapter) {
            // Get content from current chapter
            $content = $chapter->content ?? '';

            // If chapter is short, also include previous chapter
            if (mb_strlen($content) < 1000) {
                $story->loadMissing('chapters');
                $prevChapter = $story->chapters
                    ->where('chapter_number', '<', $chapter->chapter_number)
                    ->sortByDesc('chapter_number')
                    ->first();

                if ($prevChapter && $prevChapter->content) {
                    $prevContent = $this->truncateText($prevChapter->content, 1500);
                    $context .= "[Попередня глава]\n{$prevContent}\n\n";
                }
            }

            $context .= "[Поточна глава: {$chapter->title}]\n";
            $context .= $this->truncateText($content, 2000);
        } else {
            // No specific chapter - get recent content from all chapters
            $story->loadMissing('chapters');
            $recentChapters = $story->chapters
                ->sortByDesc('chapter_number')
                ->take(2);

            foreach ($recentChapters->reverse() as $ch) {
                if ($ch->content) {
                    $context .= "[{$ch->title}]\n";
                    $context .= $this->truncateText($ch->content, 1000) . "\n\n";
                }
            }
        }

        if ($context === "=== ПОПЕРЕДНІЙ ТЕКСТ ===\n") {
            $context .= "(Історія тільки починається, попереднього тексту немає)\n";
        }

        return $context;
    }

    /**
     * Truncate text to max length, trying to break at sentence end.
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        // Get last portion of text
        $text = mb_substr($text, -$maxLength);

        // Try to start from a sentence
        $sentenceStart = mb_strpos($text, '. ');
        if ($sentenceStart !== false && $sentenceStart < 200) {
            $text = mb_substr($text, $sentenceStart + 2);
        }

        return '...' . $text;
    }

    /**
     * Continue writing with adult content (uses Ollama - uncensored).
     */
    public function continueWritingAdult(Story $story, ?Chapter $chapter, string $userPrompt = ''): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($story, isAdult: true);
        $context = $this->buildContext($story, $chapter);

        $message = $context;
        if (!empty($userPrompt)) {
            $message .= "\n\nІНСТРУКЦІЯ АВТОРА: " . $userPrompt;
        }
        $message .= "\n\nПродовж історію. Напиши наступні 2-3 абзаци. ";
        $message .= "Не обмежуй себе, пиши відверто та емоційно.";

        return $this->ai->chatAdult($systemPrompt, $message, 2000);
    }

    /**
     * Check if AI service is available.
     */
    public function isAvailable(): bool
    {
        return $this->ai->isAvailable();
    }

    /**
     * Get status of all AI providers.
     */
    public function getProvidersStatus(): array
    {
        return $this->ai->getProvidersStatus();
    }

    /**
     * Get the primary provider currently in use.
     */
    public function getPrimaryProvider(): ?string
    {
        return $this->ai->getPrimaryProvider();
    }
}
