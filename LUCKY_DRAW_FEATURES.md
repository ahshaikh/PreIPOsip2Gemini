# Lucky Draw Configuration Features - Complete Implementation Guide

## Overview
This document provides a comprehensive guide to all 15 lucky draw configuration features implemented in the PreIPOsip2Gemini platform.

## ✅ Implemented Features (15/15)

### 1. Draw Frequency Configuration ✅
**Location**: Database migration + Settings

**Description**: Configure how often lucky draws occur.

**Frequencies Available**:
- `monthly` - Draws every month (default)
- `quarterly` - Draws every 3 months
- `custom` - Custom interval in days

**Settings**:
- `lucky_draw_frequency` - Frequency type
- `lucky_draw_custom_interval_days` - Days for custom interval

**Database Field**: `lucky_draws.frequency`

---

### 2. Prize Structure Configuration (Unlimited Tiers) ✅
**Location**: Create/Edit Draw API

**Description**: Configure unlimited prize tiers with rank, count, and amount.

**Example Structure**:
```json
[
  { "rank": 1, "count": 1, "amount": 50000 },
  { "rank": 2, "count": 5, "amount": 10000 },
  { "rank": 3, "count": 25, "amount": 2000 },
  { "rank": 4, "count": 50, "amount": 25 }
]
```

**Features**:
- Unlimited prize tiers
- Each tier has rank, count, and amount
- Automatic prize pool calculation
- Validation to ensure positive amounts

---

### 3. Entry Rules Per Plan ✅
**Location**: `LuckyDrawService.php` + Plan configs

**Description**: Each plan can have different entry allocation rules.

**Configuration** (per plan):
```json
{
  "lucky_draw_config": {
    "entries_per_payment": 1
  }
}
```

**Features**:
- Base entries configurable per plan
- Stored in `plan_configs` table
- Can be overridden in individual draws via `entry_rules` JSON field

---

### 4. Bonus Entries for On-Time Payments/Streaks ✅
**Location**: `LuckyDrawService.php:allocateEntries()`

**Description**: Award bonus entries for good payment behavior.

**Rules**:
- **On-Time Bonus**: `+1` entry for on-time payments (configurable)
- **Streak Bonus**: `+5` entries every 6 consecutive on-time payments

**Settings**:
- `lucky_draw_ontime_bonus` - Bonus entries for on-time payment (default: 1)
- `lucky_draw_streak_bonus` - Bonus entries for streak (default: 5)
- `lucky_draw_streak_months` - Months for streak bonus (default: 6)

**Example**:
- User with 12 consecutive on-time payments gets:
  - Base: 1 entry
  - On-time bonus: +1 entry
  - Streak bonus (12/6 = 2): +10 entries
  - **Total: 12 entries**

---

### 5. Create New Draw Manually ✅
**API Endpoint**: `POST /api/v1/admin/lucky-draws`

**Request**:
```json
{
  "name": "January 2026 Lucky Draw",
  "draw_date": "2026-01-31",
  "frequency": "monthly",
  "prize_structure": [...],
  "result_visibility": "public",
  "entry_rules": {}
}
```

**Features**:
- Create draw anytime
- Configure all parameters
- Set custom draw date
- Automatically marked as 'open'

---

### 6. Edit Draw Before Execution ✅
**API Endpoint**: `PUT /api/v1/admin/lucky-draws/{id}`

**Description**: Edit draw details before execution.

**Editable Fields**:
- `name`
- `draw_date`
- `prize_structure`
- `frequency`
- `entry_rules`
- `result_visibility`

**Restrictions**:
- Only 'open' status draws can be edited
- Cannot edit completed/cancelled draws
- Logged in admin activity

---

### 7. Cancel Draw ✅
**API Endpoint**: `POST /api/v1/admin/lucky-draws/{id}/cancel`

**Description**: Cancel an open draw before execution.

**Features**:
- Changes status from 'open' to 'cancelled'
- Only open draws can be cancelled
- Action is logged
- Cannot cancel completed draws

---

### 8. Manual Draw Execution Interface ✅
**API Endpoint**: `POST /api/v1/admin/lucky-draws/{id}/execute`

**Description**: Manually trigger draw execution.

**Process**:
1. Validates draw status ('open')
2. Selects winners using weighted random
3. Distributes prizes to winners' wallets
4. Marks draw as 'completed'
5. Records admin who executed (`executed_by`)
6. Sends notifications to winners

---

### 9. Automatic Draw Execution (Cron) ✅
**Location**: `ProcessMonthlyLuckyDraw` command

**Command**: `php artisan app:process-monthly-lucky-draw`

**Schedule**: Daily cron job checks:
1. **1st of month**: Create new draw
2. **Draw date**: Execute pending draws

**Features**:
- Automatic monthly draw creation
- Automatic execution on draw date
- Error handling and logging
- Status tracking

---

### 10. Prize Distribution (Auto-Credit to Wallet) ✅
**Location**: `LuckyDrawService::distributePrizes()`

**Description**: Automatically credits prizes to winners' wallets.

