<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDismissedSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contextual_suggestion_id',
        'display_count',
        'first_displayed_at',
        'last_displayed_at',
        'dismissed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'contextual_suggestion_id' => 'integer',
        'display_count' => 'integer',
        'first_displayed_at' => 'datetime',
        'last_displayed_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    /**
     * Get the user who dismissed the suggestion
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dismissed suggestion
     */
    public function contextualSuggestion(): BelongsTo
    {
        return $this->belongsTo(ContextualSuggestion::class);
    }

    /**
     * Record that suggestion was displayed
     */
    public static function recordDisplay(int $userId, int $suggestionId): void
    {
        $record = self::firstOrNew([
            'user_id' => $userId,
            'contextual_suggestion_id' => $suggestionId,
        ]);

        if (!$record->exists) {
            $record->first_displayed_at = now();
        }

        $record->last_displayed_at = now();
        $record->display_count = ($record->display_count ?? 0) + 1;
        $record->save();
    }

    /**
     * Record that suggestion was dismissed
     */
    public static function recordDismissal(int $userId, int $suggestionId): void
    {
        $record = self::firstOrCreate(
            [
                'user_id' => $userId,
                'contextual_suggestion_id' => $suggestionId,
            ],
            [
                'first_displayed_at' => now(),
                'last_displayed_at' => now(),
                'display_count' => 1,
            ]
        );

        $record->update([
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Check if suggestion can be shown again
     */
    public function canShowAgain(int $maxDisplays, int $daysBetween): bool
    {
        // Check max displays
        if ($maxDisplays > 0 && $this->display_count >= $maxDisplays) {
            return false;
        }

        // Check days between displays
        if ($daysBetween > 0) {
            $daysSinceLastDisplay = now()->diffInDays($this->last_displayed_at);
            return $daysSinceLastDisplay >= $daysBetween;
        }

        return true;
    }
}
