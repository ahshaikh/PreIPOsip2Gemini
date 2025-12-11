<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorialStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutorial_id',
        'step_number',
        'title',
        'content',

        // Element highlighting
        'target_element',
        'highlight_style',

        // Positioning
        'position',
        'offset_x',
        'offset_y',

        // Media
        'image_url',
        'video_url',
        'gif_url',

        // Actions
        'requires_action',
        'action_type',
        'action_target',
        'action_validation',

        // Navigation
        'can_skip',
        'next_button_text',
        'back_button_text',
    ];

    protected $casts = [
        'tutorial_id' => 'integer',
        'step_number' => 'integer',
        'offset_x' => 'integer',
        'offset_y' => 'integer',
        'requires_action' => 'boolean',
        'can_skip' => 'boolean',
    ];

    /**
     * Get the tutorial this step belongs to
     */
    public function tutorial(): BelongsTo
    {
        return $this->belongsTo(Tutorial::class);
    }

    /**
     * Check if this is the first step
     */
    public function isFirstStep(): bool
    {
        return $this->step_number === 1;
    }

    /**
     * Check if this is the last step
     */
    public function isLastStep(): bool
    {
        $totalSteps = $this->tutorial->tutorialSteps()->count();
        return $this->step_number === $totalSteps;
    }

    /**
     * Get the next step
     */
    public function nextStep(): ?self
    {
        return $this->tutorial->tutorialSteps()
            ->where('step_number', '>', $this->step_number)
            ->orderBy('step_number')
            ->first();
    }

    /**
     * Get the previous step
     */
    public function previousStep(): ?self
    {
        return $this->tutorial->tutorialSteps()
            ->where('step_number', '<', $this->step_number)
            ->orderBy('step_number', 'desc')
            ->first();
    }

    /**
     * Format step data for frontend InteractiveTutorial component
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'stepNumber' => $this->step_number,
            'title' => $this->title,
            'content' => $this->content,
            'targetElement' => $this->target_element,
            'highlightStyle' => $this->highlight_style ?? 'pulse',
            'position' => $this->position ?? 'modal',
            'imageUrl' => $this->image_url,
            'videoUrl' => $this->video_url,
            'gifUrl' => $this->gif_url,
            'requiresAction' => $this->requires_action,
            'actionType' => $this->action_type,
            'canSkip' => $this->can_skip ?? true,
            'nextButtonText' => $this->next_button_text,
            'backButtonText' => $this->back_button_text,
        ];
    }

    /**
     * Validate if required action has been completed
     * This is a placeholder - actual validation would be done on frontend
     */
    public function validateAction(array $data): bool
    {
        if (!$this->requires_action) {
            return true;
        }

        // This would contain custom validation logic based on action_type
        // For now, just return true as frontend handles this
        return true;
    }
}
