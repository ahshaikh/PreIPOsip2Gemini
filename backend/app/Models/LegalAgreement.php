<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperLegalAgreement
 */
class LegalAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'description',
        'content',
        'version',
        'status',
        'effective_date',
        'expiry_date',
        'require_signature',
        'is_template',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'require_signature' => 'boolean',
        'is_template' => 'boolean',
    ];

    // Relationships
    public function versions()
    {
        return $this->hasMany(LegalAgreementVersion::class);
    }

    public function auditTrail()
    {
        return $this->hasMany(LegalAgreementAuditTrail::class);
    }

    public function userAcceptances()
    {
        return $this->hasMany(UserLegalAcceptance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function logAudit($eventType, $description, $changes = null, $user = null)
    {
        return $this->auditTrail()->create([
            'event_type' => $eventType,
            'description' => $description,
            'changes' => $changes,
            'version' => $this->version,
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function createVersion($changeSummary = null, $user = null)
    {
        return $this->versions()->create([
            'version' => $this->version,
            'content' => $this->content,
            'change_summary' => $changeSummary,
            'status' => $this->status,
            'effective_date' => $this->effective_date,
            'created_by' => $user ? $user->id : null,
        ]);
    }
}
