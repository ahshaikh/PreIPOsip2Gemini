<?php
// V-PHASE2-1730-047


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'is_active', 'rollout_percentage', 'target_users'];
    protected $casts = ['target_users' => 'json'];
}