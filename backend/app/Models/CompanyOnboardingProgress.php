<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCompanyOnboardingProgress
 */
class CompanyOnboardingProgress extends Model
{
    use HasFactory;

    protected $table = 'company_onboarding_progress';

    protected $fillable = [
        'company_id',
        'completed_steps',
        'current_step',
        'total_steps',
        'completion_percentage',
        'started_at',
        'completed_at',
        'is_completed',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'completion_percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep($stepId)
    {
        $completedSteps = $this->completed_steps ?? [];

        if (!in_array($stepId, $completedSteps)) {
            $completedSteps[] = $stepId;
            $this->completed_steps = $completedSteps;

            $this->completion_percentage = (count($completedSteps) / $this->total_steps) * 100;
            $this->current_step = min(count($completedSteps) + 1, $this->total_steps);

            if (count($completedSteps) >= $this->total_steps) {
                $this->is_completed = true;
                $this->completed_at = now();
            }

            $this->save();
        }
    }
}
