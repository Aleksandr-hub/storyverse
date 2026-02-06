<?php

namespace App\Services\Story;

use Illuminate\Support\Facades\Storage;

class StoryCoverService
{
    /**
     * Check if URL is from our internal storage.
     */
    public function isInternalUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        $storageBaseUrl = Storage::disk('public')->url('');

        return str_starts_with($url, $storageBaseUrl);
    }

    /**
     * Extract storage path from full URL.
     */
    public function getPathFromUrl(string $url): ?string
    {
        $storageBaseUrl = Storage::disk('public')->url('');

        if (! str_starts_with($url, $storageBaseUrl)) {
            return null;
        }

        return str_replace($storageBaseUrl, '', $url);
    }

    /**
     * Delete a cover file if it's from our storage.
     */
    public function deleteOldCover(?string $coverUrl): bool
    {
        if (! $coverUrl || ! $this->isInternalUrl($coverUrl)) {
            return false;
        }

        $path = $this->getPathFromUrl($coverUrl);

        // Only delete files from covers directory (safety check)
        if ($path && str_starts_with($path, 'covers/')) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Handle cover update - delete old file if being replaced.
     */
    public function handleCoverUpdate(?string $oldCoverUrl, ?string $newCoverUrl): void
    {
        // Only delete if we're actually changing to a different URL
        if ($oldCoverUrl && $newCoverUrl && $oldCoverUrl !== $newCoverUrl) {
            $this->deleteOldCover($oldCoverUrl);
        }

        // Also delete if clearing the cover (setting to null)
        if ($oldCoverUrl && $newCoverUrl === null) {
            $this->deleteOldCover($oldCoverUrl);
        }
    }
}
