<?php

namespace App\Console\Commands;

use App\Models\attendance;
use App\Models\Employee;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

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
        foreach (Employee::all() as $employee) {
            attendance::create([
                'employee_id' => $employee->id,
                'date' => now(),
                'status' => 'absent',
            ]);
        }
    }
}
