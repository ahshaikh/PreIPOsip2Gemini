# Push Notifications Setup Guide

## Overview
The push notification system uses FCM (Firebase Cloud Messaging) or OneSignal to send notifications to user devices (iOS, Android, Web).

## Current Status After Fix
✅ **Fixed:** Segments endpoint 500 error
✅ **Fixed:** Validation errors (target_type, user_ids)
✅ **Fixed:** Silent failures (now shows warnings when infrastructure missing)
✅ **Added:** Database migrations for user_devices and push_logs
✅ **Added:** UserDevice model
✅ **Updated:** SendPushCampaignJob to query real device tokens

## Database Setup

### 1. Run Migrations
```bash
cd backend
php artisan migrate
```

This creates two tables:
- **`user_devices`**: Stores FCM/OneSignal device tokens
- **`push_logs`**: Tracks notification delivery (sent, delivered, opened)

### 2. Verify Tables Created
```bash
php artisan tinker
```
```php
\DB::table('user_devices')->count();  // Should return 0 (no devices yet)
\DB::table('push_logs')->count();     // Should return 0 (no logs yet)
```

## Configuration

### Option A: Firebase Cloud Messaging (FCM)

1. **Get FCM Server Key:**
   - Go to [Firebase Console](https://console.firebase.google.com/)
   - Select your project → Settings → Cloud Messaging
   - Copy "Server key"

2. **Add to Settings Table:**
```sql
INSERT INTO settings (category, `key`, value, type, description) VALUES
('notification', 'push_provider', 'fcm', 'select', 'Push notification provider'),
('notification', 'fcm_server_key', 'YOUR_FCM_SERVER_KEY_HERE', 'text', 'FCM Server Key for push notifications');
```

### Option B: OneSignal

1. **Get OneSignal Credentials:**
   - Go to [OneSignal Dashboard](https://app.onesignal.com/)
   - Settings → Keys & IDs
   - Copy "App ID" and "REST API Key"

2. **Add to Settings Table:**
```sql
INSERT INTO settings (category, `key`, value, type, description) VALUES
('notification', 'push_provider', 'onesignal', 'select', 'Push notification provider'),
('notification', 'onesignal_app_id', 'YOUR_APP_ID', 'text', 'OneSignal App ID'),
('notification', 'onesignal_api_key', 'YOUR_API_KEY', 'text', 'OneSignal REST API Key');
```

## Device Registration (Frontend/Mobile App)

### Web App (FCM)
```javascript
// In your frontend app, register device token
const messaging = getMessaging(app);
const token = await getToken(messaging, {
  vapidKey: 'YOUR_VAPID_KEY'
});

// Send to backend
await api.post('/user/devices/register', {
  device_token: token,
  device_type: 'web',
  provider: 'fcm',
  platform: 'web',
  browser: navigator.userAgent
});
```

### iOS/Android App
```swift
// iOS - AppDelegate.swift
func application(_ application: UIApplication,
                 didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
    let token = deviceToken.map { String(format: "%02.2hhx", $0) }.joined()

    // Send to backend
    apiClient.post("/user/devices/register", [
        "device_token": token,
        "device_type": "ios",
        "provider": "fcm",
        "platform": "ios",
        "device_model": UIDevice.current.model
    ])
}
```

## Backend API Endpoint (To Be Created)

Add this route to `routes/api.php`:
```php
// User device registration
Route::post('/user/devices/register', [UserDeviceController::class, 'register'])->middleware('auth:sanctum');
Route::get('/user/devices', [UserDeviceController::class, 'index'])->middleware('auth:sanctum');
Route::delete('/user/devices/{device}', [UserDeviceController::class, 'destroy'])->middleware('auth:sanctum');
```

Create controller `app/Http/Controllers/Api/User/UserDeviceController.php`:
```php
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android,web',
            'provider' => 'required|in:fcm,onesignal',
            'device_name' => 'nullable|string',
            'device_model' => 'nullable|string',
            'os_version' => 'nullable|string',
            'app_version' => 'nullable|string',
            'platform' => 'nullable|string',
            'browser' => 'nullable|string',
        ]);

        // Deactivate old tokens for this device
        UserDevice::where('user_id', $request->user()->id)
            ->where('device_token', $validated['device_token'])
            ->update(['is_active' => false]);

        // Create new device entry
        $device = UserDevice::create([
            'user_id' => $request->user()->id,
            'device_token' => $validated['device_token'],
            'device_type' => $validated['device_type'],
            'provider' => $validated['provider'],
            'device_name' => $validated['device_name'] ?? null,
            'device_model' => $validated['device_model'] ?? null,
            'os_version' => $validated['os_version'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'browser' => $validated['browser'] ?? null,
            'is_active' => true,
            'registered_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device
        ]);
    }

    public function index(Request $request)
    {
        $devices = UserDevice::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->get();

        return response()->json($devices);
    }

    public function destroy(Request $request, UserDevice $device)
    {
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device->update(['is_active' => false]);

        return response()->json(['message' => 'Device removed successfully']);
    }
}
```

## Queue Configuration

Push notifications are sent asynchronously via Laravel queues.

### 1. Configure Queue Driver

In `.env`:
```env
QUEUE_CONNECTION=database
```

### 2. Create Jobs Table (if not exists)
```bash
php artisan queue:table
php artisan migrate
```

### 3. Start Queue Worker
```bash
# Development
php artisan queue:work

# Production (with supervisor)
# See backend/deploy/supervisor-queue.conf
```

## Testing

### 1. Test Segments Endpoint
```bash
curl http://localhost:8000/api/v1/admin/users/segments \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

Should return:
```json
[
  {"id": "all", "name": "All Users", "count": 100},
  {"id": "active", "name": "Active Subscribers", "count": 50},
  ...
]
```

### 2. Send Test Notification

**Without Device Tokens (Expected Warning):**
```bash
curl -X POST http://localhost:8000/api/v1/admin/notifications/push/send \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "all",
    "title": "Test Notification",
    "body": "This is a test"
  }'
```

Response:
```json
{
  "message": "Notification queued for 100 users",
  "warning": "No device tokens registered. Users must enable push notifications in their app first.",
  "total_users": 100
}
```

**With Device Tokens:**
```bash
# First, register a test device token
curl -X POST http://localhost:8000/api/v1/user/devices/register \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "device_token": "YOUR_FCM_TOKEN",
    "device_type": "web",
    "provider": "fcm"
  }'

