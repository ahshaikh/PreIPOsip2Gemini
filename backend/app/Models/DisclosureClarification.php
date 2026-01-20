<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PHASE 1 - MODEL 4/5: DisclosureClarification
 *
 * PURPOSE:
 * Represents Q&A between admins and companies during disclosure review.
 * Enables structured clarification requests with field-level precision
 * and threading support for follow-up questions.
 *
 * KEY RESPONSIBILITIES:
 * - Store admin questions and company answers
 * - Target specific fields via JSON path
 * - Support threaded conversations (parent_id)
 * - Track status (open → answered → accepted/disputed)
 * - Enforce deadlines and priority levels
 *
 * WORKFLOW:
 * 1. Admin asks question (status: open)
 * 2. Company answers (status: answered)
 * 3. Admin reviews answer:
 *    - Accept: Issue resolved (status: accepted)
 *    - Dispute: Company must revise disclosure (status: disputed)
 *    - Withdraw: Question was mistake (status: withdrawn)
 *
 * @property int $id
 * @property int $company_disclosure_id Disclosure being clarified
 * @property int $company_id Denormalized company ID
 * @property int $disclosure_module_id Denormalized module ID
 * @property int|null $parent_id Parent clarification for threading
 * @property int $thread_depth Nesting level (0=root, 1=reply, etc.)
 * @property string $question_subject Brief subject line
 * @property string $question_body Detailed question from admin
 * @property string $question_type Category: missing_data, inconsistency, etc.
 * @property int $asked_by Admin who asked
 * @property \Illuminate\Support\Carbon $asked_at
 * @property string|null $field_path JSON path to specific field
 * @property array|null $highlighted_data Snapshot of problematic data
 * @property array|null $suggested_fix Admin suggestion
 * @property string|null $answer_body Company response
 * @property int|null $answered_by CompanyUser who answered
 * @property \Illuminate\Support\Carbon|null $answered_at
 * @property array|null $supporting_documents Documents with answer
 * @property string $status Current status
 * @property string|null $resolution_notes Admin notes on resolution
 * @property int|null $resolved_by Admin who resolved
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string $priority Urgency level
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property bool $is_blocking Whether approval is blocked
 * @property string|null $internal_notes Admin-only notes
 * @property bool $is_visible_to_company Visibility flag
 * @property int $reminder_count Email reminders sent
 * @property \Illuminate\Support\Carbon|null $last_reminder_at
 * @property string|null $asked_by_ip
 * @property string|null $answered_by_ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class DisclosureClarification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_clarifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_disclosure_id',
        'company_id',
        'disclosure_module_id',
        'parent_id',
        'thread_depth',
        'question_subject',
        'question_body',
        'question_type',
        'asked_by',
        'asked_at',
        'field_path',
        'highlighted_data',
        'suggested_fix',
        'answer_body',
        'answered_by_type',  // Polymorphic: User or CompanyUser
        'answered_by_id',    // Polymorphic ID
        'answered_at',
        'supporting_documents',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'priority',
        'due_date',
        'is_blocking',
        'internal_notes',
        'is_visible_to_company',
        'reminder_count',
        'last_reminder_at',
        'asked_by_ip',
        'answered_by_ip',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'thread_depth' => 'integer',
        'asked_at' => 'datetime',
        'highlighted_data' => 'array',
        'suggested_fix' => 'array',
        'answered_at' => 'datetime',
        'supporting_documents' => 'array',
        'resolved_at' => 'datetime',
        'due_date' => 'datetime',
        'is_blocking' => 'boolean',
        'is_visible_to_company' => 'boolean',
        'reminder_count' => 'integer',
        'last_reminder_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Disclosure being clarified
     */
    public function companyDisclosure()
    {
        return $this->belongsTo(CompanyDisclosure::class);
    }

    /**
     * Company that owns the disclosure
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Disclosure module
     */
    public function disclosureModule()
    {
        return $this->belongsTo(DisclosureModule::class);
    }

    /**
     * Parent clarification (for threading)
     */
    public function parent()
    {
        return $this->belongsTo(DisclosureClarification::class, 'parent_id');
    }

    /**
     * Child clarifications (replies)
     */
    public function replies()
    {
        return $this->hasMany(DisclosureClarification::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Admin who asked the question
     * Note: asked_by remains FK to users (only admins ask questions)
     */
    public function asker()
    {
        return $this->belongsTo(User::class, 'asked_by');
    }

    /**
     * Polymorphic: CompanyUser or User who answered the clarification
     * Usually: CompanyUser answers, but Admin may also answer in some cases
     */
    public function answeredBy()
    {
        return $this->morphTo(__FUNCTION__, 'answered_by_type', 'answered_by_id');
    }

    /**
     * DEPRECATED: Use answeredBy() instead (polymorphic)
     * Kept for backwards compatibility
     */
    public function answerer()
    {
        return $this->answeredBy();
    }

    /**
     * Admin who resolved (accepted/disputed)
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to clarifications by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to open clarifications
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to answered clarifications
     */
    public function scopeAnswered($query)
    {
        return $query->where('status', 'answered');
    }

    /**
     * Scope to accepted clarifications
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to blocking clarifications
     */
    public function scopeBlocking($query)
    {
        return $query->where('is_blocking', true);
    }

    /**
     * Scope to overdue clarifications
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', 'open');
    }

    /**
     * Scope to root clarifications (not replies)
     */
    public function scopeRootOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to visible to company
     */
    public function scopeVisibleToCompany($query)
    {
        return $query->where('is_visible_to_company', true);
    }

    /**
     * Scope to clarifications by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Submit answer to clarification (company action)
     *
     * @param int $userId CompanyUser ID
     * @param string $answer Answer text
     * @param array|null $documents Supporting documents
     * @throws \RuntimeException If already answered
     */
    public function submitAnswer(int $userId, string $answer, ?array $documents = null): void
    {
        if ($this->status !== 'open') {
            throw new \RuntimeException('Can only answer open clarifications');
        }

        $this->update([
            'answer_body' => $answer,
            'answered_by' => $userId,
            'answered_at' => now(),
            'supporting_documents' => $documents,
            'status' => 'answered',
            'answered_by_ip' => request()->ip(),
        ]);
    }

    /**
     * Accept answer (admin action)
     *
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin notes
     * @throws \RuntimeException If not answered
     */
    public function acceptAnswer(int $adminId, ?string $notes = null): void
    {
        if ($this->status !== 'answered') {
            throw new \RuntimeException('Can only accept answered clarifications');
        }

        $this->update([
            'status' => 'accepted',
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Dispute answer (admin action)
     *
     * @param int $adminId Admin user ID
     * @param string $notes Admin explanation of dispute
     * @throws \RuntimeException If not answered
     */
    public function disputeAnswer(int $adminId, string $notes): void
    {
        if ($this->status !== 'answered') {
            throw new \RuntimeException('Can only dispute answered clarifications');
        }

        $this->update([
            'status' => 'disputed',
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Withdraw clarification (admin action - question was mistake)
     *
     * @param int $adminId Admin user ID
     * @param string $reason Reason for withdrawal
     */
    public function withdraw(int $adminId, string $reason): void
    {
        $this->update([
            'status' => 'withdrawn',
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'resolution_notes' => $reason,
        ]);
    }

    /**
     * Create a reply to this clarification
     *
     * @param int $userId User creating reply (admin or company)
     * @param string $subject Reply subject
     * @param string $body Reply body
     * @return self
     */
    public function createReply(int $userId, string $subject, string $body): self
    {
        return self::create([
            'company_disclosure_id' => $this->company_disclosure_id,
            'company_id' => $this->company_id,
            'disclosure_module_id' => $this->disclosure_module_id,
            'parent_id' => $this->id,
            'thread_depth' => $this->thread_depth + 1,
            'question_subject' => $subject,
            'question_body' => $body,
            'question_type' => 'other',
            'asked_by' => $userId,
            'asked_at' => now(),
            'priority' => $this->priority,
            'is_visible_to_company' => $this->is_visible_to_company,
        ]);
    }

    /**
     * Send reminder to company
     *
     * @return void
     */
    public function sendReminder(): void
    {
        // TODO: Queue email notification to company

        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
    }

    /**
     * Check if clarification is overdue
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status === 'open';
    }

    /**
     * Get days until due
     *
     * @return int|null
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Check if this is a root clarification
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get full conversation thread
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFullThread()
    {
        // If this is a reply, get the root and all replies
        if (!$this->isRoot()) {
            $root = $this->parent;
            while ($root->parent_id !== null) {
                $root = $root->parent;
            }
            return collect([$root])->merge($root->replies);
        }

        // If this is root, return self and all replies
        return collect([$this])->merge($this->replies);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Awaiting Answer',
            'answered' => 'Awaiting Admin Review',
            'accepted' => 'Accepted',
            'disputed' => 'Disputed - Needs Revision',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get human-readable priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    /**
     * Check if answer is pending
     */
    public function getIsPendingAnswerAttribute(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if answer is pending review
     */
    public function getIsPendingReviewAttribute(): bool
    {
        return $this->status === 'answered';
    }
}
