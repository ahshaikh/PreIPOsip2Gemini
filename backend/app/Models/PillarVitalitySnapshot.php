<?php

namespace App\Models;

use App\Enums\DisclosurePillar;
use App\Enums\PillarVitality;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PillarVitalitySnapshot
 * 
 * Point-in-time record of pillar vitality for audit trail.
 * Created daily by DisclosureFreshnessService.
 * 
 * VITALITY STATES (FROZEN - DO NOT ADD SYNONYMS):
 * - healthy: All artifacts current
 * - needs_attention: Any aging OR 1 stale/unstable
 * - at_risk: 2+ stale OR 2+ unstable
 *
 * @property int $id
 * @property int $company_id
 * @property string $pillar
 * @property string $vitality_state
 * @property int $current_count
 * @property int $aging_count
 * @property int $stale_count
 * @property int $unstable_count
 * @property int $total_count
 * @property array|null $vitality_drivers
 * @property \Carbon\Carbon $computed_at
 * @mixin IdeHelperPillarVitalitySnapshot
 */
class PillarVitalitySnapshot extends Model
{
    use HasFactory;

    protected $table = 'pillar_vitality_snapshots';

    protected $fillable = [
        'company_id',
        'pillar',
        'vitality_state',
        'current_count',
        'aging_count',
        'stale_count',
        'unstable_count',
        'total_count',
        'vitality_drivers',
        'computed_at',
    ];

    protected $casts = [
        'current_count' => 'integer',
        'aging_count' => 'integer',
        'stale_count' => 'integer',
        'unstable_count' => 'integer',
        'total_count' => 'integer',
        'vitality_drivers' => 'array',
        'computed_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Company this snapshot belongs to
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to latest snapshot per pillar
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('computed_at', 'desc');
    }

    /**
     * Scope to specific pillar
     */
    public function scopeForPillar($query, string $pillar)
    {
        return $query->where('pillar', $pillar);
    }

    /**
     * Scope to at-risk pillars
     */
    public function scopeAtRisk($query)
    {
        return $query->where('vitality_state', PillarVitality::AT_RISK->value);
    }

    /**
     * Scope to pillars needing attention
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where('vitality_state', PillarVitality::NEEDS_ATTENTION->value);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the vitality state as enum
     */
    public function getVitalityEnumAttribute(): PillarVitality
    {
        return PillarVitality::from($this->vitality_state);
    }

    /**
     * Get the pillar as enum
     */
    public function getPillarEnumAttribute(): DisclosurePillar
    {
        return DisclosurePillar::from($this->pillar);
    }

    /**
     * Check if pillar is healthy
     */
    public function isHealthy(): bool
    {
        return $this->vitality_state === PillarVitality::HEALTHY->value;
    }

    /**
     * Get freshness breakdown as array
     */
    public function getFreshnessBreakdownAttribute(): array
    {
        return [
            'current' => $this->current_count,
            'aging' => $this->aging_count,
            'stale' => $this->stale_count,
            'unstable' => $this->unstable_count,
        ];
    }
}
