<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAdminDashboardWidget
 */
class AdminDashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'widget_type',
        'position',
        'width',
        'height',
        'config',
        'is_visible',
    ];

    protected $casts = [
        'config' => 'array',
        'is_visible' => 'boolean',
        'position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
