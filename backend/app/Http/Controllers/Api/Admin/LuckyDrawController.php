// V-PHASE3-1730-096
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// ... Models

class LuckyDrawController extends Controller
{
    // TODO: Build out CRUD for LuckyDraws
    // TODO: Build logic for RunMonthlyLuckyDrawJob
    // This job will:
    // 1. Get all entries for the active draw
    // 2. Use a secure random method to select winners
    // 3. Create BonusTransaction for each winner
    // 4. Credit their wallet
    // 5. Mark the draw as 'completed'
}