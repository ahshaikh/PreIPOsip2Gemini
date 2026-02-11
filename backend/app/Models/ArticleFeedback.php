<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Added

/**
 * @mixin IdeHelperArticleFeedback
 */
class ArticleFeedback extends Model
{
    use HasFactory;

    // Explicitly define table
    protected $table = 'article_feedback'; 

    protected $fillable = [
        'article_id', 
        'is_helpful', 
        'comment', 
        'ip_address', 
        'user_id'
    ];
    
    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    // Add relationships for the dashboard "Recent Feedback" list
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}