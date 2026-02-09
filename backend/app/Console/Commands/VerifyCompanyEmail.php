<?php

namespace App\Console\Commands;

use App\Models\CompanyUser;
use Illuminate\Console\Command;

class VerifyCompanyEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:verify-email
                            {user_id? : The company user ID to verify}
                            {--email= : Verify by email address instead of ID}
                            {--all : Verify all unverified company users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually verify company user email (for development/testing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $count = CompanyUser::whereNull('email_verified_at')->update([
                'email_verified_at' => now(),
            ]);
            $this->info("Verified {$count} company user(s).");
            return 0;
        }

        $userId = $this->argument('user_id');
        $email = $this->option('email');

        if (!$userId && !$email) {
            $this->error('Please provide a user_id or --email option.');
            return 1;
        }

        $query = CompanyUser::query();

        if ($userId) {
            $query->where('id', $userId);
        } else {
            $query->where('email', $email);
        }

        $user = $query->first();

        if (!$user) {
            $this->error('Company user not found.');
            return 1;
        }

        if ($user->email_verified_at) {
            $this->warn("Email already verified for: {$user->email}");
            return 0;
        }

        $user->update(['email_verified_at' => now()]);

        $this->info("Email verified for: {$user->email} (ID: {$user->id})");
        return 0;
    }
}
