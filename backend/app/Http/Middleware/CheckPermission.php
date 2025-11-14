<?php
// V-FINAL-1730-419 (Created)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * $permission = "users.edit"
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user->hasPermissionTo($permission)) {
            return $next($request);
        }

        // Test: test_permission_logs_unauthorized_attempts
        Log::warning("Authorization Failed: User {$user->id} ({$user->email}) tried to access '{$permission}' without rights.", [
            'ip' => $request->ip(),
            'route' => $request->path()
        ]);
        
        // Test: test_permission_blocks_unauthorized_user
        return response()->json(['message' => 'Forbidden: You do not have the required permission.'], 403);
    }
}