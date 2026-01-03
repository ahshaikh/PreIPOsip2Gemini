<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * DIAGNOSTIC CONTROLLER - Avatar Issue Investigation
 *
 * This controller provides detailed diagnostics for avatar upload/display issues.
 * It checks:
 * 1. Database state (both users and user_profiles tables)
 * 2. File system state
 * 3. API response format
 * 4. Full request/response lifecycle
 */
class DiagnosticsController extends Controller
{
    /**
     * Get complete avatar diagnostic information for current user
     */
    public function avatarDiagnostics(Request $request)
    {
        $user = $request->user();

        // 1. Check database - users table
        $userRecord = DB::table('users')
            ->where('id', $user->id)
            ->select('id', 'username', 'email', 'avatar_url')
            ->first();

        // 2. Check database - user_profiles table
        $profileRecord = DB::table('user_profiles')
            ->where('user_id', $user->id)
            ->select('id', 'user_id', 'first_name', 'last_name', 'avatar_url')
            ->first();

        // 3. Check Eloquent model state
        $eloquentUser = $user->fresh();
        $eloquentUser->load('profile');

        // 4. Check file system
        $avatarFiles = [];
        $publicAvatarsPath = "avatars/{$user->id}";
        if (Storage::disk('public')->exists($publicAvatarsPath)) {
            $avatarFiles = Storage::disk('public')->files($publicAvatarsPath);
        }

        // 5. Check storage/app/public symlink
        $publicPath = storage_path('app/public');
        $symlinkTarget = public_path('storage');
        $symlinkExists = is_link($symlinkTarget);
        $symlinkCorrect = $symlinkExists && readlink($symlinkTarget) === $publicPath;

        // 6. Build diagnostic response
        $diagnostics = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user->id,

            'database' => [
                'users_table' => [
                    'exists' => $userRecord !== null,
                    'avatar_url' => $userRecord->avatar_url ?? null,
                    'raw_record' => $userRecord,
                ],
                'user_profiles_table' => [
                    'exists' => $profileRecord !== null,
                    'avatar_url' => $profileRecord->avatar_url ?? null,
                    'raw_record' => $profileRecord,
                ],
            ],

            'eloquent_models' => [
                'user_model' => [
                    'has_avatar_url_attribute' => isset($eloquentUser->avatar_url),
                    'avatar_url_value' => $eloquentUser->avatar_url ?? null,
                ],
                'profile_model' => [
                    'exists' => $eloquentUser->profile !== null,
                    'has_avatar_url_attribute' => isset($eloquentUser->profile->avatar_url),
                    'avatar_url_value' => $eloquentUser->profile->avatar_url ?? null,
                ],
            ],

            'filesystem' => [
                'public_disk_path' => Storage::disk('public')->path($publicAvatarsPath),
                'avatar_folder_exists' => Storage::disk('public')->exists($publicAvatarsPath),
                'avatar_files' => $avatarFiles,
                'files_with_urls' => array_map(fn($file) => [
                    'file' => $file,
                    'url' => '/storage/' . $file,
                    'exists' => Storage::disk('public')->exists($file),
                    'size' => Storage::disk('public')->size($file),
                ], $avatarFiles),
            ],

            'storage_configuration' => [
                'symlink_path' => $symlinkTarget,
                'symlink_target' => $publicPath,
                'symlink_exists' => $symlinkExists,
                'symlink_correct' => $symlinkCorrect,
                'symlink_actual_target' => $symlinkExists ? readlink($symlinkTarget) : null,
            ],

            'api_response_structure' => [
                'user_to_array' => $eloquentUser->toArray(),
                'profile_in_user_array' => isset($eloquentUser->toArray()['profile']),
                'avatar_url_in_profile' => $eloquentUser->toArray()['profile']['avatar_url'] ?? null,
            ],

            'recommendations' => $this->generateRecommendations([
                'users_avatar' => $userRecord->avatar_url ?? null,
                'profile_avatar' => $profileRecord->avatar_url ?? null,
                'files' => $avatarFiles,
                'symlink' => $symlinkCorrect,
            ]),
        ];

        // Log diagnostics
        Log::info("Avatar diagnostics requested", $diagnostics);

        return response()->json($diagnostics);
    }

    /**
     * Generate recommendations based on diagnostic findings
     */
    private function generateRecommendations(array $data): array
    {
        $recommendations = [];

        // Check if both tables have avatar_url (duplication issue)
        if ($data['users_avatar'] && $data['profile_avatar']) {
            $recommendations[] = [
                'severity' => 'WARNING',
                'issue' => 'Avatar URL stored in both users and user_profiles tables',
                'recommendation' => 'Choose one table to store avatar_url and migrate data',
            ];
        }

        // Check if neither table has avatar_url
        if (!$data['users_avatar'] && !$data['profile_avatar']) {
            if (!empty($data['files'])) {
                $recommendations[] = [
                    'severity' => 'ERROR',
                    'issue' => 'Avatar files exist but no database record',
                    'recommendation' => 'Database save is failing - check UserProfile fillable array',
                ];
            } else {
                $recommendations[] = [
                    'severity' => 'INFO',
                    'issue' => 'No avatar uploaded yet',
                    'recommendation' => 'Upload an avatar to test the flow',
                ];
            }
        }

        // Check symlink
        if (!$data['symlink']) {
            $recommendations[] = [
                'severity' => 'CRITICAL',
                'issue' => 'Storage symlink not configured correctly',
                'recommendation' => 'Run: php artisan storage:link',
            ];
        }

        // Check if files exist but no URL
        if (!empty($data['files']) && !$data['profile_avatar']) {
            $recommendations[] = [
                'severity' => 'ERROR',
                'issue' => 'Files uploaded but database not updated',
                'recommendation' => 'ProfileController save logic is failing silently',
            ];
        }

        return $recommendations;
    }
}
