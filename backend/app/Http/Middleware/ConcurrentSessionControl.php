<?php
// V-SECURITY-SESSION - Concurrent Session Control Middleware

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ConcurrentSessionControl
{
    /**
     * Maximum concurrent sessions per user (configurable via settings)
     */
    protected int $maxSessions = 3;

    /**
     * Session timeout in minutes
     */
    protected int $sessionTimeout = 60;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Get max sessions from settings or use default
        $this->maxSessions = (int) setting('max_concurrent_sessions', 3);
        $this->sessionTimeout = (int) setting('session_timeout_minutes', 60);

        $currentToken = $request->bearerToken();
        if (!$currentToken) {
            return $next($request);
        }

        $tokenHash = hash('sha256', $currentToken);
        $sessionKey = "user_sessions:{$user->id}";

        // Get all active sessions for this user
        $sessions = Cache::get($sessionKey, []);

        // Clean expired sessions
        $sessions = $this->cleanExpiredSessions($sessions);

        // Check if current token is in the session list
        $tokenExists = isset($sessions[$tokenHash]);

        if (!$tokenExists) {
            // New session - check if we've hit the limit
            if (count($sessions) >= $this->maxSessions) {
                // Option 1: Reject new session (strict mode)
                if (setting('session_strict_mode', false)) {
                    return response()->json([
                        'message' => 'Maximum concurrent sessions exceeded. Please log out from another device.',
                        'error' => 'max_sessions_exceeded',
                        'active_sessions' => count($sessions),
                    ], 429);
                }

                // Option 2: Remove oldest session (default - LIFO)
                $oldestTokenHash = array_key_first($sessions);
                unset($sessions[$oldestTokenHash]);

                // Revoke the old token if possible
                $this->revokeOldToken($user, $oldestTokenHash);

                Log::info("Session limit reached. Removed oldest session for user {$user->id}");
            }
        }

        // Update/add current session
        $sessions[$tokenHash] = [
            'last_activity' => now()->timestamp,
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 255),
            'device' => $this->parseDevice($request->userAgent()),
        ];

        // Store updated sessions
        Cache::put($sessionKey, $sessions, now()->addMinutes($this->sessionTimeout));

        // Add session info to request for potential use in controllers
        $request->merge(['_active_sessions' => count($sessions)]);

        return $next($request);
    }

    /**
     * Clean expired sessions from the list
     */
    protected function cleanExpiredSessions(array $sessions): array
    {
        $cutoff = now()->subMinutes($this->sessionTimeout)->timestamp;

        return array_filter($sessions, function ($session) use ($cutoff) {
            return isset($session['last_activity']) && $session['last_activity'] > $cutoff;
        });
    }

    /**
     * Attempt to revoke an old token
     */
    protected function revokeOldToken(User $user, string $tokenHash): void
    {
        try {
            // Find and delete the token from personal_access_tokens
            $user->tokens()
                ->where('token', $tokenHash)
                ->delete();
        } catch (\Exception $e) {
            Log::warning("Failed to revoke old token for user {$user->id}: {$e->getMessage()}");
        }
    }

    /**
     * Parse device type from user agent
     */
    protected function parseDevice(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'Mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'Tablet';
        }

        if (str_contains($userAgent, 'windows') || str_contains($userAgent, 'macintosh') || str_contains($userAgent, 'linux')) {
            return 'Desktop';
        }

        return 'Other';
    }

    /**
     * Force logout all sessions for a user (can be called from controller)
     */
    public static function forceLogoutAll(User $user): int
    {
        $sessionKey = "user_sessions:{$user->id}";
        $sessions = Cache::get($sessionKey, []);
        $count = count($sessions);

        // Delete all tokens
        $user->tokens()->delete();

        // Clear session cache
        Cache::forget($sessionKey);

        Log::info("Force logged out all {$count} sessions for user {$user->id}");

        return $count;
    }

    /**
     * Get active sessions for a user
     */
    public static function getActiveSessions(User $user): array
    {
        $sessionKey = "user_sessions:{$user->id}";
        $sessions = Cache::get($sessionKey, []);

        return array_map(function ($session, $tokenHash) {
            return [
                'token_hash' => substr($tokenHash, 0, 8) . '...',
                'last_activity' => date('Y-m-d H:i:s', $session['last_activity']),
                'ip' => $session['ip'] ?? 'Unknown',
                'device' => $session['device'] ?? 'Unknown',
                'user_agent' => $session['user_agent'] ?? 'Unknown',
            ];
        }, $sessions, array_keys($sessions));
    }

    /**
     * Terminate a specific session by token hash prefix
     */
    public static function terminateSession(User $user, string $tokenHashPrefix): bool
    {
        $sessionKey = "user_sessions:{$user->id}";
        $sessions = Cache::get($sessionKey, []);

        foreach ($sessions as $tokenHash => $session) {
            if (str_starts_with($tokenHash, $tokenHashPrefix)) {
                // Remove from cache
                unset($sessions[$tokenHash]);
                Cache::put($sessionKey, $sessions, now()->addMinutes(60));

                // Revoke the token
                $user->tokens()->where('token', $tokenHash)->delete();

                Log::info("Terminated session {$tokenHashPrefix}... for user {$user->id}");
                return true;
            }
        }

        return false;
    }
}
