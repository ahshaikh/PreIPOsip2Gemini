<?php

namespace Database\Seeders;

use App\Models\LuckyDraw;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lucky Draw Seeder (Post-Audit - Phase 2)
 *
 * Seeds lucky draw configurations for user engagement.
 *
 * Creates:
 * - 1 monthly lucky draw configuration
 *
 * IMPORTANT:
 * - All draw configurations follow the "Zero Hardcoded Values" principle
 * - Prize structures and entry rules are database-driven and admin-editable
 * - Idempotent: Safe to run multiple times
 * - Production-safe: Uses updateOrCreate
 * - Draw execution requires admin approval
 *
 * Prize Structure Format:
 * [
 *   ['rank' => 1, 'amount' => 25000, 'count' => 1, 'description' => 'First Prize'],
 *   ['rank' => 2, 'amount' => 15000, 'count' => 1, 'description' => 'Second Prize'],
 *   ...
 * ]
 *
 * Entry Rules Format:
 * [
 *   'min_investment' => 5000,
 *   'min_active_months' => 1,
 *   'entries_per_5k' => 1,
 *   'streak_bonus_entries' => 5,
 * ]
 */
class LuckyDrawSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to set as creator
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin')->orWhere('name', 'super-admin');
        })->first();

        if (!$adminUser) {
            $this->command->warn('⚠️  No admin user found. Skipping lucky draw seeder.');
            $this->command->warn('   Run UserSeeder first to create admin users.');
            return;
        }

        DB::transaction(function () use ($adminUser) {
            $this->seedLuckyDraws($adminUser);
        });

        $this->command->info('✓ Lucky draws seeded successfully');
    }

    /**
     * Seed lucky draw configurations
     */
    private function seedLuckyDraws(User $adminUser): void
    {
        $draws = [
            [
                'name' => 'Monthly Lucky Draw - January 2026',
                'draw_date' => now()->endOfMonth()->addDays(3), // 3 days after month end
                'prize_structure' => [
                    [
                        'rank' => 1,
                        'amount' => 25000,
                        'count' => 1,
                        'description' => 'First Prize - ₹25,000',
                    ],
                    [
                        'rank' => 2,
                        'amount' => 15000,
                        'count' => 1,
                        'description' => 'Second Prize - ₹15,000',
                    ],
                    [
                        'rank' => 3,
                        'amount' => 10000,
                        'count' => 1,
                        'description' => 'Third Prize - ₹10,000',
                    ],
                    [
                        'rank' => 4,
                        'amount' => 2500,
                        'count' => 10,
                        'description' => 'Consolation Prize - ₹2,500 each',
                    ],
                ],
                'status' => 'open',
                'frequency' => 'monthly',
                'entry_rules' => [
                    'min_investment' => 5000,
                    'min_active_months' => 1,
                    'entries_per_5k' => 1, // 1 entry for every ₹5,000 invested
                    'ontime_payment_bonus' => 1, // +1 entry for on-time SIP payment
                    'streak_months_required' => 6, // Consistency streak requirement
                    'streak_bonus_entries' => 5, // +5 entries for 6-month streak
                    'max_entries_per_user' => 50, // Cap to ensure fairness
                ],
                'result_visibility' => 'public',
                'certificate_template' => null,
                'draw_video_url' => null,
                'draw_metadata' => [
                    'total_prize_pool' => 75000, // Sum of all prizes: 25k + 15k + 10k + (10 × 2.5k)
                    'expected_participants' => 500,
                    'draw_method' => 'random_weighted',
                    'notes' => 'Monthly recurring draw for active investors',
                ],
                'created_by' => $adminUser->id,
                'executed_by' => null, // Not executed yet
            ],
        ];

        foreach ($draws as $drawData) {
            // Use name and draw_date as unique identifier for updateOrCreate
            LuckyDraw::updateOrCreate(
                [
                    'name' => $drawData['name'],
                    'draw_date' => $drawData['draw_date'],
                ],
                $drawData
            );
        }

        $this->command->info('  ✓ Lucky draws seeded: ' . count($draws) . ' draw');
        $this->command->info('  ℹ  Prize Pool: ₹75,000 (1 × ₹25k + 1 × ₹15k + 1 × ₹10k + 10 × ₹2.5k)');
        $this->command->info('  ℹ  Entry Rules: 1 entry per ₹5,000 invested + bonuses for streaks');
    }
}
