<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionalMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'type',
        'file_url',
        'file_name',
        'file_size',
        'thumbnail_url',
        'preview_url',
        'dimensions',
        'download_count',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all downloads for this material
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(PromotionalMaterialDownload::class);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
