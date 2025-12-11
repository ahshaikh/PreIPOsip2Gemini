<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TicketSlaTracking extends Model
{
    use HasFactory;

    protected $table = 'ticket_sla_tracking';

    protected $fillable = [
        'support_ticket_id',
        'sla_policy_id',
        'response_due_at',
        'first_responded_at',
        'response_sla_breached',
        'response_time_minutes',
        'resolution_due_at',
        'resolved_at',
        'resolution_sla_breached',
        'resolution_time_minutes',
        'escalated',
        'escalated_at',
        'escalated_to_user_id',
        'escalation_reason',
        'paused_at',
        'total_paused_minutes',
    ];

    protected $casts = [
        'response_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'paused_at' => 'datetime',
        'response_sla_breached' => 'boolean',
        'resolution_sla_breached' => 'boolean',
        'escalated' => 'boolean',
        'response_time_minutes' => 'integer',
        'resolution_time_minutes' => 'integer',
        'total_paused_minutes' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    /**
     * Check if response SLA is about to breach (at threshold)
     */
    public function isResponseSlaNearBreach(): bool
    {
        if (!$this->response_due_at || $this->first_responded_at || $this->response_sla_breached) {
            return false;
        }

        $policy = $this->policy;
        if (!$policy || !$policy->auto_escalate) {
            return false;
        }

        $threshold = $policy->escalation_threshold_percent;
        $totalMinutes = Carbon::parse($this->ticket->created_at)->diffInMinutes($this->response_due_at);
        $elapsedMinutes = Carbon::parse($this->ticket->created_at)->diffInMinutes(now());

        return ($elapsedMinutes / $totalMinutes * 100) >= $threshold;
    }

    /**
     * Check if resolution SLA is about to breach (at threshold)
     */
    public function isResolutionSlaNearBreach(): bool
    {
        if (!$this->resolution_due_at || $this->resolved_at || $this->resolution_sla_breached) {
            return false;
        }

        $policy = $this->policy;
        if (!$policy || !$policy->auto_escalate) {
            return false;
        }

        $threshold = $policy->escalation_threshold_percent;
        $totalMinutes = Carbon::parse($this->ticket->created_at)->diffInMinutes($this->resolution_due_at);
        $elapsedMinutes = Carbon::parse($this->ticket->created_at)->diffInMinutes(now());

        return ($elapsedMinutes / $totalMinutes * 100) >= $threshold;
    }

    /**
     * Get response SLA status
     */
    public function getResponseSlaStatus(): array
    {
        if ($this->first_responded_at) {
            return [
                'status' => 'met',
                'breached' => false,
                'time_taken' => $this->response_time_minutes,
                'message' => 'First response provided'
            ];
        }

        if ($this->response_sla_breached) {
            return [
                'status' => 'breached',
                'breached' => true,
                'overdue_minutes' => now()->diffInMinutes($this->response_due_at),
                'message' => 'Response SLA breached'
            ];
        }

        if ($this->isResponseSlaNearBreach()) {
            return [
                'status' => 'at_risk',
                'breached' => false,
                'minutes_remaining' => $this->response_due_at->diffInMinutes(now()),
                'message' => 'Response SLA at risk'
            ];
        }

        return [
            'status' => 'on_track',
            'breached' => false,
            'minutes_remaining' => $this->response_due_at ? $this->response_due_at->diffInMinutes(now()) : null,
            'message' => 'Response SLA on track'
        ];
    }

    /**
     * Get resolution SLA status
     */
    public function getResolutionSlaStatus(): array
    {
        if ($this->resolved_at) {
            return [
                'status' => 'met',
                'breached' => false,
                'time_taken' => $this->resolution_time_minutes,
                'message' => 'Ticket resolved'
            ];
        }

        if ($this->resolution_sla_breached) {
            return [
                'status' => 'breached',
                'breached' => true,
                'overdue_minutes' => now()->diffInMinutes($this->resolution_due_at),
                'message' => 'Resolution SLA breached'
            ];
        }

        if ($this->isResolutionSlaNearBreach()) {
            return [
                'status' => 'at_risk',
                'breached' => false,
                'minutes_remaining' => $this->resolution_due_at->diffInMinutes(now()),
                'message' => 'Resolution SLA at risk'
            ];
        }

        return [
            'status' => 'on_track',
            'breached' => false,
            'minutes_remaining' => $this->resolution_due_at ? $this->resolution_due_at->diffInMinutes(now()) : null,
            'message' => 'Resolution SLA on track'
        ];
    }
}
