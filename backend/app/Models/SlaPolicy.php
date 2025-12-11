<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ticket_category',
        'ticket_priority',
        'response_time_hours',
        'resolution_time_hours',
        'business_hours_only',
        'work_start_time',
        'work_end_time',
        'working_days',
        'auto_escalate',
        'escalation_threshold_percent',
        'priority_order',
        'is_active',
    ];

    protected $casts = [
        'working_days' => 'array',
        'business_hours_only' => 'boolean',
        'auto_escalate' => 'boolean',
        'is_active' => 'boolean',
        'response_time_hours' => 'integer',
        'resolution_time_hours' => 'integer',
        'escalation_threshold_percent' => 'integer',
        'priority_order' => 'integer',
    ];

    public function trackings(): HasMany
    {
        return $this->hasMany(TicketSlaTracking::class);
    }

    /**
     * Find the most specific SLA policy for a ticket
     */
    public static function findForTicket(string $category, string $priority): ?self
    {
        // Try to find exact match first (category + priority)
        $policy = self::active()
            ->where('ticket_category', $category)
            ->where('ticket_priority', $priority)
            ->orderBy('priority_order')
            ->first();

        if ($policy) {
            return $policy;
        }

        // Try category match (any priority)
        $policy = self::active()
            ->where('ticket_category', $category)
            ->whereNull('ticket_priority')
            ->orderBy('priority_order')
            ->first();

        if ($policy) {
            return $policy;
        }

        // Try priority match (any category)
        $policy = self::active()
            ->whereNull('ticket_category')
            ->where('ticket_priority', $priority)
            ->orderBy('priority_order')
            ->first();

        if ($policy) {
            return $policy;
        }

        // Default policy (no category or priority specified)
        return self::active()
            ->whereNull('ticket_category')
            ->whereNull('ticket_priority')
            ->orderBy('priority_order')
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
