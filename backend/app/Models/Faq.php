<?php
// V-FINAL-1730-284

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperFaq
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question', 'answer', 'category_id', 'display_order'
    ];
}