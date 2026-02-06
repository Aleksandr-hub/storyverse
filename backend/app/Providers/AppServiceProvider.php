<?php

namespace App\Providers;

use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Story;
use App\Policies\ChapterPolicy;
use App\Policies\CommentPolicy;
use App\Policies\StoryPolicy;
use App\Services\AI\MultiProviderAIService;
use App\Services\AI\StableDiffusionService;
use App\Services\AI\StoryAIService;
use App\Services\Story\StoryQueryService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons for better performance
        $this->app->singleton(MultiProviderAIService::class);
        $this->app->singleton(StableDiffusionService::class);
        $this->app->singleton(StoryQueryService::class);

        // StoryAIService depends on MultiProviderAIService
        $this->app->singleton(StoryAIService::class, function ($app) {
            return new StoryAIService($app->make(MultiProviderAIService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerPolicies();
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // AI rate limiter - different limits for free vs premium users
        RateLimiter::for('ai', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perHour(0); // No AI for unauthenticated
            }

            // Premium users get 100 requests per hour
            if ($user->is_premium) {
                return Limit::perHour(100)->by($user->id);
            }

            // Free users get 20 requests per hour
            return Limit::perHour(20)->by($user->id);
        });
    }
}
