<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DisclosureDocument Model
 *
 * Represents an immutable document attachment for a disclosure event.
 * Documents are versioned by event and never overwritten.
 *
 * IMMUTABILITY:
 * - Documents are never updated
 * - Documents are never deleted (except via cascade)
 * - Each event gets its own document snapshots
 *
 * @property int $id
 * @property int $disclosure_event_id
 * @property int $company_disclosure_id
 * @property string $file_name
 * @property string $storage_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $file_hash
 * @property string|null $document_type
 * @property string|null $description
 * @property string|null $uploaded_by_type
 * @property int|null $uploaded_by_id
 * @property string $uploaded_by_name
 * @property bool $is_public
 * @property string $visibility
 * @property string|null $uploaded_from_ip
 * @property \Carbon\Carbon $created_at
 */
class DisclosureDocument extends Model
{
    /**
     * Disable updated_at (documents are immutable)
     */
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_documents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'disclosure_event_id',
        'company_disclosure_id',
        'file_name',
        'storage_path',
        'mime_type',
        'file_size',
        'file_hash',
        'document_type',
        'description',
        'uploaded_by_type',
        'uploaded_by_id',
        'uploaded_by_name',
        'is_public',
        'visibility',
        'uploaded_from_ip',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Disable model updates and deletes to enforce immutability
     */
    public static function boot()
    {
        parent::boot();

        // Prevent updates
        static::updating(function ($model) {
            throw new \Exception('Disclosure documents cannot be updated (immutable)');
        });

        // Prevent deletes (except via cascade)
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                throw new \Exception('Disclosure documents cannot be deleted (immutable)');
            }
        });
    }

    /**
     * Get the parent event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(DisclosureEvent::class, 'disclosure_event_id');
    }

    /**
     * Get the parent disclosure thread
     */
    public function disclosure(): BelongsTo
    {
        return $this->belongsTo(CompanyDisclosure::class, 'company_disclosure_id');
    }

    /**
     * Get the uploader (polymorphic)
     */
    public function uploadedBy(): MorphTo
    {
        return $this->morphTo('uploaded_by');
    }

    /**
     * Get document URL for frontend
     */
    public function getUrlAttribute(): string
    {
        // Use storage proxy URL
        return '/api/storage/' . $this->storage_path;
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
