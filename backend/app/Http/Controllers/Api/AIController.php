<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Illustration;
use App\Models\Story;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\StableDiffusionService;
use App\Services\AI\StoryAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(
        private StoryAIService $aiService,
        private StableDiffusionService $imageService
    ) {}

    /**
     * Check if AI service is available.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'available' => $this->aiService->isAvailable(),
            'primary_provider' => $this->aiService->getPrimaryProvider(),
            'providers' => $this->aiService->getProvidersStatus(),
        ]);
    }

    /**
     * Continue writing a story.
     */
    public function continueWriting(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $validated = $request->validate([
            'chapter_id' => 'nullable|uuid|exists:chapters,id',
            'prompt' => 'nullable|string|max:500',
        ]);

        $chapter = null;
        if (! empty($validated['chapter_id'])) {
            $chapter = Chapter::find($validated['chapter_id']);
        }

        $result = $this->aiService->continueWriting(
            $story,
            $chapter,
            $validated['prompt'] ?? ''
        );

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс тимчасово недоступний',
            ], 503);
        }

        return response()->json([
            'text' => $result,
        ]);
    }

    /**
     * Get suggestions for story continuation.
     */
    public function getSuggestions(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $validated = $request->validate([
            'chapter_id' => 'nullable|uuid|exists:chapters,id',
        ]);

        $chapter = null;
        if (! empty($validated['chapter_id'])) {
            $chapter = Chapter::find($validated['chapter_id']);
        }

        $result = $this->aiService->getSuggestions($story, $chapter);

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс тимчасово недоступний',
            ], 503);
        }

        return response()->json([
            'suggestions' => $result,
        ]);
    }

    /**
     * Improve/edit text.
     */
    public function improveText(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'instruction' => 'required|string|max:500',
        ]);

        $result = $this->aiService->improveText(
            $story,
            $validated['text'],
            $validated['instruction']
        );

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс тимчасово недоступний',
            ], 503);
        }

        return response()->json([
            'text' => $result,
        ]);
    }

    /**
     * Generate title suggestions.
     */
    public function generateTitle(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $result = $this->aiService->generateTitle($story);

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс тимчасово недоступний',
            ], 503);
        }

        // Parse titles into array
        $titles = array_filter(
            array_map('trim', explode("\n", $result)),
            fn ($line) => ! empty($line)
        );

        return response()->json([
            'titles' => array_values($titles),
        ]);
    }

    /**
     * Generate description/summary.
     */
    public function generateDescription(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $result = $this->aiService->generateDescription($story);

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс тимчасово недоступний',
            ], 503);
        }

        return response()->json([
            'description' => $result,
        ]);
    }

    /**
     * Generate illustration for a chapter using Stable Diffusion.
     */
    public function generateIllustration(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        $validated = $request->validate([
            'chapter_id' => 'required|uuid|exists:chapters,id',
            'prompt' => 'required|string|max:1000',
            'negative_prompt' => 'nullable|string|max:500',
            'width' => 'nullable|integer|min:256|max:1024',
            'height' => 'nullable|integer|min:256|max:1024',
            'style' => 'nullable|string|in:anime,realistic,fantasy,sketch',
        ]);

        // Build enhanced prompt with style
        $prompt = $validated['prompt'];
        $style = $validated['style'] ?? 'fantasy';

        $stylePrompts = [
            'anime' => 'anime style, manga, japanese animation, vibrant colors, clean lines',
            'realistic' => 'photorealistic, detailed, 8k uhd, realistic lighting, professional photography',
            'fantasy' => 'fantasy art, digital painting, epic, dramatic lighting, detailed',
            'sketch' => 'sketch, pencil drawing, line art, black and white, artistic',
        ];

        $prompt = $stylePrompts[$style].', '.$prompt;

        $result = $this->imageService->generate(
            $prompt,
            $validated['negative_prompt'] ?? '',
            $validated['width'] ?? 512,
            $validated['height'] ?? 512
        );

        if ($result === null) {
            return response()->json([
                'message' => 'Сервіс генерації зображень недоступний. Переконайтесь, що Stable Diffusion запущено.',
            ], 503);
        }

        // Save illustration to database
        $chapter = Chapter::find($validated['chapter_id']);
        $lastPosition = Illustration::where('chapter_id', $chapter->id)->max('position') ?? 0;

        $illustration = Illustration::create([
            'story_id' => $story->id,
            'chapter_id' => $chapter->id,
            'image_url' => $result['url'],
            'prompt' => $validated['prompt'],
            'position' => $lastPosition + 1,
        ]);

        return response()->json([
            'illustration' => $illustration,
            'url' => $result['url'],
        ]);
    }

    /**
     * Check image generation service status.
     */
    public function imageStatus(): JsonResponse
    {
        return response()->json([
            'available' => $this->imageService->isAvailable(),
            'models' => $this->imageService->getModels(),
            'samplers' => $this->imageService->getSamplers(),
        ]);
    }

    /**
     * Continue writing with adult content (uses Ollama).
     */
    public function continueWritingAdult(Request $request, Story $story): JsonResponse
    {
        // Check ownership
        if ($story->author_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не маєте доступу до цієї історії',
            ], 403);
        }

        // Check story has adult rating
        if (! in_array($story->rating, ['R', 'NC-17', '18+'])) {
            return response()->json([
                'message' => 'Цей режим доступний лише для історій з рейтингом 18+',
            ], 403);
        }

        $validated = $request->validate([
            'chapter_id' => 'nullable|uuid|exists:chapters,id',
            'prompt' => 'nullable|string|max:500',
        ]);

        $chapter = null;
        if (! empty($validated['chapter_id'])) {
            $chapter = Chapter::find($validated['chapter_id']);
        }

        $result = $this->aiService->continueWritingAdult(
            $story,
            $chapter,
            $validated['prompt'] ?? ''
        );

        if ($result === null) {
            return response()->json([
                'message' => 'AI сервіс для дорослого контенту недоступний. Переконайтесь, що Ollama запущено.',
            ], 503);
        }

        return response()->json([
            'text' => $result,
        ]);
    }

    /**
     * Check Ollama (adult AI) service status.
     */
    public function adultStatus(): JsonResponse
    {
        $ollama = new OllamaProvider;

        return response()->json([
            'available' => $ollama->isAvailable(),
            'service_running' => $ollama->isServiceRunning(),
            'models' => $ollama->getAvailableModels(),
        ]);
    }
}
