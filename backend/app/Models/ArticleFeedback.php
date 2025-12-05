<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleFeedback extends Model
{
    // FIX: Explicitly define the table name because 'feedback' is uncountable in Laravel
    protected $table = 'article_feedbacks';

    protected $fillable = ['article_id', 'is_helpful', 'comment', 'ip_address', 'user_id'];
    
    protected $casts = [
        'is_helpful' => 'boolean',
    ];
    
    // Optional: Add relationship back to Article if needed for the Admin Dashboard
    public function article()
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    // Optional: Add relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}