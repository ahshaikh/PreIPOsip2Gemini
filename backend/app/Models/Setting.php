// V-PHASE2-1730-036
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Bust the cache when a setting is updated.
     */
    protected static function boot()
    {
        parent::boot();
        static::updated(function ($setting) {
            Cache::forget('settings');
            Cache::forget('setting.' . $setting->key);
        });
        static::created(function ($setting) {
            Cache::forget('settings');
        });
        static::deleted(function ($setting) {
            Cache::forget('settings');
            Cache::forget('setting.' . $setting->key);
        });
    }
}