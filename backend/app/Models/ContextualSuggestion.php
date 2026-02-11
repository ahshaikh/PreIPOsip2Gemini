<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperContextualSuggestion
 */
class ContextualSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_pattern',
        'trigger_element',
        'title',
        'message',
        'type',
        'related_articles',
        'related_tutorials',
        'action_url',
        'action_text',
        'display_conditions',
        'max_displays',
        'days_between_displays',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'related_articles' => 'array',
        'related_tutorials' => 'array',
        'display_conditions' => 'array',
        'max_displays' => 'integer',
        'days_between_displays' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active suggestions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering by page pattern
     */
    public function scopeForPage($query, string $pageUrl)
    {
        return $query->where(function ($q) use ($pageUrl) {
            $q->where('page_pattern', $pageUrl)
              ->orWhere(function ($subQ) use ($pageUrl) {
                  // Support wildcard patterns like /dashboard*
                  $subQ->whereRaw("? LIKE REPLACE(page_pattern, '*', '%')", [$pageUrl]);
              });
        });
    }

    /**
     * Scope for ordering by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if suggestion matches current page
     */
    public function matchesPage(string $pageUrl): bool
    {
        // Exact match
        if ($this->page_pattern === $pageUrl) {
            return true;
        }

        // Wildcard pattern matching
        $pattern = str_replace('*', '.*', preg_quote($this->page_pattern, '/'));
        return preg_match('/^' . $pattern . '$/', $pageUrl) === 1;
    }

    /**
     * Check if suggestion should be shown to user
     */
    public function shouldShowToUser(int $userId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if user has dismissed this suggestion
        $dismissed = UserDismissedSuggestion::where('user_id', $userId)
            ->where('contextual_suggestion_id', $this->id)
            ->first();

        if (!$dismissed) {
            return true;
        }

        // Check max displays limit
        if ($this->max_displays > 0 && $dismissed->display_count >= $this->max_displays) {
            return false;
        }

        // Check days between displays
        if ($this->days_between_displays > 0) {
            $daysSinceLastDisplay = now()->diffInDays($dismissed->last_displayed_at);
            return $daysSinceLastDisplay >= $this->days_between_displays;
        }

        return true;
    }

    /**
     * Check if display conditions are met
     */
    public function checkConditions(array $context): bool
    {
        if (!$this->display_conditions) {
            return true;
        }

        // Example conditions: user_type, feature_enabled, etc.
        foreach ($this->display_conditions as $key => $value) {
            if (!isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get related KB articles
     */
    public function getRelatedArticles()
    {
        if (!$this->related_articles || empty($this->related_articles)) {
            return collect([]);
        }

        return KbArticle::whereIn('id', $this->related_articles)
            ->where('status', 'published')
            ->get();
    }

    /**
     * Get related tutorials
     */
    public function getRelatedTutorials()
    {
        if (!$this->related_tutorials || empty($this->related_tutorials)) {
            return collect([]);
        }

        return Tutorial::whereIn('id', $this->related_tutorials)
            ->active()
            ->get();
    }

    /**
     * Format suggestion data for frontend
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'relatedArticles' => $this->getRelatedArticles()->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'url' => "/help-center/articles/{$article->slug}",
                ];
            }),
            'relatedTutorials' => $this->getRelatedTutorials()->map(function ($tutorial) {
                return [
                    'id' => $tutorial->id,
                    'title' => $tutorial->title,
                    'slug' => $tutorial->slug,
                    'difficulty' => $tutorial->difficulty,
                ];
            }),
            'actionUrl' => $this->action_url,
            'actionText' => $this->action_text,
        ];
    }
}
