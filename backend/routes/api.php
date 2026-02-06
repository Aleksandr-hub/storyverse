<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\OAuthController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UniverseController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);

    // OAuth routes
    Route::get('oauth/{provider}', [OAuthController::class, 'redirect']);
    Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me', MeController::class);
        Route::put('me', [MeController::class, 'update']);
        Route::put('me/password', [MeController::class, 'updatePassword']);
        Route::post('logout', LogoutController::class);
        Route::post('logout/all', [LogoutController::class, 'logoutAll']);
    });

    // Stories
    Route::get('stories/my', [StoryController::class, 'myStories']);
    Route::post('stories', [StoryController::class, 'store']);
    Route::put('stories/{story}', [StoryController::class, 'update']);
    Route::delete('stories/{story}', [StoryController::class, 'destroy']);
    Route::post('stories/{story}/publish', [StoryController::class, 'publish']);

    // Chapters
    Route::post('stories/{story}/chapters', [ChapterController::class, 'store']);
    Route::put('chapters/{chapter}', [ChapterController::class, 'update']);
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy']);
    Route::post('stories/{story}/chapters/reorder', [ChapterController::class, 'reorder']);

    // Likes
    Route::post('stories/{story}/like', [LikeController::class, 'store']);
    Route::delete('stories/{story}/like', [LikeController::class, 'destroy']);
    Route::get('stories/{story}/like', [LikeController::class, 'check']);

    // Comments (create, update, delete)
    Route::post('stories/{story}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // File uploads
    Route::post('upload', [UploadController::class, 'upload']);
    Route::delete('upload', [UploadController::class, 'delete']);

    // Characters
    Route::get('characters/my', [CharacterController::class, 'myCharacters']);
    Route::post('characters', [CharacterController::class, 'store']);
    Route::put('characters/{character}', [CharacterController::class, 'update']);
    Route::delete('characters/{character}', [CharacterController::class, 'destroy']);

    // AI - with rate limiting (20 requests per hour for free users)
    Route::prefix('ai')->middleware('throttle:ai')->group(function () {
        Route::get('status', [AIController::class, 'status']);
        Route::post('stories/{story}/continue', [AIController::class, 'continueWriting']);
        Route::post('stories/{story}/suggestions', [AIController::class, 'getSuggestions']);
        Route::post('stories/{story}/improve', [AIController::class, 'improveText']);
        Route::post('stories/{story}/title', [AIController::class, 'generateTitle']);
        Route::post('stories/{story}/description', [AIController::class, 'generateDescription']);

        // Adult content (uses Ollama - uncensored)
        Route::post('stories/{story}/continue-adult', [AIController::class, 'continueWritingAdult']);
        Route::get('adult-status', [AIController::class, 'adultStatus']);

        // Image generation (uses Stable Diffusion)
        Route::post('stories/{story}/illustrate', [AIController::class, 'generateIllustration']);
        Route::get('image-status', [AIController::class, 'imageStatus']);
    });
});

// Public routes
Route::get('stories', [StoryController::class, 'index']);
Route::get('stories/{story}', [StoryController::class, 'show']);
Route::get('stories/{story}/chapters', [ChapterController::class, 'index']);
Route::get('stories/{story}/chapters/{chapter}', [ChapterController::class, 'show']);
Route::get('stories/{story}/comments', [CommentController::class, 'index']);

Route::get('universes', [UniverseController::class, 'index']);
Route::get('universes/{universe}', [UniverseController::class, 'show']);

Route::get('tags', [TagController::class, 'index']);
Route::get('tags/popular', [TagController::class, 'popular']);
Route::get('tags/{tag}', [TagController::class, 'show']);

// Users (public profiles)
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('users/{user}/stories', [UserController::class, 'stories']);

// Characters (public)
Route::get('characters', [CharacterController::class, 'index']);
Route::get('characters/{character}', [CharacterController::class, 'show']);

// Health check
Route::get('health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()]));
