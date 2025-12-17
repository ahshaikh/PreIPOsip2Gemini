<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V-AUDIT-MODULE19-MEDIUM: Pruning Policy Required (with Compliance Considerations)
 *
 * PROBLEM: Audit logs grow indefinitely as admins perform actions across the platform.
 * Without pruning, this table will accumulate millions of records over years, causing:
 * - Database bloat (especially with large JSON payloads in old_values/new_values)
 * - Export timeouts (AuditLogController::export becomes unusable)
 * - Slow dashboard queries (filtering/searching becomes progressively slower)
 * - Backup size explosion (audit_logs table can become 50%+ of database dump)
 *
 * COMPLIANCE WARNING: Before implementing pruning, verify regulatory requirements.
 * Many industries require audit log retention for 1-7 years (GDPR, SOX, HIPAA, PCI-DSS).
 *
 * RECOMMENDED SOLUTION: Implement archival + pruning strategy:
 *
 * OPTION 1: Pruning with Long Retention (Compliance-Safe)
 * 1. Add Prunable trait to this model:
 *    use Illuminate\Database\Eloquent\Prunable;
 *    class AuditLog extends Model
 *    {
 *        use HasFactory, Prunable;
 *
 * 2. Define prunable query (keep 1-2 years, configurable):
 *    public function prunable()
 *    {
 *        // Keep records based on setting (default: 730 days = 2 years)
 *        $retentionDays = setting('audit_log_retention_days', 730);
 *        return static::where('created_at', '<=', now()->subDays($retentionDays));
 *    }
 *
 * 3. Optional: Archive before deletion (export to cold storage):
 *    public function pruning()
 *    {
 *        // Before deleting, archive to S3/external storage
 *        \App\Services\AuditArchiveService::archiveToS3($this);
 *    }
 *
 * 4. Schedule in app/Console/Kernel.php:
 *    $schedule->command('model:prune', ['--model' => AuditLog::class])
 *             ->monthly(); // Less frequent than health checks
 *
 * OPTION 2: Archive-Only (No Deletion)
 * - Keep all audit logs in database (if storage is not a concern)
 * - Periodically export old logs to S3/cold storage for cost optimization
 * - Mark as 'archived' in DB but don't delete
 *
 * RETENTION POLICY RECOMMENDATION:
 * - Minimum 1 year (for troubleshooting and forensics)
 * - 2-3 years for most platforms (balances compliance and storage cost)
 * - 7 years for heavily regulated industries (financial, healthcare)
 * - Configure via setting('audit_log_retention_days') to allow per-tenant customization
 *
 * IMPACT: Without pruning/archival, this table grows by ~10K-100K records/month
 * depending on admin activity, reaching 1M+ records within 1-2 years.
 *
 * ACTION REQUIRED: Consult with legal/compliance team before implementing pruning.
 */
class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'module',
        'target_type',
        'target_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'target_id' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function target()
    {
        return $this->morphTo();
    }
}
