<?php
// V-PHASE2-1730-036 (Created) | V-FINAL-1730-399 (Logic Upgraded) | V-AUDIT-FIX-ENCRYPTION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    use HasFactory;

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
            $setting->bustCache();
        });

        static::deleted(function ($setting) {
            $setting->bustCache();
        });
    }

    /**
     * Invalidate all related caches for this setting.
     */
    public function bustCache()
    {
        \Illuminate\Support\Facades\Cache::forget('setting.' . $this->key);
        \Illuminate\Support\Facades\Cache::forget('settings');
        \Illuminate\Support\Facades\Cache::forget('settings.all_grouped');
        \Illuminate\Support\Facades\Cache::forget('settings.all_grouped_full');
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
                    'encrypted' => $this->decryptValue($value), // [AUDIT FIX] Handle decryption
                    default   => (string) $value,
                };
            },
        );
    }

    /**
     * Helper to safely decrypt values.
     */
    private function decryptValue($value)
    {
        if (empty($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return raw if decryption fails (fallback)
        }
    }
}