# Then send notification
curl -X POST http://localhost:8000/api/v1/admin/notifications/push/send \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "all",
    "title": "Test Notification",
    "body": "This is a test"
  }'
```

Response:
```json
{
  "message": "Push notification campaign queued successfully!",
  "total_users": 100,
  "batches": 1
}
```

### 3. Check Queue Jobs
```bash
# View queued jobs
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log | grep "SendPushCampaignJob"
```

### 4. Verify Push Logs
```bash
php artisan tinker
```
```php
\App\Models\PushLog::latest()->get();
```

## Troubleshooting

### Issue: "user_devices table missing"
**Fix:** Run migrations
```bash
php artisan migrate
```

### Issue: "No device tokens registered"
**Fix:** Register device tokens from frontend/mobile app (see Device Registration section)

### Issue: Notifications queued but not sending
**Fix:** Start queue worker
```bash
php artisan queue:work
```

### Issue: FCM returns "Invalid credentials"
**Fix:** Check FCM server key in settings table
```sql
SELECT * FROM settings WHERE `key` = 'fcm_server_key';
```

### Issue: Segments endpoint returns 500 error
**Fix:** Check Laravel logs
```bash
tail -100 storage/logs/laravel.log
```
Likely cause: Missing relationship in User model. Fixed in commit eb722f9.

## Architecture

```
Admin Panel (Frontend)
    ↓ POST /api/v1/admin/notifications/push/send
NotificationController@sendPush
    ↓ Validates + Segments users
    ↓ Dispatches Bus::batch([SendPushCampaignJob])
Queue Worker
    ↓ Processes SendPushCampaignJob
    ↓ Queries user_devices table for tokens
    ↓ Batches tokens (500 per request)
    ↓ Sends to FCM/OneSignal API
    ↓ Logs to push_logs table
Users Receive Notification
```

## Next Steps

1. ✅ Run migrations
2. ✅ Configure FCM/OneSignal in settings table
3. ⏳ Create UserDeviceController (see example above)
4. ⏳ Add device registration to frontend/mobile app
5. ⏳ Start queue worker
6. ⏳ Test end-to-end flow

## Related Files

- **Migrations:**
  - `database/migrations/2025_01_01_000001_create_user_devices_table.php`
  - `database/migrations/2025_01_01_000002_create_push_logs_table.php`

- **Models:**
  - `app/Models/UserDevice.php`
  - `app/Models/PushLog.php`

- **Jobs:**
  - `app/Jobs/SendPushCampaignJob.php`

- **Controllers:**
  - `app/Http/Controllers/Api/Admin/NotificationController.php`
  - `app/Http/Controllers/Api/Admin/AdminUserController.php` (segments endpoint)

- **Frontend:**
  - `frontend/app/admin/notifications/push/page.tsx`
