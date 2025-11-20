<?php
// V-PHASE2-1730-036 (Created) | V-FINAL-1730-399 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false; // Settings are not timestamped by default

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'updated_by'
    ];

    /**
     * The "booted" method of the model.
     * We ensure the cache is cleared *any* time a setting is changed.
     */
    protected static function booted()
    {
        static::saved(function ($setting) {
            // Invalidate this specific key
            \Illuminate\Support\Facades\Cache::forget('setting.' . $setting->key);
            // Invalidate the "all settings" cache
            \Illuminate\Support\Facades\Cache::forget('settings');
        });
    }

    // --- RELATIONSHIPS ---

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // --- ACCESSORS & CASTS ---

    /**
     * Automatically cast the 'value' based on the 'type' column.
     * This is the core logic.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $type = $attributes['type'] ?? 'string';
                
                return match ($type) {
                    'boolean' => in_array($value, ['true', '1', 1, true], true),
                    'number'  => (int) $value,
                    'json'    => json_decode($value, true),
                    default   => (string) $value,
                };
            },
        );
    }
}