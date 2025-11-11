// V-PHASE2-1730-043
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('display_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('display_order');
    }
}