<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTutorialProgress extends Model
{
    use HasFactory;

    protected $table = 'user_tutorial_progress';

    protected $fillable = [
        'user_id',
        'tutorial_id',
        'current_step',
        'total_steps',
        'completed',
        'completed_at',
        'started_at',
        'last_activity_at',
        'time_spent_seconds',
        'steps_completed',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tutorial_id' => 'integer',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'steps_completed' => 'array',
    ];

    /**
     * Get the user who owns this progress
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tutorial this progress belongs to
     */
    public function tutorial(): BelongsTo
    {
        return $this->belongsTo(Tutorial::class);
    }

    /**
     * Mark a step as completed
     */
    public function markStepCompleted(int $stepNumber): void
    {
        $stepsCompleted = $this->steps_completed ?? [];

        if (!in_array($stepNumber, $stepsCompleted)) {
            $stepsCompleted[] = $stepNumber;
            $this->steps_completed = $stepsCompleted;
        }

        $this->current_step = $stepNumber;
        $this->last_activity_at = now();
        $this->save();

        // Check if tutorial is now completed
        if (count($stepsCompleted) >= $this->total_steps) {
            $this->markCompleted();
        }
    }

    /**
     * Mark tutorial as completed
     */
    public function markCompleted(): void
    {
        if (!$this->completed) {
            $this->update([
                'completed' => true,
                'completed_at' => now(),
                'last_activity_at' => now(),
            ]);

            // Update tutorial completion stats
            $this->tutorial->incrementCompletions();
        }
    }

    /**
     * Add time spent on tutorial
     */
    public function addTimeSpent(int $seconds): void
    {
        $this->increment('time_spent_seconds', $seconds);
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        $stepsCompleted = count($this->steps_completed ?? []);
        return round(($stepsCompleted / $this->total_steps) * 100, 2);
    }

    /**
     * Check if user is still actively working on tutorial
     * (activity within last 30 minutes)
     */
    public function isActive(): bool
    {
        if (!$this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->isAfter(now()->subMinutes(30));
    }

    /**
     * Check if tutorial was abandoned
     * (started but not completed, no activity in 7 days)
     */
    public function isAbandoned(): bool
    {
        if ($this->completed) {
            return false;
        }

        if (!$this->last_activity_at) {
            return true;
        }

        return $this->last_activity_at->isBefore(now()->subDays(7));
    }

    /**
     * Reset progress to start over
     */
    public function reset(): void
    {
        $this->update([
            'current_step' => 1,
            'completed' => false,
            'completed_at' => null,
            'steps_completed' => [],
            'time_spent_seconds' => 0,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Format progress data for frontend
     */
    public function toFrontendFormat(): array
    {
        return [
            'tutorialId' => $this->tutorial_id,
            'currentStep' => $this->current_step,
            'totalSteps' => $this->total_steps,
            'completed' => $this->completed,
            'completedAt' => $this->completed_at?->toIso8601String(),
            'completionPercentage' => $this->getCompletionPercentage(),
            'timeSpentMinutes' => round($this->time_spent_seconds / 60, 1),
            'stepsCompleted' => $this->steps_completed ?? [],
            'isActive' => $this->isActive(),
        ];
    }
}
