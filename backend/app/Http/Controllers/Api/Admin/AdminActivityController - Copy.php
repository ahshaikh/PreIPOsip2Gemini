<?php
// V-FINAL-1730-224

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,username,email');

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('username', 'like', "%{$search}%");
                  });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return $query->latest()->paginate(50);
    }
}