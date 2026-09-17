<?php

namespace App\Console\Commands;

use App\Jobs\EvaluateMaintenanceRemindersJob;
use Illuminate\Console\Command;

class CheckMaintenanceRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autosecure:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate vehicle maintenance reminders and dispatch alert notifications.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Evaluating vehicle care reminders...');
        EvaluateMaintenanceRemindersJob::dispatchSync();
        $this->info('Reminders successfully processed.');

        return self::SUCCESS;
    }
}
