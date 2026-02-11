<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCompanyFinancialReport
 */
class CompanyFinancialReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'uploaded_by',
        'year',
        'quarter',
        'report_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'published_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'file_size' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Report belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Report uploaded by CompanyUser
     */
    public function uploadedBy()
    {
        return $this->belongsTo(CompanyUser::class, 'uploaded_by');
    }

    /**
     * Scope: Published reports
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Reports for a specific year
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'Unknown';

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
