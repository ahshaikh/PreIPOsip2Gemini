<?php
// V-FINAL-1730-540 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

class IpWhitelist extends Model
{
    use HasFactory;
    
    protected $table = 'ip_whitelist';
    
    protected $fillable = ['ip_address', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if a given IP matches this rule.
     * Supports single IPs (1.2.3.4) and CIDR ranges (1.2.3.0/24).
     */
    public static function isIpAllowed(string $clientIp, $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if (IpUtils::checkIp($clientIp, $allowedIp)) {
                return true;
            }
        }
        return false;
    }
}