**Process**:
1. Marks entry as winner
2. Records prize rank and amount
3. Creates bonus transaction
4. Credits wallet via `WalletService`
5. All within database transaction

**Transaction Type**: `bonus_credit`
**Bonus Type**: `lucky_draw`

---

### 11. Winner Management (View, Disqualify, Replace) ✅

#### View Winners
**API Endpoint**: `GET /api/v1/admin/lucky-draws/{id}/winners`

Returns all winners with:
- User details
- Prize rank
- Prize amount
- Entry statistics

#### Disqualify Winner + Replace
**API Endpoint**: `POST /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/disqualify`

**Request**:
```json
{
  "reason": "Fraud detected"
}
```

**Process**:
1. Marks original winner as disqualified
2. Finds replacement (user with most entries)
3. Marks replacement as winner
4. Credits wallet for replacement
5. Logs action

---

### 12. Result Publishing Controls (Privacy Settings) ✅
**Location**: `lucky_draws.result_visibility` field

**Options**:
- `public` - Results visible to everyone (default)
- `private` - Results hidden from public
- `winners_only` - Only winners can see results

**Settings**:
- `lucky_draw_auto_publish` - Auto-publish after execution (default: true)
- `lucky_draw_publish_full_details` - Show full details or just winner count (default: false)

---

### 13. Winner Certificates Generation ✅
**API Endpoint**: `GET /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/certificate`

**Description**: Generate PDF certificate for winners.

**Template**: `resources/views/certificates/lucky-draw-winner.blade.php`

**Features**:
- Professional certificate design
- Winner name and details
- Prize rank and amount
- Draw name and date
- Customizable footer text
- Downloads as PDF

**Settings**:
- `lucky_draw_enable_certificates` - Enable/disable certificates (default: true)
- `lucky_draw_certificate_footer` - Custom footer text

**PDF Library**: DomPDF (barryvdh/laravel-dompdf)

---

### 14. Draw Video Upload for Transparency ✅
**API Endpoint**: `POST /api/v1/admin/lucky-draws/{id}/upload-video`

**Description**: Upload video recording of draw execution.

**Features**:
- Supports: MP4, MOV, AVI, WMV
- Max size: 100MB
- Stored in: `storage/lucky-draws/videos/`
- Public URL returned
- Stored in `draw_video_url` field

**Purpose**: Transparency and trust - users can watch draw execution

---

### 15. Draw Statistics and Analytics ✅
**API Endpoint**: `GET /api/v1/admin/lucky-draws/{id}/analytics`

**Metrics Provided**:

#### Entry Distribution
- Groups: 1-5, 6-10, 11-20, 21+ entries
- Count of users in each group

#### Winners by Plan
- Number of winners per subscription plan
- Helps analyze if draws are fair across plans

#### Prize Distribution
- Winners count per rank
- Total amount distributed per rank

#### Overall Statistics
- Total participants
- Total entries
- Average entries per user
- Prize pool breakdown

**Example Response**:
```json
{
  "draw": {...},
  "analytics": {
    "entry_distribution": {
      "1-5": 120,
      "6-10": 45,
      "11-20": 20,
      "21+": 5
    },
    "winners_by_plan": {
      "Gold Plan": 35,
      "Silver Plan": 25,
      "Bronze Plan": 21
    },
    "prize_distribution": {
      "1": { "count": 1, "total_amount": 50000 },
      "2": { "count": 5, "total_amount": 50000 },
      "3": { "count": 25, "total_amount": 50000 }
    },
    "total_participants": 190,
    "total_entries": 1247,
    "average_entries_per_user": 6.56
  }
}
```

---

## API Endpoints Summary

### Lucky Draw Management
```
GET    /api/v1/admin/lucky-draws                              - List all draws with stats
GET    /api/v1/admin/lucky-draws/{id}                         - Get draw details
POST   /api/v1/admin/lucky-draws                              - Create new draw
PUT    /api/v1/admin/lucky-draws/{id}                         - Edit draw (before execution)
DELETE /api/v1/admin/lucky-draws/{id}                         - Delete draw (soft delete)
POST   /api/v1/admin/lucky-draws/{id}/execute                 - Execute draw manually
POST   /api/v1/admin/lucky-draws/{id}/cancel                  - Cancel draw

GET    /api/v1/admin/lucky-draws-settings                     - Get settings
PUT    /api/v1/admin/lucky-draws-settings                     - Update settings

GET    /api/v1/admin/lucky-draws/{id}/winners                 - Get all winners
POST   /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/disqualify  - Disqualify winner

POST   /api/v1/admin/lucky-draws/{id}/upload-video            - Upload draw video
GET    /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/certificate - Generate certificate

GET    /api/v1/admin/lucky-draws/{id}/analytics               - Get draw analytics
```

### User Endpoints
```
GET    /api/v1/user/lucky-draws                               - User's draw entries
```

---

## Database Schema

### lucky_draws Table
```sql
CREATE TABLE lucky_draws (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255),
  draw_date DATE,
  prize_structure JSON,
  status VARCHAR (open, completed, cancelled, failed),
  frequency VARCHAR (monthly, quarterly, custom),
  entry_rules JSON,
  result_visibility VARCHAR (public, private, winners_only),
  certificate_template VARCHAR,
  draw_video_url VARCHAR,
  draw_metadata JSON,
  created_by BIGINT,
  executed_by BIGINT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP (soft deletes)
);
```

