<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutorial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Basic info
        'slug',
        'title',
        'description',
        'category',
        'thumbnail_url',

        // Targeting
        'user_role',
        'difficulty',
        'estimated_minutes',

        // Auto-launch conditions
        'auto_launch',
        'trigger_page',
        'trigger_conditions',

        // Tracking
        'views_count',
        'completions_count',
        'avg_completion_rate',

        // Order and status
        'sort_order',
        'is_featured',
        'is_active',

        // Legacy fields (from old structure)
        'content',
        'video_url',
        'thumbnail',
        'duration_minutes',
        'steps',
        'resources',
        'tags',
        'likes_count',
        'rating',
        'status',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'auto_launch' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'completions_count' => 'integer',
        'avg_completion_rate' => 'decimal:2',
        'estimated_minutes' => 'integer',
        'sort_order' => 'integer',

        // Legacy fields
        'steps' => 'array',
        'resources' => 'array',
        'tags' => 'array',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the tutorial steps (new structure with separate table)
     */
    public function tutorialSteps(): HasMany
    {
        return $this->hasMany(TutorialStep::class)->orderBy('step_number');
    }

    /**
     * Get user progress records
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserTutorialProgress::class);
    }

    /**
     * Scope for active tutorials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for published tutorials (legacy)
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured tutorials
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for filtering by user role
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('user_role', $role)
              ->orWhere('user_role', 'all');
        });
    }

    /**
     * Scope for filtering by difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if tutorial should auto-launch on given page
     */
    public function shouldAutoLaunchOnPage(string $currentPage): bool
    {
        if (!$this->auto_launch || !$this->is_active) {
            return false;
        }

        if (!$this->trigger_page) {
            return false;
        }

        // Simple pattern matching (supports wildcards)
        $pattern = str_replace('*', '.*', $this->trigger_page);
        return preg_match('#^' . $pattern . '$#', $currentPage) === 1;
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment completion count and update avg completion rate
     */
    public function incrementCompletions(): void
    {
        $this->increment('completions_count');
        $this->updateCompletionRate();
    }

    /**
     * Update average completion rate
     */
    protected function updateCompletionRate(): void
    {
        if ($this->views_count > 0) {
            $rate = ($this->completions_count / $this->views_count) * 100;
            $this->update(['avg_completion_rate' => round($rate, 2)]);
        }
    }

    /**
     * Get tutorial data formatted for frontend (new interactive tutorial format)
     */
    public function toInteractiveFormat(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'thumbnail_url' => $this->thumbnail_url ?? $this->thumbnail,
            'difficulty' => $this->difficulty,
            'estimatedMinutes' => $this->estimated_minutes ?? $this->duration_minutes,
            'isFeatured' => $this->is_featured,
            'steps' => $this->tutorialSteps->map(fn($step) => $step->toFrontendFormat())->toArray(),
        ];
    }
}

