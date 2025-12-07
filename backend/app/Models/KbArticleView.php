<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Added HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticleView extends Model
{
    use HasFactory;

    // Explicitly define table to prevent "Table not found" errors
    protected $table = 'kb_article_views';

    protected $fillable = [
        'kb_article_id', 
        'user_id', 
        'ip_address', 
        'user_agent'
    ];

    // Ensure timestamps are enabled for the 'views_today' query
    public $timestamps = true;

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'kb_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}