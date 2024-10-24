<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserHoursManagement;
use Carbon\Carbon;

class SetDefaultUserHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-default-user-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentMonth = Carbon::now()->format('Y-m'); // e.g., "2024-10"
        
        $users = User::all();

        foreach ($users as $user) {
            UserHoursManagement::updateOrCreate(
                ['user_id' => $user->id, 'month' => $currentMonth],
                ['total_hours' => 160]
            );
        }

        $this->info('Default hours set for all users for the current month.');
    }
}
