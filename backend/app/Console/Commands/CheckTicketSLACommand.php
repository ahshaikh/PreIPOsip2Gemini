<?php
// V-FINAL-1730-384 (Created)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SupportService;

class CheckTicketSLACommand extends Command
{
    protected $signature = 'app:check-ticket-sla';
    protected $description = 'Hourly check for ticket SLA breaches and auto-closure';

    public function handle(SupportService $service)
    {
        $this->info('Checking for overdue tickets...');
        $escalated = $service->escalateOverdueTickets();
        $this->info("Escalated {$escalated} tickets.");

        $this->info('Checking for old tickets to close...');
        $closed = $service->autoCloseOldTickets();
        $this->info("Auto-closed {$closed} tickets.");
        
        return 0;
    }
}