<?php
// V-PHASE2-1730-047 (Created) | V-FINAL-1730-403 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'description',
        'is_active',
        'percentage', // <-- NEW
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'percentage' => 'integer',
    ];

    /**
     * The main function to check if a feature is enabled for a specific user.
     * This handles simple toggles and percentage rollouts.
     */
    public function isEnabled(?User $user = null): bool
    {
        // 1. If the flag is globally OFF, it's off for everyone.
        if (!$this->is_active) {
            return false;
        }

        // 2. If 'percentage' is null (or 100), it's on for everyone.
        if (is_null($this->percentage) || $this->percentage >= 100) {
            return true;
        }

        // 3. If it's a percentage rollout, we need a user.
        if (!$user) {
            // Anonymous users are "off" for percentage rollouts
            return false; 
        }

        // 4. The Percentage Logic
        // We use a stable hash (CRC32) of the user's ID + flag key.
        // This ensures the *same user* always gets the *same result*.
        $hash = crc32($user->id . $this->key);
        $userBucket = $hash % 100; // Puts user in a bucket 0-99

        return $userBucket < $this->percentage;
    }
}