<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleFeedback extends Model
{
    protected $fillable = ['article_id', 'is_helpful', 'comment', 'ip_address', 'user_id'];
    
    protected $casts = [
        'is_helpful' => 'boolean',
    ];
}