### lucky_draw_entries Table
```sql
CREATE TABLE lucky_draw_entries (
  id BIGINT PRIMARY KEY,
  user_id BIGINT,
  lucky_draw_id BIGINT,
  payment_id BIGINT,
  base_entries INT DEFAULT 0,
  bonus_entries INT DEFAULT 0,
  is_winner BOOLEAN DEFAULT FALSE,
  prize_rank INT,
  prize_amount DECIMAL(10,2),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(user_id, lucky_draw_id)
);
```

---

## Settings Configuration

All settings stored in `settings` table with group `lucky_draw_config`:

| Setting Key | Default | Type | Description |
|------------|---------|------|-------------|
| `lucky_draw_frequency` | `monthly` | string | Draw frequency |
| `lucky_draw_custom_interval_days` | `30` | number | Custom interval days |
| `lucky_draw_ontime_bonus` | `1` | number | Bonus entries for on-time |
| `lucky_draw_streak_bonus` | `5` | number | Bonus entries for streak |
| `lucky_draw_streak_months` | `6` | number | Months for streak bonus |
| `lucky_draw_prize_pool` | `152500` | number | Default prize pool |
| `lucky_draw_auto_publish` | `true` | boolean | Auto-publish results |
| `lucky_draw_publish_full_details` | `false` | boolean | Show full details |
| `lucky_draw_enable_certificates` | `true` | boolean | Enable certificates |
| `lucky_draw_certificate_footer` | `Congratulations!` | string | Certificate footer text |

---

## Console Commands

### Process Monthly Lucky Draw
```bash
php artisan app:process-monthly-lucky-draw
```

**Scheduling** (in `app/Console/Kernel.php`):
```php
$schedule->command('app:process-monthly-lucky-draw')->daily();
```

**Features**:
- Creates new draw on 1st of month
- Executes pending draws on draw date
- Sends winner notifications
- Handles errors gracefully

**Force Execution**:
```bash
php artisan app:process-monthly-lucky-draw --force
```

---

## Frontend Integration

### Admin Page
**Location**: `/frontend/app/admin/lucky-draws/page.tsx`

**Features**:
- List all draws
- Create new draw
- Execute draw
- View statistics
- Upload video

---

## Testing Guide

### 1. Test Draw Creation
1. Navigate to Admin → Lucky Draws
2. Click "Create New Draw"
3. Fill in details and prize structure
4. Verify draw appears in list

### 2. Test Entry Allocation
1. Make on-time payment for a user
2. Check `lucky_draw_entries` table
3. Verify base + bonus entries allocated

### 3. Test Draw Execution
1. Create draw with future date
2. Make some payments to generate entries
3. Execute draw manually
4. Verify winners selected and prizes credited

### 4. Test Winner Disqualification
1. View winners for completed draw
2. Disqualify a winner with reason
3. Verify replacement winner selected
4. Check wallet credits

### 5. Test Video Upload
1. Select a draw
2. Upload video (MP4 format)
3. Verify video URL stored
4. Check video is accessible

### 6. Test Certificate Generation
1. Get a winner entry
2. Generate certificate
3. Verify PDF downloads
4. Check certificate content

### 7. Test Analytics
1. Execute a draw with multiple winners
2. View analytics
3. Verify entry distribution
4. Check winners by plan statistics

---

## Security Considerations

1. **Permission Protection**: All admin endpoints require `bonuses.manage_config` permission
2. **Status Validation**: Only 'open' draws can be executed/edited
3. **Transaction Safety**: Prize distribution uses database transactions
4. **Audit Trail**: All actions logged with admin ID
5. **Soft Deletes**: Draws use soft deletes for data integrity
6. **Video Validation**: File type and size validation

---

## Troubleshooting

### Draw Execution Fails
**Check**:
- Draw status is 'open'
- At least one participant has entries
- Enough unique participants for prize pool

### Certificate Generation Fails
**Check**:
- DomPDF package installed
- Entry is marked as winner
- Certificates enabled in settings

### Video Upload Fails
**Check**:
- File size under 100MB
- File type is supported (MP4, MOV, AVI, WMV)
- Storage permissions correct

### Bonus Entries Not Allocated
**Check**:
- Lucky draw enabled in settings
- Active draw exists with 'open' status
- Plan has entry configuration

---

## Changelog

### Version 2025-12-09
- ✅ All 15 Lucky Draw features implemented
- ✅ Enhanced AdminLuckyDrawController with 11 new methods
- ✅ Added draw frequency configuration
- ✅ Implemented winner management (disqualify/replace)
- ✅ Added result publishing controls
- ✅ Implemented PDF certificate generation
- ✅ Added video upload for transparency
- ✅ Created comprehensive analytics system
- ✅ Added migration for new fields
- ✅ Updated LuckyDraw model
- ✅ Added 10 new API routes
- ✅ Created certificate template

---

**End of Documentation**
