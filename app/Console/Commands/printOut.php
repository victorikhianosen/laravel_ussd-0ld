<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class printOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printOut';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
         // your task logic here
    $this->info('MyTask has started!');

    // more logic...

    $this->info('MyTask has finished!');
        print('running scheduled task');
        return Command::SUCCESS;
    }
}
